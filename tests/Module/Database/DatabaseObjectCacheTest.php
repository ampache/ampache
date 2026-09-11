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

namespace Ampache\Module\Database;

use Override;
use PHPUnit\Framework\TestCase;

class DatabaseObjectCacheTest extends TestCase
{
    public function testAnEmptyResultIsCachedLikeAnyOther(): void
    {
        // a page warm that found nothing for an object must not send the object back to the database
        database_object::add_to_cache('things', 7, []);

        self::assertTrue(database_object::is_cached('things', 7));
        self::assertSame([], database_object::get_from_cache('things', 7));
    }

    public function testAnUnknownObjectIsNotCached(): void
    {
        database_object::add_to_cache('things', 7, ['a' => 1]);

        self::assertFalse(database_object::is_cached('things', 8));
        self::assertFalse(database_object::is_cached('others', 7));
        self::assertSame([], database_object::get_from_cache('things', 8));
    }

    public function testRemovingForgetsTheEntry(): void
    {
        database_object::add_to_cache('things', 7, []);
        database_object::remove_from_cache('things', 7);

        self::assertFalse(database_object::is_cached('things', 7));
    }

    #[Override]
    protected function tearDown(): void
    {
        database_object::clear_cache();

        parent::tearDown();
    }
}
