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

namespace Ampache\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AmpConfigTest extends TestCase
{
    /**
     * @return list<array{0: mixed, 1: bool}>
     */
    public static function boolDataProvider(): array
    {
        return [
            ['1', true],
            [1, true],
            [true, true],
            // the string zero is what the old `get()` handed back, and it is true to anything typed bool
            ['0', false],
            [0, false],
            [false, false],
            ['', false],
            ['true', true],
            ['false', false],
            [null, false],
            // a list answers for whether it holds anything
            [['mysql'], true],
            [[], false],
        ];
    }

    /**
     * A config value arrives from an ini file or a varchar column, so a string is the normal case
     *
     * @return list<array{0: mixed, 1: int}>
     */
    public static function intDataProvider(): array
    {
        return [
            ['10', 10],
            [10, 10],
            ['0', 0],
            ['-1', -1],
            ['10.7', 10],
            [true, 1],
            [false, 0],
            // not a number at all, so the default is kept rather than quietly meaning none
            ['', 7],
            ['unlimited', 7],
            [[], 7],
            [null, 7],
        ];
    }

    #[DataProvider('boolDataProvider')]
    public function testGetBool(mixed $stored, bool $expected): void
    {
        AmpConfig::set('probe_bool', $stored, true);

        self::assertSame($expected, AmpConfig::get_bool('probe_bool'), var_export($stored, true));
    }

    public function testGetBoolReturnsTheDefaultWhenUnset(): void
    {
        self::assertTrue(AmpConfig::get_bool('probe_missing_bool', true));
        self::assertFalse(AmpConfig::get_bool('probe_missing_bool'));
    }

    #[DataProvider('intDataProvider')]
    public function testGetInt(mixed $stored, int $expected): void
    {
        AmpConfig::set('probe_int', $stored, true);

        self::assertSame($expected, AmpConfig::get_int('probe_int', 7), var_export($stored, true));
    }

    public function testGetIntReturnsTheDefaultWhenUnset(): void
    {
        self::assertSame(5, AmpConfig::get_int('probe_missing_int', 5));
        self::assertSame(0, AmpConfig::get_int('probe_missing_int'));
    }
}
