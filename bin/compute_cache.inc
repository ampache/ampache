<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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
 *
 */

define('NO_SESSION', '1');
define('CLI', 1);
define('NO_CRON_CACHE', '1');
$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

// Turn off output buffering we don't need it for a command line script
ob_end_clean();

debug_event('compute_cache', 'started cache process', 5);

$thresholds   = [ 0, AmpConfig::get('stats_threshold'), AmpConfig::get('popular_threshold', 10) ];
$count_types  = [ 'stream', 'download', 'skip' ];
// TODO fix playlist sql.
$object_types = [ 'album', 'artist', 'song', 'genre', 'catalog', 'live_stream', 'video', 'podcast_episode' ];

foreach ($thresholds as $threshold) {
    foreach ($count_types as $count_type) {
        foreach ($object_types as $object_type) {
            $sql = "INSERT INTO `cache_object_count_run` ( `object_id`, `count`, `object_type`, `count_type`, `threshold` ) ";
            $sql .= Stats::get_top_sql($object_type, $threshold, $count_type);
            $sql .= " ON DUPLICATE KEY UPDATE `count` = VALUES ( `count` )";
            Dba::write($sql);
        }
    }
}

$sql = "RENAME TABLE `cache_object_count_run` TO `cache_object_count_tmp`, `cache_object_count` TO `cache_object_count_run`, `cache_object_count_tmp` TO `cache_object_count`";
Dba::write($sql);
$sql = "TRUNCATE `cache_object_count_run`";
Dba::write($sql);

debug_event('compute_cache', 'Completed cache process', 5);
