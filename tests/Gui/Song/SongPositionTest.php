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

namespace Ampache\Gui\Song;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * A third of a real catalogue has no track number, so the row has to disappear rather than read zero.
 */
class SongPositionTest extends TestCase
{
    /**
     * @return list<array{?int, int, string}>
     */
    public static function positions(): array
    {
        return [
            [null, 11, ''],
            [0, 11, ''],
            [-1, 11, ''],
            [6, 11, '6 <span class="of-total">/ 11</span>'],
            [11, 11, '11 <span class="of-total">/ 11</span>'],
            [1, 1, '1'],
            [1, 0, '1'],
        ];
    }

    #[DataProvider('positions')]
    public function testPositionShowsTheTotalOnlyWhenItAddsSomething(?int $number, int $total, string $expected): void
    {
        $method = new ReflectionMethod(SongViewAdapter::class, 'position');

        self::assertSame(
            $expected,
            $method->invoke(
                new ReflectionClass(SongViewAdapter::class)->newInstanceWithoutConstructor(),
                $number,
                $total
            )
        );
    }
}
