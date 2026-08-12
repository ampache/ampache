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
 * replayed. A row therefore cannot exist without the work behind it, and the table stays small
 * enough to live in memory.
 */
final readonly class PowService implements PowServiceInterface
{
    private const int DEFAULT_DIFFICULTY = 21;

    private const int DEFAULT_TTL = 1800;

    /** Roughly how often a verification also clears out answers that have expired. */
    private const int GC_ODDS = 50;

    /** The solver only computes the first 32 bits of the digest, so that is the hard ceiling. */
    private const int MAX_DIFFICULTY = 32;

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
        $view = new PowChallengeView(
            $this->issue($scope),
            (string) $request->getUri(),
            AmpConfig::get_web_path()
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
    public function verify(string $scope, array $answer): bool
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
            return $this->reject($scope, 'malformed answer');
        }

        if ($difficulty < self::MIN_DIFFICULTY || $difficulty > self::MAX_DIFFICULTY) {
            return $this->reject($scope, 'difficulty out of range');
        }

        // The signature covers the scope, so an answer earned on a cheap endpoint cannot be spent on
        // an expensive one, and the difficulty cannot be talked down on the way back.
        if (!hash_equals($this->sign($id, $scope, $difficulty, $expire), $signature)) {
            return $this->reject($scope, 'bad signature');
        }

        if ($expire <= time()) {
            return $this->reject($scope, 'expired challenge');
        }

        // Checked before anything is written, so only an answer that cost real work can put a row in
        // the table. A wrong nonce is free to retry against the same challenge, which is fine: every
        // attempt still has to pay for itself.
        if (!$this->hasLeadingZeroBits(hash('sha256', $id . ':' . $nonce, true), $difficulty)) {
            return $this->reject($scope, 'insufficient proof of work');
        }

        if (!$this->consume($id, $expire)) {
            return $this->reject($scope, 'answer already used');
        }

        return true;
    }

    #[Override]
    public function verifyRequest(ServerRequestInterface $request, string $scope): bool
    {
        $body   = (array) $request->getParsedBody();
        $query  = $request->getQueryParams();
        $answer = [];

        foreach (['pow_id', 'pow_exp', 'pow_diff', 'pow_sig', 'pow_nonce'] as $field) {
            $answer[$field] = (string) ($body[$field] ?? $query[$field] ?? '');
        }

        return $this->verify($scope, $answer);
    }

    private function collectGarbage(): void
    {
        try {
            $this->database->query('DELETE FROM `' . self::TABLE . '` WHERE `expire` < ?', [time()]);
        } catch (Throwable) {
            // Nothing to clean up if the table is not there yet.
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
        } catch (Throwable) {
            try {
                // The table is created on first use rather than through a migration, which keeps
                // this out of the way of the schema version and of anything upstream might add.
                $this->createTable();

                $statement = $this->database->query(
                    'INSERT IGNORE INTO `' . self::TABLE . '` (`id`, `expire`) VALUES (?, ?)',
                    [$id, $expire]
                );
            } catch (Throwable $error) {
                // A memory table that has filled up lands here. Refusing is the safe direction: the
                // alternative is accepting answers that can no longer be checked for replay.
                $this->logger->critical(
                    'Could not record a proof of work answer: ' . $error->getMessage(),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                $this->collectGarbage();

                return false;
            }
        }

        if (random_int(1, self::GC_ODDS) === 1) {
            $this->collectGarbage();
        }

        return $statement->rowCount() === 1;
    }

    /**
     * A fixed width ascii key and no payload, so a row is 36 bytes and the memory engine is a
     * natural fit. The engine is configurable because a memory table is emptied on restart and is
     * awkward under replication, which some installs care about more than the speed.
     */
    private function createTable(): void
    {
        $engine = (string) ($this->configContainer->get(ConfigurationKeyEnum::POW_TABLE_ENGINE) ?: 'MEMORY');
        if (!in_array(strtoupper($engine), ['MEMORY', 'INNODB'], true)) {
            $engine = 'MEMORY';
        }

        $this->database->query(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` ('
            . '`id` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, '
            . '`expire` int(11) unsigned NOT NULL, '
            . 'PRIMARY KEY (`id`), '
            . 'KEY `expire` (`expire`)'
            . ') ENGINE=' . $engine
        );
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

    private function reject(string $scope, string $reason): bool
    {
        $this->logger->warning(
            sprintf('Proof of work rejected for `%s`: %s', $scope, $reason),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return false;
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
