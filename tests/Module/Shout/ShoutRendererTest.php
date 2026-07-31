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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoutRendererTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private PrivilegeCheckerInterface&MockObject $privilegeChecker;
    private ShoutObjectLoaderInterface&MockObject $shoutObjectLoader;
    private ShoutRenderer $subject;

    public function testRenderIncludesShoutTextAndPostLinkWhenAllowed(): void
    {
        $shout = $this->createMock(Shoutbox::class);
        $song  = $this->createMock(Song::class);

        $shout->method('getObjectId')->willReturn(42);
        $shout->method('getObjectType')->willReturn(LibraryItemEnum::SONG);
        $shout->method('getText')->willReturn('some shout text');
        $shout->method('getDate')->willReturn(new DateTimeImmutable('2026-01-01'));
        $shout->method('getUser')->willReturn(null);

        $this->shoutObjectLoader->expects(static::once())
            ->method('loadByShout')
            ->with($shout)
            ->willReturn($song);

        $this->configContainer->method('getWebPath')
            ->willReturn('https://example.com');

        $this->privilegeChecker->expects(static::once())
            ->method('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            ->willReturn(true);

        $song->method('get_f_link')->willReturn('Some Song');

        $result = $this->subject->render($shout);

        static::assertStringContainsString('some shout text', $result);
        static::assertStringContainsString('show_add_shout', $result);
        static::assertStringContainsString(T_('Guest'), $result);
    }

    public function testRenderOmitsPostLinkWhenNotAllowed(): void
    {
        $shout = $this->createMock(Shoutbox::class);
        $song  = $this->createMock(Song::class);

        $shout->method('getObjectId')->willReturn(42);
        $shout->method('getObjectType')->willReturn(LibraryItemEnum::SONG);
        $shout->method('getText')->willReturn('some shout text');
        $shout->method('getDate')->willReturn(new DateTimeImmutable('2026-01-01'));
        $shout->method('getUser')->willReturn(null);

        $this->shoutObjectLoader->method('loadByShout')
            ->willReturn($song);

        $this->configContainer->method('getWebPath')
            ->willReturn('https://example.com');

        $this->privilegeChecker->method('check')
            ->willReturn(false);

        $song->method('get_f_link')->willReturn('Some Song');

        $result = $this->subject->render($shout);

        static::assertStringNotContainsString('show_add_shout', $result);
    }

    public function testRenderReturnsEmptyStringWhenObjectCannotBeLoaded(): void
    {
        $shout = $this->createMock(Shoutbox::class);

        $shout->method('getObjectId')->willReturn(42);
        $shout->method('getObjectType')->willReturn(LibraryItemEnum::SONG);

        $this->shoutObjectLoader->expects(static::once())
            ->method('loadByShout')
            ->with($shout)
            ->willReturn(null);

        static::assertSame('', $this->subject->render($shout));
    }

    protected function setUp(): void
    {
        $this->privilegeChecker  = $this->createMock(PrivilegeCheckerInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->shoutObjectLoader = $this->createMock(ShoutObjectLoaderInterface::class);

        $this->subject = new ShoutRenderer(
            $this->privilegeChecker,
            $this->configContainer,
            $this->shoutObjectLoader,
        );
    }
}
