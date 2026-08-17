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
     * @return array{table_sql: string, where_sql: string, join: array<string, bool>}
     */
    private function albumRatingSql(bool $albumGroup): array
    {
        AmpConfig::set('album_group', $albumGroup, true);
        AmpConfig::set('catalog_disable', false, true);
        AmpConfig::set('catalog_filter', false, true);

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        $search              = new Search(0, 'song', $user);
        $search->search_user = $user;
        // set_rules() reaches an access check, so the rules are given in the shape it would have produced
        $search->rules = [['albumrating', 'gte', 4]];

        $result = new SongSearch()->getSql($search);

        return [
            'table_sql' => $result['table_sql'],
            'where_sql' => $result['where_sql'],
            'join' => $result['join'],
        ];
    }
}
