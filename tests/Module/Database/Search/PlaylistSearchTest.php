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
use Ampache\MockeryTestCase;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;

class PlaylistSearchTest extends MockeryTestCase
{
    private const VISIBILITY = "(`playlist`.`type` = 'public' OR `playlist`.`user` = 2 OR FIND_IN_SET(2, `playlist`.`collaborate`) > 0)";

    /**
     * `?` placeholder order must match parameter order across two groups, not rule-definition order
     */
    public function testParameterOrderMatchesAcrossGroups(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['title', 'contain', 'Workout'],
                    ['title', 'contain', 'Running'],
                ],
            ],
            ['id', 'gt', 5],
        ]);

        $this->assertSame(['%Workout%', '%Running%', 5], $result['parameters']);
    }

    /**
     * an OR'd group combined with an ungrouped rule parenthesizes only the group, and joins the two
     * with the search's own top-level operator
     */
    public function testTwoGroupsCombineWithParensAndOuterOperator(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['title', 'contain', 'Workout'],
                    ['title', 'contain', 'Running'],
                ],
            ],
            ['id', 'gt', 5],
        ]);

        $this->assertSame(
            "(" . self::VISIBILITY . " OR `playlist`.`name` LIKE ? OR " . self::VISIBILITY . " OR `playlist`.`name` LIKE ?)"
            . " AND " . self::VISIBILITY . " AND `playlist`.`id` > ?",
            $result['where_sql']
        );
    }

    /**
     * an ungrouped rule must never get wrapped in parens, even though it internally produces more
     * than one raw `where` fragment (its own visibility clause alongside it)
     */
    public function testUngroupedRulesProduceAFlatImplode(): void
    {
        $result = $this->ruleSql([
            ['title', 'contain', 'Workout'],
            ['id', 'gt', 5],
        ]);

        $this->assertSame(
            self::VISIBILITY . " AND `playlist`.`name` LIKE ? AND " . self::VISIBILITY . " AND `playlist`.`id` > ?",
            $result['where_sql']
        );
        $this->assertSame(['%Workout%', 5], $result['parameters']);
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{where_sql: string, parameters: array<int, mixed>}
     */
    private function ruleSql(array $rules, string $logicOperator = 'and'): array
    {
        AmpConfig::set('catalog_disable', false, true);
        AmpConfig::set('catalog_filter', false, true);

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        $search                 = new Search(0, 'playlist', $user);
        $search->search_user    = $user;
        $search->logic_operator = $logicOperator;
        $search->rules          = $rules;

        $result = new PlaylistSearch()->getSql($search);

        return [
            'where_sql' => $result['where_sql'],
            'parameters' => $result['parameters'],
        ];
    }
}
