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
 * Covers the window the panel reads its ranking over.
 *
 * With `cron_cache` on, the ranking is only answered for the windows the statistics task has actually built. A
 * window outside that set returns nothing at all, and the panel then renders as an empty space rather than an
 * error, so a stored value that no longer matches has to be brought back onto the list before it is used.
 */
class AmpachePopularVarietyTest extends TestCase
{
    public function testAnEmptyCacheLeavesTheStoredWindowAlone(): void
    {
        $this->assertSame(30, $this->nearest(30, []));
    }

    public function testAWindowOutsideTheCacheMovesToTheNearestOneInIt(): void
    {
        $this->assertSame(365, $this->nearest(300, [0, 7, 10, 50, 365]));
    }

    public function testAWindowTheCacheHoldsIsUsedAsItIs(): void
    {
        $this->assertSame(50, $this->nearest(50, [0, 7, 10, 50, 365]));
    }

    public function testTheAllTimeWindowIsAsEligibleAsAnyOther(): void
    {
        $this->assertSame(0, $this->nearest(2, [0, 50]));
    }

    public function testTheNearestWindowIsTheClosestOneAndNotSimplyTheSmallest(): void
    {
        $this->assertSame(50, $this->nearest(40, [0, 7, 10, 50, 365]));
    }

    /**
     * @param list<int> $windows
     */
    private function nearest(int $days, array $windows): int
    {
        $method = new ReflectionMethod(AmpachePopularVariety::class, 'nearestWindow');

        return $method->invoke(new AmpachePopularVariety(), $days, $windows);
    }
}
