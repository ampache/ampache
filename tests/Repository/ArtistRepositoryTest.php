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
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ArtistFieldEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ArtistRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private ArtistRepository $subject;

    public function testAddArtistMapInsertsIgnoringDuplicates(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
                [666, 'song', 42]
            );

        $this->subject->addArtistMap(666, 'song', 42);
    }

    public function testCollectGarbageCleansUp(): void
    {
        $this->connection->expects(static::exactly(5))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = ? AND `artist_map`.`object_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL);'],
                    ['DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = ? AND `artist_map`.`object_id` NOT IN (SELECT `id` FROM `album`);'],
                    ['DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = ? AND `artist_map`.`object_id` NOT IN (SELECT `id` FROM `song`);'],
                    ['DELETE FROM `artist_map` WHERE `artist_map`.`artist_id` NOT IN (SELECT `id` FROM `artist`);'],
                    ['DELETE FROM `artist` WHERE `id` IN (SELECT `id` FROM (SELECT `id` FROM `artist` LEFT JOIN (SELECT DISTINCT `song`.`artist` AS `artist_id` FROM `song` UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id` FROM `album` UNION SELECT DISTINCT `wanted`.`artist` AS `artist_id` FROM `wanted` UNION SELECT DISTINCT `artist_id` FROM `artist_map`) AS `artist_map` ON `artist_map`.`artist_id` = `artist`.`id` WHERE `artist_map`.`artist_id` IS NULL AND `artist`.`user` IS NULL) AS `null_artist`);'],
                )
            );

        $this->subject->collectGarbage();
    }

    public function testCreateReturnsNullWhenTheInsertFailed(): void
    {
        // the caller reads null as "no artist" and gives up, so the exception must not escape
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertNull($this->subject->create('some-artist', null, null, null));
    }

    public function testCreateReturnsTheNewId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `artist` (`name`, `prefix`, `mbid`, `user`) VALUES(?, ?, ?, ?)',
                ['some-artist', 'The', 'some-mbid', 42]
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        static::assertSame(666, $this->subject->create('some-artist', 'The', 'some-mbid', 42));
    }

    public function testDeleteDeletes(): void
    {
        $artistId = 666;

        $artist = $this->createMock(Artist::class);

        $artist->expects(static::once())
            ->method('getId')
            ->willReturn($artistId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `artist` WHERE `id` = ?',
                [$artistId]
            );

        $this->subject->delete($artist);
    }

    public function testFindByNameReturnsNullIfNoEntryWasFound(): void
    {
        $value = 'snafu';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ? ",
                [$value, $value]
            )
            ->willReturn(false);

        self::assertNull(
            $this->subject->findByName($value)
        );
    }

    public function testFindIdByNamePicksTheStatementFromTheMbidFlag(): void
    {
        // the two statements differ only in the mbid predicate, and the caller tries them in that order
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                "SELECT `id` FROM `artist` WHERE `mbid` IS NOT NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id` LIMIT 1;",
                ['some-artist', 'some-artist feat. someone']
            )
            ->willReturn('666');

        static::assertSame(666, $this->subject->findIdByName('some-artist', 'some-artist feat. someone', true));
    }

    public function testGetUploaderIdReturnsZeroWhenTheArtistWasNotUploaded(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::assertSame(0, $this->subject->getUploaderId(666));
    }

    public function testMigrateClearsTheCreditWhenThereIsNoReplacement(): void
    {
        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['UPDATE `song` SET `artist` = NULL WHERE `artist` = ?;', [666]],
                    ['UPDATE `album` SET `album_artist` = NULL WHERE `album_artist` = ?;', [666]],
                    ['DELETE FROM `artist_map` WHERE `artist_id` = ?;', [666]],
                    ["DELETE FROM `album_map` WHERE `object_id` = ? AND `object_type` = 'album';", [666]],
                )
            );

        $this->subject->migrate(666, 0);
    }

    public function testMigrateMovesEverythingOntoTheNewArtist(): void
    {
        $this->connection->expects(static::exactly(6))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['UPDATE `song` SET `artist` = ? WHERE `artist` = ?;', [42, 666]],
                    ['UPDATE `album` SET `album_artist` = ? WHERE `album_artist` = ?;', [42, 666]],
                    ['UPDATE IGNORE `artist_map` SET `artist_id` = ? WHERE `artist_id` = ?;', [42, 666]],
                    ["UPDATE IGNORE `album_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = 'album';", [42, 666]],
                    ['DELETE FROM `artist_map` WHERE `artist_id` = ?;', [666]],
                    ["DELETE FROM `album_map` WHERE `object_id` = ? AND `object_type` = 'album';", [666]],
                )
            );

        $this->subject->migrate(666, 42);
    }

    public function testSetFieldWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `artist` SET `mbid` = ? WHERE `id` = ?', ['some-mbid', 666]);

        static::assertTrue($this->subject->setField(666, ArtistFieldEnum::MBID, 'some-mbid'));
    }

    public function testUpdateInfoStampsTheManualFlagAsAnInt(): void
    {
        // `manual_update` is a tinyint and PDO binds false as an empty string, which MySQL rejects for the column
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ?, `lastfm_url` = ? WHERE `id` = ?',
                ['some-summary', 'some-place', 1999, 123456, 0, null, 666]
            );

        $this->subject->updateInfo(666, 'some-summary', 'some-place', 1999, 123456, false);
    }

    public function testUpdateInfoStoresTheLastFmUrl(): void
    {
        // the url is cached with the rest of the info, so a refresh inside six months still has one to serve
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ?, `lastfm_url` = ? WHERE `id` = ?',
                ['some-summary', 'some-place', 1999, 123456, 0, 'https://www.last.fm/music/Some+Artist', 666]
            );

        $this->subject->updateInfo(666, 'some-summary', 'some-place', 1999, 123456, false, 'https://www.last.fm/music/Some+Artist');
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new ArtistRepository(
            $this->connection
        );
    }
}
