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

namespace Ampache\Module\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function filenameDataProvider(): array
    {
        return [
            'a plain name is untouched' => ['song.mp3', 'song.mp3'],
            'spaces and unicode survive' => ['Will Atkinson - Victims.mp3', 'Will Atkinson - Victims.mp3'],
            'relative traversal' => ['../../../../tmp/pwned.mp3', 'pwned.mp3'],
            'absolute path' => ['/etc/cron.d/pwned.mp3', 'pwned.mp3'],
            'windows separators' => ['..\\..\\windows\\pwned.mp3', 'pwned.mp3'],
            'mixed separators' => ['../foo\\bar/pwned.mp3', 'pwned.mp3'],
            'a bare traversal segment leaves nothing usable' => ['../..', '..'],
            'empty stays empty' => ['', ''],
        ];
    }

    /**
     * The name is joined onto the catalog directory, so a name carrying a path would write outside the catalog.
     */
    #[DataProvider('filenameDataProvider')]
    public function testCleanFilenameKeepsOnlyTheName(string $given, string $expected): void
    {
        static::assertSame($expected, Upload::clean_filename($given));
    }
}
