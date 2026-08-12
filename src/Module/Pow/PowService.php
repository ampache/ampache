<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Pow;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Pow\PowChallengeView;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\User;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Proof-of-work protection for the endpoints named in `pow_protected`.
 *
 * A challenge is a random id whose answer is a nonce making `sha256(id:nonce)` start with a
 * configured number of zero bits. The work is exponential in that number for the client and a
 * single hash for the server, which is what makes bulk abuse expensive without charging the
 * visitor anything they notice.
 *
 * Issuing costs nothing: the terms travel with the client under an HMAC rather than being written
 * down, so asking for challenges in a loop buys an attacker no storage. The only thing recorded is
 * an answer that has already been paid for, which is what keeps a solved challenge from being
 * replayed. A row therefore cannot exist without the work behind it, and expired ones are swept as
 * they are noticed, so `pow_challenge` never grows past the answers still inside their TTL.
 */
final readonly class PowService implements PowServiceInterface
{
    private const int DEFAULT_DIFFICULTY = 21;

    private const int DEFAULT_TTL = 1800;

    /** Roughly how often a verification also clears out answers that have expired. */
    private const int GC_ODDS = 50;

    /** Each bit doubles the work; 26 is already ~a minute on ordinary hardware. */
    private const int MAX_DIFFICULTY = 26;

    /** Below this a challenge is free to solve, above it a phone would sit there for minutes. */
    private const int MIN_DIFFICULTY = 8;
    private const string TABLE       = 'pow_challenge';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private DatabaseConnectionInterface $database,
        private LoggerInterface $logger,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    #[Override]
    public function createChallengeResponse(ServerRequestInterface $request, string $scope): ResponseInterface
    {
        // The interstitial replays the interrupted request as a GET form, so a body would be
        // dropped on the way through. Refusing is loud; rebuilding the request as a GET is not.
        // Anything that posts belongs on the inline widget instead, as `register` does.
        if ($request->getMethod() !== 'GET') {
            return $this->responseFactory
                ->createResponse(405)
                ->withHeader('Allow', 'GET')
                ->withHeader('Cache-Control', 'no-store');
        }

        $view = new PowChallengeView(
            $this->issue($scope),
            (string) $request->getUri(),
            AmpConfig::get_web_path(),
            $request->getHeaderLine('Referer')
        );

        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streamFactory->createStream($view->render()));
    }

    #[Override]
    public function isRequired(string $scope, ?User $user): bool
    {
        $mode = PowMode::fromConfig($this->configContainer->get(ConfigurationKeyEnum::POW_MODE));
        if ($mode === PowMode::OFF) {
            return false;
        }

        if (!in_array($scope, $this->configContainer->getArray(ConfigurationKeyEnum::POW_PROTECTED), true)) {
            return false;
        }

        // Anonymous visitors are challenged in both remaining modes; that is the whole point of `guest`.
        if (!$user instanceof User || $user->getId() <= 0) {
            return true;
        }

        if ($mode === PowMode::GUEST) {
            return false;
        }

        // In `all` mode a trusted access level can still be waved through, so admins and scripted
        // maintenance are not made to burn CPU on every download.
        $exemptLevel = (int) ($this->configContainer->get(ConfigurationKeyEnum::POW_EXEMPT_LEVEL) ?? 0);

        return !($exemptLevel > 0 && $user->access >= $exemptLevel);
    }

    #[Override]
    public function issue(string $scope): PowChallenge
    {
        $id         = bin2hex(random_bytes(16));
        $difficulty = $this->getDifficulty();
        $expire     = time() + $this->getTtl();

        return new PowChallenge(
            $id,
            $difficulty,
            $expire,
            $this->sign($id, $scope, $difficulty, $expire)
        );
    }

    #[Override]
    public function verify(string $scope, array $answer, ?User $user = null): bool
    {
        return $this->check($scope, $answer, $user, null);
    }

    #[Override]
    public function verifyRequest(ServerRequestInterface $request, string $scope, ?User $user = null): bool
    {
        $body   = (array) $request->getParsedBody();
        $query  = $request->getQueryParams();
        $answer = [];

        foreach (['pow_id', 'pow_exp', 'pow_diff', 'pow_sig', 'pow_nonce'] as $field) {
            $answer[$field] = (string) ($body[$field] ?? $query[$field] ?? '');
        }

        return $this->check($scope, $answer, $user, $request->getHeaderLine('User-Agent'));
    }

    /**
     * @param array<string, string> $answer
     */
    private function check(string $scope, array $answer, ?User $user, ?string $agent): bool
    {
        $id         = $answer['pow_id'] ?? '';
        $nonce      = $answer['pow_nonce'] ?? '';
        $signature  = $answer['pow_sig'] ?? '';
        $expire     = (int) ($answer['pow_exp'] ?? 0);
        $difficulty = (int) ($answer['pow_diff'] ?? 0);

        if (
            preg_match('/^[a-f0-9]{32}$/', $id) !== 1
            || preg_match('/^[0-9]{1,20}$/', $nonce) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $signature) !== 1
        ) {
            return $this->fail($scope, $user, $agent, 'malformed answer');
        }

        // Measured against the current setting, not just the static floor, so an answer signed
        // when the config was lax stops working once an admin tightens it.
        if ($difficulty < $this->getDifficulty() || $difficulty > self::MAX_DIFFICULTY) {
            return $this->fail($scope, $user, $agent, 'difficulty out of range');
        }

        // The signature covers the scope, so an answer earned on a cheap endpoint cannot be spent on
        // an expensive one, and the difficulty cannot be talked down on the way back.
        if (!hash_equals($this->sign($id, $scope, $difficulty, $expire), $signature)) {
            return $this->fail($scope, $user, $agent, 'bad signature');
        }

        if ($expire <= time()) {
            return $this->fail($scope, $user, $agent, 'expired challenge');
        }

        // Checked before anything is written, so only an answer that cost real work can put a row in
        // the table. A wrong nonce is free to retry against the same challenge, which is fine: every
        // attempt still has to pay for itself.
        if (!$this->hasLeadingZeroBits(hash('sha256', $id . ':' . $nonce, true), $difficulty)) {
            return $this->fail($scope, $user, $agent, 'insufficient proof of work');
        }

        if (!$this->consume($id, $expire)) {
            return $this->fail($scope, $user, $agent, 'answer already used');
        }

        return true;
    }

    private function collectGarbage(): void
    {
        try {
            $this->database->query('DELETE FROM `' . self::TABLE . '` WHERE `expire` < ?', [time()]);
        } catch (Throwable) {
            // Housekeeping only; expiry is enforced on `expire` regardless.
        }
    }

    /**
     * Records an answer so it cannot be presented twice.
     *
     * Returns false when this one has been seen before. `INSERT IGNORE` makes that a row count
     * rather than an exception, and makes the check atomic: two requests racing with the same answer
     * resolve to exactly one winner.
     */
    private function consume(string $id, int $expire): bool
    {
        try {
            $statement = $this->database->query(
                'INSERT IGNORE INTO `' . self::TABLE . '` (`id`, `expire`) VALUES (?, ?)',
                [$id, $expire]
            );
        } catch (Throwable $error) {
            // Refuse rather than accept answers that can no longer be checked for replay.
            $this->logger->critical(
                'Could not record a proof of work answer: ' . $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return false;
        }

        if (random_int(1, self::GC_ODDS) === 1) {
            $this->collectGarbage();
        }

        return $statement->rowCount() === 1;
    }

    /** Refuses an answer, recording why on the way out. */
    private function fail(string $scope, ?User $user, ?string $agent, string $reason): false
    {
        $this->logFailure($scope, $user, $agent, $reason);

        return false;
    }

    /**
     * Clamped so a mistyped config cannot lock every visitor out with an unsolvable challenge.
     */
    private function getDifficulty(): int
    {
        $difficulty = (int) ($this->configContainer->get(ConfigurationKeyEnum::POW_DIFFICULTY) ?: self::DEFAULT_DIFFICULTY);

        return max(self::MIN_DIFFICULTY, min(self::MAX_DIFFICULTY, $difficulty));
    }

    private function getTtl(): int
    {
        return max(60, (int) ($this->configContainer->get(ConfigurationKeyEnum::POW_TTL) ?: self::DEFAULT_TTL));
    }

    private function hasLeadingZeroBits(string $hash, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);
        for ($index = 0; $index < $fullBytes; $index++) {
            if ($hash[$index] !== "\0") {
                return false;
            }
        }

        $remaining = $bits % 8;

        return $remaining === 0 || (ord($hash[$fullBytes]) >> (8 - $remaining)) === 0;
    }

    /**
     * Records a blocked attempt, if the admin asked for it.
     *
     * Off by default because it writes a line for every bot that walks into the check, which on a
     * site being crawled is most of the traffic. Turn it on to tune the difficulty or to work out
     * who is getting caught, rather than leaving it running.
     */
    private function logFailure(string $scope, ?User $user, ?string $agent, string $reason): void
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::POW_LOG_FAILURES)) {
            return;
        }

        $identity = ($user instanceof User && $user->getId() > 0)
            ? sprintf('%s (id %d)', $user->username ?? '?', $user->getId())
            : 'anonymous';

        $this->logger->warning(
            sprintf(
                'Blocked `%s`: %s | user: %s | ip: %s | agent: %s',
                $scope,
                $reason,
                $identity,
                // Core reads the proxy headers Ampache is configured to trust; a PSR request would not.
                Core::get_user_ip() ?: '?',
                substr($agent ?? '', 0, 200) ?: '?'
            ),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
    }

    private function sign(string $id, string $scope, int $difficulty, int $expire): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$id, $scope, $difficulty, $expire]),
            (string) $this->configContainer->get(ConfigurationKeyEnum::SECRET_KEY)
        );
    }
}
