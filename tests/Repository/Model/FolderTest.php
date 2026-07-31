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

use Ampache\Repository\FolderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class FolderTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;

    public function testGetChildrenAsksForTheRootWhenTheIdIsMinusOne(): void
    {
        $subject = new Folder();

        $subject->id = -1;

        $this->folderRepository->expects(static::once())
            ->method('getChildren')
            ->with(null)
            ->willReturn([]);

        static::assertSame([], $subject->get_children('some-name'));
    }

    public function testGetChildrenPassesTheIdForARealFolder(): void
    {
        $subject = new Folder();

        $subject->id = 666;

        $this->folderRepository->expects(static::once())
            ->method('getChildren')
            ->with(666)
            ->willReturn([]);

        static::assertSame([], $subject->get_children('some-name'));
    }

    public function testGetMediasDelegatesWithTheFilterType(): void
    {
        $subject = new Folder();

        $subject->id = 666;

        $this->folderRepository->expects(static::once())
            ->method('getMedias')
            ->with($subject, 'song')
            ->willReturn([]);

        static::assertSame([], $subject->get_medias('song'));
    }

    public function testGetNameByIdReturnsAnEmptyStringWhenUnknown(): void
    {
        $this->folderRepository->expects(static::once())
            ->method('getNameById')
            ->with(666)
            ->willReturn(null);

        static::assertSame('', Folder::get_name_by_id(666));
    }

    public function testGetNameByIdSkipsTheLookupForAnEmptyId(): void
    {
        $this->folderRepository->expects(static::never())
            ->method('getNameById');

        static::assertSame('', Folder::get_name_by_id(0));
    }

    public function testHasChildrenDelegatesToTheRepository(): void
    {
        $subject = new Folder();

        $subject->id = 666;

        $this->folderRepository->expects(static::once())
            ->method('hasChildren')
            ->with(666)
            ->willReturn(true);

        static::assertTrue($subject->has_children('some-name'));
    }

    public function testMigrateMovesTheMapRows(): void
    {
        $this->folderRepository->expects(static::once())
            ->method('migrateObject')
            ->with('song', 21, 33);

        Folder::migrate('song', 21, 33);
    }

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(FolderRepositoryInterface::class);
        $this->dic              = $this->createMock(ContainerInterface::class);

        // some of these methods also call debug_event(), which resolves the logger off the same
        // container, so the mock has to answer for more than the repository
        $logger = $this->createMock(LoggerInterface::class);

        $this->dic->method('get')
            ->willReturnCallback(fn(string $id): object => ($id === FolderRepositoryInterface::class)
                ? $this->folderRepository
                : $logger);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
