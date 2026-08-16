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

namespace Ampache\Module\System;

use Ampache\MockeryTestCase;

class CoreTest extends MockeryTestCase
{
    /** 1x1 gif */
    private const string GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /** 1x1 jpeg */
    private const string JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
        . 'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAA'
        . 'AAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==';

    /** 1x1 png */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function testGenerateRandomKeyIsUnpredictableAcrossCalls(): void
    {
        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $keys[] = Core::generate_random_key();
        }

        $this->assertCount(100, array_unique($keys));
    }

    /**
     * Backs session/api keys and the CSRF form token, so it must be a full 128 bits from `random_bytes()`,
     * not a hash of a predictable seed
     */
    public function testGenerateRandomKeyReturnsA32CharacterHexString(): void
    {
        $key = Core::generate_random_key();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $key);
    }

    public function testImageMimeDetectsGif(): void
    {
        $this->assertSame('image/gif', Core::image_mime((string) base64_decode(self::GIF)));
    }

    public function testImageMimeDetectsJpeg(): void
    {
        $this->assertSame('image/jpeg', Core::image_mime((string) base64_decode(self::JPEG)));
    }

    public function testImageMimeDetectsPng(): void
    {
        $this->assertSame('image/png', Core::image_mime((string) base64_decode(self::PNG)));
    }

    /**
     * The reason this helper exists: callers used to build the mime from the uploaded filename, so
     * png bytes named `cover.jpg` were stored as `image/jpg`. The bytes decide, not the name.
     */
    public function testImageMimeIgnoresAMisleadingFilename(): void
    {
        $this->assertSame('image/png', Core::image_mime((string) base64_decode(self::PNG)));
        $this->assertNotSame('image/jpg', Core::image_mime((string) base64_decode(self::PNG)));
    }

    public function testImageMimeReturnsNullForEmptyData(): void
    {
        $this->assertNull(Core::image_mime(''));
    }

    public function testImageMimeReturnsNullForNonImageData(): void
    {
        $this->assertNull(Core::image_mime('this is not an image'));
    }
}
