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

class AlbumSearchTest extends MockeryTestCase
{
    /**
     * the album artist is dated by its own stream rows
     */
    public function testLastPlayArtistComparesTheAlbumArtistsPlayDate(): void
    {
        $result = $this->ruleSql([['last_play_artist', 'gt', 7]]);

        $this->assertStringContainsString("WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream'", $result['table_sql']);
        $this->assertStringContainsString('ON `album`.`album_artist` = `last_play_artist_2`.`object_id`', $result['table_sql']);
        $this->assertStringContainsString('`last_play_artist_2`.`date` > (UNIX_TIMESTAMP() - (? * 86400))', $result['where_sql']);
    }

    /**
     * an album is matched on the play date of any song it holds
     */
    public function testLastPlaySongJoinsTheAlbumsSongStreams(): void
    {
        $result = $this->ruleSql([['last_play_song', 'gt', 7]]);

        $this->assertStringContainsString('LEFT JOIN `song` ON `song`.`album` = `album`.`id`', $result['table_sql']);
        $this->assertStringContainsString("WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream'", $result['table_sql']);
        $this->assertStringContainsString('ON `song`.`id` = `last_play_song_album_2`.`object_id`', $result['table_sql']);
        $this->assertStringContainsString('`last_play_song_album_2`.`date` > (UNIX_TIMESTAMP() - (? * 86400))', $result['where_sql']);
    }

    /**
     * the album's own name and the _album alias build the same join
     */
    public function testLastSkipAlbumMatchesLastSkip(): void
    {
        $this->assertSame($this->ruleSql([['last_skip', 'lt', 3]]), $this->ruleSql([['last_skip_album', 'lt', 3]]));
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
            '((`album`.`id` > ? OR `album`.`id` > ?) AND `album`.`year` > ?) AND `album`.`enabled` = 1',
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
            '(`album`.`id` > ? AND `album`.`year` > ?) AND `album`.`enabled` = 1',
            $result['where_sql']
        );
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{table_sql: string, where_sql: string}
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

        $search              = new Search(0, 'album', $user);
        $search->search_user = $user;
        // set_rules() reaches an access check, so the rules are given in the shape it would have produced
        $search->rules = $rules;

        $result = new AlbumSearch()->getSql($search);

        return [
            'table_sql' => $result['table_sql'],
            'where_sql' => $result['where_sql'],
        ];
    }
}
