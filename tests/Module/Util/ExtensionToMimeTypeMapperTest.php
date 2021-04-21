<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Util;

use Ampache\MockeryTestCase;

class ExtensionToMimeTypeMapperTest extends MockeryTestCase
{
    private ExtensionToMimeTypeMapper $subject;

    public function setUp(): void
    {
        $this->subject = new ExtensionToMimeTypeMapper();
    }

    /**
     * @dataProvider audioDataProvider
     */
    public function testMapAudioReturnsType(
        string $extension,
        string $mimeType
    ): void {
        $this->assertSame(
            $this->subject->mapAudio($extension),
            $mimeType
        );
    }

    public function audioDataProvider(): array
    {
        return [
            ['spx', 'application/ogg'],
            ['ogg', 'application/ogg'],
            ['opus', 'audio/ogg; codecs=opus'],
            ['wma', 'audio/x-ms-wma'],
            ['asf', 'audio/x-ms-wma'],
            ['rm', 'audio/x-realaudio'],
            ['ra', 'audio/x-realaudio'],
            ['flac', 'audio/x-flac'],
            ['wv', 'audio/x-wavpack'],
            ['aac', 'audio/mp4'],
            ['mp4', 'audio/mp4'],
            ['m4a', 'audio/mp4'],
            ['aacp', 'audio/aacp'],
            ['mpc', 'audio/x-musepack'],
            ['mkv', 'audio/x-matroska'],
            ['mpeg3', 'audio/mpeg'],
            ['mp3', 'audio/mpeg'],
            ['foobar', 'audio/mpeg'],
        ];
    }
    /**
     * @dataProvider videoDataProvider
     */
    public function testMapVideoReturnsType(
        string $extension,
        string $mimeType
    ): void {
        $this->assertSame(
            $this->subject->mapVideo($extension),
            $mimeType
        );
    }

    public function videoDataProvider(): array
    {
        return [
            ['avi', 'video/avi'],
            ['ogg', 'application/ogg'],
            ['ogv', 'application/ogg'],
            ['wmv', 'audio/x-ms-wmv'],
            ['mp4', 'video/mp4'],
            ['m4v', 'video/mp4'],
            ['mkv', 'video/x-matroska'],
            ['mov', 'video/quicktime'],
            ['divx', 'video/x-divx'],
            ['webm', 'video/webm'],
            ['flv', 'video/x-flv'],
            ['ts', 'video/mp2t'],
            ['mpg', 'video/mpeg'],
            ['mpeg', 'video/mpeg'],
            ['m2ts', 'video/mpeg'],
            ['foobar', 'video/mpeg'],
        ];
    }
}
