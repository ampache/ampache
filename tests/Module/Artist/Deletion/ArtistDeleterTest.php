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

namespace Ampache\Module\Artist\Deletion;

use Ampache\Module\Album\Deletion\AlbumDeleterInterface;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Artist\Deletion\Exception\ArtistDeletionException;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ArtistDeleterTest extends TestCase
{
    private AlbumDeleterInterface&MockObject $albumDeleter;
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private ArtCleanupInterface&MockObject $artCleanup;
    private ArtistRepositoryInterface&MockObject $artistRepository;
    private CatalogCounterInterface&MockObject $catalogCounter;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LabelRepositoryInterface&MockObject $labelRepository;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockObject $modelFactory;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private SongRepositoryInterface&MockObject $songRepository;
    private ArtistDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testRemoveDeletesArtistAndCascadesGarbageCollection(): void
    {
        $artist     = $this->createMock(Artist::class);
        $artist->id = 21;

        $album      = $this->createMock(Album::class);
        $albumId    = 42;
        $artistId   = 21;

        $artist->method('getId')
            ->willReturn($artistId);

        $songId = 84;

        $this->albumRepository->expects(static::once())
            ->method('getAlbumByArtist')
            ->with(21)
            ->willReturn([$albumId]);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->with($albumId)
            ->willReturn($album);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($albumId)
            ->willReturn([$songId]);

        $this->albumDeleter->expects(static::once())
            ->method('delete')
            ->with($album, true);

        $this->artistRepository->expects(static::once())
            ->method('delete')
            ->with($artist);

        $this->songRepository->expects(static::once())
            ->method('collectGarbageForSongs')
            ->with([$songId]);

        $this->albumRepository->expects(static::once())
            ->method('collectGarbageForAlbums')
            ->with([$albumId]);

        $this->artistRepository->expects(static::once())
            ->method('collectGarbageForArtist')
            ->with($artistId);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('artist', $artistId);

        $this->labelRepository->expects(static::once())
            ->method('collectGarbage');

        $this->folderRepository->expects(static::once())
            ->method('collectGarbage');

        $shoutGarbageCalls = [];
        $this->shoutRepository->expects(static::exactly(2))
            ->method('collectGarbage')
            ->willReturnCallback(static function (...$args) use (&$shoutGarbageCalls): void {
                $shoutGarbageCalls[] = $args;
            });

        $userActivityGarbageCalls = [];
        $this->userActivityRepository->expects(static::exactly(2))
            ->method('collectGarbage')
            ->willReturnCallback(static function (...$args) use (&$userActivityGarbageCalls): void {
                $userActivityGarbageCalls[] = $args;
            });

        $this->subject->remove($artist);

        self::assertSame(
            [['album', null], ['artist', $artistId]],
            $shoutGarbageCalls,
        );
        self::assertSame(
            [['album', null], ['artist', $artistId]],
            $userActivityGarbageCalls,
        );
    }

    public function testRemoveThrowsExceptionWhenAlbumDeletionFails(): void
    {
        $artist     = $this->createMock(Artist::class);
        $artist->id = 21;

        $album      = $this->createMock(Album::class);
        $albumId    = 42;

        $this->albumRepository->expects(static::once())
            ->method('getAlbumByArtist')
            ->with(21)
            ->willReturn([$albumId]);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->with($albumId)
            ->willReturn($album);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($albumId)
            ->willReturn([]);

        $this->albumDeleter->expects(static::once())
            ->method('delete')
            ->with($album, true)
            ->willThrowException(new AlbumDeletionException());

        $this->logger->expects(static::once())
            ->method('critical');

        $this->artistRepository->expects(static::never())
            ->method('delete');

        $this->expectException(ArtistDeletionException::class);

        $this->subject->remove($artist);
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
        $this->artistRepository       = $this->createMock(ArtistRepositoryInterface::class);
        $this->albumRepository        = $this->createMock(AlbumRepositoryInterface::class);
        $this->modelFactory           = $this->createMock(ModelFactoryInterface::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->labelRepository        = $this->createMock(LabelRepositoryInterface::class);
        $this->artCleanup             = $this->createMock(ArtCleanupInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);
        $this->songRepository         = $this->createMock(SongRepositoryInterface::class);

        $this->catalogCounter = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new ArtistDeleter(
            $this->albumDeleter,
            $this->artistRepository,
            $this->albumRepository,
            $this->songRepository,
            $this->modelFactory,
            $this->logger,
            $this->shoutRepository,
            $this->userActivityRepository,
            $this->labelRepository,
            $this->artCleanup,
            $this->folderRepository,
            $this->catalogCounter,
        );
    }
}
