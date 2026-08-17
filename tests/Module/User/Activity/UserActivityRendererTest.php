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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserActivityRendererTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private ModelFactoryInterface&MockObject $modelFactory;
    private UserActivityRenderer $subject;

    public function testShowRendersFollowActivityAgainstTheFollowedUser(): void
    {
        $useractivity                = $this->createMock(Useractivity::class);
        $useractivity->id            = 1;
        $useractivity->user          = 21;
        $useractivity->action        = 'follow';
        $useractivity->object_type   = 'user';
        $useractivity->object_id     = 42;
        $useractivity->activity_date = 1000000000;

        $this->configContainer->method('get')
            ->with('ratings')
            ->willReturn(true);

        $actor = $this->createMock(User::class);
        $actor->method('get_f_link')
            ->willReturn('<a>actor</a>');

        $followed = $this->createMock(User::class);
        $followed->method('isNew')
            ->willReturn(false);
        $followed->method('get_f_link')
            ->willReturn('<a>followed</a>');

        $this->modelFactory->method('createUser')
            ->willReturnMap([[21, $actor], [42, $followed]]);

        $this->libraryItemLoader->expects(static::never())
            ->method('load');

        $result = $this->subject->show($useractivity);

        self::assertStringContainsString('<a>actor</a>', $result);
        self::assertStringContainsString('<a>followed</a>', $result);
    }

    public function testShowReturnsEmptyStringWhenActivityHasNoId(): void
    {
        $useractivity     = $this->createMock(Useractivity::class);
        $useractivity->id = 0;

        $this->configContainer->method('get')
            ->with('ratings')
            ->willReturn(true);

        self::assertSame('', $this->subject->show($useractivity));
    }

    public function testShowReturnsEmptyStringWhenFollowedUserIsGone(): void
    {
        $useractivity              = $this->createMock(Useractivity::class);
        $useractivity->id          = 1;
        $useractivity->user        = 21;
        $useractivity->object_type = 'user';
        $useractivity->object_id   = 42;

        $this->configContainer->method('get')
            ->with('ratings')
            ->willReturn(true);

        $followed = $this->createMock(User::class);
        $followed->method('isNew')
            ->willReturn(true);

        $this->modelFactory->method('createUser')
            ->willReturnMap([[21, $this->createMock(User::class)], [42, $followed]]);

        self::assertSame('', $this->subject->show($useractivity));
    }

    public function testShowReturnsEmptyStringWhenLibraryItemCannotBeLoaded(): void
    {
        $useractivity              = $this->createMock(Useractivity::class);
        $useractivity->id          = 1;
        $useractivity->user        = 21;
        $useractivity->object_type = 'song';
        $useractivity->object_id   = 42;

        $this->configContainer->method('get')
            ->with('ratings')
            ->willReturn(true);

        $user = $this->createMock(User::class);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with(21)
            ->willReturn($user);

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 42)
            ->willReturn(null);

        self::assertSame('', $this->subject->show($useractivity));
    }

    public function testShowReturnsEmptyStringWhenRatingsDisabled(): void
    {
        $useractivity     = $this->createMock(Useractivity::class);
        $useractivity->id = 1;

        $this->configContainer->method('get')
            ->with('ratings')
            ->willReturn(false);

        $this->modelFactory->expects(static::never())
            ->method('createUser');

        self::assertSame('', $this->subject->show($useractivity));
    }

    protected function setUp(): void
    {
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);

        $this->subject = new UserActivityRenderer(
            $this->configContainer,
            $this->modelFactory,
            $this->libraryItemLoader,
        );
    }
}
