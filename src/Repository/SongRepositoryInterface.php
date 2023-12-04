<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Repository;

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Generator;
use Traversable;

interface SongRepositoryInterface
{
    /**
     * gets the songs for an album takes an optional limit
     *
     * @return list<int>
     */
    public function getByAlbum(int $albumId, int $limit = 0): array;

    /**
     * gets the songs for an album for a single disk takes an optional limit
     *
     * @return int[]
     */
    public function getByAlbumDisk(int $albumDiskId, int $limit = 0): array;

    /**
     * gets the songs for a label, based on label name
     *
     * @return int[]
     */
    public function getByLabel(
        string $labelName
    ): array;

    /**
     * Gets the songs from the artist in a random order
     *
     * @return int[]
     */
    public function getRandomByArtist(
        Artist $artist
    ): array;

    /**
     * gets the songs for this artist
     *
     * @return int[]
     */
    public function getTopSongsByArtist(
        Artist $artist,
        int $count = 50
    ): array;

    /**
     * gets the songs for this artist
     *
     * @return int[]
     */
    public function getByArtist(
        int $artistId
    ): array;

    /**
     * gets the songs (including songs where they are the album artist) for this artist
     *
     * @return int[]
     */
    public function getAllByArtist(
        int $artistId
    ): array;

    /**
     * Returns a list of song ID's attached to a license ID.
     *
     * @return int[]
     */
    public function getByLicense(int $licenseId): array;

    public function delete(int $songId): bool;

    public function collectGarbage(Song $song): void;

    /**
     * Returns all song ids linked to the provided catalog (or all)
     *
     * @return Traversable<int>
     */
    public function getByCatalog(?Catalog $catalog = null): Traversable;
}
