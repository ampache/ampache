<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Plugin\Adapter\UserMediaPlaySaverAdapterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class RecordPlayMethodTest extends MockeryTestCase
{
    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var UserMediaPlaySaverAdapterInterface|MockInterface|null */
    private MockInterface $userMediaPlaySaverAdapter;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private RecordPlayMethod $subject;

    public function setUp(): void
    {
        $this->userRepository            = $this->mock(UserRepositoryInterface::class);
        $this->userMediaPlaySaverAdapter = $this->mock(UserMediaPlaySaverAdapterInterface::class);
        $this->streamFactory             = $this->mock(StreamFactoryInterface::class);
        $this->logger                    = $this->mock(LoggerInterface::class);
        $this->modelFactory              = $this->mock(ModelFactoryInterface::class);

        $this->subject = new RecordPlayMethod(
            $this->userRepository,
            $this->userMediaPlaySaverAdapter,
            $this->streamFactory,
            $this->logger,
            $this->modelFactory
        );
    }

    /**
     * @dataProvider requestParameterDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamsMissing(
        array $input,
        string $keyName
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $keyName));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function requestParameterDataProvider(): array
    {
        return [
            [[], 'id'],
            [['id' => 1], 'user'],
        ];
    }

    public function testHandleThrowsExceptionIfAccessIsDeniedForNonAdminUsers(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $playUserName = 'some-user';
        $playUserId   = 666;
        $apiUserId    = 42;

        $this->userRepository->shouldReceive('findByUsername')
            ->with($playUserName)
            ->once()
            ->andReturn($playUserId);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($apiUserId);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => 21,
                'user' => $playUserName
            ]
        );
    }

    public function testHandleThrowsExceptionIfUserIsNotAllowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $apiUserId = 42;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $apiUserId));

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($apiUserId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => 21,
                'user' => $apiUserId
            ]
        );
    }

    public function testHandleThrowsExceptionIfSongWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $apiUserId = 42;
        $songId    = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $songId));

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($apiUserId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([$apiUserId]);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => (string) $songId,
                'user' => (string) $apiUserId
            ]
        );
    }

    public function testHandleRegisters(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);
        $user       = $this->mock(User::class);

        $apiUserId = 42;
        $songId    = 666;
        $result    = 'some-result';
        $date      = 1234;
        $client    = 'some-client';
        $userName  = 'some-name';

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($apiUserId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([$apiUserId]);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);
        $this->modelFactory->shouldReceive('createUser')
            ->with($apiUserId)
            ->once()
            ->andReturn($user);

        $user->username = $userName;

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('record_play: %s for %s using %s %d', $songId, $userName, $client, $date),
                [LegacyLogger::CONTEXT_TYPE => RecordPlayMethod::class]
            )
            ->once();

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('set_played')
            ->with($apiUserId, $client, [], $date)
            ->once()
            ->andReturnTrue();

        $this->userMediaPlaySaverAdapter->shouldReceive('save')
            ->with($user, $song)
            ->once();

        $output->shouldReceive('success')
            ->with(
                sprintf('successfully recorded play: %s for: %s', $songId, $userName)
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'id' => (string) $songId,
                    'user' => (string) $apiUserId,
                    'client' => $client,
                    'date' => (string) $date,
                ]
            )
        );
    }
}
