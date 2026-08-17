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

namespace Ampache\Module\Podcast\Feed;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeedDurationTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: int}>
     */
    public static function durationDataProvider(): array
    {
        return [
            // the three documented forms
            ['01:02:03', 3723],
            ['1:02:03', 3723],
            ['15:23', 923],
            ['5:03', 303],
            ['24325', 24325],
            ['0', 0],
            // padding
            ['  01:00:00  ', 3600],
            // the hour is not capped at 24
            ['30:00:00', 108000],
            // not a duration
            ['', 0],
            ['not a time', 0],
            ['1:2:3:4', 0],
            ['12:99', 0],
        ];
    }

    #[DataProvider('durationDataProvider')]
    public function testToSecondsReadsTheDocumentedForms(string $input, int $expected): void
    {
        self::assertSame($expected, FeedDuration::toSeconds($input));
    }
}
