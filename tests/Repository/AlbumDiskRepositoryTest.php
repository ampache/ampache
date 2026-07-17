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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Album;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AlbumDiskRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private AlbumDiskRepository $subject;

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

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new AlbumDiskRepository($this->connection);
    }
}
