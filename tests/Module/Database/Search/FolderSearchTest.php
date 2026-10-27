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

class FolderSearchTest extends MockeryTestCase
{
    public function testAddedAndUpdatedConvertTheDateToATimestamp(): void
    {
        $result = $this->ruleSql([['added', 'gt', '2024-01-01']]);

        $this->assertSame('`folder`.`addition_time` > ?', $result['where_sql']);
        $this->assertSame([strtotime('2024-01-01')], $result['parameters']);
    }

    /**
     * the catalog rule is a direct column equality/inequality, not a text match
     */
    public function testCatalogRuleMatchesTheFolderCatalogColumn(): void
    {
        $result = $this->ruleSql([
            ['catalog', 'equal', 3],
        ]);

        $this->assertSame("`folder`.`catalog` <=> ?", $result['where_sql']);
        $this->assertSame(['3'], $result['parameters']);
    }

    /**
     * `addition_time` is never NULL, so "not added before" is a plain operator swap with no OR branch
     */
    public function testDaysAddedNotBeforeSwapsTheOperator(): void
    {
        $result = $this->ruleSql([['days_added', 'notlt', 14]]);

        $this->assertSame('(`folder`.`addition_time` >= (UNIX_TIMESTAMP() - (14 * 86400)))', $result['where_sql']);
    }

    public function testMyFlaggedFolderJoinsTheCurrentUsersOwnFlagRow(): void
    {
        $result = $this->ruleSql([['my_flagged_folder', 'true', '1']]);

        $this->assertSame('`my_flagged__folder_2`.`object_id` IS NOT NULL', $result['where_sql']);
        $this->assertStringContainsString("`user_flag`.`object_type` = 'folder' AND `user_flag`.`user` = 2", $result['table_sql']);
    }

    public function testMyratingJoinsTheCurrentUsersOwnRatingRow(): void
    {
        $result = $this->ruleSql([['myrating', 'gte', 4]]);

        $this->assertSame('IFNULL(`rating_folder_2`.`rating`, 0) >= ?', $result['where_sql']);
        $this->assertStringContainsString("AND `object_type` = 'folder') AS `rating_folder_2`", $result['table_sql']);
        $this->assertStringContainsString('`rating_folder_2`.`object_id` = `folder`.`id`', $result['table_sql']);
    }

    public function testObjectCountMatchesTheFolderObjectCountColumn(): void
    {
        $result = $this->ruleSql([['object_count', 'gte', 3]]);

        $this->assertSame('`folder`.`object_count` >= ?', $result['where_sql']);
        $this->assertSame([3], $result['parameters']);
    }

    public function testOtherUserLovedChecksAnotherUsersFavoriteFlag(): void
    {
        $result = $this->ruleSql([['other_user', 'love', 7]]);

        $this->assertSame(
            "`favorite_folder_7`.`user` = 7 AND `favorite_folder_7`.`object_type` = 'folder'",
            $result['where_sql']
        );
    }

    /**
     * `?` placeholder order must match parameter order across two groups, not rule-definition order
     */
    public function testParameterOrderMatchesAcrossGroups(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['title', 'contain', 'Music'],
                    ['path', 'start', '/mnt/music'],
                ],
            ],
            ['id', 'gt', 5],
        ]);

        $this->assertSame(['%Music%', '/mnt/music%', 5], $result['parameters']);
    }

    /**
     * `parent_id` is a direct column on `folder` itself, unlike `parent_name`/`parent_path`, so it
     * needs no self-join
     */
    public function testParentIdMatchesTheFolderParentColumnWithNoJoin(): void
    {
        $result = $this->ruleSql([['parent_id', 'equal', 5]]);

        $this->assertSame('`folder`.`parent` <=> ?', $result['where_sql']);
        $this->assertSame([5], $result['parameters']);
        $this->assertStringNotContainsString('folder_parent', $result['table_sql']);
    }

    /**
     * `parent_name`/`parent_path` read the parent row through a self-join, kept out of the base query
     * so a folder with no parent (the catalog root) still matches on its own columns
     */
    public function testParentNameAndPathJoinTheParentFolder(): void
    {
        $result = $this->ruleSql([
            ['parent_name', 'contain', 'Music'],
            ['parent_path', 'start', '/mnt/music'],
        ]);

        $this->assertStringContainsString(
            'LEFT JOIN `folder` AS `folder_parent` ON `folder_parent`.`id` = `folder`.`parent`',
            $result['table_sql']
        );
        $this->assertSame(
            '`folder_parent`.`name` LIKE ? AND `folder_parent`.`path_name` LIKE ?',
            $result['where_sql']
        );
        $this->assertSame(['%Music%', '/mnt/music%'], $result['parameters']);
    }

    /**
     * `playable` bakes the operator's own boolean literal into the SQL rather than binding it, matching
     * the `enabled`/`has_image` boolean rules on other types
     */
    public function testPlayableRuleBakesTheBooleanLiteralIntoTheSql(): void
    {
        $true  = $this->ruleSql([['playable', 'true', '1']]);
        $false = $this->ruleSql([['playable', 'false', '0']]);

        $this->assertSame('`folder`.`playable` = 1', $true['where_sql']);
        $this->assertSame('`folder`.`playable` = 0', $false['where_sql']);
    }

    public function testPlayedAndSkippedTimesMatchTheirOwnFolderColumns(): void
    {
        $played        = $this->ruleSql([['played_times', 'gte', 3]]);
        $skipped       = $this->ruleSql([['skipped_times', 'gte', 2]]);
        $playedOrSkip  = $this->ruleSql([['played_or_skipped_times', 'gte', 4]]);

        $this->assertSame('`folder`.`total_count` >= ?', $played['where_sql']);
        $this->assertSame('`folder`.`total_skip` >= ?', $skipped['where_sql']);
        $this->assertSame('((`folder`.`total_count` + `folder`.`total_skip`) >= ?)', $playedOrSkip['where_sql']);
    }

    public function testRatingAveragesTheFolderRatingTable(): void
    {
        $result = $this->ruleSql([['rating', 'gte', 3]]);

        $this->assertSame('IFNULL(`average_rating`.`avg`, 0) >= ?', $result['where_sql']);
        $this->assertStringContainsString("`rating`.`object_type`='folder'", $result['table_sql']);
    }

    /**
     * the subquery is keyed by a hash of the input and operator so two different `recent_added` rules
     * in the same search don't collide on one alias
     */
    public function testRecentAddedBuildsASelfReferencingSubquery(): void
    {
        $result = $this->ruleSql([['recent_added', 'add', 10]]);
        $key    = md5('10`addition_time`');

        $this->assertSame(sprintf('`addition_time_%s`.`id` IS NOT NULL', $key), $result['where_sql']);
        $this->assertStringContainsString(
            sprintf('LEFT JOIN (SELECT `id` FROM `folder` ORDER BY `addition_time` DESC LIMIT 10) AS `addition_time_%s` ON `folder`.`id` = `addition_time_%s`.`id`', $key, $key),
            $result['table_sql']
        );
    }

    /**
     * the widget takes minutes, matching the `time` rule on every other type, but `folder`.`time` is
     * stored in seconds
     */
    public function testTimeRuleConvertsMinutesToSeconds(): void
    {
        $result = $this->ruleSql([['time', 'gte', 2]]);

        $this->assertSame('`folder`.`time` >= ?', $result['where_sql']);
        $this->assertSame([120], $result['parameters']);
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
                    ['title', 'contain', 'Music'],
                    ['path', 'start', '/mnt/music'],
                ],
            ],
            ['id', 'gt', 5],
        ]);

        $this->assertSame(
            "(`folder`.`name` LIKE ? OR `folder`.`path_name` LIKE ?) AND `folder`.`id` > ?",
            $result['where_sql']
        );
    }

    /**
     * a rule list with no group wrapper at all must still produce a single flat implode, with no
     * parentheses added anywhere
     */
    public function testUngroupedRulesProduceAFlatImplode(): void
    {
        $result = $this->ruleSql([
            ['title', 'contain', 'Music'],
            ['path', 'start', '/mnt/music'],
        ]);

        $this->assertSame(
            "`folder`.`name` LIKE ? AND `folder`.`path_name` LIKE ?",
            $result['where_sql']
        );
        $this->assertSame(['%Music%', '/mnt/music%'], $result['parameters']);
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{where_sql: string, parameters: array<int, mixed>, table_sql: string}
     */
    private function ruleSql(array $rules, string $logicOperator = 'and'): array
    {
        AmpConfig::set('catalog_disable', false, true);
        AmpConfig::set('catalog_filter', false, true);

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        $search                 = new Search(0, 'folder', $user);
        $search->search_user    = $user;
        $search->logic_operator = $logicOperator;
        $search->rules          = $rules;

        $result = new FolderSearch()->getSql($search);

        return [
            'where_sql' => $result['where_sql'],
            'parameters' => $result['parameters'],
            'table_sql' => $result['table_sql'],
        ];
    }
}
