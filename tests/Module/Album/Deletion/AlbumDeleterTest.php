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
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AlbumDeleterTest extends TestCase
{
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private ArtCleanupInterface&MockObject $artCleanup;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockObject $modelFactory;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private SongDeleterInterface&MockObject $songDeleter;
    private SongRepositoryInterface&MockObject $songRepository;
    private AlbumDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testDeleteRemovesAlbumAndCascadesGarbageCollectionWhenNotAParent(): void
    {
        $album   = $this->createMock(Album::class);
        $song    = $this->createMock(Song::class);
        $songId  = 42;
        $albumId = 21;

        $album->method('getId')
            ->willReturn($albumId);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with($albumId)
            ->willReturn([$songId]);

        $this->modelFactory->expects(static::once())
            ->method('createSong')
            ->with($songId)
            ->willReturn($song);

        $this->songDeleter->expects(static::once())
            ->method('delete')
            ->with($song, true)
            ->willReturn(true);

        $this->albumRepository->expects(static::once())
            ->method('delete')
            ->with($album);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('album', $albumId);

        $this->artCleanup->expects(static::never())
            ->method('collectGarbage');

        $this->folderRepository->expects(static::once())
            ->method('collectGarbage');

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

        $this->subject->delete($album);

        static::assertSame(
            [['song', null], ['album', $albumId]],
            $shoutGarbageCalls,
        );
        static::assertSame(
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

        $this->albumRepository->expects(static::once())
            ->method('delete')
            ->with($album);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('album', $albumId);

        $this->artCleanup->expects(static::never())
            ->method('collectGarbage');

        $this->folderRepository->expects(static::never())
            ->method('collectGarbage');

        $this->shoutRepository->expects(static::never())
            ->method('collectGarbage');

        $this->userActivityRepository->expects(static::never())
            ->method('collectGarbage');

        $this->subject->delete($album, true);
    }

    public function testDeleteThrowsExceptionWhenSongDeletionFails(): void
    {
        $album  = $this->createMock(Album::class);
        $song   = $this->createMock(Song::class);
        $songId = 42;

        $album->method('getId')
            ->willReturn(21);

        $this->songRepository->expects(static::once())
            ->method('getByAlbum')
            ->with(21)
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

        $this->albumRepository->expects(static::never())
            ->method('delete');

        $this->folderRepository->expects(static::never())
            ->method('collectGarbage');

        $this->expectException(AlbumDeletionException::class);

        $this->subject->delete($album);
    }

    protected function setUp(): void
    {
        $this->albumRepository        = $this->createMock(AlbumRepositoryInterface::class);
        $this->modelFactory           = $this->createMock(ModelFactoryInterface::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->songRepository         = $this->createMock(SongRepositoryInterface::class);
        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->songDeleter            = $this->createMock(SongDeleterInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->artCleanup             = $this->createMock(ArtCleanupInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);

        $this->subject = new AlbumDeleter(
            $this->albumRepository,
            $this->modelFactory,
            $this->logger,
            $this->songRepository,
            $this->shoutRepository,
            $this->songDeleter,
            $this->userActivityRepository,
            $this->artCleanup,
            $this->folderRepository,
        );
    }
}
