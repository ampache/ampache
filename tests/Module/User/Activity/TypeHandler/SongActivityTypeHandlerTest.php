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

namespace Ampache\Module\User\Activity\TypeHandler;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\UserActivityRepositoryInterface;
use Mockery\MockInterface;

class SongActivityTypeHandlerTest extends MockeryTestCase
{
    /** @var UserActivityRepositoryInterface|MockInterface|null */
    private MockInterface $useractivityRepository;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private ?SongActivityTypeHandler $subject;

    public function setUp(): void
    {
        $this->useractivityRepository = $this->mock(UserActivityRepositoryInterface::class);
        $this->modelFactory           = $this->mock(ModelFactoryInterface::class);

        $this->subject = new SongActivityTypeHandler(
            $this->useractivityRepository,
            $this->modelFactory
        );
    }

    public function testRegisterActivityRegisterAlbumActivity(): void
    {
        $song = $this->mock(Song::class);

        $objectId             = 666;
        $objectType           = 'some-object-type';
        $action               = 'some-action';
        $userId               = 42;
        $date                 = 123;
        $albumName            = 'some-album-name';
        $artistName           = 'some-artist-name';
        $songName             = 'some-song-name';
        $songMusicBrainzId    = 'some-song-mbid';
        $albumMusicBrainzId   = 'some-album-mbid';
        $artistMusicBrainzId  = 'some-artist-mbid';

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $song->f_name      = $songName;
        $song->f_artist    = $artistName;
        $song->f_album     = $albumName;
        $song->mbid        = $songMusicBrainzId;
        $song->artist_mbid = $artistMusicBrainzId;
        $song->album_mbid  = $albumMusicBrainzId;

        $song->shouldReceive('get_fullname')
            ->withNoArgs()
            ->once()
            ->andReturn($songName);

        $this->useractivityRepository->shouldReceive('registerSongEntry')
            ->with(
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $songName,
                $artistName,
                $albumName,
                $songMusicBrainzId,
                $artistMusicBrainzId,
                $albumMusicBrainzId
            )
            ->once();

        $this->subject->registerActivity(
            $objectId,
            $objectType,
            $action,
            $userId,
            $date
        );
    }

    public function testRegisterActivityRegisterGenericActivity(): void
    {
        $song = $this->mock(Song::class);

        $objectId   = 666;
        $objectType = 'some-object-type';
        $action     = 'some-action';
        $userId     = 42;
        $date       = 123;

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $song->f_name = '';

        $song->shouldReceive('get_fullname')
            ->withNoArgs()
            ->once()
            ->andReturn($song->f_name);

        $this->useractivityRepository->shouldReceive('registerGenericEntry')
            ->with(
                $userId,
                $action,
                $objectType,
                $objectId,
                $date
            )
            ->once();

        $this->subject->registerActivity(
            $objectId,
            $objectType,
            $action,
            $userId,
            $date
        );
    }
}
