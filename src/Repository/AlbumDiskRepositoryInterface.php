<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;

interface AlbumDiskRepositoryInterface
{
    /**
     * Returns the id of the matching disk, creating or moving it when needed
     *
     * Pass $currentId to move an existing row rather than create a second; 0 means nothing was written.
     */
    public function check(
        int $albumId,
        int $disk,
        int $catalogId,
        ?string $disksubtitle = null,
        ?int $currentId = null,
    ): int;

    /**
     * Loads a single album-disk, or null when the id matches nothing
     */
    public function findById(int $objectId): ?AlbumDisk;

    /**
     * Returns the number of distinct artists mapped to the disk's album
     */
    public function getArtistCount(AlbumDisk $albumDisk): int;

    /**
     * Returns the disks for an album
     *
     * @return list<AlbumDisk>
     */
    public function getByAlbum(Album $album): array;

    /**
     * Returns the ids of every song on the disk
     *
     * @return int[]
     */
    public function getSongs(AlbumDisk $albumDisk): array;
}
