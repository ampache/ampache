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

namespace Ampache\Module\Artist;

use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\SongRepositoryInterface;

final class ArtistCountUpdater implements ArtistCountUpdaterInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private ArtistRepositoryInterface $artistRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ArtistRepositoryInterface $artistRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->albumRepository  = $albumRepository;
        $this->artistRepository = $artistRepository;
        $this->songRepository   = $songRepository;
    }

    /**
     * Get album_count, album_group_count for an artist and set it.
     */
    public function update(
        Artist $artist
    ): void {
        $artistId = $artist->getId();

        $album_count = $this->albumRepository->getCountByArtist($artist);
        if ($album_count > 0 && $album_count !== $artist->getAlbumCount() && $artistId) {
            $this->artistRepository->updateAlbumCount($artist, $album_count);

            $artist->setAlbumCount($album_count);

            $this->artistRepository->updateLastUpdate($artistId);
        }

        $group_count = $this->albumRepository->getGroupedCountByArtist($artist);
        if ($group_count > 0 && $group_count !== $artist->getAlbumGroupCount() && $artistId) {
            $this->artistRepository->updateAlbumGroupCount($artist, $group_count);

            $artist->setAlbumGroupCount($group_count);

            $this->artistRepository->updateLastUpdate($artistId);
        }

        $song_count = $this->songRepository->getCountByArtist($artist);
        if ($song_count > 0 && $song_count !== $artist->getSongCount() && $artistId) {
            $this->artistRepository->updateSongCount($artist, $song_count);

            $artist->setSongCount($song_count);

            $this->artistRepository->updateLastUpdate($artistId);
        }

        $artist->update_time();
    }
}
