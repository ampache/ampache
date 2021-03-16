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
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class PlaylistSongsMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    private ?PlaylistSongsMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);
        $this->logger        = $this->mock(LoggerInterface::class);

        $this->subject = new PlaylistSongsMethod(
            $this->streamFactory,
            $this->modelFactory,
            $this->logger
        );
    }

    public function testHandleThrowsExceptionIfFilterParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfObjectIsNew(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $playlist   = $this->mock(Search::class);

        $userId   = 666;
        $objectId = 42;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: smart_%s', $objectId));

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf(
                    'User %d loading playlist: smart_%s',
                    $userId,
                    $objectId
                ),
                [LegacyLogger::CONTEXT_TYPE => PlaylistSongsMethod::class]
            )
            ->once();

        $this->modelFactory->shouldReceive('createSearch')
            ->with($objectId, 'song', $user)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 'smart_' . $objectId]
        );
    }

    public function testHandleThrowsExceptionIfAccessToObjectIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $playlist   = $this->mock(Playlist::class);

        $userId   = 666;
        $objectId = 42;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf(
                    'User %d loading playlist: %s',
                    $userId,
                    $objectId
                ),
                [LegacyLogger::CONTEXT_TYPE => PlaylistSongsMethod::class]
            )
            ->once();

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $playlist->type = 'private';
        $playlist->shouldReceive('has_access')
            ->with($userId)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $objectId]
        );
    }

    public function testHandleReturnsEmptyResponse(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $playlist   = $this->mock(Playlist::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId   = 666;
        $objectId = 42;
        $result   = 'some-result';

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf(
                    'User %d loading playlist: %s',
                    $userId,
                    $objectId
                ),
                [LegacyLogger::CONTEXT_TYPE => PlaylistSongsMethod::class]
            )
            ->once();

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $playlist->shouldReceive('get_items')
            ->withNoArgs()
            ->once()
            ->andReturn([]);
        $playlist->type = 'public';

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive('emptyResult')
            ->with('song')
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
                ['filter' => $objectId]
            )
        );
    }

    public function testHandleReturnResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $playlist   = $this->mock(Playlist::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId   = 666;
        $objectId = 42;
        $limit    = 21;
        $offset   = 33;
        $songId   = 1234;
        $result   = 'some-result';

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf(
                    'User %d loading playlist: %s',
                    $userId,
                    $objectId
                ),
                [LegacyLogger::CONTEXT_TYPE => PlaylistSongsMethod::class]
            )
            ->once();

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $playlist->shouldReceive('get_items')
            ->withNoArgs()
            ->once()
            ->andReturn([[
                'object_type' => 'song',
                'object_id' => (string) $songId,
            ]]);
        $playlist->type = 'public';

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive('songs')
            ->with(
                [$songId],
                $userId,
                true,
                true,
                true,
                $limit,
                $offset
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
                ['filter' => $objectId, 'limit' => $limit, 'offset' => $offset]
            )
        );
    }
}
