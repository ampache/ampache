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

namespace Ampache\Module\Art;

use PHPUnit\Framework\TestCase;

/**
 * The placeholder a type falls back to. A caller naming its own file drifted from what actually shipped,
 * so a folder was served nothing at all; the name now comes from one place and every name is checked.
 */
class ArtFallbackImageTest extends TestCase
{
    private const array SIZES = ['48x48', '128x128', '200x200', '256x256', '384x384', '768x768', '1400x1400', 'original'];

    private const array TYPES = ['album', 'artist', 'folder', 'podcast', 'podcast_episode', 'live_stream'];

    public function testATypeWithItsOwnPlaceholderKeepsItAndTheOthersShareOne(): void
    {
        self::assertSame('blankfolder', Art::fallback_image_name('folder'));
        self::assertSame('blankpodcast', Art::fallback_image_name('podcast'));
        self::assertSame('blankpodcast', Art::fallback_image_name('podcast_episode'));
        self::assertSame('blankalbum', Art::fallback_image_name('album'));
        self::assertSame('blankalbum', Art::fallback_image_name('anything else'));
    }

    public function testEveryPlaceholderIsShippedInEverySizeItCanBeAskedFor(): void
    {
        $images = __DIR__ . '/../../../public/images/';
        foreach (self::TYPES as $type) {
            $name = Art::fallback_image_name($type);
            foreach (self::SIZES as $size) {
                $file = $images . $name . Art::fallback_size($size) . '.png';
                self::assertFileExists($file, sprintf('%s asked for %s', $type, $size));
            }
        }
    }
}
