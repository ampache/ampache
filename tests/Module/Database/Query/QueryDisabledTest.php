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
class QueryDisabledTest extends TestCase
{
    public function testAManagerBrowsesAlbumsWithoutTheCondition(): void
    {
        $sql = $this->sqlFor('album', true);

        self::assertStringNotContainsString('`album`.`enabled`', $sql, 'the manager is the one who can put it back');
    }

    public function testAnAlbumDiskBrowseReadsTheFlagOnTheAlbumItBelongsTo(): void
    {
        $sql = $this->sqlFor('album_disk', false);

        self::assertStringContainsString(
            'EXISTS (SELECT 1 FROM `album` AS `album_dis` WHERE `album_dis`.`id` = `album_disk`.`album_id` AND `album_dis`.`enabled` = 1)',
            $sql
        );
    }

    public function testAnArtistBrowseCarriesTheConditionForALowerLevel(): void
    {
        $sql = $this->sqlFor('artist', false);

        self::assertStringContainsString('`artist`.`enabled` = 1', $sql);
    }

    public function testAnOrdinaryListenerBrowsesAlbumsWithTheCondition(): void
    {
        $sql = $this->sqlFor('album', false);

        self::assertStringContainsString('`album`.`enabled` = 1', $sql);
    }

    public function testAskingForDisabledAlbumsAsALowerLevelReturnsNothing(): void
    {
        $query = $this->query(false);
        $query->set_type('album');
        $query->set_filter('enabled', 0);

        $sql = (string) new ReflectionMethod(Query::class, '_get_sql')->invoke($query, false, false);

        // the tell that the guard fired: both conditions stand, so the browse can never match a row
        self::assertStringContainsString('`album`.`enabled` = 0', $sql);
        self::assertStringContainsString('`album`.`enabled` = 1', $sql);
    }

    /**
     * Deliberate, and the reason the condition names its three types instead of walking up the tree: a song
     * turned back on by hand stays listed even while the album it sits on is off. Closing that would take
     * the only way there is to publish a single track of a withdrawn release.
     */
    public function testASongBrowseNeverConsultsTheStateOfItsAlbum(): void
    {
        $sql = $this->sqlFor('song', false);

        self::assertStringNotContainsString('`album`.`enabled`', $sql);
        self::assertStringNotContainsString('`artist`.`enabled`', $sql);
        self::assertStringNotContainsString('`album_dis`', $sql);
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
