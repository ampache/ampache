<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

namespace Ampache\Module\Playlist\Search;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Search;

final class PodcastEpisodeSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for podcast_episode searches.
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
        $sql_logic_operator = $search->logic_operator;
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
                        $where[] = "NOT (`podcast_episode`.`title` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`podcast_episode`.`title` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    break;
                case 'podcast':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`podcast`.`title` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`podcast`.`title` $operator_sql ?";
                    }
                    $parameters[]    = $input;
                    $join['podcast'] = true;
                    break;
                case 'time':
                    $input        = ((int)$input) * 60;
                    $where[]      = "`podcast_episode`.`time` $operator_sql ?";
                    $parameters[] = $input;
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
                    break;
                case 'pubdate':
                    $input        = strtotime((string) $input);
                    $where[]      = "`podcast_episode`.`pubdate` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'played':
                    $where[] = "`podcast_episode`.`played` = '$operator_sql'";
                    break;
                case 'last_play':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `last_play_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type' "
                        : "";
                    $where[] = "`last_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    break;
                case 'last_play_or_skip':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (" . (int)$input . " * 86400))";
                    break;
                case 'played_times':
                    $where[]      = "(`podcast_episode`.`total_count` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = "(`podcast_episode`.`total_skip` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = "((`podcast_episode`.`total_count` + `podcast_episode`.`total_skip`) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'myplayed_times':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
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
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myskipped_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type' "
                        : "";
                    $where[]      = "`myskipped_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'myplayed_or_skipped_times':
                    $my_type = 'podcast_episode';
                    if (!array_key_exists('myplayed_or_skip', $table)) {
                        $table['myplayed_or_skip'] = '';
                    }
                    $table['myplayed_or_skip'] .= (!strpos((string) $table['myplayed_or_skip'], "myplayed_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[]      = "`myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'play_skip_ratio':
                    $where[]      = "(((`podcast_episode`.`total_count`/`podcast_episode`.`total_skip`) * 100) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'myplayed':
                    $my_type      = 'podcast_episode';
                    $operator_sql = ((int) $operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `podcast_episode`.`id` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` $operator_sql";
                    break;
                case 'added':
                    $input        = strtotime((string) $input);
                    $where[]      = "`podcast_episode`.`addition_time` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'id':
                    $where[]      = "`podcast_episode`.`id` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'file':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`podcast_episode`.`file` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`podcast_episode`.`file` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    break;
                default:
                    debug_event(self::class, 'ERROR! rule not found: ' . $rule[0], 3);
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if (array_key_exists('podcast', $join)) {
            $table['0_podcast'] = "LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast`";
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `podcast_episode`.`catalog`";
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
            'base' => 'SELECT DISTINCT(`podcast_episode`.`id`), `podcast_episode`.`pubdate` FROM `podcast_episode`',
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
