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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PlaylistRemoveSongMethodTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    private PlaylistRemoveSongMethod $subject;

    public function setUp(): void
    {
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);

        $this->subject = new PlaylistRemoveSongMethod(
            $this->modelFactory,
            $this->streamFactory
        );
    }

    public function testHandleThrowsExceptionIfFilterIsMissing(): void
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

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $playlistId = 666;
        $userId     = 42;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $playlist->shouldReceive('has_access')
            ->with($userId)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $playlistId]
        );
    }

    public function testHandleClearsAllSonsgs(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $playlist   = $this->mock(Playlist::class);
        $stream     = $this->mock(StreamInterface::class);

        $playlistId = 666;
        $userId     = 42;
        $result     = 'some-result';

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $playlist->shouldReceive('delete_all')
            ->withNoArgs()
            ->once();

        $output->shouldReceive('success')
            ->with('all songs removed from playlist')
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
                ['filter' => (string) $playlistId, 'clear' => 1]
            )
        );
    }

    public function testHandleThrowsExceptionIfTrackIsNotContained(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $playlistId = 666;
        $userId     = 42;
        $trackId    = 33;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage('Not Found');

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $playlist->shouldReceive('has_item')
            ->with($trackId)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $playlistId, 'track' => (string) $trackId]
        );
    }

    public function testHandleDeleteTrack(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $playlist   = $this->mock(Playlist::class);
        $stream     = $this->mock(StreamInterface::class);

        $playlistId = 666;
        $userId     = 42;
        $result     = 'some-result';
        $trackId    = 33;

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $playlist->shouldReceive('has_item')
            ->with($trackId)
            ->once()
            ->andReturnTrue();
        $playlist->shouldReceive('delete_song')
            ->with($trackId)
            ->once();
        $playlist->shouldReceive('regenerate_track_numbers')
            ->withNoArgs()
            ->once();

        $output->shouldReceive('success')
            ->with('song removed from playlist')
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
                ['filter' => (string) $playlistId, 'song' => (string) $trackId]
            )
        );
    }
}
