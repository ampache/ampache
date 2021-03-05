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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Playlist\SearchType;

use Ampache\Repository\Model\Search;

final class UserSearchType extends AbstractSearchType
{
    /**
     * Handles the generation of the SQL for user searches.
     * @return array
     */
    public function getSql(
        Search $search
    ): array {
        $sql_logic_operator = $search->logic_operator;
        $where              = array();
        $table              = array();
        $join               = array();

        foreach ($search->rules as $rule) {
            $type     = $search->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($search->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->mangleData($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'username':
                    $where[] = "`user`.`username` $sql_match_operator '$input'";
                    break;
                default:
                    break;
            } // switch on ruletype
        } // foreach rule

        $where_sql = implode(" $sql_logic_operator ", $where);
        ksort($table);

        return array(
            'base' => 'SELECT DISTINCT(`user`.`id`), `user`.`username` FROM `user`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => '',
            'group_sql' => '',
            'having_sql' => ''
        );
    }
}
