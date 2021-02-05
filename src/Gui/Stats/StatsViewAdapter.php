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
use Ampache\Model\Catalog;
use Ampache\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

final class StatsViewAdapter implements StatsViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private GuiFactoryInterface $guiFactory;

    private VideoRepositoryInterface $videoRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        GuiFactoryInterface $guiFactory,
        VideoRepositoryInterface $videoRepository
    ) {
        $this->configContainer = $configContainer;
        $this->guiFactory      = $guiFactory;
        $this->videoRepository = $videoRepository;
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
        $catalogs = Catalog::get_catalogs();
        
        $result = [];
        
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $catalog->format();
            
            $result[] = $this->guiFactory->createCatalogDetails($catalog);
        }
        
        return $result;
    }
}
