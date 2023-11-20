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

namespace Ampache\Module\Playlist\Search;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Search;

final class LabelSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for label searches.
     * @param Search $search
     * @return array
     */
    public function getSql(
        Search $search
    ): array {
        $search_user_id     = $search->search_user->id ?? -1;
        $sql_logic_operator = $search->logic_operator;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where      = array();
        $table      = array();
        $join       = array();
        $parameters = array();

        foreach ($search->rules as $rule) {
            $type     = $search->get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($search->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input        = $search->filter_data($rule[2], $type, $operator);
            $operator_sql = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'title':
                    $where[]      = "`label`.`name` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'category':
                    $where[]      = "`label`.`category` $operator_sql ?";
                    $parameters[] = $input;
                    break;
            } // switch on ruletype
        } // foreach rule

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        if ($catalog_disable || $catalog_filter) {
            $table['0_label_asso']  = "LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id`";
            $table['1_artist']      = "LEFT JOIN `artist` ON `label_asso`.`artist` = `artist`.`id`";
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_artist` ON `catalog_map_artist`.`object_id` = `artist`.`id` AND `catalog_map_artist`.`object_type` = 'artist'";
        }

        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_map_artist`.`object_type` = 'artist' AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_map_artist`.`object_type` = 'artist' AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        if ($join['catalog']) {
            $table['3_catalog'] = "LEFT JOIN `catalog`AS `catalog_se` ON `catalog_map_artist`.`catalog_id` = `catalog_se`.`id`";
            if ($catalog_disable) {
                if (!empty($where_sql)) {
                    $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
                } else {
                    $where_sql = "`catalog_se`.`enabled` = '1'";
                }
            }
        }
        $table_sql = implode(' ', $table);

        return array(
            'base' => 'SELECT DISTINCT(`label`.`id`), `label`.`name` FROM `label`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => '',
            'having_sql' => '',
            'parameters' => $parameters
        );
    }
}
