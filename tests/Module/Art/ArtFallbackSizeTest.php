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

namespace Ampache\Module\Art;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A missing cover is served a pre-rendered placeholder. Picking the largest file for an unrecognised size
 * hands a 200x200 thumbnail slot the 1400x1400 original, which is what this guards against.
 */
class ArtFallbackSizeTest extends TestCase
{
    /**
     * @return list<array{0: ?string, 1: string}>
     */
    public static function sizeDataProvider(): array
    {
        return [
            // smaller than every pre-rendered file
            ['48x48', '_128x128'],
            ['128x128', '_128x128'],
            // the size the browse pages ask for
            ['200x200', '_200x200'],
            ['256x256', '_256x256'],
            // no file of its own, so the next one up
            ['300x300', '_384x384'],
            ['768x768', '_768x768'],
            // bigger than anything pre-rendered, and the podcast feeds that want the full size
            ['1400x1400', ''],
            ['original', ''],
            // an unreadable size is treated as zero, so it lands on the smallest file; callers that have no
            // size at all skip this helper and keep the full image
            [null, '_128x128'],
            ['nonsense', '_128x128'],
        ];
    }

    #[DataProvider('sizeDataProvider')]
    public function testFallbackSizePicksTheClosestPreRenderedFile(?string $size, string $expected): void
    {
        self::assertSame($expected, Art::fallback_size($size));
    }

    public function testFallbackSizeReadsTheLargerSideOfANonSquareRequest(): void
    {
        // a wide request still has to fit, so the larger side decides
        self::assertSame('_384x384', Art::fallback_size('380x120'));
    }
}
