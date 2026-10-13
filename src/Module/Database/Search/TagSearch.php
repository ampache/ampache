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

use Ampache\Module\Database\Query\Search;

final class TagSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for tag (genre) searches.
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
        $table      = [];
        $join       = [];
        $parameters = [];

        $combined = RuleGroupCombiner::combine(
            $search->rules,
            $search->logic_operator ?? 'and',
            function (array $rules) use ($search, &$parameters): array {
                return $this->evaluateRules($rules, $search, $parameters);
            },
        );

        return [
            'base' => 'SELECT DISTINCT(`tag`.`id`), `tag`.`name` FROM `tag`',
            'join' => $join,
            'where' => $combined['where'],
            'where_sql' => $combined['where_sql'],
            'table' => $table,
            'table_sql' => '',
            'group_sql' => '',
            'having_sql' => $combined['having_sql'],
            'parameters' => $parameters,
        ];
    }

    /**
     * Evaluates one group's worth of plain rule tuples (or the whole rule list, ungrouped).
     * @param list<array<int, mixed>> $rules
     * @param array<int, mixed> $parameters
     * @return array{where: string[], having: string[]}
     */
    private function evaluateRules(array $rules, Search $search, array &$parameters): array
    {
        $where = [];

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
                case 'category':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`tag`.`category` SOUNDS LIKE ?)";
                    } else {
                        $where[] = sprintf('`tag`.`category` %s ?', $operator_sql);
                    }

                    $parameters[] = $input;
                    break;
                case 'title':
                    $where[] = $operator_sql === 'NOT SOUNDS LIKE' ? "NOT (`tag`.`name` SOUNDS LIKE ?)" : sprintf('`tag`.`name` %s ?', $operator_sql);

                    $parameters[] = $input;
                    break;
                case 'artist_count':
                    $where[]      = sprintf('`tag`.`artist` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'album_count':
                    $where[]      = sprintf('`tag`.`album` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'song_count':
                    $where[]      = sprintf('`tag`.`song` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'video_count':
                    $where[]      = sprintf('`tag`.`video` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                default:
                    debug_event(self::class, 'ERROR! rule not found: ' . $rule[0], 3);
                    break;
            } // switch on ruletype
        } // foreach rule

        return ['where' => $where, 'having' => []];
    }
}
