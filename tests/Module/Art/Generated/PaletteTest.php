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

namespace Ampache\Module\Art\Generated;

use PHPUnit\Framework\TestCase;

/**
 * The colour has to be stable: an album that changed shade between two page loads would look like a
 * different album, and the family effect only works if every release of one artist lands on one pair.
 */
class PaletteTest extends TestCase
{
    public function testDifferentSeedsSpreadAcrossThePalette(): void
    {
        $seen = [];
        foreach (range(1, 60) as $index) {
            $seen[Palette::accentForSeed('artist ' . $index)] = true;
        }

        // a hash that piled every name onto one or two pairs would defeat the point
        $this->assertGreaterThan(4, count($seen));
    }

    public function testEverySeedGivesAUsablePair(): void
    {
        foreach (['a', 'Leyda', 'Ø', '—', str_repeat('x', 500), '0'] as $seed) {
            $pair = Palette::forSeed($seed);

            $this->assertArrayHasKey('ground', $pair);
            $this->assertArrayHasKey('accent', $pair);
            $this->assertArrayHasKey('face', $pair);
            foreach ($pair as $colour) {
                $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $colour);
            }
        }
    }

    public function testTheSameSeedAlwaysGivesTheSamePair(): void
    {
        $first  = Palette::forSeed('Orchestre du Soir');
        $second = Palette::forSeed('Orchestre du Soir');

        $this->assertSame($first, $second);
    }
}
