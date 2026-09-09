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

class PluralTranslationTest extends TestCase
{
    /**
     * @return list<array{0: int, 1: string}>
     */
    public static function countDataProvider(): array
    {
        return [
            [0, '%d albums'],
            [1, '%d album'],
            [2, '%d albums'],
            [11, '%d albums'],
        ];
    }

    #[DataProvider('countDataProvider')]
    public function testFallsBackOnTheFormThatMatchesTheCount(int $count, string $expected): void
    {
        if (function_exists('n__')) {
            self::markTestSkipped('gettext is loaded, so the fallback is not the code under test');
        }

        self::assertSame($expected, nT_('%d album', '%d albums', $count));
    }
}
