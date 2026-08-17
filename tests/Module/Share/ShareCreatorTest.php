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

namespace Ampache\Module\Share;

use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShareCreatorTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private PluginRetrieverInterface&MockObject $pluginRetriever;
    private ShareCreator $subject;

    public function testCreatePersistsShareAndReturnsIdWhenNoShortenerPluginAvailable(): void
    {
        $user     = $this->createMock(User::class);
        $userId   = 7;

        $user->method('getId')
            ->willReturn($userId);

        $this->pluginRetriever->expects(static::once())
            ->method('retrieveByType')
            ->willReturnCallback(static function (): iterable {
                yield from [];
            });

        $result = $this->subject->create(
            $user,
            LibraryItemEnum::SONG,
            42,
            true,
            true,
            7,
            'some-secret',
            0,
            'Some description',
        );

        self::assertSame(0, $result);
    }

    public function testCreateReturnsNullForInvalidObjectType(): void
    {
        $user = $this->createMock(User::class);

        $this->logger->expects(static::once())
            ->method('error');

        $this->pluginRetriever->expects(static::never())
            ->method('retrieveByType');

        $result = $this->subject->create($user, LibraryItemEnum::LABEL, 42);

        self::assertNull($result);
    }

    public function testCreateReturnsNullWhenNeitherStreamNorDownloadAllowed(): void
    {
        $user = $this->createMock(User::class);

        $this->logger->expects(static::once())
            ->method('error');

        $this->pluginRetriever->expects(static::never())
            ->method('retrieveByType');

        $result = $this->subject->create($user, LibraryItemEnum::SONG, 42, false, false);

        self::assertNull($result);
    }

    protected function setUp(): void
    {
        $this->pluginRetriever = $this->createMock(PluginRetrieverInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->subject = new ShareCreator(
            $this->pluginRetriever,
            $this->logger,
        );
    }
}
