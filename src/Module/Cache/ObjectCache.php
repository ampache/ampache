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

declare(strict_types=0);

namespace Ampache\Module\Cache;

use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;

final class ObjectCache implements ObjectCacheInterface
{
    public function compute(): void
    {
        $count_types = ['stream', 'download', 'skip'];
        $thresholds  = [0, 7, 10];
        $sql         = "SELECT DISTINCT(`user_preference`.`value`) FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` WHERE `preference`.`name` IN ('stats_threshold', 'popular_threshold')";
        $db_results  = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            // get individual user thresholds if not the default
            $thresholds[] = (int)$row['value'];
        }
        $object_types = ['album', 'artist', 'song', 'genre', 'catalog', 'live_stream', 'video', 'podcast', 'podcast_episode', 'playlist'];

        foreach ($thresholds as $threshold) {
            foreach ($count_types as $count_type) {
                foreach ($object_types as $object_type) {
                    $sql = "INSERT INTO `cache_object_count_run` (`object_id`, `count`, `object_type`, `count_type`, `threshold`) ";
                    $sql .= Stats::get_top_sql($object_type, $threshold, $count_type, null, false, 0, 0, true);
                    $sql .= " ON DUPLICATE KEY UPDATE `count` = VALUES (`count`)";
                    Dba::write($sql);
                }
            }
        }

        $sql = "RENAME TABLE `cache_object_count_run` TO `cache_object_count_tmp`, `cache_object_count` TO `cache_object_count_run`, `cache_object_count_tmp` TO `cache_object_count`";
        Dba::write($sql);
        $sql = "TRUNCATE `cache_object_count_run`";
        Dba::write($sql);

        debug_event('compute_cache', 'Completed cache process', 5);
    }
}
