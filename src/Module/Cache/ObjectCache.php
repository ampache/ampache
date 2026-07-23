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

namespace Ampache\Module\Cache;

use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;

final class ObjectCache implements ObjectCacheInterface
{
    public function compute(): void
    {
        $count_types = [
            'stream',
            'download',
            'skip',
        ];
        $thresholds = [0, 7, 10];
        $sql        = "SELECT DISTINCT(`user_preference`.`value`) FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` WHERE `preference`.`name` IN ('stats_threshold', 'popular_threshold')";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            // get individual user thresholds if not the default
            $thresholds[] = (int) $row['value'];
        }

        $object_types = [
            'album',
            'album_disk',
            'artist',
            'catalog',
            'folder',
            'genre',
            'live_stream',
            'playlist',
            'podcast_episode',
            'podcast',
            'song',
            'video',
        ];

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

        // Merge consolidated play history into the all-time (threshold 0) counts, mirroring the aggregation shapes of
        // Stats::get_top_sql: plain types count their own rows, podcast and album_disk roll up episode / song plays.
        $summary_types = [
            'album',
            'artist',
            'live_stream',
            'playlist',
            'podcast_episode',
            'song',
            'tag',
            'video',
        ];
        foreach ($count_types as $count_type) {
            foreach ($summary_types as $object_type) {
                $sql = "INSERT INTO `cache_object_count_run` (`object_id`, `count`, `object_type`, `count_type`, `threshold`) SELECT `object_id`, SUM(`count`), `object_type`, `count_type`, 0 FROM `object_count_summary` WHERE `object_type` = '" . $object_type . "' AND `count_type` = '" . $count_type . "' GROUP BY `object_id`, `object_type`, `count_type` ON DUPLICATE KEY UPDATE `count` = `cache_object_count_run`.`count` + VALUES(`count`);";
                Dba::write($sql);
            }
            $sql = "INSERT INTO `cache_object_count_run` (`object_id`, `count`, `object_type`, `count_type`, `threshold`) SELECT `album_disk`.`id`, SUM(`object_count_summary`.`count`), 'album_disk', `object_count_summary`.`count_type`, 0 FROM `object_count_summary` LEFT JOIN `song` ON `song`.`id` = `object_count_summary`.`object_id` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `object_count_summary`.`object_type` = 'song' AND `object_count_summary`.`count_type` = '" . $count_type . "' AND `album_disk`.`id` IS NOT NULL GROUP BY `album_disk`.`id`, `object_count_summary`.`count_type` ON DUPLICATE KEY UPDATE `count` = `cache_object_count_run`.`count` + VALUES(`count`);";
            Dba::write($sql);
            $sql = "INSERT INTO `cache_object_count_run` (`object_id`, `count`, `object_type`, `count_type`, `threshold`) SELECT `podcast_episode`.`podcast`, SUM(`object_count_summary`.`count`), 'podcast', `object_count_summary`.`count_type`, 0 FROM `object_count_summary` LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `object_count_summary`.`object_id` WHERE `object_count_summary`.`object_type` = 'podcast_episode' AND `object_count_summary`.`count_type` = '" . $count_type . "' AND `podcast_episode`.`podcast` IS NOT NULL GROUP BY `podcast_episode`.`podcast`, `object_count_summary`.`count_type` ON DUPLICATE KEY UPDATE `count` = `cache_object_count_run`.`count` + VALUES(`count`);";
            Dba::write($sql);
        }

        $sql = "RENAME TABLE `cache_object_count_run` TO `cache_object_count_tmp`, `cache_object_count` TO `cache_object_count_run`, `cache_object_count_tmp` TO `cache_object_count`";
        Dba::write($sql);
        $sql = "TRUNCATE `cache_object_count_run`";
        Dba::write($sql);

        // record when the cache was generated so live counters can add only the
        // plays that arrived since, keeping cron_cache fast but always accurate
        Catalog::set_update_info('cache_object_count', time());

        debug_event('compute_cache', 'Completed cache process', 5);
    }
}
