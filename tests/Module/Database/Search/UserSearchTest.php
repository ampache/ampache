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

use Ampache\MockeryTestCase;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;

class UserSearchTest extends MockeryTestCase
{
    /**
     * `?` placeholder order must match parameter order across two groups, not rule-definition order
     */
    public function testParameterOrderMatchesAcrossGroups(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['username', 'contain', 'admin'],
                    ['email', 'contain', 'example.com'],
                ],
            ],
            ['fullname', 'equal', 'Jane Doe'],
        ]);

        $this->assertSame(['%admin%', '%example.com%', 'Jane Doe'], $result['parameters']);
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
                    ['username', 'contain', 'admin'],
                    ['email', 'contain', 'example.com'],
                ],
            ],
            ['fullname', 'equal', 'Jane Doe'],
        ]);

        $this->assertSame(
            "(`user`.`username` LIKE ? OR COALESCE(`user`.`email`, '') LIKE ?) AND COALESCE(`user`.`fullname`, '') = ?",
            $result['where_sql']
        );
    }

    /**
     * a rule list with no group wrapper at all must still produce today's single flat implode,
     * with no parentheses added anywhere
     */
    public function testUngroupedRulesProduceAFlatImplode(): void
    {
        $result = $this->ruleSql([
            ['username', 'contain', 'admin'],
            ['fullname', 'equal', 'Jane Doe'],
        ]);

        $this->assertSame(
            "`user`.`username` LIKE ? AND COALESCE(`user`.`fullname`, '') = ?",
            $result['where_sql']
        );
        $this->assertSame(['%admin%', 'Jane Doe'], $result['parameters']);
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{where_sql: string, parameters: array<int, mixed>}
     */
    private function ruleSql(array $rules, string $logicOperator = 'and'): array
    {
        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        $search                 = new Search(0, 'user', $user);
        $search->search_user    = $user;
        $search->logic_operator = $logicOperator;
        $search->rules          = $rules;

        $result = new UserSearch()->getSql($search);

        return [
            'where_sql' => $result['where_sql'],
            'parameters' => $result['parameters'],
        ];
    }
}
