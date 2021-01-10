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

namespace Ampache\Repository;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;

final class AlbumRepository implements AlbumRepositoryInterface
{
    /**
     * This returns a number of random album
     *
     * @return int[] Album ids
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array {
        $results = [];

        if (!$count) {
            $count = 1;
        }

        $sort_disk = (AmpConfig::get('album_group')) ? 'AND `album`.`disk` = 1 ' : '';

        $sql = 'SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` ';
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ';
            $where = sprintf(
                'WHERE `catalog`.`enabled` = \'1\' %s',
                $sort_disk
            );
        } else {
            $where = sprintf(
                'WHERE 1=1 %s',
                $sort_disk
            );
        }
        $sql .= $where;

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5) {
            $sql .= sprintf(
                'AND `album`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'album\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ',
                $rating_filter,
                $userId
            );
        }
        $sql .= sprintf(
            'ORDER BY RAND() LIMIT %d',
            $count
        );
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * Get the add date of first added song
     */
    public function getFirstSongAddTime(
        int $albumId
    ): int {
        $time = 0;

        $db_results = Dba::read(
            'SELECT MIN(`addition_time`) AS `addition_time` FROM `song` WHERE `album` = ?',
            [$albumId]
        );
        if ($data = Dba::fetch_row($db_results)) {
            $time = (int) $data[0];
        }

        return $time;
    }

    /**
     * gets a random number, and a random assortment of songs from this album
     *
     * @return int[] Album ids
     */
    public function getRandomSongs(
        int $albumId
    ): array {
        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ';
        }
        $sql .= 'WHERE `song`.`album` = ? ';
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }
        $sql .= 'ORDER BY RAND()';
        $db_results = Dba::read($sql, [$albumId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * Deletes the album entry
     */
    public function delete(
        int $albumId
    ): bool {
        $result = Dba::write(
            'DELETE FROM `artist` WHERE `id` = ?',
            [$albumId]
        );

        return $result !== false;
    }
}
