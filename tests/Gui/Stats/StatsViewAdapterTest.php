<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Gui\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Catalog\CatalogDetailsInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;
use Mockery\MockInterface;

class StatsViewAdapterTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private ?MockInterface $configContainer;

    /** @var GuiFactoryInterface|MockInterface|null */
    private ?MockInterface $guiFactory;

    /** @var MockInterface|VideoRepositoryInterface|null */
    private ?MockInterface $videoRepository;

    /** @var MockInterface|CatalogRepositoryInterface|null */
    private MockInterface $catalogRepository;

    /** @var MockInterface|CatalogLoaderInterface|null */
    private MockInterface $catalogLoader;

    private ?StatsViewAdapter $subject;

    public function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->guiFactory        = $this->mock(GuiFactoryInterface::class);
        $this->videoRepository   = $this->mock(VideoRepositoryInterface::class);
        $this->catalogRepository = $this->mock(CatalogRepositoryInterface::class);
        $this->catalogLoader     = $this->mock(CatalogLoaderInterface::class);

        $this->subject = new StatsViewAdapter(
            $this->configContainer,
            $this->guiFactory,
            $this->videoRepository,
            $this->catalogRepository,
            $this->catalogLoader
        );
    }

    public function testDisplayVideoReturnsTrueIfItemsExist(): void
    {
        $this->videoRepository->shouldReceive('getItemCount')
            ->with(Video::class)
            ->once()
            ->andReturn(42);

        $this->assertTrue(
            $this->subject->displayVideo()
        );
    }

    public function testDisplayPodcastReturnsValue(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->displayPodcast()
        );
    }

    public function testGetCatalogDetailsReturnsListOfCatalogDetailInstances(): void
    {
        $catalogId1 = 666;
        $catalogId2 = 42;

        $catalog        = $this->mock(Catalog::class);
        $catalogDetails = $this->mock(CatalogDetailsInterface::class);

        $this->catalogRepository->shouldReceive('getList')
            ->withNoArgs()
            ->once()
            ->andReturn([$catalogId1, $catalogId2]);

        $this->catalogLoader->shouldReceive('byId')
            ->with($catalogId1)
            ->once()
            ->andThrow(new CatalogNotFoundException());
        $this->catalogLoader->shouldReceive('byId')
            ->with($catalogId2)
            ->once()
            ->andReturn($catalog);

        $catalog->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->guiFactory->shouldReceive('createCatalogDetails')
            ->with($catalog)
            ->once()
            ->andReturn($catalogDetails);

        $this->assertSame(
            [$catalogDetails],
            $this->subject->getCatalogDetails()
        );
    }
}
