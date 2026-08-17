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

namespace Ampache\Module\Playlist;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PlaylistLoaderTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;
    private PlaylistLoader $subject;

    public function testLoadByUserIdReturnsEmptyArrayWhenUserHasNoPlaylists(): void
    {
        $userId = 21;
        $user   = $this->createMock(User::class);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $this->modelFactory->expects(static::never())
            ->method('createPlaylist');

        $result = $this->subject->loadByUserId($userId);

        self::assertSame([], $result);
    }

    protected function setUp(): void
    {
        // `Playlist` reads through the `global $dic` bridge
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            PlaylistRepositoryInterface::class => $this->createMock(PlaylistRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;

        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new PlaylistLoader($this->modelFactory);
    }
}
