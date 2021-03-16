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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class AlbumSongsMethodTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var SongRepositoryInterface|MockInterface|null */
    private MockInterface $songRepository;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private ?AlbumSongsMethod $subject;

    public function setUp(): void
    {
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->songRepository  = $this->mock(SongRepositoryInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new AlbumSongsMethod(
            $this->modelFactory,
            $this->songRepository,
            $this->streamFactory,
            $this->configContainer
        );
    }

    public function testHandleThrowsExceptionIfFilterIsMissing(): void
    {
        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfAlbumIsInvalid(): void
    {
        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $objectId);

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $album      = $this->mock(Album::class);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($objectId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $objectId]
        );
    }

    public function testHandleReturnsSongsFromGroupedAlbums(): void
    {
        $objectId = 666;
        $discId   = 42;
        $songId   = 33;
        $result   = 'some-result';
        $userId   = 21;

        $gatekeeper   = $this->mock(GatekeeperInterface::class);
        $response     = $this->mock(ResponseInterface::class);
        $output       = $this->mock(ApiOutputInterface::class);
        $album        = $this->mock(Album::class);
        $albumDiskOne = $this->mock(Album::class);
        $stream       = $this->mock(StreamInterface::class);

        $albumDiskOne->id = $discId;

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($objectId)
            ->once()
            ->andReturn($album);
        $this->modelFactory->shouldReceive('createAlbum')
            ->with($discId)
            ->once()
            ->andReturn($albumDiskOne);

        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $album->shouldReceive('get_group_disks_ids')
            ->withNoArgs()
            ->once()
            ->andReturn([$discId]);

        $this->songRepository->shouldReceive('getByAlbum')
            ->with($discId)
            ->once()
            ->andReturn([$songId]);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALBUM_GROUP)
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('songs')
            ->with(
                [$songId],
                $userId,
                true,
                true,
                true,
                0,
                0
            )
            ->once()
            ->andReturn($result);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $objectId]
        );
    }

    public function testHandleReturnsEmptyListFromSingleAlbum(): void
    {
        $objectId = 666;
        $discId   = 42;
        $result   = 'some-result';

        $gatekeeper   = $this->mock(GatekeeperInterface::class);
        $response     = $this->mock(ResponseInterface::class);
        $output       = $this->mock(ApiOutputInterface::class);
        $album        = $this->mock(Album::class);
        $stream       = $this->mock(StreamInterface::class);

        $album->id = $discId;

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($objectId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->songRepository->shouldReceive('getByAlbum')
            ->with($discId)
            ->once()
            ->andReturn([]);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALBUM_GROUP)
            ->once()
            ->andReturnFalse();

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

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $objectId]
        );
    }
}
