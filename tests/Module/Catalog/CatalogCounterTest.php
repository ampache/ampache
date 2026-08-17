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
use InvalidArgumentException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogCounterTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private CatalogCounter $subject;
    private UpdateInfoRepositoryInterface&MockObject $updateInfoRepository;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testAdjustDoesNothingWhenEveryDeltaIsZero(): void
    {
        $this->connection->expects(static::never())->method('query');
        $this->updateInfoRepository->expects(static::never())->method('setCounts');
        $this->updateInfoRepository->expects(static::never())->method('setCountByKey');

        $this->subject->adjust(CountableTableEnum::SONG, 0);
    }

    public function testAdjustMovesTheStoredTotalsWithoutReadingAnyTable(): void
    {
        $this->connection->expects(static::never())->method('query');

        $stored = [];
        $this->updateInfoRepository->method('setCounts')
            ->willReturnCallback(function (array $counts) use (&$stored): void {
                $stored = array_merge($stored, $counts);
            });
        $this->updateInfoRepository->method('getAllFloatCounts')->willReturn([
            'song' => 41.0,
            'song_time' => 400.0,
            'song_size' => 8.5,
            'video' => 0.0,
            'video_time' => 0.0,
            'video_size' => 0.0,
            'podcast_episode' => 0.0,
            'podcast_episode_time' => 0.0,
            'podcast_episode_size' => 0.0,
        ]);

        $this->subject->adjust(CountableTableEnum::SONG, -1, -200, -4.0);

        self::assertSame(40, $stored['song']);
        self::assertSame(200, $stored['song_time']);
        self::assertSame(4.5, $stored['song_size']);

        // the totals follow from the adjusted contribution, still with no table read
        self::assertSame(40, $stored['items']);
        self::assertSame(200, $stored['time']);
        self::assertSame(4.5, $stored['size']);
    }

    public function testAdjustNeverDrivesATotalBelowZero(): void
    {
        $stored = [];
        $this->updateInfoRepository->method('setCounts')
            ->willReturnCallback(function (array $counts) use (&$stored): void {
                $stored = array_merge($stored, $counts);
            });
        $this->updateInfoRepository->method('getAllFloatCounts')->willReturn([
            'song' => 0.0,
            'song_time' => 0.0,
            'song_size' => 0.0,
            'video' => 0.0,
            'video_time' => 0.0,
            'video_size' => 0.0,
            'podcast_episode' => 0.0,
            'podcast_episode_time' => 0.0,
            'podcast_episode_size' => 0.0,
        ]);

        $this->subject->adjust(CountableTableEnum::SONG, -5, -100, -3.0);

        self::assertSame(0, $stored['song']);
        self::assertSame(0, $stored['song_time']);
        self::assertSame(0.0, $stored['song_size']);
    }

    public function testCountCatalogReachesPodcastEpisodesThroughTheirPodcast(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                'SELECT COUNT(`id`) AS `items`, IFNULL(SUM(`time`), 0) AS `time`, IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `podcast_episode` WHERE `podcast` IN (SELECT `id` FROM `podcast` WHERE `catalog` = ?)',
                [7]
            )
            ->willReturn(['items' => '4', 'time' => '600', 'size' => '12.5']);

        self::assertSame(
            ['items' => 4, 'time' => 600, 'size' => 12],
            $this->subject->countCatalog(7, 'podcast')
        );
    }

    public function testCountCatalogReturnsZeroesWhenThereIsNoRow(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->willReturn(false);

        self::assertSame(
            ['items' => 0, 'time' => 0, 'size' => 0],
            $this->subject->countCatalog(0, 'music')
        );
    }

    public function testCountForCatalogAllowsEveryTableThatCarriesOne(): void
    {
        $this->connection->method('fetchOne')->willReturn('4');

        foreach (CountableTableEnum::cases() as $case) {
            if (!$case->hasCatalogColumn()) {
                continue;
            }

            self::assertSame(4, $this->subject->countForCatalog($case, 7));
        }
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

        self::assertSame(3, $this->subject->countForCatalog(CountableTableEnum::VIDEO, 7, 123456, 100));
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

        self::assertSame(9, $this->subject->countForCatalog(CountableTableEnum::ALBUM, 7));
    }

    public function testCountForCatalogRefusesATableWithNoCatalogOfItsOwn(): void
    {
        // the generated SQL used to name a column that does not exist, so MySQL answered "unknown column"
        $this->connection->expects(static::never())->method('fetchOne');

        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('artist rows do not carry a catalog');

        $this->subject->countForCatalog(CountableTableEnum::ARTIST, 7);
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

        self::assertSame(4, $this->subject->count(CountableTableEnum::LABEL));
    }

    public function testCountReadsOnlyItsOwnTableAndSumsTheRestFromStorage(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn([42, 600, 12.5]);

        // exactly one aggregate: the sibling media tables must not be re-read
        $this->connection->expects(static::once())
            ->method('query')
            ->willReturn($statement);

        $stored = [];
        $this->updateInfoRepository->method('setCounts')
            ->willReturnCallback(function (array $counts) use (&$stored): void {
                $stored = array_merge($stored, $counts);
            });
        $this->updateInfoRepository->method('getAllFloatCounts')->willReturn([
            'song' => 42.0,
            'song_time' => 600.0,
            'song_size' => 12.5,
            'video' => 3.0,
            'video_time' => 100.0,
            'video_size' => 7.5,
            'podcast_episode' => 1.0,
            'podcast_episode_time' => 50.0,
            'podcast_episode_size' => 2.0,
        ]);

        self::assertSame(42, $this->subject->count(CountableTableEnum::SONG));

        self::assertSame(42, $stored['song']);
        self::assertSame(600, $stored['song_time']);
        self::assertSame(12.5, $stored['song_size']);

        // items/time/size are the three stored contributions added together
        self::assertSame(46, $stored['items']);
        self::assertSame(750, $stored['time']);
        self::assertSame(22.0, $stored['size']);
    }

    public function testCountRereadsAMediaTableThatHasNoStoredContributionYet(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn([42, 600, 12.5]);

        // song for the count itself, then video and podcast_episode because neither has been stored
        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturn($statement);

        $this->updateInfoRepository->method('getAllFloatCounts')->willReturn([
            'song' => 42.0,
            'song_time' => 600.0,
            'song_size' => 12.5,
        ]);

        self::assertSame(42, $this->subject->count(CountableTableEnum::SONG));
    }

    public function testCountVideosBindsTheCatalogId(): void
    {
        // the version this replaced wrapped the value in backticks, so any catalog id raised "unknown column"
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(`video`.`id`) FROM `video` WHERE `video`.`catalog` = ?', [7])
            ->willReturn('2');

        self::assertSame(2, $this->subject->countVideos(7));
    }

    public function testCountVideosDropsTheWhereClauseForTheWholeServer(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(`video`.`id`) FROM `video`')
            ->willReturn('3');

        self::assertSame(3, $this->subject->countVideos());
    }

    public function testGetStoredCountReadsTheDatabaseOnlyOncePerKey(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('getCountByKey')
            ->with('song')
            ->willReturn(42);

        self::assertSame(42, $this->subject->getStoredCount('song', 0));
        self::assertSame(42, $this->subject->getStoredCount('song', 0));
    }

    public function testGetStoredCountReadsUserDataForARealUser(): void
    {
        $this->updateInfoRepository->expects(static::never())
            ->method('getCountByKey');

        $this->userRepository->expects(static::once())
            ->method('getUserData')
            ->with(42, 'song')
            ->willReturn(['song' => '7']);

        self::assertSame(7, $this->subject->getStoredCount('song', 42));
    }

    public function testGetStoredCountsCastsTheUserDataValuesOverTheFullShape(): void
    {
        $this->userRepository->expects(static::once())
            ->method('getUserData')
            ->with(42, null)
            ->willReturn(['song' => '7', 'album' => '3']);

        $counts = $this->subject->getStoredCounts(42);

        self::assertSame(7, $counts['song']);
        self::assertSame(3, $counts['album']);
        // a key the user has no row for still comes back, so no caller has to check for it
        self::assertSame(0, $counts['video']);
    }

    public function testSetStoredCountIsWhatTheReadCacheFollows(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('setCountByKey')
            ->with('song', 99);

        $this->updateInfoRepository->expects(static::never())
            ->method('getCountByKey');

        $this->subject->setStoredCount('song', 99);

        self::assertSame(99, $this->subject->getStoredCount('song', 0));
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
