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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

    /**
     * The cron cache is read by the widgets whenever it is warm, so leaving it unfiltered would serve
     * withdrawn releases on exactly the instances that turned the cache on
     */
    public function testTheCachedTopListCarriesTheConditionToo(): void
    {
        $this->bootDic(false);
        AmpConfig::set('cron_cache', true, true);

        self::assertStringContainsString(
            '`id` = `cache_object_count`.`object_id`',
            Stats::get_top_sql('song')
        );

        AmpConfig::set('cron_cache', false, true);
    }

    /**
     * The cache is filled through this very statement and then read by everybody, managers included, so a run
     * that filtered would freeze a truncated top list for the whole instance until the next sweep.
     */
    public function testTheCacheIsWrittenWithoutTheCondition(): void
    {
        $this->bootDic(false);

        $sql = Stats::get_top_sql('song', 0, 'stream', null, false, 0, 0, true);

        self::assertStringNotContainsString('`song`.`enabled`', $sql);
    }

    public function testTheRecentWidgetDropsWithdrawnItemsForAListener(): void
    {
        $this->bootDic(false);

        // this path reads `album` itself, so the flag is stated directly rather than through a subquery
        self::assertStringContainsString('`album`.`enabled` = 1', Stats::get_recent_sql('album'));
        self::assertStringNotContainsString('`album`.`id` = `album`.`id`', Stats::get_recent_sql('album'));
    }

    public function testTheRecentWidgetKeepsWithdrawnItemsForAManager(): void
    {
        $this->bootDic(true);

        self::assertStringNotContainsString('`album`.`enabled` = 1', Stats::get_recent_sql('album'));
    }

    /**
     * The recent widget reads the tables holding `last_played` rather than the play history, and a disk has
     * no flag of its own there either
     */
    public function testTheRecentWidgetOfDisksReadsTheAlbum(): void
    {
        $this->bootDic(false);

        self::assertStringContainsString(
            '`id` = `album_disk`.`album_id`',
            Stats::get_recent_sql('album_disk')
        );
    }

    public function testTheTopListDropsWithdrawnItemsForAListener(): void
    {
        $this->bootDic(false);

        self::assertStringContainsString('`enabled` = 1', Stats::get_top_sql('song'));
    }

    public function testTheTopListKeepsWithdrawnItemsForAManager(): void
    {
        $this->bootDic(true);

        self::assertStringNotContainsString('`enabled` = 1', Stats::get_top_sql('song'));
    }

    /**
     * `$type` has become 'song' by the time the condition is built, so the column it correlates on is the
     * tell: a disk reads the state of its album, never the state of one of its tracks
     */
    public function testTheTopListOfDisksReadsTheAlbumAndNotTheSong(): void
    {
        $this->bootDic(false);

        $sql = Stats::get_top_sql('album_disk');

        self::assertStringContainsString('`id` = `album_disk`.`album_id`', $sql);
        self::assertStringNotContainsString('`song`.`enabled`', $sql);
    }

    private function assertIsNow(?int $date): void
    {
        $before = time();
        $result = $this->clamp($date);
        $after  = time();

        self::assertGreaterThanOrEqual($before, $result);
        self::assertLessThanOrEqual($after, $result);
    }

    private function bootDic(bool $isManager): void
    {
        $privilegeChecker = $this->createMock(PrivilegeCheckerInterface::class);
        $privilegeChecker->method('check')->willReturn($isManager);

        $catalogRepository = $this->createMock(CatalogRepositoryInterface::class);
        $catalogRepository->method('getIds')->willReturn([]);

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            CatalogRepositoryInterface::class => $catalogRepository,
            PrivilegeCheckerInterface::class => $privilegeChecker,
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;
    }

    private function clamp(?int $date): int
    {
        /** @var int $result */
        $result = new ReflectionMethod(Stats::class, '_clampDate')->invoke(null, $date);

        return $result;
    }
}
