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

namespace Ampache\Module\Song\Deletion;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class SongDeleterTest extends TestCase
{
    private ArtCleanupInterface&MockObject $artCleanup;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LoggerInterface&MockObject $logger;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private SongRepositoryInterface&MockObject $songRepository;
    private SongDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testDeleteLogsAndReturnsFalseWhenFileCannotBeUnlinked(): void
    {
        $root = vfsStream::setup('', 0000);
        $file = vfsStream::newFile('song.mp3', 0000);
        $root->addChild($file);

        $song       = $this->createMock(Song::class);
        $song->file = $root->url() . '/song.mp3';

        $this->logger->expects(static::once())
            ->method('critical');

        $this->songRepository->expects(static::never())
            ->method('delete');

        $result = $this->subject->delete($song);

        static::assertFalse($result);
    }

    public function testDeleteRemovesFileAndCascadesGarbageCollectionWhenNotAParent(): void
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('song.mp3');
        $root->addChild($file);

        $song       = $this->createMock(Song::class);
        $song->file = $root->url() . '/song.mp3';
        $songId     = 42;

        $song->method('getId')
            ->willReturn($songId);

        $this->songRepository->expects(static::once())
            ->method('delete')
            ->with($songId)
            ->willReturn(true);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('song', $songId);

        $this->shoutRepository->expects(static::once())
            ->method('collectGarbage')
            ->with('song', $songId);

        $this->userActivityRepository->expects(static::once())
            ->method('collectGarbage')
            ->with('song', $songId);

        $this->songRepository->expects(static::once())
            ->method('collectGarbage')
            ->with($song);

        $this->folderRepository->expects(static::once())
            ->method('collectGarbage');

        $result = $this->subject->delete($song);

        static::assertTrue($result);
        static::assertFalse($root->hasChild('song.mp3'));
    }

    public function testDeleteReturnsFalseWhenRepositoryDeleteFails(): void
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('song.mp3');
        $root->addChild($file);

        $song       = $this->createMock(Song::class);
        $song->file = $root->url() . '/song.mp3';
        $songId     = 42;

        $song->method('getId')
            ->willReturn($songId);

        $this->songRepository->expects(static::once())
            ->method('delete')
            ->with($songId)
            ->willReturn(false);

        $this->artCleanup->expects(static::never())
            ->method('collectGarbageForObject');

        $result = $this->subject->delete($song);

        static::assertFalse($result);
        static::assertFalse($root->hasChild('song.mp3'));
    }

    public function testDeleteSkipsGarbageCollectionCascadeWhenAParent(): void
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('song.mp3');
        $root->addChild($file);

        $song       = $this->createMock(Song::class);
        $song->file = $root->url() . '/song.mp3';
        $songId     = 42;

        $song->method('getId')
            ->willReturn($songId);

        $this->songRepository->expects(static::once())
            ->method('delete')
            ->with($songId)
            ->willReturn(true);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('song', $songId);

        $this->shoutRepository->expects(static::never())
            ->method('collectGarbage');

        $this->userActivityRepository->expects(static::never())
            ->method('collectGarbage');

        $this->songRepository->expects(static::never())
            ->method('collectGarbage');

        $this->folderRepository->expects(static::never())
            ->method('collectGarbage');

        $result = $this->subject->delete($song, true);

        static::assertTrue($result);
    }

    public function testDeleteSkipsUnlinkWhenSongHasNoFile(): void
    {
        $song       = $this->createMock(Song::class);
        $song->file = null;
        $songId     = 42;

        $song->method('getId')
            ->willReturn($songId);

        $this->songRepository->expects(static::once())
            ->method('delete')
            ->with($songId)
            ->willReturn(true);

        $result = $this->subject->delete($song, true);

        static::assertTrue($result);
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

        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->songRepository         = $this->createMock(SongRepositoryInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->artCleanup             = $this->createMock(ArtCleanupInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);

        $this->subject = new SongDeleter(
            $this->logger,
            $this->shoutRepository,
            $this->songRepository,
            $this->userActivityRepository,
            $this->artCleanup,
            $this->folderRepository,
        );
    }
}
