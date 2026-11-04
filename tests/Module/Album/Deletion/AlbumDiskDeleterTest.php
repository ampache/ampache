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

namespace Ampache\Module\Album\Deletion;

use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Album\Deletion\Exception\AlbumDiskDeletionException;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Repository\AlbumDiskRepositoryInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class AlbumDiskDeleterTest extends TestCase
{
    private AlbumDeleterInterface&MockObject $albumDeleter;
    private AlbumDiskRepositoryInterface&MockObject $albumDiskRepository;
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private CatalogCounterInterface&MockObject $catalogCounter;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockObject $modelFactory;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private SongDeleterInterface&MockObject $songDeleter;
    private SongRepositoryInterface&MockObject $songRepository;
    private AlbumDiskDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testDeleteCascadesToTheAlbumWhenNoDiskIsLeft(): void
    {
        $albumDisk   = $this->createMock(AlbumDisk::class);
        $album       = $this->createMock(Album::class);
        $albumId     = 21;
        $albumDiskId = 7;

        $albumDisk->method('getId')->willReturn($albumDiskId);
        $albumDisk->method('getAlbumId')->willReturn($albumId);

        $this->songRepository->expects(static::once())
            ->method('getByAlbumDisk')
            ->with($albumDiskId)
            ->willReturn([]);

        $this->albumDiskRepository->expects(static::once())
            ->method('delete')
            ->with($albumDisk);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->with($albumId)
            ->willReturn($album);

        $this->albumDiskRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($album)
            ->willReturn([]);

        $this->albumDeleter->expects(static::once())
            ->method('delete')
            ->with($album);

        $this->albumRepository->expects(static::never())
            ->method('updateCounts');

        $this->catalogCounter->expects(static::never())
            ->method('count');

        $deleted = $this->subject->delete($albumDisk);

        self::assertTrue($deleted);
    }

    public function testDeleteRemovesOnlyTheDiskWhenOtherDisksRemain(): void
    {
        $albumDisk   = $this->createMock(AlbumDisk::class);
        $album       = $this->createMock(Album::class);
        $song        = $this->createMock(Song::class);
        $songId      = 42;
        $albumId     = 21;
        $albumDiskId = 7;

        $albumDisk->method('getId')->willReturn($albumDiskId);
        $albumDisk->method('getAlbumId')->willReturn($albumId);

        $this->songRepository->expects(static::once())
            ->method('getByAlbumDisk')
            ->with($albumDiskId)
            ->willReturn([$songId]);

        $this->modelFactory->expects(static::once())
            ->method('createSong')
            ->with($songId)
            ->willReturn($song);

        $this->songDeleter->expects(static::once())
            ->method('delete')
            ->with($song, true)
            ->willReturn(true);

        $this->albumDiskRepository->expects(static::once())
            ->method('delete')
            ->with($albumDisk);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->with($albumId)
            ->willReturn($album);

        $this->albumDiskRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($album)
            ->willReturn([$this->createMock(AlbumDisk::class)]);

        $this->albumDeleter->expects(static::never())
            ->method('delete');

        $this->albumRepository->expects(static::once())
            ->method('updateCounts')
            ->with($albumId);

        $countedTables = [];
        $this->catalogCounter->expects(static::exactly(2))
            ->method('count')
            ->willReturnCallback(static function (CountableTableEnum $table) use (&$countedTables): int {
                $countedTables[] = $table;

                return 0;
            });

        $deleted = $this->subject->delete($albumDisk);

        self::assertFalse($deleted);
        self::assertSame(
            [CountableTableEnum::ALBUM_DISK, CountableTableEnum::SONG],
            $countedTables,
        );
    }

    public function testDeleteThrowsExceptionWhenCascadedAlbumDeletionFails(): void
    {
        $albumDisk = $this->createMock(AlbumDisk::class);
        $album     = $this->createMock(Album::class);

        $albumDisk->method('getId')->willReturn(7);
        $albumDisk->method('getAlbumId')->willReturn(21);

        $this->songRepository->expects(static::once())
            ->method('getByAlbumDisk')
            ->willReturn([]);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->willReturn($album);

        $this->albumDiskRepository->expects(static::once())
            ->method('getByAlbum')
            ->willReturn([]);

        $this->albumDeleter->expects(static::once())
            ->method('delete')
            ->with($album)
            ->willThrowException(new AlbumDeletionException());

        $this->logger->expects(static::once())
            ->method('critical');

        $this->expectException(AlbumDiskDeletionException::class);

        $this->subject->delete($albumDisk);
    }

    public function testDeleteThrowsExceptionWhenSongDeletionFails(): void
    {
        $albumDisk = $this->createMock(AlbumDisk::class);
        $song      = $this->createMock(Song::class);
        $songId    = 42;

        $albumDisk->method('getId')->willReturn(7);
        $albumDisk->method('getAlbumId')->willReturn(21);

        $this->songRepository->expects(static::once())
            ->method('getByAlbumDisk')
            ->with(7)
            ->willReturn([$songId]);

        $this->modelFactory->expects(static::once())
            ->method('createSong')
            ->with($songId)
            ->willReturn($song);

        $this->songDeleter->expects(static::once())
            ->method('delete')
            ->with($song, true)
            ->willReturn(false);

        $this->logger->expects(static::once())
            ->method('critical');

        $this->albumDiskRepository->expects(static::never())
            ->method('delete');

        $this->expectException(AlbumDiskDeletionException::class);

        $this->subject->delete($albumDisk);
    }

    protected function setUp(): void
    {
        $this->dic = $this->createMock(ContainerInterface::class);

        // Rating::garbage_collection() reaches its repository through the `global $dic` bridge
        $this->dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            RatingRepositoryInterface::class => $this->createMock(RatingRepositoryInterface::class),
            UserflagRepositoryInterface::class => $this->createMock(UserflagRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $this->dic;

        $this->albumDeleter           = $this->createMock(AlbumDeleterInterface::class);
        $this->albumDiskRepository    = $this->createMock(AlbumDiskRepositoryInterface::class);
        $this->albumRepository        = $this->createMock(AlbumRepositoryInterface::class);
        $this->modelFactory           = $this->createMock(ModelFactoryInterface::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->songRepository         = $this->createMock(SongRepositoryInterface::class);
        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->songDeleter            = $this->createMock(SongDeleterInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);
        $this->catalogCounter         = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new AlbumDiskDeleter(
            $this->albumDeleter,
            $this->albumDiskRepository,
            $this->albumRepository,
            $this->modelFactory,
            $this->logger,
            $this->songRepository,
            $this->shoutRepository,
            $this->songDeleter,
            $this->userActivityRepository,
            $this->folderRepository,
            $this->catalogCounter,
        );
    }
}
