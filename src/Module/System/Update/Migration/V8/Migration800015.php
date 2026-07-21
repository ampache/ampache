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

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `object_count_archive` to keep the detail rows removed by stats consolidation
 */
final class Migration800015 extends AbstractMigration
{
    protected array $changelog = ['Add `object_count_archive` table to retain consolidated play history detail'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        // no surrogate key and a single lookup index: the archive is cold storage, the index overhead is the
        // whole point of moving rows out of `object_count`
        $this->updateDatabase(
            sprintf(
                "CREATE TABLE IF NOT EXISTS `object_count_archive` (`object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') NOT NULL, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `date` int(11) UNSIGNED NOT NULL DEFAULT 0, `user` int(11) NOT NULL, `agent` varchar(255) DEFAULT NULL, `geo_latitude` decimal(10,6) DEFAULT NULL, `geo_longitude` decimal(10,6) DEFAULT NULL, `geo_name` varchar(255) DEFAULT NULL, `count_type` enum('download','stream','skip') NOT NULL, KEY `object_count_archive_IDX` (`object_type`,`object_id`)) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;",
                $engine,
                $charset,
                $collation
            )
        );

        // compression is a bonus, not a requirement (it needs innodb_file_per_table)
        if ($engine === 'InnoDB') {
            Dba::write("ALTER TABLE `object_count_archive` ROW_FORMAT=COMPRESSED;", [], true);
        }
    }
}
