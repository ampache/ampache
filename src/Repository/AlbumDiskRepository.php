<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;

/**
 * Provides database access to album-disks
 */
final class AlbumDiskRepository implements AlbumDiskRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    public function __construct(
        DatabaseConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Returns the disks for an album
     *
     * @return list<AlbumDisk>
     */
    public function getByAlbum(Album $album): array
    {
        $result = $this->connection->query(
            'SELECT DISTINCT `id`, `disk` FROM `album_disk` WHERE `album_id` = ? ORDER BY `disk`',
            [$album->getId()]
        );

        $results    = array();
        while ($rowId = $result->fetchColumn()) {
            $results[] = new AlbumDisk((int) $rowId);
        }

        return $results;
    }
}
