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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ArtCollectorTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private ContainerInterface&MockObject $dic;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private LoggerInterface&MockObject $logger;
    private ArtCollector $subject;

    public function testCollectDelegatesToConfiguredCollectorModule(): void
    {
        $art    = $this->createMock(Art::class);
        $module = $this->createMock(CollectorModuleInterface::class);

        $this->configContainer->method('get')
            ->willReturnMap([
                ['art_order', 'db'],
                ['art_search_limit', 5],
            ]);

        $this->dic->expects(static::once())
            ->method('get')
            ->with(DbCollectorModule::class)
            ->willReturn($module);

        $module->expects(static::once())
            ->method('collectArt')
            ->with($art, 5, ['type' => 'song'])
            ->willReturn([['db' => 1]]);

        $result = $this->subject->collect($art, ['type' => 'song']);

        static::assertSame([['db' => 1]], $result);
    }

    public function testCollectReturnsEmptyArrayWhenArtOrderIsEmpty(): void
    {
        $art = $this->createMock(Art::class);

        $this->configContainer->method('get')
            ->with('art_order')
            ->willReturn(null);

        $this->dic->expects(static::never())
            ->method('get');

        static::assertSame([], $this->subject->collect($art, ['type' => 'song']));
    }

    public function testCollectReturnsEmptyArrayWhenNoOptionsGiven(): void
    {
        $art = $this->createMock(Art::class);

        $this->configContainer->expects(static::never())
            ->method('get');

        static::assertSame([], $this->subject->collect($art));
    }

    protected function setUp(): void
    {
        $this->dic               = $this->createMock(ContainerInterface::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);

        $this->subject = new ArtCollector(
            $this->dic,
            $this->logger,
            $this->configContainer,
            $this->libraryItemLoader,
        );

        // `Plugin` reads its stored version through the `global $dic` bridge, which is not the mock above
        $globalDic         = $this->createMock(ContainerInterface::class);
        $catalogRepository = $this->createMock(CatalogRepositoryInterface::class);
        $catalogRepository->method('getIds')->willReturn([]);

        $globalDic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            UpdateInfoRepositoryInterface::class => $this->createMock(UpdateInfoRepositoryInterface::class),
            CatalogRepositoryInterface::class => $catalogRepository,
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $globalDic;
    }
}
