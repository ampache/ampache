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

namespace Ampache\Repository\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Album and AlbumDisk once returned an empty string here, which silently dropped the
 * duration from the page rather than showing a wrong value.
 */
class AlbumDurationTest extends TestCase
{
    /**
     * @return list<array{int, string}>
     */
    public static function durations(): array
    {
        return [
            [0, ''],
            [59, '59'],
            [90, '1:30'],
            [2691, '44:51'],
            [3600, '1:00:00'],
            [7384, '2:03:04'],
        ];
    }

    #[DataProvider('durations')]
    public function testAlbumDiskFormatsItsDuration(int $seconds, string $expected): void
    {
        $disk       = new AlbumDisk();
        $disk->time = $seconds;

        self::assertSame($expected, $disk->get_f_time());
    }

    #[DataProvider('durations')]
    public function testAlbumFormatsItsDuration(int $seconds, string $expected): void
    {
        $album       = new Album();
        $album->time = $seconds;

        self::assertSame($expected, $album->get_f_time());
    }
}
