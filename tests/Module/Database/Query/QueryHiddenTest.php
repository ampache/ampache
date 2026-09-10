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

namespace Ampache\Module\Database\Query;

use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;

/**
 * A withdrawn album or artist has to leave every browse at once, which is why the condition lives beside
 * the catalog filter rather than in each caller. These pin that it is emitted, and only for the listener.
 */
class QueryHiddenTest extends TestCase
{
    public function testAManagerBrowsesAlbumsWithoutTheCondition(): void
    {
        $sql = $this->sqlFor('album', true);

        self::assertStringNotContainsString('`album`.`hidden`', $sql, 'the manager is the one who can put it back');
    }

    public function testAnAlbumDiskBrowseReadsTheFlagOnTheAlbumItBelongsTo(): void
    {
        $sql = $this->sqlFor('album_disk', false);

        self::assertStringContainsString(
            'NOT EXISTS (SELECT 1 FROM `album` AS `album_hid` WHERE `album_hid`.`id` = `album_disk`.`album_id` AND `album_hid`.`hidden` = 1)',
            $sql
        );
    }

    public function testAnArtistBrowseCarriesTheConditionForALowerLevel(): void
    {
        $sql = $this->sqlFor('artist', false);

        self::assertStringContainsString('`artist`.`hidden` = 0', $sql);
    }

    public function testAnOrdinaryListenerBrowsesAlbumsWithTheCondition(): void
    {
        $sql = $this->sqlFor('album', false);

        self::assertStringContainsString('`album`.`hidden` = 0', $sql);
    }

    public function testAskingForHiddenAlbumsAsALowerLevelReturnsNothing(): void
    {
        $query = $this->query(false);
        $query->set_type('album');
        $query->set_filter('hidden', 1);

        $sql = (string) new ReflectionMethod(Query::class, '_get_sql')->invoke($query, false, false);

        // the tell that the guard fired: both conditions stand, so the browse can never match a row
        self::assertStringContainsString('`album`.`hidden` = 1', $sql);
        self::assertStringContainsString('`album`.`hidden` = 0', $sql);
    }

    private function query(bool $isManager): Query
    {
        $privilegeChecker = $this->createMock(PrivilegeCheckerInterface::class);
        $privilegeChecker->method('check')->willReturn($isManager);

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturn($privilegeChecker);

        $GLOBALS['dic'] = $dic;

        return new Query(0, false);
    }

    private function sqlFor(string $type, bool $isManager): string
    {
        $query = $this->query($isManager);
        $query->set_type($type);

        return (string) new ReflectionMethod(Query::class, '_get_sql')->invoke($query, false, false);
    }
}
