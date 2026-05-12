<?php

declare(strict_types=0);

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

namespace Ampache\Module\Playlist\Search;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Search;

final class PodcastSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for podcast searches.
     * @param Search $search
     * @return array{
     *     base: string,
     *     join: array<string, bool>,
     *     where: string[],
     *     where_sql: string,
     *     table: array<string, string>,
     *     table_sql: string,
     *     group_sql: string,
     *     having_sql: string,
     *     parameters: array<int, mixed>,
     * }
     */
    public function getSql(
        Search $search
    ): array {
        $search_user_id     = $search->search_user->getId();
        $sql_logic_operator = strtoupper($search->logic_operator ?? 'and');
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where      = [];
        $table      = [];
        $join       = [];
        $group      = [];
        $having     = [];
        $parameters = [];

        foreach ($search->rules as $rule) {
            $type     = $search->get_rule_type($rule[0]);
            $operator = [];
            if ($type === null) {
                continue;
            }
            foreach ($search->basetypes[$type] as $baseOperator) {
                if ($baseOperator['name'] == $rule[1]) {
                    $operator = $baseOperator;
                    break;
                }
            }
            $input        = $search->filter_data((string)$rule[2], $type, $operator);
            $operator_sql = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`podcast`.`title` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`podcast`.`title` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    break;
                case 'episode_count':
                    $where[]      = "`podcast`.`episodes` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'podcast_episode':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`podcast_episode`.`title` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`podcast_episode`.`title` $operator_sql ?";
                    }
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'time':
                    $input                   = ((int)$input) * 60;
                    $where[]                 = "`podcast_episode`.`time` $operator_sql ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'state':
                    $where[] = "`podcast_episode`.`state` $operator_sql ?";
                    switch ($input) {
                        case 0:
                            $parameters[] = 'skipped';
                            break;
                        case 1:
                            $parameters[] = 'pending';
                            break;
                        case 2:
                            $parameters[] = 'completed';
                    }
                    $join['podcast_episode'] = true;
                    break;
                case 'pubdate':
                    $input                   = strtotime((string) $input);
                    $where[]                 = "`podcast_episode`.`pubdate` $operator_sql ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "IFNULL(`average_rating`.`avg`, 0) $operator_sql ?";
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='podcast' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `podcast`.`id` ";
                    break;
                case 'favorite':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`podcast`.`title` SOUNDS LIKE ? AND `favorite_podcast_" . $search_user_id . "`.`user` = " . $search_user_id . " AND `favorite_podcast_" . $search_user_id . "`.`object_type` = 'podcast')";
                    } else {
                        $where[] = "`podcast`.`title` $operator_sql ? AND `favorite_podcast_" . $search_user_id . "`.`user` = " . $search_user_id . " AND `favorite_podcast_" . $search_user_id . "`.`object_type` = 'podcast'";
                    }
                    $parameters = array_merge($parameters, [$input]);
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_podcast_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = " . $search_user_id . ") AS `favorite_podcast_" . $search_user_id . "` ON `podcast`.`id` = `favorite_podcast_" . $search_user_id . "`.`object_id` AND `favorite_podcast_" . $search_user_id . "`.`object_type` = 'podcast'"
                        : "";
                    break;
                case 'myrating':
                case 'podcastrating':
                case 'podcast_episoderating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my' || $looking == 'podcast') ? '`podcast`.`id`' : '`podcast_episode`.`id`';
                    $my_type = ($looking == 'my' || $looking == 'podcast') ? 'podcast' : $looking;
                    if ($input == 0 && $operator_sql == '>=') {
                        break;
                    }
                    if ($input == 0 && $operator_sql == '<') {
                        $input        = -1;
                        $operator_sql = '<=>';
                    }
                    if ($input == 0 && $operator_sql == '<>') {
                        $input        = 1;
                        $operator_sql = '>=';
                    }
                    $where[]      = "IFNULL(`rating_" . $my_type . "_" . $search_user_id . "`.`rating`, 0) $operator_sql ?";
                    $parameters[] = $input;
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type` = '" . $my_type . "') AS `rating_" . $my_type . "_" . $search_user_id . "` ON `rating_" . $my_type . "_" . $search_user_id . "`.`object_id` = $column"
                        : "";
                    if ($my_type == 'podcast_episode') {
                        $join['podcast_episode'] = true;
                    }
                    break;
                case 'my_flagged_podcast':
                case 'my_flagged_podcast_episode':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('my_flagged_', '', $rule[0]);
                    $column       = 'id';
                    $my_type      = $looking;
                    $operator_sql = ((int) $operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('my_flagged_', $table)) {
                        $table['my_flagged_'] = '';
                    }
                    $table['my_flagged_'] .= (!strpos((string) $table['my_flagged_'], "my_flagged__" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user_flag`.`object_type` = '" . $my_type . "' AND `user_flag`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `my_flagged__" . $my_type . "_" . $search_user_id . "` ON `" . $my_type . "`.`$column` = `my_flagged__" . $my_type . "_" . $search_user_id . "`.`object_id` AND `my_flagged__" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'"
                        : "";
                    $where[] = "`my_flagged__" . $my_type . "_" . $search_user_id . "`.`object_id` $operator_sql";
                    if ($my_type == 'podcast_episode') {
                        $join['podcast_episode'] = true;
                    }
                    break;
                case 'played':
                    $where[]                 = "`podcast_episode`.`played` = '$operator_sql'";
                    $join['podcast_episode'] = true;
                    break;
                case 'last_play':
                    $my_type = 'podcast';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $search_user_id . "` ON `podcast`.`id` = `last_play_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "' "
                        : "";
                    $where[]                 = "`last_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    $join['podcast_episode'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'"
                        : "";
                    $where[]                 = "`last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    $join['podcast_episode'] = true;
                    break;
                case 'days_added':
                    $where[]                 = "`podcast_episode`.`addition_time` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    $join['podcast_episode'] = true;
                    break;
                case 'days_updated':
                    $where[]                 = "`podcast_episode`.`update_time` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    $join['podcast_episode'] = true;
                    break;
                case 'played_times':
                    $where[]      = "(`podcast`.`total_count` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = "(`podcast`.`total_skip` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'id':
                    $where[]      = "(`podcast`.`id` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = "((`podcast`.`total_count` + `podcast`.`total_skip`) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'myplayed_times':
                    $my_type = 'podcast';
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `podcast`.`id` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'"
                        : "";
                    $where[]      = "`myplayed_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'myskipped_times':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('myskipped', $table)) {
                        $table['myskipped'] = '';
                    }
                    $table['myskipped'] .= (!strpos((string) $table['myskipped'], "myskipped_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myskipped_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "' "
                        : "";
                    $where[]                 = "`myskipped_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'myplayed_or_skipped_times':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('myplayed_or_skip', $table)) {
                        $table['myplayed_or_skip'] = '';
                    }
                    $table['myplayed_or_skip'] .= (!strpos((string) $table['myplayed_or_skip'], "myplayed_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'"
                        : "";
                    $where[]                 = "`myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'play_skip_ratio':
                    $where[]      = "(((`podcast`.`total_count`/`podcast`.`total_skip`) * 100) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($operator_sql == 'userflag') {
                        $where[] = "`favorite_podcast_$other_userid`.`user` = $other_userid AND `favorite_podcast_$other_userid`.`object_type` = 'podcast'";
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_podcast_$other_userid"))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_podcast_$other_userid` ON `podcast`.`id` = `favorite_podcast_$other_userid`.`object_id` AND `favorite_podcast_$other_userid`.`object_type` = 'podcast'"
                            : "";
                    } else {
                        $column  = 'id';
                        $my_type = 'podcast';
                        $where[] = "`rating_podcast_" . $other_userid . '`.' . $operator_sql . " AND `rating_podcast_$other_userid`.`user` = $other_userid AND `rating_podcast_$other_userid`.`object_type` = 'podcast'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $search_user_id))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $search_user_id . "` ON `rating_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "' AND `rating_" . $my_type . "_" . $search_user_id . "`.`object_id` = `$my_type`.`$column` AND `rating_" . $my_type . "_" . $search_user_id . "`.`user` = " . $search_user_id
                            : "";
                    }
                    break;
                case 'myplayed':
                    $my_type      = 'podcast';
                    $operator_sql = ((int) $operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `podcast`.`id` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` $operator_sql";
                    break;
                case 'added':
                    $input                   = strtotime((string) $input);
                    $where[]                 = "`podcast_episode`.`addition_time` $operator_sql ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'updated':
                    $input                   = strtotime((string) $input);
                    $where[]                 = "`podcast_episode`.`update_time` $operator_sql ?";
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                case 'file':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`podcast_episode`.`file` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`podcast_episode`.`file` $operator_sql ?";
                    }
                    $parameters[]            = $input;
                    $join['podcast_episode'] = true;
                    break;
                default:
                    debug_event(self::class, 'ERROR! rule not found: ' . $rule[0], 3);
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if (array_key_exists('podcast_episode', $join)) {
            $table['0_podcast'] = "LEFT JOIN `podcast_episode` ON `podcast_episode`.`podcast` = `podcast`.`id`";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `podcast`.`catalog`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1'";
                }
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = ($search_user_id > 0)
                    ? "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)"
                    : "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = ($search_user_id > 0)
                    ? "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)"
                    : "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return [
            'base' => 'SELECT DISTINCT(`podcast`.`id`), `podcast`.`title` FROM `podcast`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters,
        ];
    }
}
