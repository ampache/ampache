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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumFieldEnum;
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
                    ["SELECT `id` FROM `album_disk` WHERE CONCAT(`album_id`, '_', `disk`) NOT IN (SELECT CONCAT(`album`, '_', `disk`) AS `id` FROM `song`);"],
                )
            );

        $this->subject->collectGarbage();
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

        static::assertSame(666, $this->subject->create($this->createProperties(), 123456));
    }

    public function testCreateReturnsZeroWhenTheInsertFailed(): void
    {
        // the caller reads 0 as "no album" and carries on, so the exception must not escape
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertSame(0, $this->subject->create($this->createProperties(), 123456));
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

    public function testFindByPropertiesMatchesUnsetPropertiesAgainstNull(): void
    {
        // an unset property has to be matched as NULL, or a partially tagged release collides with a fully tagged one
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT DISTINCT(`album`.`id`) AS `id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`year` = ? AND `album`.`prefix` = ? AND `album`.`mbid` IS NULL AND `album`.`mbid_group` IS NULL AND `album`.`album_artist` = ? AND `album`.`release_type` IS NULL AND `album`.`release_status` IS NULL AND `album`.`original_year` IS NULL AND `album`.`barcode` IS NULL AND `album`.`catalog_number` IS NULL AND `album`.`version` IS NULL AND `album`.`catalog` = ?;",
                ['some-album', 'some-album', 1999, 'The', 42, 7]
            )
            ->willReturn('666');

        static::assertSame(666, $this->subject->findByProperties($this->createProperties()));
    }

    public function testFindByPropertiesReturnsNullWhenNothingMatched(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::assertNull($this->subject->findByProperties($this->createProperties()));
    }

    public function testGetAlbumArtistIdReturnsAlbumArtistId(): void
    {
        $albumId = 666;
        $result  = 42;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT DISTINCT `album_artist` FROM `album` WHERE `id` = ?;',
                [$albumId]
            )
            ->willReturn((string) $result);

        self::assertSame(
            $result,
            $this->subject->getAlbumArtistId($albumId)
        );
    }

    public function testGetAlbumArtistIdReturnsNullIfNotFound(): void
    {
        $albumId = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT DISTINCT `album_artist` FROM `album` WHERE `id` = ?;',
                [$albumId]
            )
            ->willReturn(false);

        self::assertNull(
            $this->subject->getAlbumArtistId($albumId)
        );
    }

    public function testGetArtistMapReturnsArtistList(): void
    {
        $album  = $this->createMock(Album::class);
        $result = $this->createMock(PDOStatement::class);

        $objectType = 'some-object';
        $albumId    = 666;
        $artistId   = 42;

        $album->expects(static::once())
            ->method('getId')
            ->willReturn($albumId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `object_id` FROM `album_map` WHERE `object_type` = ? AND `album_id` = ?',
                [$objectType, $albumId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $artistId, false);

        self::assertSame(
            [$artistId],
            $this->subject->getArtistMap($album, $objectType)
        );
    }

    public function testGetByMbidGroupReturnsData(): void
    {
        $musicBrainzId = '1234';
        $albumId       = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `album`.`id` FROM `album` WHERE `album`.`mbid_group` = ?',
                [$musicBrainzId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $albumId, false);

        self::assertSame(
            [$albumId],
            $this->subject->getByMbidGroup($musicBrainzId)
        );
    }

    public function testGetByName(): void
    {
        $albumId  = 666;
        $name     = 'some-name';
        $artistId = 1234;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                "SELECT `album`.`id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`album_artist` = ?",
                [$name, $name, $artistId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $albumId, false);

        self::assertSame(
            [$albumId],
            $this->subject->getByName($name, $artistId)
        );
    }

    public function testGetNamesReturnsArrayWithDefaultsIfEmpty(): void
    {
        $albumId = 666;

        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                "SELECT `album`.`prefix`, `album`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `name` FROM `album` WHERE `id` = ?",
                [$albumId]
            )
            ->willReturn(false);

        self::assertSame(
            [
                'prefix' => '',
                'basename' => '',
                'name' => ''
            ],
            $this->subject->getNames($albumId)
        );
    }

    public function testGetNamesReturnsRow(): void
    {
        $albumId = 666;
        $data    = ['some-data'];

        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with(
                "SELECT `album`.`prefix`, `album`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `name` FROM `album` WHERE `id` = ?",
                [$albumId]
            )
            ->willReturn($data);

        self::assertSame(
            $data,
            $this->subject->getNames($albumId)
        );
    }

    public function testGetRandomSongsReturnsIds(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ORDER BY RAND()',
                [666]
            )
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturn('42', '33', false);

        static::assertSame(
            [42, 33],
            $this->subject->getRandomSongs(666)
        );
    }

    public function testGetSongsByAlbumDiskReturnsIdsInDiskTrackOrder(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`',
                [666]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('42', false);

        static::assertSame(
            [42],
            $this->subject->getSongsByAlbumDisk(666)
        );
    }

    public function testIsOrphanMatchesTheUntranslatedPlaceholderToo(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT `id` FROM `album` WHERE `id` = ? AND (`name` = 'Unknown (Orphaned)' OR `name` = ?);",
                [666, T_('Unknown (Orphaned)')]
            )
            ->willReturn('666');

        static::assertTrue($this->subject->isOrphan(666));
    }

    public function testIsOrphanReturnsFalseWhenNothingMatched(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::assertFalse($this->subject->isOrphan(666));
    }

    public function testRemoveAlbumMapDeletes(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `album_map` WHERE `album_id` = ? AND `object_type` = ? AND `object_id` = ?;',
                [666, 'song', 42]
            );

        $this->subject->removeAlbumMap(666, 'song', 42);
    }

    public function testRemoveUnusedAlbumMapKeepsTheRowWhileTheArtistMapBacksIt(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `artist_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_id` = ? AND `object_type` = ?;',
                [42, 666, 'album']
            )
            ->willReturn('42');

        $this->connection->expects(static::never())->method('query');

        static::assertFalse($this->subject->removeUnusedAlbumMap(666, 'album', 42));
    }

    public function testRemoveUnusedAlbumMapLooksThroughTheSongsForATrackArtist(): void
    {
        // a `song` mapping survives while any track on the album still credits the artist, hence the subquery
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `artist_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_id` IN (SELECT `id` FROM `song` WHERE `album` = ?) AND `object_type` = ?;',
                [42, 666, 'song']
            )
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `album_map` WHERE `album_id` = ? AND `object_type` = ? AND `object_id` = ?;',
                [666, 'song', 42]
            );

        static::assertTrue($this->subject->removeUnusedAlbumMap(666, 'song', 42));
    }

    public function testSetFieldReturnsFalseWhenTheWriteFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertFalse($this->subject->setField(666, AlbumFieldEnum::NAME, 'some-name'));
    }

    public function testSetFieldWritesNullWithoutASpecialStatement(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `album` SET `original_year` = ? WHERE `id` = ?', [null, 666]);

        static::assertTrue($this->subject->setField(666, AlbumFieldEnum::ORIGINAL_YEAR, null));
    }

    public function testSetFieldWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `album` SET `catalog_number` = ? WHERE `id` = ?', ['some-number', 666]);

        static::assertTrue($this->subject->setField(666, AlbumFieldEnum::CATALOG_NUMBER, 'some-number'));
    }

    public function testUpdateAllCountsRunsTheWholeSweepEvenWhenOneStatementFails(): void
    {
        // a maintenance statement that dies must not take the rest of the sweep with it, as `Dba::write()` did not
        $this->connection->expects(static::exactly(14))
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        $this->subject->updateAllCounts();
    }

    public function testUpdateCountsBindsTheAlbumIntoEveryStatement(): void
    {
        $bound = [];

        $this->connection->expects(static::exactly(13))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use (&$bound): PDOStatement {
                $bound[] = $params;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateCounts(666);

        foreach ($bound as $params) {
            static::assertSame([666], array_unique($params));
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
