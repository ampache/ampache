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

namespace Ampache\Module\User\Activity;

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The catalog-membership branch (a real `CatalogItemInterface` object with a catalog id) calls the static,
 * database-backed `User::get_user_catalogs()` and is not covered here for that reason; it is exercised by
 * `NowPlayingViewTest`-style manual verification instead, per the project's DB-free unit test constraint.
 */
class UserActivityAccessCheckerTest extends TestCase
{
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private UserActivityAccessChecker $subject;
    private User&MockObject $viewer;

    public function testIsVisibleToReturnsTrueForAnObjectWithNoCatalogOfItsOwn(): void
    {
        $useractivity              = $this->createMock(Useractivity::class);
        $useractivity->object_type = 'playlist';
        $useractivity->object_id   = 42;

        $libitem = $this->createMock(library_item::class);

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::PLAYLIST, 42)
            ->willReturn($libitem);

        self::assertTrue($this->subject->isVisibleTo($useractivity, $this->viewer));
    }

    public function testIsVisibleToReturnsTrueForAnUnrecognisedObjectType(): void
    {
        $useractivity              = $this->createMock(Useractivity::class);
        $useractivity->object_type = 'user';
        $useractivity->object_id   = 42;

        $this->libraryItemLoader->expects(static::never())
            ->method('load');

        self::assertTrue($this->subject->isVisibleTo($useractivity, $this->viewer));
    }

    public function testIsVisibleToReturnsTrueWhenTheCatalogIdIsNotPositive(): void
    {
        $useractivity              = $this->createMock(Useractivity::class);
        $useractivity->object_type = 'artist';
        $useractivity->object_id   = 42;

        $libitem = $this->createMock(Artist::class);
        $libitem->method('getCatalogId')
            ->willReturn(0);

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::ARTIST, 42)
            ->willReturn($libitem);

        self::assertTrue($this->subject->isVisibleTo($useractivity, $this->viewer));
    }

    public function testIsVisibleToReturnsTrueWhenTheObjectCannotBeLoaded(): void
    {
        $useractivity              = $this->createMock(Useractivity::class);
        $useractivity->object_type = 'song';
        $useractivity->object_id   = 42;

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 42)
            ->willReturn(null);

        self::assertTrue($this->subject->isVisibleTo($useractivity, $this->viewer));
    }

    protected function setUp(): void
    {
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->viewer            = $this->createMock(User::class);

        $this->subject = new UserActivityAccessChecker($this->libraryItemLoader);
    }
}
