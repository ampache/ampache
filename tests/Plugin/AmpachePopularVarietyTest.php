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

namespace Ampache\Plugin;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The rule that turns a play count ranking into a panel worth looking at.
 *
 * A ranking on a deep catalogue is won by whichever record someone listened through, so the top of the list
 * is the same cover over and over. Keeping only the first track of each album and of each artist is the whole
 * plugin: get it wrong and the panel is either repetitive again or silently short.
 */
class AmpachePopularVarietyTest extends TestCase
{
    public function testAPerformerAlreadyShownIsSkippedEvenOnAnotherRecord(): void
    {
        $this->assertFalse($this->isFirstOfBoth(2, 7, [1 => true], [7 => true]));
    }

    public function testARecordAlreadyShownIsSkippedEvenWithAnotherPerformer(): void
    {
        $this->assertFalse($this->isFirstOfBoth(1, 9, [1 => true], [7 => true]));
    }

    public function testATrackWithNoPerformerIsJudgedOnItsRecordAlone(): void
    {
        $this->assertTrue($this->isFirstOfBoth(2, null, [1 => true], [7 => true]));
        $this->assertFalse($this->isFirstOfBoth(1, null, [1 => true], [7 => true]));
    }

    public function testBothBeingNewIsWhatLetsATrackThrough(): void
    {
        $this->assertTrue($this->isFirstOfBoth(2, 9, [1 => true], [7 => true]));
    }

    public function testNothingShownYetLetsAnythingThrough(): void
    {
        $this->assertTrue($this->isFirstOfBoth(1, 7, [], []));
    }

    /**
     * @param array<int, true> $albums
     * @param array<int, true> $artists
     */
    private function isFirstOfBoth(int $album, ?int $artist, array $albums, array $artists): bool
    {
        $method = new ReflectionMethod(AmpachePopularVariety::class, 'isFirstOfBoth');

        return $method->invoke(null, $album, $artist, $albums, $artists);
    }
}
