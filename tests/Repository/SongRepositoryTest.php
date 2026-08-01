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
use Ampache\Repository\Model\Catalog;
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

        static::assertTrue($this->subject->delete($songId));
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

        static::assertFalse($this->subject->delete(666));
    }

    public function testFindOwnerIdReturnsFalseWhenTheSongDoesNotExist(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);

        static::assertFalse($this->subject->findOwnerId(666));
    }

    public function testFindOwnerIdReturnsNullWhenTheSongWasNotUploaded(): void
    {
        // distinct from the missing-song case below: this row exists, so an owner check may still downgrade
        $this->connection->method('fetchRow')->willReturn(['user_upload' => null]);

        static::assertNull($this->subject->findOwnerId(666));
    }

    public function testFindOwnerIdReturnsTheUploader(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with('SELECT `user_upload` FROM `song` WHERE `id` = ?', [666])
            ->willReturn(['user_upload' => '42']);

        static::assertSame(42, $this->subject->findOwnerId(666));
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

        static::assertSame(
            [666, 33],
            $this->subject->getByLicense(42)
        );
    }

    public function testSetDataFieldReturnsFalseWhenTheWriteFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertFalse($this->subject->setDataField(666, SongDataFieldEnum::LYRICS, 'some-lyrics'));
    }

    public function testSetDataFieldWritesToSongDataKeyedBySongId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song_data` SET `comment` = ? WHERE `song_id` = ?', ['some-comment', 666]);

        static::assertTrue($this->subject->setDataField(666, SongDataFieldEnum::COMMENT, 'some-comment'));
    }

    public function testSetFieldAcceptsANullValue(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song` SET `license` = ? WHERE `id` = ?', [null, 666]);

        static::assertTrue($this->subject->setField(666, SongFieldEnum::LICENSE, null));
    }

    public function testSetFieldReturnsFalseWhenTheWriteFailed(): void
    {
        // the model's callers branch on this, so the exception must not escape as a fatal
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertFalse($this->subject->setField(666, SongFieldEnum::TITLE, 'some-title'));
    }

    public function testSetFieldWritesTheColumnFromTheEnum(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `song` SET `title` = ? WHERE `id` = ?', ['some-title', 666]);

        static::assertTrue($this->subject->setField(666, SongFieldEnum::TITLE, 'some-title'));
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
