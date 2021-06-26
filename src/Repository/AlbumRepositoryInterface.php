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

namespace Ampache\Repository;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;

interface AlbumRepositoryInterface
{
    /**
     * This returns a number of random albums.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array;

    /**
     * gets songs from this album
     *
     * @return int[] Album ids
     */
    public function getSongs(
        int $albumId
    ): array;

    /**
     * gets songs from this album group
     *
     * @return int[] Song ids
     */
    public function getSongsGrouped(
        array $albumIdList
    ): array;

    /**
     * gets a random order of songs from this album
     *
     * @return int[] Song ids
     */
    public function getRandomSongs(
        int $albumId
    ): array;

    /**
     * gets a random order of songs from this album group
     *
     * @return int[] Song ids
     */
    public function getRandomSongsGrouped(
        array $albumIdList
    ): array;

    /**
     * Deletes the album entry
     */
    public function delete(
        int $albumId
    ): bool;

    /**
     * gets the album ids with the same musicbrainz identifier
     *
     * @return int[]
     */
    public function getAlbumSuite(
        Album $album,
        int $catalogId = 0
    ): array;

    /**
     * Cleans out unused albums
     */
    public function collectGarbage(): void;

    /**
     * Get time for an album disk by album.
     */
    public function getAlbumDuration(int $albumId): int;

    /**
     * Get play count for an album disk by album id.
     */
    public function getAlbumPlayCount(int $albumId): int;

    /**
     * Get song count for an album disk by album id.
     */
    public function getSongCount(int $albumId): int;

    /**
     * Get distinct artist count for an album disk by album id.
     */
    public function getArtistCount(int $albumId): int;

    /**
     * gets the album ids that this artist is a part of
     *
     * @return int[]
     */
    public function getByArtist(
        int $artistId,
        ?int $catalog = null,
        bool $group_release_type = false
    ): array;
}
