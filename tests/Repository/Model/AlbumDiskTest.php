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
 */

namespace Ampache\Repository\Model;

use Ampache\Repository\AlbumDiskRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AlbumDiskTest extends TestCase
{
    private AlbumDiskRepositoryInterface&MockObject $albumDiskRepository;
    private ContainerInterface&MockObject $dic;

    public function testCheckDelegatesToTheRepository(): void
    {
        $this->albumDiskRepository->expects(static::once())
            ->method('check')
            ->with(21, 2, 7, 'some-subtitle', 42)
            ->willReturn(666);

        self::assertSame(
            666,
            AlbumDisk::check(21, 2, 7, 'some-subtitle', 42)
        );
    }

    public function testGetArtistCountDelegatesToTheRepository(): void
    {
        $subject = new AlbumDisk();

        $this->albumDiskRepository->expects(static::once())
            ->method('getArtistCount')
            ->with($subject)
            ->willReturn(3);

        self::assertSame(3, $subject->get_artist_count());
    }

    public function testGetSongsDelegatesToTheRepository(): void
    {
        $subject = new AlbumDisk();

        $this->albumDiskRepository->expects(static::once())
            ->method('getSongs')
            ->with($subject)
            ->willReturn([1, 2]);

        self::assertSame([1, 2], $subject->get_songs());
    }

    public function testIsNewReturnsTrueForAnUnsavedItem(): void
    {
        $subject = new AlbumDisk();

        self::assertTrue($subject->isNew());
        self::assertSame(0, $subject->getId());
        self::assertSame(LibraryItemEnum::ALBUM_DISK, $subject->getMediaType());
    }

    protected function setUp(): void
    {
        $this->albumDiskRepository = $this->createMock(AlbumDiskRepositoryInterface::class);
        $this->dic                 = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(AlbumDiskRepositoryInterface::class)
            ->willReturn($this->albumDiskRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
