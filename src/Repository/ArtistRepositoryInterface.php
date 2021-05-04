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

use Ampache\Repository\Model\Artist;
use Generator;

interface ArtistRepositoryInterface
{
    /**
     * Deletes the artist entry
     */
    public function delete(int $artistId): bool;

    /**
     * This returns a number of random artists.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        int $count = 1
    ): array;

    /**
     * Get time for an artist's songs.
     */
    public function getDuration(Artist $artist): int;

    /**
     * This gets an artist object based on the artist name
     */
    public function findByName(string $name): ?Artist;

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void;

    /**
     * Update artist associated user.
     */
    public function updateArtistUser(Artist $artist, int $user): void;

    /**
     * Update artist last_update time.
     */
    public function updateLastUpdate(int $artistId): void;

    public function updateAlbumCount(Artist $artist, int $count): void;

    public function updateAlbumGroupCount(Artist $artist, int $count): void;

    public function updateSongCount(Artist $artist, int $count): void;

    public function updateTime(Artist $artist, int $time): void;

    public function updateArtistInfo(
        Artist $artist,
        string $summary,
        string $placeformed,
        int $yearformed,
        bool $manual = false
    ): void;

    /**
     * @return Generator<array<string, mixed>>
     */
    public function getByIdList(
        array $idList
    ): Generator;

    /**
     * Get each id from the artist table with the minimum detail required for subsonic
     * @param int[] $catalogIds
     * @return array
     */
    public function getSubsonicRelatedDataByCatalogs(array $catalogIds = []): array;

    /**
     * Get info from the artist table with the minimum detail required for subsonic
     *
     * @return array{id: int, full_name: string, name: string, album_count: int, song_count: int}
     */
    public function getSubsonicRelatedDataByArtist(int $artistId): array;
}
