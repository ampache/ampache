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
 *
 */

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * get_child()/has_children() are WebDAV's only path-resolution entry point (as opposed to the already
 * catalog_filter-aware directory listing), so this is where a restricted catalog has to be kept out of reach.
 */
class CatalogChildAccessTest extends TestCase
{
    private CatalogFilterRepositoryInterface&MockObject $catalogFilterRepository;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private ?string $priorCatalogFilter = null;

    public function testGetChildAsksNoAccessQuestionForTheVirtualRoot(): void
    {
        AmpConfig::set('catalog_filter', true, true);

        $this->catalogFilterRepository->expects(static::never())->method('hasAccess');

        $folder = Catalog::get_child('/');

        self::assertInstanceOf(Folder::class, $folder);
        self::assertSame(-1, $folder->getId());
    }

    public function testGetChildReturnsNullWhenCatalogFilterDeniesAccess(): void
    {
        AmpConfig::set('catalog_filter', true, true);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);
        $GLOBALS['user'] = $user;

        $folder = $this->createMock(Folder::class);
        $folder->method('getCatalogId')->willReturn(5);
        $this->folderRepository->method('getByName')->willReturn($folder);

        $this->catalogFilterRepository->expects(static::once())
            ->method('hasAccess')
            ->with(5, 42)
            ->willReturn(false);

        self::assertNull(Catalog::get_child('Artist'));
    }

    public function testGetChildReturnsNullWhenNothingMatchesTheName(): void
    {
        $this->folderRepository->method('getByName')->willReturn(null);

        $this->catalogFilterRepository->expects(static::never())->method('hasAccess');

        self::assertNull(Catalog::get_child('ghost'));
    }

    public function testGetChildReturnsTheChildWhenCatalogFilterAllowsAccess(): void
    {
        AmpConfig::set('catalog_filter', true, true);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);
        $GLOBALS['user'] = $user;

        $folder = $this->createMock(Folder::class);
        $folder->method('getCatalogId')->willReturn(5);
        $this->folderRepository->method('getByName')->willReturn($folder);

        $this->catalogFilterRepository->expects(static::once())
            ->method('hasAccess')
            ->with(5, 42)
            ->willReturn(true);

        self::assertSame($folder, Catalog::get_child('Artist'));
    }

    public function testGetChildReturnsTheChildWhenCatalogFilterIsDisabled(): void
    {
        AmpConfig::set('catalog_filter', false, true);

        $folder = $this->createMock(Folder::class);
        $folder->method('getCatalogId')->willReturn(5);
        $this->folderRepository->method('getByName')->willReturn($folder);

        $this->catalogFilterRepository->expects(static::never())->method('hasAccess');

        self::assertSame($folder, Catalog::get_child('Artist'));
    }

    public function testHasChildrenReturnsFalseWhenCatalogFilterDeniesAccess(): void
    {
        AmpConfig::set('catalog_filter', true, true);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);
        $GLOBALS['user'] = $user;

        $folder = $this->createMock(Folder::class);
        $folder->method('getCatalogId')->willReturn(5);
        $folder->method('isNew')->willReturn(false);
        $folder->expects(static::never())->method('has_children');
        $this->folderRepository->method('getByName')->willReturn($folder);

        $this->catalogFilterRepository->expects(static::once())
            ->method('hasAccess')
            ->with(5, 42)
            ->willReturn(false);

        self::assertFalse(Catalog::has_children('Artist'));
    }

    protected function setUp(): void
    {
        $this->priorCatalogFilter = (string) AmpConfig::get('catalog_filter', '');

        $this->folderRepository        = $this->createMock(FolderRepositoryInterface::class);
        $this->catalogFilterRepository = $this->createMock(CatalogFilterRepositoryInterface::class);
        $this->dic                     = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            FolderRepositoryInterface::class => $this->folderRepository,
            CatalogFilterRepositoryInterface::class => $this->catalogFilterRepository,
            default => $this->createMock(LoggerInterface::class),
        });

        // Catalog reaches both repositories through the `global $dic` bridge
        $GLOBALS['dic'] = $this->dic;
    }

    protected function tearDown(): void
    {
        AmpConfig::set('catalog_filter', $this->priorCatalogFilter, true);
        unset($GLOBALS['user']);
    }
}
