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

namespace Ampache\Module\Catalog;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogCounterTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private CatalogCounter $subject;
    private UpdateInfoRepositoryInterface&MockObject $updateInfoRepository;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testCountCatalogReachesPodcastEpisodesThroughTheirPodcast(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                'SELECT COUNT(`id`) AS `items`, IFNULL(SUM(`time`), 0) AS `time`, IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `podcast_episode` WHERE `podcast` IN (SELECT `id` FROM `podcast` WHERE `catalog` = ?)',
                [7]
            )
            ->willReturn(['items' => '4', 'time' => '600', 'size' => '12.5']);

        static::assertSame(
            ['items' => 4, 'time' => 600, 'size' => 12],
            $this->subject->countCatalog(7, 'podcast')
        );
    }

    public function testCountCatalogReturnsZeroesWhenThereIsNoRow(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->willReturn(false);

        static::assertSame(
            ['items' => 0, 'time' => 0, 'size' => 0],
            $this->subject->countCatalog(0, 'music')
        );
    }

    public function testCountCountsTheWholeTableAndStoresTheTotal(): void
    {
        $this->connection->method('fetchOne')->willReturn('42');
        $this->connection->method('query')->willReturn($this->createMock(PDOStatement::class));

        $stored = [];
        $this->updateInfoRepository->method('setCountByKey')
            ->willReturnCallback(function (string $key, float|int $value) use (&$stored): void {
                $stored[$key] = $value;
            });

        static::assertSame(42, $this->subject->count(CountableTableEnum::SONG));

        // a media table's count moves items/time/size with it, so they are stored in the same breath
        static::assertSame(42, $stored['song']);
        static::assertArrayHasKey('items', $stored);
        static::assertArrayHasKey('time', $stored);
        static::assertArrayHasKey('size', $stored);
    }

    public function testCountForCatalogChainsEveryNarrowingWithAnd(): void
    {
        // the version this replaced never moved past WHERE for the update_time clause, so a catalog-less
        // call with an update time produced two WHERE keywords and invalid SQL
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT COUNT(DISTINCT `id`) FROM (SELECT `id` FROM `video` WHERE `video`.`catalog` = ? AND `update_time` <= ? AND `video`.`enabled` = 1 LIMIT 100) AS `table_count`;",
                [7, 123456]
            )
            ->willReturn('3');

        $this->updateInfoRepository->expects(static::never())
            ->method('setCountByKey');

        static::assertSame(3, $this->subject->countForCatalog(CountableTableEnum::VIDEO, 7, 123456, 100));
    }

    public function testCountForCatalogCountsAlbumsThroughTheirSongs(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(`id`) FROM (SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` WHERE `album`.`catalog` = ? AND `song`.`enabled` = 1 ) AS `table_count`;',
                [7]
            )
            ->willReturn('9');

        static::assertSame(9, $this->subject->countForCatalog(CountableTableEnum::ALBUM, 7));
    }

    public function testCountLeavesTheDerivedTotalsAloneForAListTable(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(DISTINCT `id`) FROM (SELECT `id` FROM `label` ) AS `table_count`;',
                []
            )
            ->willReturn('4');

        $this->updateInfoRepository->expects(static::once())
            ->method('setCountByKey')
            ->with('label', 4);

        static::assertSame(4, $this->subject->count(CountableTableEnum::LABEL));
    }

    public function testCountVideosBindsTheCatalogId(): void
    {
        // the version this replaced wrapped the value in backticks, so any catalog id raised "unknown column"
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(`video`.`id`) FROM `video` WHERE `video`.`catalog` = ?', [7])
            ->willReturn('2');

        static::assertSame(2, $this->subject->countVideos(7));
    }

    public function testCountVideosDropsTheWhereClauseForTheWholeServer(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(`video`.`id`) FROM `video`')
            ->willReturn('3');

        static::assertSame(3, $this->subject->countVideos());
    }

    public function testGetStoredCountReadsTheDatabaseOnlyOncePerKey(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('getCountByKey')
            ->with('song')
            ->willReturn(42);

        static::assertSame(42, $this->subject->getStoredCount('song', 0));
        static::assertSame(42, $this->subject->getStoredCount('song', 0));
    }

    public function testGetStoredCountReadsUserDataForARealUser(): void
    {
        $this->updateInfoRepository->expects(static::never())
            ->method('getCountByKey');

        $this->userRepository->expects(static::once())
            ->method('getUserData')
            ->with(42, 'song')
            ->willReturn(['song' => '7']);

        static::assertSame(7, $this->subject->getStoredCount('song', 42));
    }

    public function testGetStoredCountsCastsTheUserDataValuesOverTheFullShape(): void
    {
        $this->userRepository->expects(static::once())
            ->method('getUserData')
            ->with(42, null)
            ->willReturn(['song' => '7', 'album' => '3']);

        $counts = $this->subject->getStoredCounts(42);

        static::assertSame(7, $counts['song']);
        static::assertSame(3, $counts['album']);
        // a key the user has no row for still comes back, so no caller has to check for it
        static::assertSame(0, $counts['video']);
    }

    public function testSetStoredCountIsWhatTheReadCacheFollows(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('setCountByKey')
            ->with('song', 99);

        $this->updateInfoRepository->expects(static::never())
            ->method('getCountByKey');

        $this->subject->setStoredCount('song', 99);

        static::assertSame(99, $this->subject->getStoredCount('song', 0));
    }

    protected function setUp(): void
    {
        $this->connection           = $this->createMock(DatabaseConnectionInterface::class);
        $this->updateInfoRepository = $this->createMock(UpdateInfoRepositoryInterface::class);
        $this->userRepository       = $this->createMock(UserRepositoryInterface::class);

        $this->subject = new CatalogCounter(
            $this->connection,
            $this->updateInfoRepository,
            $this->userRepository
        );
    }
}
