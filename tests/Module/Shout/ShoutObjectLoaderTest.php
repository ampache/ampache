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

namespace Ampache\Module\Shout;

use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoutObjectLoaderTest extends TestCase
{
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private ShoutObjectLoader $subject;

    public function testLoadByObjectTypeReturnsEnabledSong(): void
    {
        $song          = $this->createMock(Song::class);
        $song->enabled = true;

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 42)
            ->willReturn($song);

        self::assertSame($song, $this->subject->loadByObjectType(LibraryItemEnum::SONG, 42));
    }

    public function testLoadByObjectTypeReturnsNullForDisabledSong(): void
    {
        $song          = $this->createMock(Song::class);
        $song->enabled = false;

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 42)
            ->willReturn($song);

        self::assertNull($this->subject->loadByObjectType(LibraryItemEnum::SONG, 42));
    }

    public function testLoadByShoutDelegatesToLoadByObjectType(): void
    {
        $shout         = $this->createMock(Shoutbox::class);
        $song          = $this->createMock(Song::class);
        $song->enabled = true;

        $shout->method('getObjectType')->willReturn(LibraryItemEnum::SONG);
        $shout->method('getObjectId')->willReturn(42);

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 42)
            ->willReturn($song);

        self::assertSame($song, $this->subject->loadByShout($shout));
    }

    protected function setUp(): void
    {
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);

        $this->subject = new ShoutObjectLoader($this->libraryItemLoader);
    }
}
