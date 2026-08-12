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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class PowServiceTest extends MockeryTestCase
{
    private const string SECRET = 'a-secret-long-enough-to-sign-with';

    private MockInterface&ConfigContainerInterface $configContainer;
    private MockInterface&DatabaseConnectionInterface $database;
    private MockInterface&LoggerInterface $logger;
    private MockInterface&ResponseFactoryInterface $responseFactory;
    private MockInterface&StreamFactoryInterface $streamFactory;
    private PowService $subject;

    /**
     * @return array<string, array{string}>
     */
    public static function badAckTokenDataProvider(): array
    {
        return [
            'absent' => [''],
            'too short' => [str_repeat('a', 31)],
            'too long' => [str_repeat('a', 33)],
            'not hex' => [str_repeat('Z', 32)],
            // Would break out of the header if it were echoed unchecked.
            'header injection' => ["aaaaaaaaaaaaaaaa\r\nSet-Cookie: session=evil"],
            'trailing newline' => [str_repeat('a', 31) . "\n"],
        ];
    }

    /**
     * @return list<array{int, int}>
     */
    public static function difficultyClampDataProvider(): array
    {
        return [
            [99, 26],
            [27, 26],
            [1, 8],
            [0, 21],
            [21, 21],
        ];
    }

    // -- who gets challenged ----------------------------------------------

    /**
     * @return array<string, array{string, string, int, int, bool}>
     */
    public static function isRequiredDataProvider(): array
    {
        return [
            'off, anonymous' => ['off', 'register', 0, 0, false],
            'off, member' => ['off', 'batch', 7, 0, false],
            'guest, anonymous' => ['guest', 'register', 0, 0, true],
            'guest, member' => ['guest', 'batch', 7, 0, false],
            'all, anonymous' => ['all', 'register', 0, 0, true],
            'all, member' => ['all', 'batch', 7, 0, true],
            'all, admin with nobody exempt' => ['all', 'batch', 1, 0, true],
            'all, admin exempt' => ['all', 'batch', 1, 100, false],
            'unknown mode falls back to off' => ['nonsense', 'register', 0, 0, false],
            'download, guest' => ['guest', 'download', 0, 0, true],
        ];
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function malformedAnswerDataProvider(): array
    {
        return [
            'empty' => [[]],
            'short id' => [['pow_id' => 'abc', 'pow_exp' => '1', 'pow_diff' => '12', 'pow_sig' => str_repeat('a', 64), 'pow_nonce' => '1']],
            'non hex id' => [['pow_id' => str_repeat('z', 32), 'pow_exp' => '1', 'pow_diff' => '12', 'pow_sig' => str_repeat('a', 64), 'pow_nonce' => '1']],
            'negative nonce' => [['pow_id' => str_repeat('a', 32), 'pow_exp' => '1', 'pow_diff' => '12', 'pow_sig' => str_repeat('a', 64), 'pow_nonce' => '-1']],
            'short signature' => [['pow_id' => str_repeat('a', 32), 'pow_exp' => '1', 'pow_diff' => '12', 'pow_sig' => 'ff', 'pow_nonce' => '1']],
            'difficulty below the floor' => [['pow_id' => str_repeat('a', 32), 'pow_exp' => '1', 'pow_diff' => '4', 'pow_sig' => str_repeat('a', 64), 'pow_nonce' => '1']],
            'difficulty above the ceiling' => [['pow_id' => str_repeat('a', 32), 'pow_exp' => '1', 'pow_diff' => '48', 'pow_sig' => str_repeat('a', 64), 'pow_nonce' => '1']],
            'difficulty the solver could do but the service will not sign' => [['pow_id' => str_repeat('a', 32), 'pow_exp' => '1', 'pow_diff' => '32', 'pow_sig' => str_repeat('a', 64), 'pow_nonce' => '1']],
        ];
    }

    public function testABlockedAnonymousAttemptIsLoggedWithItsReason(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, logFailures: true);

        $logged = $this->captureWarning();

        self::assertFalse($this->subject->verify('batch', []));
        self::assertStringContainsString('Blocked `batch`', $logged->message);
        self::assertStringContainsString('malformed answer', $logged->message);
        self::assertStringContainsString('user: anonymous', $logged->message);
    }

    public function testABlockedAttemptNamesTheUserWhenThereIsOne(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, logFailures: true);

        $user           = $this->mock(User::class);
        $user->username = 'zoe';
        $user->shouldReceive('getId')->andReturn(42);

        $logged = $this->captureWarning();

        self::assertFalse($this->subject->verify('download', [], $user));
        self::assertStringContainsString('Blocked `download`', $logged->message);
        self::assertStringContainsString('user: zoe (id 42)', $logged->message);
    }

    public function testAReplayedAnswerIsLoggedAsSuch(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, logFailures: true);
        $challenge = $this->subject->issue('register');

        $this->expectConsume($challenge->id, affected: 0);

        $logged = $this->captureWarning();

        self::assertFalse($this->subject->verify('register', $this->answerFor($challenge)));
        self::assertStringContainsString('answer already used', $logged->message);
    }

    #[DataProvider('badAckTokenDataProvider')]
    public function testConfirmDeliveryDropsATokenItDidNotIssue(string $token): void
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getQueryParams')->andReturn(['pow_ack' => $token]);

        $response = $this->mock(ResponseInterface::class);
        $response->shouldNotReceive('withAddedHeader');

        self::assertSame($response, $this->subject->confirmDelivery($request, $response));
    }

    public function testConfirmDeliveryEchoesAValidTokenAsAReadableCookie(): void
    {
        $token = str_repeat('a', 32);

        $uri = $this->mock(UriInterface::class);
        $uri->shouldReceive('getScheme')->andReturn('https');

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getQueryParams')->andReturn(['pow_ack' => $token]);
        $request->shouldReceive('getUri')->andReturn($uri);

        $decorated = $this->mock(ResponseInterface::class);
        $response  = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withAddedHeader')
            ->once()
            ->andReturnUsing(function (string $name, string $value) use ($decorated, $token): ResponseInterface {
                self::assertSame('Set-Cookie', $name);
                self::assertStringContainsString('pow_ack=' . $token, $value);
                self::assertStringContainsString('SameSite=Lax', $value);
                self::assertStringContainsString('Secure', $value);
                // The page has to read it, so it deliberately is not HttpOnly.
                self::assertStringNotContainsString('HttpOnly', $value);

                return $decorated;
            });

        self::assertSame($decorated, $this->subject->confirmDelivery($request, $response));
    }

    public function testConfirmDeliveryLeavesSecureOffPlainHttp(): void
    {
        $uri = $this->mock(UriInterface::class);
        $uri->shouldReceive('getScheme')->andReturn('http');

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getQueryParams')->andReturn(['pow_ack' => str_repeat('b', 32)]);
        $request->shouldReceive('getUri')->andReturn($uri);

        $response = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withAddedHeader')
            ->once()
            ->andReturnUsing(function (string $name, string $value) use ($response): ResponseInterface {
                self::assertStringNotContainsString('Secure', $value);

                return $response;
            });

        $this->subject->confirmDelivery($request, $response);
    }

    public function testCreateChallengeResponseRefusesToReplayANonGetRequest(): void
    {
        // The interstitial replays as a GET form, so a body would be dropped on the way through.
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn('POST');

        $response = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->andReturnSelf();

        $this->responseFactory->shouldReceive('createResponse')->with(405)->once()->andReturn($response);
        $this->streamFactory->shouldNotReceive('createStream');

        self::assertSame($response, $this->subject->createChallengeResponse($request, 'batch'));
    }

    // -- the interstitial --------------------------------------------------

    public function testCreateChallengeResponseServesThePageWithoutTouchingTheDatabase(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);

        $uri = $this->mock(UriInterface::class);
        $uri->shouldReceive('__toString')->andReturn('https://music.example/batch.php?action=album&id=42');

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('getUri')->andReturn($uri);
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('https://music.example/albums.php?id=42');

        $body = $this->mock(StreamInterface::class);
        $this->streamFactory->shouldReceive('createStream')
            ->once()
            ->andReturnUsing(function (string $html) use ($body): StreamInterface {
                self::assertStringContainsString('data-challenge="', $html);
                self::assertStringContainsString('name="pow_sig"', $html);
                // Submitted into the frame, so the page is not unloaded while the archive is built.
                self::assertStringContainsString('target="pow-sink"', $html);
                // Relative, so it can only ever resolve against this origin.
                self::assertStringContainsString('data-pow-return="/albums.php?id=42"', $html);
                // `batch` returns a response object, so it can echo the acknowledgement cookie.
                self::assertStringContainsString('data-pow-ack="1"', $html);

                return $body;
            });

        $response = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->andReturnSelf();
        $response->shouldReceive('withBody')->with($body)->andReturnSelf();

        $this->responseFactory->shouldReceive('createResponse')->with(200)->andReturn($response);

        self::assertSame($response, $this->subject->createChallengeResponse($request, 'batch'));
    }

    #[DataProvider('isRequiredDataProvider')]
    public function testIsRequired(string $mode, string $scope, int $userId, int $exemptLevel, bool $expected): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POW_MODE)
            ->andReturn($mode);
        $this->configContainer->shouldReceive('getArray')
            ->with(ConfigurationKeyEnum::POW_PROTECTED)
            ->andReturn(['register', 'batch', 'download']);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POW_EXEMPT_LEVEL)
            ->andReturn($exemptLevel);

        $user = null;
        if ($userId > 0) {
            $user         = $this->mock(User::class);
            $user->access = $userId === 1 ? 100 : 25;
            $user->shouldReceive('getId')->andReturn($userId);
        }

        self::assertSame($expected, $this->subject->isRequired($scope, $user));
    }

    public function testIsRequiredIgnoresScopesOutsideTheConfiguredList(): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POW_MODE)
            ->andReturn('all');
        $this->configContainer->shouldReceive('getArray')
            ->with(ConfigurationKeyEnum::POW_PROTECTED)
            ->andReturn(['register']);

        self::assertFalse($this->subject->isRequired('batch', null));
        self::assertFalse($this->subject->isRequired('download', null));
        self::assertTrue($this->subject->isRequired('register', null));
    }

    #[DataProvider('difficultyClampDataProvider')]
    public function testIssueClampsTheConfiguredDifficulty(int $configured, int $expected): void
    {
        $this->configureFor(difficulty: $configured, ttl: 1800);

        self::assertSame($expected, $this->subject->issue('batch')->difficulty);
    }

    public function testIssueCreatesADistinctChallengeEveryTime(): void
    {
        $this->configureFor(difficulty: 14, ttl: 1800);

        self::assertNotSame(
            $this->subject->issue('register')->id,
            $this->subject->issue('register')->id
        );
    }

    // -- issuing ----------------------------------------------------------

    public function testIssueSignsTheTermsWithoutTouchingTheDatabase(): void
    {
        $this->configureFor(difficulty: 14, ttl: 1800);

        // No expectation is set on the database mock, so any query at all fails the test. That is
        // the property worth pinning down: handing out challenges has to stay free.
        $challenge = $this->subject->issue('register');

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $challenge->id);
        self::assertSame(14, $challenge->difficulty);
        self::assertSame(
            hash_hmac('sha256', implode('|', [$challenge->id, 'register', 14, $challenge->expire]), self::SECRET),
            $challenge->signature
        );
    }

    // -- logging blocked attempts -----------------------------------------

    public function testNothingIsLoggedWhenFailureLoggingIsOff(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, logFailures: false);

        $this->logger->shouldNotReceive('warning');

        self::assertFalse($this->subject->verify('register', []));
    }

    // -- verifying --------------------------------------------------------

    public function testVerifyAcceptsAPaidAnswerAndRecordsIt(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $this->expectConsume($challenge->id, affected: 1);

        self::assertTrue($this->subject->verify('register', $this->answerFor($challenge)));
    }

    public function testVerifyRefusesRatherThanAcceptWhenStorageFails(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        // Accepting would mean handing out answers that can no longer be checked for replay, so a
        // database that cannot record one has to close the endpoint rather than open it.
        $this->database->shouldReceive('query')
            ->andThrow(new \RuntimeException('Lost connection to server'));

        $this->logger->shouldReceive('critical')->once();

        self::assertFalse($this->subject->verify('register', $this->answerFor($challenge)));
    }

    public function testVerifyRejectsALoweredDifficulty(): void
    {
        $this->configureFor(difficulty: 16, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $answer              = $this->answerFor($challenge);
        $answer['pow_diff']  = '8';
        $answer['pow_nonce'] = $this->solve($challenge->id, 8);

        self::assertFalse($this->subject->verify('register', $answer));
    }

    public function testVerifyRejectsAnAnswerEarnedOnAnotherScope(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        self::assertFalse($this->subject->verify('batch', $this->answerFor($challenge)));
    }

    public function testVerifyRejectsAnAnswerThatHasAlreadyBeenUsed(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        // `INSERT IGNORE` reports nothing inserted when the id is already there.
        $this->expectConsume($challenge->id, affected: 0);

        self::assertFalse($this->subject->verify('register', $this->answerFor($challenge)));
    }

    public function testVerifyRejectsAnExtendedExpiry(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $answer            = $this->answerFor($challenge);
        $answer['pow_exp'] = (string) (time() + 86400);

        self::assertFalse($this->subject->verify('register', $answer));
    }

    public function testVerifyRejectsAProperlySignedButExpiredChallenge(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);

        $id     = str_repeat('d', 32);
        $expire = time() - 10;
        $answer = [
            'pow_id' => $id,
            'pow_exp' => (string) $expire,
            'pow_diff' => '12',
            'pow_sig' => hash_hmac('sha256', implode('|', [$id, 'batch', 12, $expire]), self::SECRET),
            'pow_nonce' => $this->solve($id, 12),
        ];

        self::assertFalse($this->subject->verify('batch', $answer));
    }

    public function testVerifyRejectsASubstitutedId(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $answer           = $this->answerFor($challenge);
        $answer['pow_id'] = str_repeat('c', 32);

        self::assertFalse($this->subject->verify('register', $answer));
    }

    public function testVerifyRejectsAWrongNonceWithoutWritingAnything(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $answer              = $this->answerFor($challenge);
        $answer['pow_nonce'] = '1';

        // Again, no database expectation: an unpaid answer must never reach storage, otherwise
        // asking for challenges in a loop would be a way to fill the table.
        self::assertFalse($this->subject->verify('register', $answer));
    }

    /**
     * @param array<string, string> $answer
     */
    #[DataProvider('malformedAnswerDataProvider')]
    public function testVerifyRejectsMalformedAnswers(array $answer): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);

        self::assertFalse($this->subject->verify('register', $answer));
    }

    public function testVerifyRequestFailsWhenTheRequestCarriesNoAnswer(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn([]);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Mozilla/5.0');
        $request->shouldReceive('getQueryParams')->andReturn([]);

        self::assertFalse($this->subject->verifyRequest($request, 'batch'));
    }

    public function testVerifyRequestReadsAnAnswerFromThePostBody(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $this->expectConsume($challenge->id, affected: 1);

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn($this->answerFor($challenge));
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Mozilla/5.0');
        $request->shouldReceive('getQueryParams')->andReturn([]);

        self::assertTrue($this->subject->verifyRequest($request, 'register'));
    }

    // -- reading an answer off a request ----------------------------------

    public function testVerifyRequestReadsAnAnswerFromTheQueryString(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('batch');

        $this->expectConsume($challenge->id, affected: 1);

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(null);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Mozilla/5.0');
        $request->shouldReceive('getQueryParams')->andReturn($this->answerFor($challenge));

        self::assertTrue($this->subject->verifyRequest($request, 'batch'));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->database        = $this->mock(DatabaseConnectionInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);

        $this->logger->shouldIgnoreMissing();

        $this->subject = new PowService(
            $this->configContainer,
            $this->database,
            $this->logger,
            $this->responseFactory,
            $this->streamFactory
        );
    }

    /**
     * @return array<string, string>
     */
    private function answerFor(PowChallenge $challenge): array
    {
        return [
            'pow_id' => $challenge->id,
            'pow_exp' => (string) $challenge->expire,
            'pow_diff' => (string) $challenge->difficulty,
            'pow_sig' => $challenge->signature,
            'pow_nonce' => $this->solve($challenge->id, $challenge->difficulty),
        ];
    }

    /**
     * Expects exactly one warning and gives back a holder for its message.
     */
    private function captureWarning(): object
    {
        $logged = new class {
            public string $message = '';
        };

        $this->logger->shouldReceive('warning')
            ->once()
            ->andReturnUsing(function (string $message) use ($logged): void {
                $logged->message = $message;
            });

        return $logged;
    }

    // -- helpers -----------------------------------------------------------

    private function configureFor(int $difficulty, int $ttl, bool $logFailures = false): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POW_DIFFICULTY)
            ->andReturn($difficulty);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POW_TTL)
            ->andReturn($ttl);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SECRET_KEY)
            ->andReturn(self::SECRET);
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::POW_LOG_FAILURES)
            ->andReturn($logFailures);
    }

    private function expectConsume(string $id, int $affected): void
    {
        $statement = $this->mock(PDOStatement::class);
        $statement->shouldReceive('rowCount')->andReturn($affected);

        $this->database->shouldReceive('query')
            ->withArgs(fn(string $sql, array $params = []): bool => str_starts_with($sql, 'INSERT IGNORE') && ($params[0] ?? '') === $id)
            ->andReturn($statement);

        // The opportunistic purge fires on a random draw, so it has to be allowed but not demanded.
        $this->database->shouldReceive('query')
            ->withArgs(fn(string $sql): bool => str_starts_with($sql, 'DELETE'))
            ->andReturn($statement);
    }

    private function solve(string $id, int $difficulty): string
    {
        for ($nonce = 0; ; $nonce++) {
            $hash  = hash('sha256', $id . ':' . $nonce, true);
            $bits  = 0;
            $bytes = unpack('C*', $hash) ?: [];

            foreach ($bytes as $byte) {
                if ($byte === 0) {
                    $bits += 8;
                    continue;
                }

                for ($index = 7; $index >= 0; $index--) {
                    if ($byte & (1 << $index)) {
                        break 2;
                    }

                    $bits++;
                }

                break;
            }

            if ($bits >= $difficulty) {
                return (string) $nonce;
            }
        }
    }
}
