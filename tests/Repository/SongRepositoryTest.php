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

use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\SongDataFieldEnum;
use Ampache\Repository\Model\SongFieldEnum;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class SongRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private SongRepository $subject;

    public function testCollectGarbageForSongsSkipsAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->collectGarbageForSongs([]);
    }

    public function testCollectGarbageForSongsSwallowsAFailedStatement(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with("DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` IN (42,666);")
            ->willThrowException(new QueryFailedException());

        $this->subject->collectGarbageForSongs([42, 666]);
    }

    public function testDeleteByCatalogReportsAFailedDelete(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `song` WHERE `catalog` = ?', [7])
            ->willThrowException(new QueryFailedException('nope'));

        self::assertFalse($this->subject->deleteByCatalog(7));
    }

    public function testDeleteByIdsCastsEveryIdAndSkipsAnEmptyList(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `song` WHERE `id` IN (1,0,3)');

        $this->subject->deleteByIds([1, 'x', 3]);
        $this->subject->deleteByIds([]);
    }

    public function testDeleteRecordsTheDeletionAndRemovesTheSong(): void
    {
        $songId = 666;

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    [
                        'REPLACE INTO `deleted_song` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist` FROM `song` WHERE `id` = ?;',
                        [$songId],
                    ],
                    [
                        'DELETE FROM `song` WHERE `id` = ?',
                        [$songId],
                    ]
                )
            );

        self::assertTrue($this->subject->delete($songId));
    }

    public function testDeleteReturnsFalseWhenTheSongCouldNotBeRemoved(): void
    {
        $calls = 0;

        // the deleted_song record is best effort, so only the delete itself decides the result
        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function () use (&$calls): PDOStatement {
                $calls++;

                if ($calls === 2) {
                    throw new QueryFailedException();
                }

                return $this->createMock(PDOStatement::class);
            });

        self::assertFalse($this->subject->delete(666));
    }

    public function testFindIdByFilePatternTakesTheFirstMatch(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `song` WHERE `file` LIKE ? LIMIT 1', ['http://host/play%oid=7&%'])
            ->willReturn('666');

        self::assertSame(666, $this->subject->findIdByFilePattern('http://host/play%oid=7&%'));
    }

    public function testFindIdsWithMissingAlbumReadsBothTheStaleMapAndTheMissingRow(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id` FROM `song` WHERE (`song`.`album` IN (SELECT `album_id` FROM `album_map` WHERE `album_id` NOT IN (SELECT `id` FROM `album`)) OR `song`.`album` NOT IN (SELECT `id` FROM `album`));')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('666', false);

        self::assertSame([666], $this->subject->findIdsWithMissingAlbum());
    }

    public function testFindOwnerIdReturnsFalseWhenTheSongDoesNotExist(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);

        self::assertFalse($this->subject->findOwnerId(666));
    }

    public function testFindOwnerIdReturnsNullWhenTheSongWasNotUploaded(): void
    {
        // distinct from the missing-song case below: this row exists, so an owner check may still downgrade
        $this->connection->method('fetchRow')->willReturn(['user_upload' => null]);

        self::assertNull($this->subject->findOwnerId(666));
    }

    public function testFindOwnerIdReturnsTheUploader(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with('SELECT `user_upload` FROM `song` WHERE `id` = ?', [666])
            ->willReturn(['user_upload' => '42']);

        self::assertSame(42, $this->subject->findOwnerId(666));
    }

    public function testGetByCatalogReturnsAllItems(): void
    {
        $songId = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `song` ORDER BY `album`, `track`',
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $songId, false);

        self::assertSame(
            [$songId],
            iterator_to_array($this->subject->getByCatalog())
        );
    }

    public function testGetByCatalogReturnsValuesForCatalog(): void
    {
        $songId    = 666;
        $catalogId = 42;

        $result  = $this->createMock(PDOStatement::class);
        $catalog = $this->createMock(Catalog::class);

        $catalog->expects(static::once())
            ->method('getId')
            ->willReturn($catalogId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `song` WHERE `catalog` = ? ORDER BY `album`, `track`',
                [$catalogId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $songId, false);

        self::assertSame(
            [$songId],
            iterator_to_array($this->subject->getByCatalog($catalog))
        );
    }

    public function testGetByLicenseReturnsSongIds(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `song` WHERE `song`.`license` = ?',
                [42]
            )
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturn('666', '33', false);

        self::assertSame(
            [666, 33],
            $this->subject->getByLicense(42)
        );
    }

    public function testGetEnabledIdsByCatalogOrdersOnlyWhenAsked(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled` = '1' ",
                [7]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('666', false);

        self::assertSame([666], $this->subject->getEnabledIdsByCatalog(7));
    }

    public function testGetEnabledIdsJoinsTheCatalogOnlyWhenDisabledCatalogsAreHidden(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with("SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`enabled` = '1' AND `catalog`.`enabled` = '1' AND `song`.`catalog` IN (1,0) ORDER BY `song`.`album`, `song`.`id` LIMIT 10")
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        self::assertSame([], $this->subject->getEnabledIds([1, 'x9'], 10, 0, true));
    }

    public function testGetFileRowsByCatalogCarriesTheTitleAVerifyCompares(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `file`, `title` FROM `song` WHERE `catalog` = ?', [7])
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn(['id' => '1', 'file' => '/a.mp3', 'title' => 'A'], false);

        self::assertSame([['id' => 1, 'file' => '/a.mp3', 'title' => 'A']], $this->subject->getFileRowsByCatalog(7));
    }

    public function testGetFilesByCatalogKeysTheFilesBySongId(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id`, `file` FROM `song` WHERE `catalog` = ? AND `file` IS NOT NULL ORDER BY `id` DESC;',
                [7]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn(['id' => '666', 'file' => '/music/song.mp3'], false);

        self::assertSame([666 => '/music/song.mp3'], $this->subject->getFilesByCatalog(7));
    }

    public function testGetIdsByFilePrefixBindsTheWildcard(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `song` WHERE `file` LIKE ?',
                ["/music/o'brien%"]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        self::assertSame([], $this->subject->getIdsByFilePrefix("/music/o'brien"));
    }

    public function testPruneDeletedHistoryDeletesOlderRows(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `deleted_song` WHERE `delete_time` < (UNIX_TIMESTAMP() - (? * 86400));',
                [365]
            );

        $this->subject->pruneDeletedHistory(365);
    }

    public function testPruneDeletedHistorySkipsWhenDaysIsNotPositive(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->pruneDeletedHistory(0);
        $this->subject->pruneDeletedHistory(-1);
    }

    public function testResetCountsWithoutHistoryMovesEveryCounterAgainstItsOwnCountType(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->resetCountsWithoutHistory();

        // a rebuild join cannot reach a song whose rows are gone, so this is the only thing that moves it
        self::assertStringContainsString('`total_count` = 0', $calls[0]);
        self::assertStringContainsString("`count_type` = 'stream'", $calls[0]);
        self::assertStringContainsString('`total_skip` = 0', $calls[1]);
        self::assertStringContainsString("`count_type` = 'skip'", $calls[1]);
        self::assertSame('UPDATE `song` SET `played` = 0 WHERE `played` = 1 AND `total_count` = 0;', $calls[2]);
        // and back to played when the history is there, which video and podcast_episode always had
        self::assertStringContainsString('`played` = 1', $calls[3]);
    }

    public function testSetDataFieldReturnsFalseWhenTheWriteFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        self::assertFalse($this->subject->setDataField(666, SongDataFieldEnum::LYRICS, 'some-lyrics'));
    }

    public function testSetDataFieldWritesToSongDataKeyedBySongId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song_data` SET `comment` = ? WHERE `song_id` = ?', ['some-comment', 666]);

        self::assertTrue($this->subject->setDataField(666, SongDataFieldEnum::COMMENT, 'some-comment'));
    }

    public function testSetFieldAcceptsANullValue(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song` SET `license` = ? WHERE `id` = ?', [null, 666]);

        self::assertTrue($this->subject->setField(666, SongFieldEnum::LICENSE, null));
    }

    public function testSetFieldReturnsFalseWhenTheWriteFailed(): void
    {
        // the model's callers branch on this, so the exception must not escape as a fatal
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        self::assertFalse($this->subject->setField(666, SongFieldEnum::TITLE, 'some-title'));
    }

    public function testSetFieldWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song` SET `title` = ? WHERE `id` = ?', ['some-title', 666]);

        self::assertTrue($this->subject->setField(666, SongFieldEnum::TITLE, 'some-title'));
    }

    public function testSetFieldWritesTheFileColumn(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song` SET `file` = ? WHERE `id` = ?', ['/new/path.mp3', 666]);

        self::assertTrue($this->subject->setField(666, SongFieldEnum::FILE, '/new/path.mp3'));
    }

    public function testUpdateAllCountsRunsTheThreeSongSweeps(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->updateAllCounts();

        foreach ($calls as $sql) {
            self::assertStringStartsWith('UPDATE `song`', $sql);
        }
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new SongRepository(
            $this->connection,
            $this->logger,
        );
    }
}
