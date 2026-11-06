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

namespace Ampache\Module\Database\Search;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\Query\Search;

final class FolderSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for folder searches.
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
        Search $search,
    ): array {
        $search_user_id  = $search->search_user->getId();
        $catalog_disable = AmpConfig::get('catalog_disable');
        $catalog_filter  = AmpConfig::get('catalog_filter');

        $table      = [];
        $join       = [];
        $parameters = [];

        $combined = RuleGroupCombiner::combine(
            $search->rules,
            $search->logic_operator ?? 'and',
            function (array $rules) use ($search, &$join, &$table, &$parameters): array {
                return $this->evaluateRules($rules, $search, $join, $table, $parameters);
            },
        );

        $where      = $combined['where'];
        $where_sql  = $combined['where_sql'];
        $having_sql = $combined['having_sql'];

        if (array_key_exists('parent', $join)) {
            $table['0_parent'] = "LEFT JOIN `folder` AS `folder_parent` ON `folder_parent`.`id` = `folder`.`parent`";
        }

        $join['catalog'] = $catalog_disable || $catalog_filter;

        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `folder`.`catalog`";
            if ($catalog_disable) {
                if ($where_sql !== '' && $where_sql !== '0') {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1'";
                }
            }
        }

        if ($catalog_filter) {
            $catalog_filter_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)";
            if ($where_sql !== '' && $where_sql !== '0') {
                $where_sql = "(" . $where_sql . ") AND " . $catalog_filter_sql;
            } else {
                $where_sql = $catalog_filter_sql;
            }
        }

        ksort($table);
        $table_sql = implode(' ', $table);

        return [
            'base' => 'SELECT DISTINCT(`folder`.`id`), `folder`.`name` FROM `folder`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => '',
            'having_sql' => $having_sql,
            'parameters' => $parameters,
        ];
    }

    /**
     * Evaluates one group's worth of plain rule tuples (or the whole rule list, ungrouped).
     *
     * `$join` and `$table` are shared across every group call, not reset per group, so a
     * once-per-user join (e.g. `myrating`/`my_flagged_folder`) is never duplicated across group boundaries.
     * @param list<array<int, mixed>> $rules
     * @param array<string, bool> $join
     * @param array<string, string> $table
     * @param array<int, mixed> $parameters
     * @return array{where: string[], having: string[]}
     */
    private function evaluateRules(
        array $rules,
        Search $search,
        array &$join,
        array &$table,
        array &$parameters,
    ): array {
        $search_user_id = $search->search_user->getId();
        $where          = [];

        foreach ($rules as $rule) {
            $type     = $search->get_rule_type_by_name($rule[0]);
            $operator = [];
            if ($type === null) {
                continue;
            }

            foreach ($search->get_basetypes()[$type] as $baseOperator) {
                if ($baseOperator['name'] == $rule[1]) {
                    $operator = $baseOperator;
                    break;
                }
            }

            $input        = $search->filter_data((string) $rule[2], $type, $operator);
            $operator_sql = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`folder`.`name` SOUNDS LIKE ?)";
                    } else {
                        $where[] = sprintf('`folder`.`name` %s ?', $operator_sql);
                    }

                    $parameters[] = $input;
                    break;
                case 'path':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`folder`.`path_name` SOUNDS LIKE ?)";
                    } else {
                        $where[] = sprintf('`folder`.`path_name` %s ?', $operator_sql);
                    }

                    $parameters[] = $input;
                    break;
                case 'parent_name':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`folder_parent`.`name` SOUNDS LIKE ?)";
                    } else {
                        $where[] = sprintf('`folder_parent`.`name` %s ?', $operator_sql);
                    }

                    $parameters[]   = $input;
                    $join['parent'] = true;
                    break;
                case 'parent_path':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`folder_parent`.`path_name` SOUNDS LIKE ?)";
                    } else {
                        $where[] = sprintf('`folder_parent`.`path_name` %s ?', $operator_sql);
                    }

                    $parameters[]   = $input;
                    $join['parent'] = true;
                    break;
                case 'catalog':
                case 'id':
                case 'object_count':
                    $where[]      = sprintf('`folder`.`%s` %s ?', $rule[0], $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'parent_id':
                    $where[]      = sprintf('`folder`.`parent` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'time':
                    $input        = ((int) $input) * 60;
                    $where[]      = sprintf('`folder`.`time` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'playable':
                    $where[] = ($operator_sql == '1') ? "`folder`.`playable` = 1" : "`folder`.`playable` = 0";
                    break;
                case 'played_times':
                    $where[]      = sprintf('`folder`.`total_count` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = sprintf('`folder`.`total_skip` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = sprintf('((`folder`.`total_count` + `folder`.`total_skip`) %s ?)', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'added':
                    $input        = strtotime((string) $input);
                    $where[]      = sprintf('`folder`.`addition_time` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'updated':
                    $input        = strtotime((string) $input);
                    $where[]      = sprintf('`folder`.`update_time` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'days_added':
                    $cutoff  = "(UNIX_TIMESTAMP() - (" . (int) $input . " * 86400))";
                    $where[] = match ($operator_sql) {
                        '!<' => "(`folder`.`addition_time` >= $cutoff)",
                        '!>' => "(`folder`.`addition_time` <= $cutoff)",
                        default => "`folder`.`addition_time` $operator_sql $cutoff",
                    };
                    break;
                case 'days_updated':
                    $cutoff  = "(UNIX_TIMESTAMP() - (" . (int) $input . " * 86400))";
                    $where[] = match ($operator_sql) {
                        '!<' => "(`folder`.`update_time` >= $cutoff)",
                        '!>' => "(`folder`.`update_time` <= $cutoff)",
                        default => "`folder`.`update_time` $operator_sql $cutoff",
                    };
                    break;
                case 'recent_added':
                    $key                         = md5($input . $operator_sql);
                    $where[]                     = sprintf('`addition_time_%s`.`id` IS NOT NULL', $key);
                    $table['2_addition_' . $key] = sprintf('LEFT JOIN (SELECT `id` FROM `folder` ORDER BY %s DESC LIMIT ', $operator_sql) . (int) $input . sprintf(') AS `addition_time_%s` ON `folder`.`id` = `addition_time_%s`.`id`', $key, $key);
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = sprintf('IFNULL(`average_rating`.`avg`, 0) %s ?', $operator_sql);
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='folder' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `folder`.`id`";
                    break;
                case 'myrating':
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

                    $where[]      = "IFNULL(`rating_folder_" . $search_user_id . sprintf('`.`rating`, 0) %s ?', $operator_sql);
                    $parameters[] = $input;
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }

                    $table['rating'] .= (strpos($table['rating'], "rating_folder_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type` = 'folder') AS `rating_folder_" . $search_user_id . "` ON `rating_folder_" . $search_user_id . "`.`object_id` = `folder`.`id`";
                    break;
                case 'my_flagged_folder':
                    $operator_sql = ((int) $operator_sql === 0) ? 'IS NULL' : 'IS NOT NULL';
                    // flagged once per user
                    if (!array_key_exists('my_flagged_', $table)) {
                        $table['my_flagged_'] = '';
                    }

                    $table['my_flagged_'] .= (strpos($table['my_flagged_'], "my_flagged__folder_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user_flag`.`object_type` = 'folder' AND `user_flag`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `my_flagged__folder_" . $search_user_id . "` ON `folder`.`id` = `my_flagged__folder_" . $search_user_id . "`.`object_id` AND `my_flagged__folder_" . $search_user_id . "`.`object_type` = 'folder'";
                    $where[] = "`my_flagged__folder_" . $search_user_id . ('`.`object_id` ' . $operator_sql);
                    break;
                case 'other_user':
                    $other_userid = (int) $input;
                    if ($operator_sql == 'userflag') {
                        $where[] = sprintf("`favorite_folder_%s`.`user` = %s AND `favorite_folder_%s`.`object_type` = 'folder'", $other_userid, $other_userid, $other_userid);
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }

                        $table['favorite'] .= (strpos($table['favorite'], 'favorite_folder_' . $other_userid))
                            ? ""
                            : sprintf("LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = %s) AS `favorite_folder_%s` ON `folder`.`id` = `favorite_folder_%s`.`object_id` AND `favorite_folder_%s`.`object_type` = 'folder'", $other_userid, $other_userid, $other_userid, $other_userid);
                    } else {
                        $unrated = ($operator_sql == 'unrated');
                        $where[] = ($unrated)
                            ? "`folder`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'folder' AND `rating`.`user` = " . $other_userid . ")"
                            : "`rating_folder_" . $other_userid . sprintf('`.%s AND `rating_folder_', $operator_sql) . $other_userid . "`.`user` = " . $other_userid . " AND `rating_folder_" . $other_userid . "`.`object_type` = 'folder'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }

                        $table['rating'] .= (strpos($table['rating'], "rating_folder_" . $other_userid))
                            ? ""
                            : "LEFT JOIN `rating` AS `rating_folder_" . $other_userid . "` ON `rating_folder_" . $other_userid . "`.`object_type` = 'folder' AND `rating_folder_" . $other_userid . "`.`object_id` = `folder`.`id` AND `rating_folder_" . $other_userid . "`.`user` = " . $other_userid;
                    }

                    break;
                default:
                    debug_event(self::class, 'ERROR! rule not found: ' . $rule[0], 3);
                    break;
            } // switch on ruletype
        } // foreach rule

        return ['where' => $where, 'having' => []];
    }
}
