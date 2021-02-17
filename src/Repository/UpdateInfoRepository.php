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

use Ampache\Module\System\Dba;

final class UpdateInfoRepository implements UpdateInfoRepositoryInterface
{
    /**
     * Updates the count of item by table name
     */
    public function updateCountByTableName(string $tableName): int
    {
        $db_results = Dba::read(
            sprintf('SELECT COUNT(`id`) FROM `%s`', Dba::escape($tableName))
        );

        $data = Dba::fetch_row($db_results);

        $value = (int) $data[0];

        $this->setCount($tableName, $value);

        return $value;
    }

    /**
     * This returns the current number of songs, videos, albums, and artists
     * across all catalogs on the server
     *
     * @return array<string, int>
     */
    public function countServer(bool $enabled = false, string $table = ''): array
    {
        // tables with media items to count, song-related tables and the rest
        $media_tables = array('song', 'video', 'podcast_episode');
        $song_tables  = array('artist', 'album');
        $list_tables  = array('search', 'playlist', 'live_stream', 'podcast', 'user', 'catalog', 'label', 'tag', 'share', 'license');
        if (!empty($table)) {
            if (in_array($table, $media_tables)) {
                $media_tables = array($table);
                $song_tables  = array();
                $list_tables  = array();
            }
            if (in_array($table, $song_tables)) {
                $media_tables = array();
                $song_tables  = array($table);
                $list_tables  = array();
            }
            if (in_array($table, $list_tables)) {
                $media_tables = array();
                $song_tables  = array();
                $list_tables  = array($table);
            }
        }

        $results = array();
        $items   = '0';
        $time    = '0';
        $size    = '0';
        foreach ($media_tables as $table) {
            $enabled_sql = ($enabled && $table !== 'podcast_episode') ? " WHERE `$table`.`enabled`='1'" : '';
            $sql         = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`), 0) FROM `$table`" . $enabled_sql;
            $db_results  = Dba::read($sql);
            $data        = Dba::fetch_row($db_results);
            // save the object and add to the current size
            $results[$table] = $data[0];
            $items += $data[0];
            $time += $data[1];
            $size += $data[2];
            // write the total_counts as well
            $this->setCount($table, (int) $data[0]);
        }
        // return the totals for all media tables
        $results['items'] = $items;
        $results['size']  = $size;
        $results['time']  = $time;

        foreach ($song_tables as $table) {
            $sql        = "SELECT COUNT(DISTINCT(`$table`)) FROM `song`";
            $db_results = Dba::read($sql);
            $data       = Dba::fetch_row($db_results);
            // save the object count
            $results[$table] = $data[0];
            // write the total_counts as well
            $this->setCount($table, (int) $data[0]);
        }

        foreach ($list_tables as $table) {
            $data = $this->updateCountByTableName($table);
            // save the object count
            $results[$table] = $data[0];
        }

        return $results;
    }

    /**
     * write the total_counts to update_info
     */
    private function setCount(string $tableName, int $value): void
    {
        Dba::write(
            'REPLACE INTO `update_info` SET `key`= ?, `value`= ?',
            [
                $tableName,
                $value
            ]
        );
    }
}
