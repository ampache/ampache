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

namespace Ampache\Repository;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\Album;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class AlbumRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private AlbumRepository $subject;

    /**
     * `Album::build_cache()` fills the artist list for a whole page and `get_parent_ids()` prefers it over a read,
     * so a map write that leaves it in place has every later read in the same request answering with stale artists
     */
    public function testAddAlbumMapForgetsTheCachedArtistList(): void
    {
        Album::add_to_cache('album_artists', 666, [1, 2, 3]);
        self::assertTrue(Album::is_cached('album_artists', 666));

        $this->connection->expects(static::once())
            ->method('query');

        $this->subject->addAlbumMap(666, 'album', 42);

        self::assertFalse(Album::is_cached('album_artists', 666));
    }

    public function testAddAlbumMapInsertsIgnoringDuplicates(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
                [666, 'album', 42]
            );

        $this->subject->addAlbumMap(666, 'album', 42);
    }

    /**
     * A song whose album row is gone used to contribute a NULL id through the outer join, and it sorts first.
     * `while ($albumId = $result->fetchColumn())` reads that as the end of the result, so a single orphaned
     * song emptied the whole album list -- which is what Subsonic and UPnP browse.
     */
    public function testAnOrphanedSongCannotContributeAnAlbumId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(self::stringContains('FROM `song` INNER JOIN `album` ON `album`.`id` = `song`.`album`'))
            ->willReturn($this->createMock(PDOStatement::class));

        $this->subject->getIdsByCatalogs([1]);
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->connection->expects(static::exactly(7))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ["DELETE FROM `album_map` WHERE `object_type` = 'album' AND `album_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL)"],
                    ['DELETE FROM `album_map` WHERE `object_id` NOT IN (SELECT `id` FROM `artist`)'],
                    ['DELETE FROM `album_map` WHERE `album_map`.`album_id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`)'],
                    ["DELETE FROM `album_map` WHERE `album_map`.`album_id` IN (SELECT `album_id` FROM (SELECT DISTINCT `album_map`.`album_id` FROM `album_map` LEFT JOIN `artist_map` ON `artist_map`.`object_type` = `album_map`.`object_type` AND `artist_map`.`artist_id` = `album_map`.`object_id` AND `artist_map`.`object_id` = `album_map`.`album_id` WHERE `artist_map`.`artist_id` IS NULL AND `album_map`.`object_type` = 'album') AS `null_album`)"],
                    ['DELETE FROM `album` WHERE `album`.`id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`) AND `album`.`id` NOT IN (SELECT DISTINCT `album_id` FROM `album_map`)'],
                    ['DELETE FROM `album_disk` WHERE `album_id` NOT IN (SELECT `id` FROM `album`)'],
                    ["SELECT `album_disk`.`id` FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE NOT (`album`.`catalog` = 0 AND `album_disk`.`catalog` = 0) AND NOT EXISTS (SELECT 1 FROM `song` WHERE `song`.`album` = `album_disk`.`album_id` AND `song`.`disk` = `album_disk`.`disk` AND `song`.`catalog` = `album_disk`.`catalog`);"],
                )
            );

        $this->subject->collectGarbage();
    }

    public function testCollectOrphanedAlbumMapsTouchesOnlyTheMapTable(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectOrphanedAlbumMaps();

        foreach ($calls as $sql) {
            self::assertStringStartsWith('DELETE FROM `album_map`', $sql);
        }
    }

    public function testCreateReturnsTheNewId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `album` (`name`, `prefix`, `year`, `mbid`, `mbid_group`, `release_type`, `release_status`, `album_artist`, `original_year`, `barcode`, `catalog_number`, `version`, `catalog`, `addition_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                ['some-album', 'The', 1999, null, null, null, null, 42, null, null, null, null, 7, 123456]
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(666, $this->subject->create($this->createProperties(), 123456));
    }

    public function testCreateReturnsZeroWhenTheInsertFailed(): void
    {
        // the caller reads 0 as "no album" and carries on, so the exception must not escape
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        self::assertSame(0, $this->subject->create($this->createProperties(), 123456));
    }

    public function testCreateStillWritesTheScannedNameAndYearWhenBothAreDroppedFromGroupingConfig(): void
    {
        // name/year aren't nullable, so dropping them from grouping doesn't null them out on create() - the
        // scanned value they're written with is a better default than a placeholder like 0
        AmpConfig::set('album_grouping_fields', 'album_artist', true);

        try {
            $this->connection->expects(static::once())
                ->method('query')
                ->with(
                    'INSERT INTO `album` (`name`, `prefix`, `year`, `mbid`, `mbid_group`, `release_type`, `release_status`, `album_artist`, `original_year`, `barcode`, `catalog_number`, `version`, `catalog`, `addition_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    ['some-album', null, 1999, null, null, null, null, 42, null, null, null, null, 7, 123456]
                );

            $this->connection->expects(static::once())
                ->method('getLastInsertedId')
                ->willReturn(666);

            self::assertSame(666, $this->subject->create($this->createProperties(), 123456));
        } finally {
            AmpConfig::set('album_grouping_fields', null, true);
        }
    }

    public function testCreateWritesNullForFieldsDroppedFromGroupingConfig(): void
    {
        // a column dropped from album_grouping_fields is never stored at all, rather than fixing the new
        // album to whichever song happened to create it
        AmpConfig::set('album_grouping_fields', 'name,year,prefix,mbid,mbid_group,album_artist,release_type,release_status,original_year,catalog_number,version', true);

        try {
            $this->connection->expects(static::once())
                ->method('query')
                ->with(
                    'INSERT INTO `album` (`name`, `prefix`, `year`, `mbid`, `mbid_group`, `release_type`, `release_status`, `album_artist`, `original_year`, `barcode`, `catalog_number`, `version`, `catalog`, `addition_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    ['some-album', 'The', 1999, null, null, null, null, 42, null, null, null, null, 7, 123456]
                );

            $this->connection->expects(static::once())
                ->method('getLastInsertedId')
                ->willReturn(666);

            $properties            = $this->createProperties();
            $properties['barcode'] = '111';

            self::assertSame(666, $this->subject->create($properties, 123456));
        } finally {
            AmpConfig::set('album_grouping_fields', null, true);
        }
    }

    public function testDeleteDeletes(): void
    {
        $album = $this->createMock(Album::class);

        $albumId = 666;

        $album->expects(static::once())
            ->method('getId')
            ->willReturn($albumId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `album` WHERE `id` = ?',
                [$albumId]
            );

        $this->subject->delete($album);
    }

    public function testDeleteEmptyCarriesOnAfterAFailedStatement(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;
                if (count($calls) === 1) {
                    throw new QueryFailedException('nope');
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->logger->expects(static::once())
            ->method('warning');

        $this->subject->deleteEmpty(666);

        self::assertSame(
            [
                'DELETE FROM `album` WHERE `id` = ?',
                'DELETE FROM `album_map` WHERE `album_id` = ?',
                "DELETE FROM `artist_map` WHERE `object_id` = ? AND `object_type` = 'album'",
            ],
            $calls
        );
    }

    public function testSetSongsEnabledCarriesTheAlbumStateDownToEverySong(): void
    {
        $bound = [];

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use (&$bound): PDOStatement {
                self::assertStringContainsString('UPDATE `song` SET `enabled` = ? WHERE `album` = ?', $sql);
                $bound[] = $params;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->setSongsEnabled(666, false);
        $this->subject->setSongsEnabled(666, true);

        // the tell that the cascade runs both ways: the same statement carries 0 and then 1
        self::assertSame([[0, 666], [1, 666]], $bound);
    }

    public function testUpdateAllCountsRunsTheWholeSweepEvenWhenOneStatementFails(): void
    {
        // a maintenance statement that dies must not take the rest of the sweep with it, as `Dba::write()` did not
        $this->connection->expects(static::exactly(16))
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        $this->subject->updateAllCounts();
    }

    public function testUpdateAllSkipCountsRollsUpBothAlbumAndAlbumDisk(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateAllSkipCounts();

        self::assertStringStartsWith('UPDATE `album`,', $calls[0]);
        self::assertStringStartsWith('UPDATE `album_disk`,', $calls[1]);
    }

    public function testUpdateAllSkipCountsSumsTheWholeAlbumButGroupsTheDisk(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateAllSkipCounts();

        // grouping the album rollup by disk would give a multi-disk album one disk's total
        self::assertStringContainsString('GROUP BY `song`.`album`)', $calls[0]);
        self::assertStringContainsString('GROUP BY `song`.`album`, `song`.`disk`)', $calls[1]);
    }

    public function testUpdateCountsBindsTheAlbumIntoEveryStatement(): void
    {
        $bound = [];

        $this->connection->expects(static::exactly(15))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use (&$bound): PDOStatement {
                $bound[] = $params;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCounts(666);

        foreach ($bound as $params) {
            self::assertSame([666], array_unique($params));
        }
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new AlbumRepository(
            $this->connection,
            $this->logger,
        );

        // the object cache is a process-wide static, so a leftover entry would leak between tests
        Album::clear_cache();
    }

    /**
     * @return array{name: string, prefix: ?string, year: int, mbid: ?string, mbid_group: ?string, release_type: ?string, release_status: ?string, album_artist: ?int, original_year: ?string, barcode: ?string, catalog_number: ?string, version: ?string, catalog: int}
     */
    private function createProperties(): array
    {
        return [
            'name' => 'some-album',
            'prefix' => 'The',
            'year' => 1999,
            'mbid' => null,
            'mbid_group' => null,
            'release_type' => null,
            'release_status' => null,
            'album_artist' => 42,
            'original_year' => null,
            'barcode' => null,
            'catalog_number' => null,
            'version' => null,
            'catalog' => 7,
        ];
    }
}
