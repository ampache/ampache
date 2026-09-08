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

namespace Ampache\Module\Catalog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The file sorter builds a destination path by substituting tag values into a pattern. Tag values are
 * untrusted, so these lock down the two sanitisers that keep a crafted tag from writing outside the catalog.
 */
class CatalogSortPathTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function legitimateNameDataProvider(): array
    {
        return [
            // unicode and accents must pass through untouched, or international libraries get mangled
            ['Björk/track'],
            ['Beyoncé/track'],
            ['宇多田ヒカル/track'],
            ['Motörhead/track'],
            ['Sigur Rós/track'],
            // dots inside a name (not a whole segment) are legitimate
            ['Vol. 2/track'],
            ['A.M./track'],
            ["O'Brien (Live)/track"],
            ['artist/album/title'],
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function traversalDataProvider(): array
    {
        return [
            // a segment made only of dots must not survive as a parent-directory hop
            ['..', '_'],
            ['.', '_'],
            ['...', '_'],
            ['a/../b', 'a/_/b'],
            ['../../etc', '_/_/etc'],
            ['a/../../b', 'a/_/_/b'],
            ['/../x', '/_/x'],
            // leading and trailing dot segments
            ['../a', '_/a'],
            ['a/..', 'a/_'],
        ];
    }

    public function testSortCleanNameKeepsUnicodeAndDots(): void
    {
        self::assertSame('Björk Vol. 2', Catalog::sort_clean_name('Björk Vol. 2'));
    }

    public function testSortCleanNameReturnsTheFallbackWhenEmpty(): void
    {
        self::assertSame('%t', Catalog::sort_clean_name('', '%t'));
    }

    public function testSortCleanNameStripsMoreOnWindows(): void
    {
        self::assertSame('a_b_c_d', Catalog::sort_clean_name('a:b*c?d', '', true));
    }

    public function testSortCleanNameStripsPathSeparators(): void
    {
        self::assertSame('a_b_c', Catalog::sort_clean_name('a/b\\c'));
    }

    #[DataProvider('legitimateNameDataProvider')]
    public function testSortCleanPathLeavesLegitimateNamesUntouched(string $input): void
    {
        self::assertSame($input, Catalog::sort_clean_path($input));
    }

    #[DataProvider('traversalDataProvider')]
    public function testSortCleanPathNeutralisesDotOnlySegments(string $input, string $expected): void
    {
        self::assertSame($expected, Catalog::sort_clean_path($input));
    }

    public function testSortCleanPathStripsNullBytes(): void
    {
        self::assertSame('abc', Catalog::sort_clean_path("a\0b\0c"));
    }
}
