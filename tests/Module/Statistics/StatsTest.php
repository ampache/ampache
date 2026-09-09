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

namespace Ampache\Module\Statistics;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the play-date clamp.
 *
 * Every play date is client-supplied: the `date` parameter on record_play and scrobble, and the `time` a Subsonic
 * client sends. A date in the future never ages out, so one wrong clock pins itself to the top of every recently
 * played list, feed and api until real time catches up. Three writers reach a date column and all three used to
 * take the value as-is, which is why the clamp is pinned here rather than at each call site.
 */
class StatsTest extends TestCase
{
    public function testClockDriftIsKept(): void
    {
        // rewriting the date of a device that is merely off ntp would make the exact-match duplicate guard miss
        $date = time() + 30;

        self::assertSame($date, $this->clamp($date));
    }

    public function testDateBeforeTheEpochBecomesNow(): void
    {
        $this->assertIsNow(-1);
        $this->assertIsNow(-2000000000);
    }

    public function testFutureDateBecomesNow(): void
    {
        $this->assertIsNow(time() + 3600);
        $this->assertIsNow(time() + 31536000);
        $this->assertIsNow(4294967295);
    }

    public function testMissingDateBecomesNow(): void
    {
        $this->assertIsNow(null);
        $this->assertIsNow(0);
    }

    public function testNowIsKept(): void
    {
        $now = time();

        self::assertSame($now, $this->clamp($now));
    }

    public function testPastDateIsKept(): void
    {
        // an offline client catching up on its queue is legitimate, only the future direction is not
        self::assertSame(1, $this->clamp(1));
        self::assertSame(1500000000, $this->clamp(1500000000));
    }

    private function assertIsNow(?int $date): void
    {
        $before = time();
        $result = $this->clamp($date);
        $after  = time();

        self::assertGreaterThanOrEqual($before, $result);
        self::assertLessThanOrEqual($after, $result);
    }

    private function clamp(?int $date): int
    {
        /** @var int $result */
        $result = (new ReflectionMethod(Stats::class, '_clampDate'))->invoke(null, $date);

        return $result;
    }
}
