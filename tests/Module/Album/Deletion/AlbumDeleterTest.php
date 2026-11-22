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

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class AlbumDeleterTest extends TestCase
{
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private ArtCleanupInterface&MockObject $artCleanup;
    private CatalogCounterInterface&MockObject $catalogCounter;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LoggerInterface&MockObject $logger;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private SongRepositoryInterface&MockObject $songRepository;
    private AlbumDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testDeleteRemovesAlbumAndCascadesGarbageCollectionWhenNotAParent(): void
    {
        $album   = $this->createMock(Album::class);
        $songId  = 42;
        $albumId = 21;

        $album->method('getId')
            ->willReturn($albumId);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($albumId)
            ->willReturn([$songId]);

        $this->songRepository->expects(static::once())
            ->method('getRowsByIds')
            ->with([$songId], false)
            ->willReturn([['id' => $songId, 'file' => '', 'total_count' => 5, 'total_skip' => 1, 'time' => 180, 'size' => 2097152, 'enabled' => 1]]);

        $this->songRepository->expects(static::once())
            ->method('deleteByIdsWithArchive')
            ->with([$songId]);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObjects')
            ->with('song', [$songId]);

        $this->albumRepository->expects(static::once())
            ->method('delete')
            ->with($album);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('album', $albumId);

        $this->artCleanup->expects(static::never())
            ->method('collectGarbage');

        $this->songRepository->expects(static::once())
            ->method('collectGarbageForSongs')
            ->with([$songId]);

        $this->albumRepository->expects(static::once())
            ->method('collectGarbageForAlbums')
            ->with([$albumId]);

        $this->folderRepository->expects(static::once())
            ->method('collectGarbageForObjects')
            ->with('song', [$songId => ['total_count' => 5, 'total_skip' => 1, 'time' => 180]]);

        $shoutGarbageCalls = [];
        $this->shoutRepository->expects(static::exactly(2))
            ->method('collectGarbage')
            ->willReturnCallback(static function (...$args) use (&$shoutGarbageCalls): void {
                $shoutGarbageCalls[] = $args;
            });

        $useractivityGarbageCalls = [];
        $this->userActivityRepository->expects(static::exactly(2))
            ->method('collectGarbage')
            ->willReturnCallback(static function (...$args) use (&$useractivityGarbageCalls): void {
                $useractivityGarbageCalls[] = $args;
            });

        $this->catalogCounter->expects(static::once())
            ->method('count')
            ->with(CountableTableEnum::ALBUM_DISK);

        $adjustCalls = [];
        $this->catalogCounter->expects(static::exactly(2))
            ->method('adjust')
            ->willReturnCallback(static function (...$args) use (&$adjustCalls): void {
                $adjustCalls[] = $args;
            });

        $this->subject->delete($album);

        self::assertSame(
            [
                [CountableTableEnum::ALBUM, -1, 0, 0.0],
                [CountableTableEnum::SONG, -1, -180, -2.0],
            ],
            $adjustCalls,
        );

        self::assertSame(
            [['song', null], ['album', $albumId]],
            $shoutGarbageCalls,
        );
        self::assertSame(
            [['song', null], ['album', $albumId]],
            $useractivityGarbageCalls,
        );
    }

    public function testDeleteSkipsGarbageCollectionCascadeWhenAParent(): void
    {
        $album   = $this->createMock(Album::class);
        $albumId = 21;

        $album->method('getId')
            ->willReturn($albumId);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($albumId)
            ->willReturn([]);

        $this->songRepository->expects(static::once())
            ->method('getRowsByIds')
            ->with([], false)
            ->willReturn([]);

        $this->songRepository->expects(static::once())
            ->method('deleteByIdsWithArchive')
            ->with([]);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObjects')
            ->with('song', []);

        $this->albumRepository->expects(static::once())
            ->method('delete')
            ->with($album);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('album', $albumId);

        $this->artCleanup->expects(static::never())
            ->method('collectGarbage');

        $this->folderRepository->expects(static::never())
            ->method('collectGarbageForObjects');

        $this->shoutRepository->expects(static::never())
            ->method('collectGarbage');

        $this->userActivityRepository->expects(static::never())
            ->method('collectGarbage');

        $this->subject->delete($album, true);
    }

    public function testDeleteThrowsExceptionWhenSongFileCannotBeDeleted(): void
    {
        $album  = $this->createMock(Album::class);
        $songId = 42;
        // a directory exists but can never be unlink()'d as a file, forcing the failure branch
        $file   = __DIR__;

        $album->method('getId')
            ->willReturn(21);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with(21)
            ->willReturn([$songId]);

        $this->songRepository->expects(static::once())
            ->method('getRowsByIds')
            ->with([$songId], false)
            ->willReturn([['id' => $songId, 'file' => $file]]);

        $this->logger->expects(static::once())
            ->method('critical');

        $this->songRepository->expects(static::never())
            ->method('deleteByIdsWithArchive');

        $this->albumRepository->expects(static::never())
            ->method('delete');

        $this->folderRepository->expects(static::never())
            ->method('collectGarbage');

        $this->expectException(\Ampache\Module\Album\Deletion\Exception\AlbumDeletionException::class);

        @$this->subject->delete($album);
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

        $this->albumRepository        = $this->createMock(AlbumRepositoryInterface::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->songRepository         = $this->createMock(SongRepositoryInterface::class);
        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->artCleanup             = $this->createMock(ArtCleanupInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);

        $this->catalogCounter = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new AlbumDeleter(
            $this->albumRepository,
            $this->logger,
            $this->songRepository,
            $this->shoutRepository,
            $this->userActivityRepository,
            $this->artCleanup,
            $this->folderRepository,
            $this->catalogCounter,
        );
    }
}
