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
use ReflectionMethod;

/**
 * Every distinct size that gets asked for is generated and then kept for good. Subsonic lets the client
 * choose the pixel count and display() derives a width from each image's own ratio, so both mint sizes
 * without bound. These two guard the snapping that keeps that set finite.
 */
class ArtCanonicalSizeTest extends TestCase
{
    /**
     * @return list<array{0: int, 1: ?int}>
     */
    public static function canonicalDataProvider(): array
    {
        return [
            // smaller than anything generated
            [1, 64],
            [48, 64],
            // sizes that already exist keep their own value
            [64, 64],
            [200, 200],
            [768, 768],
            [1400, 1400],
            // between two rungs, the larger one, so nothing is ever upscaled to fit
            [100, 128],
            [150, 200],
            [320, 400],
            [500, 512],
            // the common client sizes all land on the size the album page already generates
            [640, 768],
            [666, 768],
            // above every generated size the caller falls back to the original
            [1401, null],
            [3000, null],
        ];
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    public static function expandedDataProvider(): array
    {
        return [
            // already on a step
            [768, 768],
            [1152, 1152],
            // the widths a ratio derived expansion produces, all collapsing onto one stored size
            [998, 1024],
            [1020, 1024],
            [1022, 1024],
            [1024, 1024],
            [844, 864],
            [1, 32],
        ];
    }

    public function testCanonicalSizesAreSortedAscending(): void
    {
        $sorted = Art::CANONICAL_SIZES;
        sort($sorted);

        $this->assertSame($sorted, Art::CANONICAL_SIZES, 'canonical_size returns the first match, so order is the contract');
    }

    #[DataProvider('canonicalDataProvider')]
    public function testCanonicalSizeSnapsUpwards(int $wanted, ?int $expected): void
    {
        $this->assertSame($expected, Art::canonical_size($wanted));
    }

    #[DataProvider('expandedDataProvider')]
    public function testExpandedDimensionsRoundUpToAStep(int $value, int $expected): void
    {
        $method = new ReflectionMethod(Art::class, '_snap_expanded');

        $this->assertSame($expected, $method->invoke(null, $value));
    }
}
