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

namespace Ampache\Module\Folder\Deletion;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class FolderDeleterTest extends TestCase
{
    private ArtCleanupInterface&MockObject $artCleanup;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private FolderDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testDeleteRemovesFolderAndCascadesGarbageCollection(): void
    {
        $folder   = $this->createMock(Folder::class);
        $folderId = 21;

        $folder->method('getId')
            ->willReturn($folderId);

        $this->folderRepository->expects(static::once())
            ->method('delete')
            ->with($folderId);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('folder', $folderId);

        $this->shoutRepository->expects(static::once())
            ->method('collectGarbage')
            ->with('folder', $folderId);

        $this->userActivityRepository->expects(static::once())
            ->method('collectGarbage')
            ->with('folder', $folderId);

        $this->folderRepository->expects(static::once())
            ->method('collectGarbage');

        $this->subject->delete($folder);
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

        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->artCleanup             = $this->createMock(ArtCleanupInterface::class);

        $this->subject = new FolderDeleter(
            $this->shoutRepository,
            $this->folderRepository,
            $this->userActivityRepository,
            $this->artCleanup,
        );
    }
}
