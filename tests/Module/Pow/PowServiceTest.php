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
     * @return list<array{int, int}>
     */
    public static function difficultyClampDataProvider(): array
    {
        return [
            [99, 32],
            [33, 32],
            [1, 8],
            [0, 21],
            [21, 21],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function engineDataProvider(): array
    {
        return [
            'memory' => ['MEMORY', 'ENGINE=MEMORY'],
            'innodb' => ['InnoDB', 'ENGINE=InnoDB'],
            'unset falls back' => ['', 'ENGINE=MEMORY'],
            'hostile value falls back' => ['MEMORY; DROP TABLE session', 'ENGINE=MEMORY'],
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
        ];
    }

    // -- the interstitial --------------------------------------------------

    public function testCreateChallengeResponseServesThePageWithoutTouchingTheDatabase(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);

        $uri = $this->mock(UriInterface::class);
        $uri->shouldReceive('__toString')->andReturn('https://music.example/batch.php?action=album&id=42');

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getUri')->andReturn($uri);

        $body = $this->mock(StreamInterface::class);
        $this->streamFactory->shouldReceive('createStream')
            ->once()
            ->andReturnUsing(function (string $html) use ($body): StreamInterface {
                self::assertStringContainsString('data-challenge="', $html);
                self::assertStringContainsString('name="pow_sig"', $html);

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

    #[DataProvider('engineDataProvider')]
    public function testTheTableEngineIsValidatedBeforeItReachesTheStatement(string $configured, string $expected): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, engine: $configured);
        $challenge = $this->subject->issue('register');

        $statement = $this->mock(PDOStatement::class);
        $statement->shouldReceive('rowCount')->andReturn(1);

        $create = '';
        $this->database->shouldReceive('query')
            ->andReturnUsing(function (string $sql) use (&$create, $statement): PDOStatement {
                if (str_contains($sql, 'CREATE TABLE')) {
                    $create = $sql;

                    return $statement;
                }

                if ($create === '') {
                    throw new \RuntimeException('Table does not exist');
                }

                return $statement;
            });

        $this->subject->verify('register', $this->answerFor($challenge));

        self::assertStringContainsString($expected, $create);
        self::assertStringNotContainsString('DROP TABLE', $create);
    }

    // -- verifying --------------------------------------------------------

    public function testVerifyAcceptsAPaidAnswerAndRecordsIt(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800);
        $challenge = $this->subject->issue('register');

        $this->expectConsume($challenge->id, affected: 1);

        self::assertTrue($this->subject->verify('register', $this->answerFor($challenge)));
    }

    public function testVerifyCreatesTheTableWhenItIsMissingAndRetries(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, engine: 'MEMORY');
        $challenge = $this->subject->issue('register');

        $statement = $this->mock(PDOStatement::class);
        $statement->shouldReceive('rowCount')->andReturn(1);

        $queries = [];
        $this->database->shouldReceive('query')
            ->times(3)
            ->andReturnUsing(function (string $sql) use (&$queries, $statement): PDOStatement {
                $queries[] = $sql;
                if (count($queries) === 1) {
                    throw new \RuntimeException('Table does not exist');
                }

                return $statement;
            });

        self::assertTrue($this->subject->verify('register', $this->answerFor($challenge)));
        self::assertStringStartsWith('INSERT IGNORE', $queries[0]);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS', $queries[1]);
        self::assertStringContainsString('ENGINE=MEMORY', $queries[1]);
        // A memory table hashes its indexes unless asked otherwise, and the purge needs a range scan.
        self::assertStringContainsString('USING BTREE', $queries[1]);
        self::assertStringStartsWith('INSERT IGNORE', $queries[2]);
    }

    public function testVerifyRefusesRatherThanAcceptWhenStorageKeepsFailing(): void
    {
        $this->configureFor(difficulty: 12, ttl: 1800, engine: 'MEMORY');
        $challenge = $this->subject->issue('register');

        // A memory table that has filled up behaves like this. Accepting would mean handing out
        // answers that can no longer be checked for replay, so the service has to close.
        $this->database->shouldReceive('query')
            ->andThrow(new \RuntimeException('The table is full'));

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

    // -- helpers -----------------------------------------------------------

    private function configureFor(int $difficulty, int $ttl, string $engine = 'MEMORY'): void
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
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::POW_TABLE_ENGINE)
            ->andReturn($engine);
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
