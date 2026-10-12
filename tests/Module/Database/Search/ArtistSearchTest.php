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

class ArtistSearchTest extends MockeryTestCase
{
    /**
     * the artist's own name and the _artist alias build the same join
     */
    public function testLastPlayArtistMatchesLastPlay(): void
    {
        $this->assertSame($this->ruleSql([['last_play', 'gt', 3]]), $this->ruleSql([['last_play_artist', 'gt', 3]]));
    }

    /**
     * an artist is matched on the play date of any song mapped to it
     */
    public function testLastPlaySongJoinsTheArtistsSongStreams(): void
    {
        $result = $this->ruleSql([['last_play_song', 'gt', 7]]);

        $this->assertStringContainsString("LEFT JOIN `song` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song'", $result['table_sql']);
        $this->assertStringContainsString('ON `song`.`id` = `last_play_song_artist_2`.`object_id`', $result['table_sql']);
        $this->assertStringContainsString('`last_play_song_artist_2`.`date` > (UNIX_TIMESTAMP() - (? * 86400))', $result['where_sql']);
    }

    /**
     * album rows never record a skip, so the artist's albums are dated from their songs
     */
    public function testLastSkipAlbumRollsSongSkipsUpToTheArtistsAlbums(): void
    {
        $result = $this->ruleSql([['last_skip_album', 'lt', 30]]);

        $this->assertStringContainsString('LEFT JOIN `album` ON `album_map`.`album_id` = `album`.`id`', $result['table_sql']);
        $this->assertStringContainsString('GROUP BY `parent_song`.`album`', $result['table_sql']);
        $this->assertStringContainsString('ON `album`.`id` = `last_skip_album_2`.`object_id`', $result['table_sql']);
        $this->assertLessThan(strpos($result['table_sql'], '`last_skip_album_2`'), strpos($result['table_sql'], 'LEFT JOIN `album`'));
    }

    /**
     * @param list<array{0: string, 1: string, 2: int|string}> $rules
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

        $search              = new Search(0, 'artist', $user);
        $search->search_user = $user;
        // set_rules() reaches an access check, so the rules are given in the shape it would have produced
        $search->rules = $rules;

        $result = new ArtistSearch('artist')->getSql($search);

        return [
            'table_sql' => $result['table_sql'],
            'where_sql' => $result['where_sql'],
        ];
    }
}
