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
 */

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AlbumDiskRepositoryTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseConnectionInterface&MockObject $connection;
    private ModelFactoryInterface&MockObject $modelFactory;
    private AlbumDiskRepository $subject;

    public function testCheckAdoptsTheCollidingRowWhenTheMoveHitTheUniqueKey(): void
    {
        $currentId  = 42;
        $collidedId = 99;

        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(false, (string) $collidedId);

        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->willReturn(['id' => $currentId, 'disk' => 2]);

        // the song renumbering is skipped because the disk number did not actually change
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException());

        static::assertSame($collidedId, $this->subject->check(21, 2, 7, null, $currentId));
    }

    public function testCheckCreatesTheDiskAndSeedsItsSongCount(): void
    {
        $albumDiskId = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql) use ($matcher): PDOStatement {
                if ($matcher->numberOfInvocations() === 1) {
                    static::assertStringContainsString('REPLACE INTO `album_disk`', $sql);
                } else {
                    static::assertStringContainsString('`song_count` = `song_count` + 1', $sql);
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($albumDiskId);

        static::assertSame($albumDiskId, $this->subject->check(21, 2, 7));
    }

    public function testCheckMatchesOnTheSubtitleWhenOneIsGiven(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                static::stringContains('album_disk.`disksubtitle` = ?'),
                [21, 2, 7, 'some-subtitle']
            )
            ->willReturn('666');

        static::assertSame(666, $this->subject->check(21, 2, 7, 'some-subtitle'));
    }

    public function testCheckMovesTheCurrentDiskAndRenumbersItsSongs(): void
    {
        $currentId = 42;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('fetchRow')
            ->with('SELECT * FROM `album_disk` WHERE `id` = ?;', [$currentId])
            ->willReturn(['id' => $currentId, 'disk' => 1]);

        $matcher = static::exactly(2);

        $this->connection->expects($matcher)
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use ($matcher): PDOStatement {
                if ($matcher->numberOfInvocations() === 1) {
                    static::assertStringContainsString('UPDATE `album_disk` SET `album_id` = ?', $sql);
                } else {
                    // the songs follow the disk they were sitting on, keyed by the pre-move number
                    static::assertStringContainsString('UPDATE `song` SET `disk` = ?', $sql);
                    static::assertSame([2, 21, 1], $params);
                }

                return $this->createMock(PDOStatement::class);
            });

        static::assertSame($currentId, $this->subject->check(21, 2, 7, null, $currentId));
    }

    public function testCheckReturnsTheIdOfAnExistingDisk(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                static::stringContains('OR `album_disk`.`disksubtitle` IS NULL'),
                [21, 2, 7]
            )
            ->willReturn('666');

        $this->connection->expects(static::never())
            ->method('query');

        static::assertSame(666, $this->subject->check(21, 2, 7));
    }

    public function testCheckReturnsZeroWhenTheInsertFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException());

        static::assertSame(0, $this->subject->check(21, 2, 7));
    }

    public function testFindByIdReturnsNullWhenTheDiskDoesNotExist(): void
    {
        $albumDisk = $this->createMock(AlbumDisk::class);
        $albumDisk->method('isNew')->willReturn(true);

        $this->modelFactory->method('createAlbumDisk')->willReturn($albumDisk);

        static::assertNull($this->subject->findById(666));
    }

    public function testFindByIdReturnsTheLoadedDisk(): void
    {
        $albumDisk = $this->createMock(AlbumDisk::class);
        $albumDisk->method('isNew')->willReturn(false);

        $this->modelFactory->expects(static::once())
            ->method('createAlbumDisk')
            ->with(666)
            ->willReturn($albumDisk);

        static::assertSame($albumDisk, $this->subject->findById(666));
    }

    public function testGetArtistCountReturnsTheMappedArtistCount(): void
    {
        $albumDisk = $this->createMock(AlbumDisk::class);

        $albumDisk->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(DISTINCT(`object_id`)) AS `artist_count` FROM `album_map` WHERE `album_id` = ?;',
                [666]
            )
            ->willReturn('3');

        static::assertSame(3, $this->subject->getArtistCount($albumDisk));
    }

    public function testGetByAlbumReturnsAlbumDisksForEachRow(): void
    {
        $album   = $this->createMock(Album::class);
        $albumId = 21;
        $result  = $this->createMock(PDOStatement::class);

        $album->method('getId')
            ->willReturn($albumId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT DISTINCT `id`, `disk` FROM `album_disk` WHERE `album_id` = ? ORDER BY `disk`',
                [$albumId],
            )
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(1, 2, false);

        $albumDisks = $this->subject->getByAlbum($album);

        static::assertCount(2, $albumDisks);
    }

    public function testGetByAlbumReturnsEmptyListWhenNoDisksExist(): void
    {
        $album   = $this->createMock(Album::class);
        $albumId = 21;
        $result  = $this->createMock(PDOStatement::class);

        $album->method('getId')
            ->willReturn($albumId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT DISTINCT `id`, `disk` FROM `album_disk` WHERE `album_id` = ? ORDER BY `disk`',
                [$albumId],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getByAlbum($album));
    }

    public function testGetSongsFiltersDisabledCatalogsWhenConfigured(): void
    {
        $albumDisk = new AlbumDisk();

        $albumDisk->album_id = 21;
        $albumDisk->disk     = 2;

        $result = $this->createMock(PDOStatement::class);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->willReturn(true);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::stringContains('`catalog`.`enabled`'), [21, 2])
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(1, 2, false);

        static::assertSame([1, 2], $this->subject->getSongs($albumDisk));
    }

    public function testGetSongsSkipsTheCatalogJoinWhenNotConfigured(): void
    {
        $albumDisk = new AlbumDisk();

        $albumDisk->album_id = 21;
        $albumDisk->disk     = 2;

        $result = $this->createMock(PDOStatement::class);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`disk` = ?',
                [21, 2]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(false);

        static::assertSame([], $this->subject->getSongs($albumDisk));
    }

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new AlbumDiskRepository(
            $this->connection,
            $this->configContainer,
            $this->modelFactory
        );
    }
}
