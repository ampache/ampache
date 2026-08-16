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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Clean up after the periodic count sweep, which counts are now maintained without.
 */
final class Migration800038 extends AbstractMigration
{
    protected array $changelog = [
        'Add the missing `podcast` rows to `object_count` for episode plays that predate podcast play counting',
        'Remove the `update_counts` row from `update_info`, which timed a sweep that no longer exists',
    ];

    public function migrate(): void
    {
        // a hundred at a time, the shape the periodic sweep this replaces used
        while (true) {
            $db_results = Dba::read("SELECT `podcast_episode`.`podcast`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `podcast_episode` ON `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `podcast_episode`.`id` LEFT JOIN `object_count` AS `podcast_count` ON `podcast_count`.`object_type` = 'podcast' AND `object_count`.`date` = `podcast_count`.`date` AND `object_count`.`user` = `podcast_count`.`user` AND `object_count`.`agent` <=> `podcast_count`.`agent` AND `object_count`.`count_type` = `podcast_count`.`count_type` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `podcast_count`.`id` IS NULL LIMIT 100;");
            if (!$db_results instanceof \PDOStatement) {
                break;
            }

            $rows = 0;
            while ($row = Dba::fetch_assoc($db_results)) {
                $rows++;
                $this->updateDatabase(
                    "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    ['podcast', $row['podcast'], $row['count_type'], $row['date'], $row['user'], $row['agent'], $row['geo_latitude'], $row['geo_longitude'], $row['geo_name']]
                );
            }

            if ($rows === 0) {
                break;
            }
        }

        $this->updateDatabase("DELETE FROM `update_info` WHERE `key` = 'update_counts';");
    }
}
