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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PlaylistEditMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private PlaylistEditMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);

        $this->subject = new PlaylistEditMethod(
            $this->streamFactory,
            $this->modelFactory
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

        $objectId = 666;
        $userId   = 42;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $playlist->shouldReceive('has_access')
            ->with($userId)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'filter' => (string) $objectId
            ]
        );
    }

    public function testHandleThrowsExceptionIfNoChangesWereMade(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $objectId = 666;
        $userId   = 42;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $playlist->shouldReceive('has_access')
            ->with($userId)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'filter' => (string) $objectId
            ]
        );
    }

    public function testHandleUpdates(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $playlist   = $this->mock(Playlist::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId = 666;
        $userId   = 42;
        $name     = 'some-name';
        $type     = 'some-type';
        $sort     = 1;
        $item1    = 0;
        $item2    = 21;
        $tracks1  = 0;
        $tracks2  = 33;
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $playlist->shouldReceive('has_access')
            ->with($userId)
            ->once()
            ->andReturnTrue();
        $playlist->shouldReceive('update')
            ->with([
                'name' => $name,
                'pl_type' => $type
            ])
            ->once();
        $playlist->shouldReceive('set_by_track_number')
            ->with($item2, $tracks2)
            ->once();
        $playlist->shouldReceive('sort_tracks')
            ->withNoArgs()
            ->once();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive('success')
            ->with('playlist changes saved')
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
                    'filter' => (string) $objectId,
                    'name' => $name,
                    'type' => $type,
                    'items' => implode(',', [$item1, $item2]),
                    'tracks' => implode(',', [$tracks1, $tracks2]),
                    'sort' => (string) $sort
                ]
            )
        );
    }
}
