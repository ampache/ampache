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

use Closure;

/**
 * Combines a search's top-level `rules` -- plain tuples mixed with 2-level group wrappers -- into
 * where/having fragments; an ungrouped rule list degrades to today's single flat implode.
 */
final class RuleGroupCombiner
{
    /**
     * @param list<array<int, mixed>|array{group_id?: int, group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @param Closure(list<array<int, mixed>>): array{where: string[], having: string[]} $evaluator
     * @return array{where: string[], where_sql: string, having: string[], having_sql: string}
     */
    public static function combine(array $rules, string $logicOperator, Closure $evaluator): array
    {
        $allWhere        = [];
        $allHaving       = [];
        $whereFragments  = [];
        $havingFragments = [];

        foreach ($rules as $rule) {
            $isGroup       = array_key_exists('rules', $rule);
            $groupRules    = $isGroup ? $rule['rules'] : [$rule];
            $groupOperator = $isGroup ? strtoupper((string) ($rule['group_operator'] ?? 'and')) : 'AND';

            $result = $evaluator($groupRules);

            $allWhere  = [...$allWhere, ...$result['where']];
            $allHaving = [...$allHaving, ...$result['having']];

            $whereFragment = self::combineFragment($result['where'], $groupOperator, $isGroup);
            if ($whereFragment !== '') {
                $whereFragments[] = $whereFragment;
            }

            $havingFragment = self::combineFragment($result['having'], $groupOperator, $isGroup);
            if ($havingFragment !== '') {
                $havingFragments[] = $havingFragment;
            }
        }

        $outerOperator = strtoupper($logicOperator);

        return [
            'where' => $allWhere,
            'where_sql' => implode(sprintf(' %s ', $outerOperator), $whereFragments),
            'having' => $allHaving,
            'having_sql' => implode(sprintf(' %s ', $outerOperator), $havingFragments),
        ];
    }

    /**
     * Parenthesizes only an explicit group, never by fragment count -- a class like `PlaylistSearch`
     * emits more than one fragment per ungrouped rule, and that must stay unwrapped.
     * @param string[] $fragments
     */
    private static function combineFragment(array $fragments, string $operator, bool $isGroup): string
    {
        $combined = implode(sprintf(' %s ', $operator), $fragments);

        return ($isGroup && count($fragments) > 1) ? '(' . $combined . ')' : $combined;
    }
}
