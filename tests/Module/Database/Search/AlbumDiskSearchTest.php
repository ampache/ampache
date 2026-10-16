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
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerInterface;

class AlbumDiskSearchTest extends MockeryTestCase
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
                    ['id', 'gt', 5],
                    ['id', 'gt', 9],
                ],
            ],
            ['year', 'gt', 2000],
        ]);

        $this->assertSame([5, 9, 2000], $result['parameters']);
    }

    /**
     * `$subsearch_count` is shared across every group call, so two `smartplaylist` rules split
     * across two groups mint distinct derived-table aliases instead of both reusing `sp_1`
     */
    public function testSmartplaylistAliasesStayUniqueAcrossGroups(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [['smartplaylist', 'equal', 0]],
            ],
            [
                'group_operator' => 'or',
                'rules' => [['smartplaylist', 'equal', 0]],
            ],
        ]);

        $this->assertStringContainsString('AS sp_1)', $result['where_sql']);
        $this->assertStringContainsString('AS sp_2)', $result['where_sql']);
    }

    /**
     * an OR'd group combined with an ungrouped rule parenthesizes only the group
     */
    public function testTwoGroupsCombineWithParensAndOuterOperator(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['id', 'gt', 5],
                    ['id', 'gt', 9],
                ],
            ],
            ['year', 'gt', 2000],
        ]);

        $this->assertSame(
            '((`album`.`id` > ? OR `album`.`id` > ?) AND `album`.`year` > ?) AND EXISTS (SELECT 1 FROM `album` AS `album_wd` WHERE `album_wd`.`id` = `album_disk`.`album_id` AND `album_wd`.`enabled` = 1)',
            $result['where_sql']
        );
    }

    /**
     * an ungrouped rule list must produce today's exact flat implode, with no parens added anywhere
     */
    public function testUngroupedRulesProduceAFlatImplode(): void
    {
        $result = $this->ruleSql([
            ['id', 'gt', 5],
            ['year', 'gt', 2000],
        ]);

        $this->assertSame(
            '(`album`.`id` > ? AND `album`.`year` > ?) AND EXISTS (SELECT 1 FROM `album` AS `album_wd` WHERE `album_wd`.`id` = `album_disk`.`album_id` AND `album_wd`.`enabled` = 1)',
            $result['where_sql']
        );
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{table_sql: string, where_sql: string, parameters: array<int, mixed>}
     */
    private function ruleSql(array $rules): array
    {
        AmpConfig::set('album_group', true, true);
        AmpConfig::set('catalog_disable', false, true);
        AmpConfig::set('catalog_filter', false, true);

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $privilegeChecker->shouldReceive('check')->andReturnFalse();

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->andReturn($privilegeChecker);

        $GLOBALS['dic'] = $dic;

        $search              = new Search(0, 'album_disk', $user);
        $search->search_user = $user;
        // set_rules() reaches an access check, so the rules are given in the shape it would have produced
        $search->rules = $rules;

        $result = new AlbumDiskSearch()->getSql($search);

        return [
            'table_sql' => $result['table_sql'],
            'where_sql' => $result['where_sql'],
            'parameters' => $result['parameters'],
        ];
    }
}
