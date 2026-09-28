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

namespace Ampache\Module\System;

use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Database\database_object;
use Ampache\Repository\PreferenceRepositoryInterface;
use DI\Container;
use PHPUnit\Framework\TestCase;

/**
 * `Preference::update()` dropped the wrong cache key, so a value written in one request was still
 * read back stale from `get_by_user()` in that same request.
 */
class PreferenceUpdateCacheTest extends TestCase
{
    private const int USER_ID = 42;

    public function testAnotherAccountKeepsItsOwnCachedValue(): void
    {
        Preference::add_to_cache('get_by_user-download', 99, ['0']);

        Preference::update('download', self::USER_ID, '1');

        $this->assertTrue(Preference::is_cached('get_by_user-download', 99));
    }

    public function testWritingAPreferenceDropsTheValueThatWasCachedForIt(): void
    {
        Preference::add_to_cache('get_by_user-download', self::USER_ID, ['0']);
        $this->assertTrue(Preference::is_cached('get_by_user-download', self::USER_ID), 'the fixture itself is wrong');

        $this->assertTrue(Preference::update('download', self::USER_ID, '1'));

        $this->assertFalse(
            Preference::is_cached('get_by_user-download', self::USER_ID),
            'the stale value survived the write, so the same request would read the old one back'
        );
    }

    public function testWritingOnePreferenceLeavesTheOthersCached(): void
    {
        Preference::add_to_cache('get_by_user-download', self::USER_ID, ['0']);
        Preference::add_to_cache('get_by_user-show_lyrics', self::USER_ID, ['0']);

        Preference::update('download', self::USER_ID, '1');

        $this->assertTrue(
            Preference::is_cached('get_by_user-show_lyrics', self::USER_ID),
            'invalidating more than the written preference would throw away work for nothing'
        );
    }

    protected function setUp(): void
    {
        database_object::clear_cache();

        $repository = $this->createMock(PreferenceRepositoryInterface::class);
        $repository->method('findIdByName')->willReturn(7);
        $repository->method('getLevel')->willReturn(25);
        $repository->method('hasUserPreferenceName')->willReturn(true);

        $checker = $this->createMock(PrivilegeCheckerInterface::class);
        $checker->method('check')->willReturn(true);

        $dic = $this->createMock(Container::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            PreferenceRepositoryInterface::class => $repository,
            default => $checker,
        });
        $GLOBALS['dic'] = $dic;
    }

    protected function tearDown(): void
    {
        database_object::clear_cache();
    }
}
