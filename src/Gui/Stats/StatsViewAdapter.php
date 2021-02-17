<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Gui\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Catalog\CatalogDetailsInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

final class StatsViewAdapter implements StatsViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private GuiFactoryInterface $guiFactory;

    private VideoRepositoryInterface $videoRepository;

    private CatalogRepositoryInterface $catalogRepository;

    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        ConfigContainerInterface $configContainer,
        GuiFactoryInterface $guiFactory,
        VideoRepositoryInterface $videoRepository,
        CatalogRepositoryInterface $catalogRepository,
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->configContainer   = $configContainer;
        $this->guiFactory        = $guiFactory;
        $this->videoRepository   = $videoRepository;
        $this->catalogRepository = $catalogRepository;
        $this->catalogLoader     = $catalogLoader;
    }

    public function displayVideo(): bool
    {
        return $this->videoRepository->getItemCount(Video::class) > 0;
    }

    public function displayPodcast(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST);
    }

    public function getCatalogStats(): CatalogStatsInterface
    {
        return $this->guiFactory->createCatalogStats(Catalog::get_stats());
    }

    /**
     * @return CatalogDetailsInterface[]
     */
    public function getCatalogDetails(): array
    {
        $catalogs = $this->catalogRepository->getList();

        $result = [];

        foreach ($catalogs as $catalogId) {
            try {
                $catalog = $this->catalogLoader->byId($catalogId);
            } catch (CatalogNotFoundException $e) {
                continue;
            }
            $catalog->format();

            $result[] = $this->guiFactory->createCatalogDetails($catalog);
        }

        return $result;
    }
}
