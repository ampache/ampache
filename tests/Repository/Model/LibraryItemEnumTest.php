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
 */

namespace Ampache\Repository\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LibraryItemEnumTest extends TestCase
{
    /**
     * @return list<array{string, ?LibraryItemEnum}>
     */
    public static function aliases(): array
    {
        return [
            ['album_artist', LibraryItemEnum::ARTIST],
            ['song_artist', LibraryItemEnum::ARTIST],
            ['genre', LibraryItemEnum::TAG],
            ['smartlist', LibraryItemEnum::SEARCH],
            ['artist', LibraryItemEnum::ARTIST],
            ['song', LibraryItemEnum::SONG],
            ['bookmark', null],
            ['', null],
        ];
    }

    #[DataProvider('aliases')]
    public function testFromObjectTypeResolvesRolesAndAlternateSpellings(string $objectType, ?LibraryItemEnum $expected): void
    {
        static::assertSame($expected, LibraryItemEnum::fromObjectType($objectType));
    }
}
