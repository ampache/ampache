<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

interface AlbumRepositoryInterface
{
    /**
     * This returns a number of random albums
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array;

    /**
     * This returns a number of random album_disks
     *
     * @return int[]
     */
    public function getRandomAlbumDisk(
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
     * gets songs from this album_disk id
     *
     * @return int[] Song ids
     */
    public function getSongsByAlbumDisk(
        int $albumDiskId
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
    public function getRandomSongsByAlbumDisk(
        int $albumDiskId
    ): array;

    /**
     * Deletes the album entry
     */
    public function delete(
        int $albumId
    ): bool;

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
     * Get distinct artist count for an album array.
     */
    public function getArtistCountGroup(array $albumArray): int;

    /**
     * gets the album ids that this artist is a part of
     * Return Album or AlbumDisk based on album_group preference
     *
     * @return int[]
     */
    public function getByArtist(
        int $artistId,
        ?int $catalog = null,
        bool $group_release_type = false
    ): array;

    /**
     * gets the album ids that this artist is a part of
     * Return Album only
     *
     * @return int[]
     */
    public function getAlbumByArtist(
        int $artistId,
        ?int $catalog = null,
        bool $group_release_type = false
    ): array;

    /**
     * gets the album disk ids that this artist is a part of
     * Return AlbumDisk only
     *
     * @return int[]
     */
    public function getAlbumDiskByArtist(
        int $artistId,
        ?int $catalog = null,
        bool $group_release_type = false
    ): array;

    /**
     * gets the album id has the same artist and title
     *
     * @return int[]
     */
    public function getByName(
        string $name,
        int $artistId
    ): array;

    /**
     * gets the album id that is part of this mbid_group
     *
     * @return int[]
     */
    public function getByMbidGroup(
        string $mbid
    ): array;
}
