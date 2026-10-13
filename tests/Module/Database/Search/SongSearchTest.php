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

class SongSearchTest extends MockeryTestCase
{
    /**
     * a grouped album is rated as `album`, so the disk table is never read and joining it only multiplied rows
     */
    public function testAlbumRatingLeavesTheDiskTableOutWhenAlbumsAreGrouped(): void
    {
        $result = $this->albumRatingSql(true);

        $this->assertStringNotContainsString('LEFT JOIN `album_disk`', $result['table_sql']);
        $this->assertStringContainsString("`object_type`='album'", $result['table_sql']);
        $this->assertStringContainsString('`object_id` = `song`.`album`', $result['table_sql']);
    }

    /**
     * an `album_disk` rating is keyed by the disk row, so joining `album_id` matched a different album entirely
     */
    public function testAlbumRatingMatchesTheDiskRowWhenAlbumsAreNotGrouped(): void
    {
        $result = $this->albumRatingSql(false);

        $this->assertStringContainsString('LEFT JOIN `album_disk`', $result['table_sql']);
        $this->assertStringContainsString("`object_type`='album_disk'", $result['table_sql']);
        $this->assertStringContainsString('`object_id` = `album_disk`.`id`', $result['table_sql']);
        $this->assertStringNotContainsString('`object_id` = `album_disk`.`album_id`', $result['table_sql']);
    }

    /**
     * `addition_time` is never NULL, so "not added before" is a plain operator swap with no OR branch
     */
    public function testDaysAddedNotBeforeSwapsTheOperator(): void
    {
        $result = $this->ruleSql([['days_added', 'notlt', 14]]);

        $this->assertStringContainsString('`song`.`addition_time` >= (UNIX_TIMESTAMP() - (14 * 86400))', $result['where_sql']);
        $this->assertStringNotContainsString('IS NULL', $result['where_sql']);
    }

    /**
     * an album is dated by its own stream rows, matched through the song's album column
     */
    public function testLastPlayAlbumComparesTheAlbumsOwnPlayDate(): void
    {
        $result = $this->ruleSql([['last_play_album', 'gt', 7]]);

        $this->assertStringContainsString("WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream'", $result['table_sql']);
        $this->assertStringContainsString('ON `song`.`album` = `last_play_album_2`.`object_id`', $result['table_sql']);
        $this->assertStringContainsString('`last_play_album_2`.`date` > (UNIX_TIMESTAMP() - (7 * 86400))', $result['where_sql']);
    }

    /**
     * album and artist rows never record a skip, so a skip is rolled up from the songs the artist holds
     */
    public function testLastSkipArtistRollsSongSkipsUpToTheArtist(): void
    {
        $result = $this->ruleSql([['last_skip_artist', 'lt', 30]]);

        $this->assertStringContainsString("WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip'", $result['table_sql']);
        $this->assertStringContainsString('GROUP BY `parent_song`.`artist`', $result['table_sql']);
        $this->assertStringContainsString('ON `song`.`artist` = `last_skip_artist_2`.`object_id`', $result['table_sql']);
        $this->assertStringContainsString('`last_skip_artist_2`.`date` < (UNIX_TIMESTAMP() - (30 * 86400))', $result['where_sql']);
    }

    /**
     * a song that was never skipped has no row in the LEFT JOIN subquery, so "not skipped after"
     * must also match a NULL date or a smartlist built to exclude recent skips would drop every
     * song that was never skipped at all
     */
    public function testLastSkipNotAfterAlsoMatchesASongNeverSkipped(): void
    {
        $result = $this->ruleSql([['last_skip', 'notgt', 7]]);

        $this->assertStringContainsString(
            '(`last_skip_song_2`.`date` IS NULL OR `last_skip_song_2`.`date` <= (UNIX_TIMESTAMP() - (7 * 86400)))',
            $result['where_sql']
        );
    }

    /**
     * `$metadata` is shared across every group call in one `getSql()`, so two `metadata` rules on
     * the same field split across two groups still combine into a single `EXISTS` clause
     */
    public function testMetadataRuleCombinesAcrossGroupsIntoOneExistsClause(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [['metadata', 'equal', 'Pop', 5]],
            ],
            [
                'group_operator' => 'or',
                'rules' => [['metadata', 'equal', 'Rock', 5]],
            ],
        ]);

        $this->assertSame(1, substr_count($result['where_sql'], 'EXISTS ('));
        $this->assertStringContainsString(
            "`metadata`.`field` = 5 AND (`metadata`.`data` = ? AND `metadata`.`data` = ?)",
            $result['where_sql']
        );
        $this->assertSame(['"Pop"', '"Rock"'], $result['parameters']);
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
                    ['title', 'contain', 'Rock'],
                    ['title', 'contain', 'Pop'],
                ],
            ],
            ['time', 'gt', 3],
        ]);

        $this->assertSame(['%Rock%', '%Pop%', 180], $result['parameters']);
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
                    ['title', 'contain', 'Rock'],
                    ['title', 'contain', 'Pop'],
                ],
            ],
            ['time', 'gt', 3],
        ]);

        $this->assertSame(
            '((`song`.`title` LIKE ? OR `song`.`title` LIKE ?) AND `song`.`time` > ?) AND `song`.`enabled` = 1',
            $result['where_sql']
        );
    }

    /**
     * two independent groups plus an ungrouped rule each parenthesize and operate separately, and
     * parameter order still follows definition order across all three fragments
     */
    public function testTwoIndependentGroupsCoexistWithAnUngroupedRule(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['title', 'contain', 'Rock'],
                    ['title', 'contain', 'Pop'],
                ],
            ],
            ['time', 'gt', 3],
            [
                'group_operator' => 'and',
                'rules' => [
                    ['title', 'contain', 'Foo'],
                    ['title', 'contain', 'Bar'],
                ],
            ],
        ]);

        $this->assertSame(
            '((`song`.`title` LIKE ? OR `song`.`title` LIKE ?) AND `song`.`time` > ? AND (`song`.`title` LIKE ? AND `song`.`title` LIKE ?)) AND `song`.`enabled` = 1',
            $result['where_sql']
        );
        $this->assertSame(['%Rock%', '%Pop%', 180, '%Foo%', '%Bar%'], $result['parameters']);
    }

    /**
     * an ungrouped rule list must produce today's exact flat implode, with no parens added anywhere
     */
    public function testUngroupedRulesProduceAFlatImplode(): void
    {
        $result = $this->ruleSql([
            ['title', 'contain', 'Rock'],
            ['time', 'gt', 3],
        ]);

        // the outer parens come from `WithdrawnFilter::apply()`, not from grouping -- no inner paren is added
        $this->assertSame('(`song`.`title` LIKE ? AND `song`.`time` > ?) AND `song`.`enabled` = 1', $result['where_sql']);
        $this->assertSame(['%Rock%', 180], $result['parameters']);
    }

    /**
     * @return array{table_sql: string, where_sql: string, join: array<string, bool>, parameters: array<int, mixed>}
     */
    private function albumRatingSql(bool $albumGroup): array
    {
        return $this->ruleSql([['albumrating', 'gte', 4]], $albumGroup);
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{table_sql: string, where_sql: string, join: array<string, bool>, parameters: array<int, mixed>}
     */
    private function ruleSql(array $rules, bool $albumGroup = true): array
    {
        AmpConfig::set('album_group', $albumGroup, true);
        AmpConfig::set('catalog_disable', false, true);
        AmpConfig::set('catalog_filter', false, true);

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        // the smartlist asks whether the caller may see withdrawn tracks, so a checker has to be reachable
        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $privilegeChecker->shouldReceive('check')->andReturnFalse();

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->andReturn($privilegeChecker);

        $GLOBALS['dic'] = $dic;

        $search              = new Search(0, 'song', $user);
        $search->search_user = $user;
        // set_rules() reaches an access check, so the rules are given in the shape it would have produced
        $search->rules = $rules;

        $result = new SongSearch()->getSql($search);

        return [
            'table_sql' => $result['table_sql'],
            'where_sql' => $result['where_sql'],
            'join' => $result['join'],
            'parameters' => $result['parameters'],
        ];
    }
}
