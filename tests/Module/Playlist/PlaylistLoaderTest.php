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

use Ampache\Repository\ImageRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\PlaylistRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlaylistLoaderTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;
    private PlaylistRepositoryInterface&MockObject $playlistRepository;
    private PlaylistLoader $subject;

    public function testLoadByUserIdBuildsOnePlaylistPerEditableId(): void
    {
        // the menu offers what the user may add to, so the repository decides and the loader only inflates
        $userId    = 21;
        $playlists = [$this->createMock(Playlist::class), $this->createMock(Playlist::class)];

        $this->playlistRepository->expects(static::once())
            ->method('findEditableIds')
            ->with($userId)
            ->willReturn([7, 42]);

        $this->modelFactory->expects(static::exactly(2))
            ->method('createPlaylist')
            ->willReturnCallback(static fn(int $id): Playlist => match ($id) {
                7 => $playlists[0],
                42 => $playlists[1],
                default => throw new RuntimeException('unexpected id ' . $id),
            });

        self::assertSame($playlists, $this->subject->loadByUserId($userId));
    }

    public function testLoadByUserIdNeverAsksTheUserModel(): void
    {
        // access level plays no part any more, so nothing has to be loaded about the user
        $this->playlistRepository->expects(static::once())
            ->method('findEditableIds')
            ->willReturn([]);

        $this->modelFactory->expects(static::never())
            ->method('createUser');

        $this->subject->loadByUserId(21);
    }

    public function testLoadByUserIdReturnsEmptyArrayWhenUserHasNoPlaylists(): void
    {
        $userId = 21;

        $this->playlistRepository->expects(static::once())
            ->method('findEditableIds')
            ->with($userId)
            ->willReturn([]);

        $this->modelFactory->expects(static::never())
            ->method('createPlaylist');

        self::assertSame([], $this->subject->loadByUserId($userId));
    }

    protected function setUp(): void
    {
        // `Playlist` reads through the `global $dic` bridge
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            PlaylistRepositoryInterface::class => $this->createMock(PlaylistRepositoryInterface::class),
            ImageRepositoryInterface::class => $this->createMock(ImageRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;

        $this->modelFactory       = $this->createMock(ModelFactoryInterface::class);
        $this->playlistRepository = $this->createMock(PlaylistRepositoryInterface::class);

        $this->subject = new PlaylistLoader($this->modelFactory, $this->playlistRepository);
    }
}
