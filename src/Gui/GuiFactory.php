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

declare(strict_types=1);

namespace Ampache\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Catalog\CatalogDetails;
use Ampache\Gui\Catalog\CatalogDetailsInterface;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapter;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapterInterface;
use Ampache\Gui\Song\SongViewAdapter;
use Ampache\Gui\Song\SongViewAdapterInterface;
use Ampache\Gui\Stats\CatalogStats;
use Ampache\Gui\Stats\CatalogStatsInterface;
use Ampache\Gui\Stats\StatsViewAdapter;
use Ampache\Gui\Stats\StatsViewAdapterInterface;
use Ampache\Gui\System\ConfigViewAdapter;
use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\System\UpdateViewAdapter;
use Ampache\Gui\System\UpdateViewAdapterInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;

final class GuiFactory implements GuiFactoryInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private AjaxUriRetrieverInterface $ajaxUriRetriever;

    private PlaylistLoaderInterface $playlistLoader;

    private VideoRepositoryInterface $videoRepository;

    private CatalogRepositoryInterface $catalogRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        AjaxUriRetrieverInterface $ajaxUriRetriever,
        PlaylistLoaderInterface $playlistLoader,
        VideoRepositoryInterface $videoRepository,
        CatalogRepositoryInterface $catalogRepository
    ) {
        $this->configContainer   = $configContainer;
        $this->modelFactory      = $modelFactory;
        $this->ajaxUriRetriever  = $ajaxUriRetriever;
        $this->playlistLoader    = $playlistLoader;
        $this->videoRepository   = $videoRepository;
        $this->catalogRepository = $catalogRepository;
    }

    public function createSongViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Song $song
    ): SongViewAdapterInterface {
        return new SongViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $gatekeeper,
            $song
        );
    }

    public function createConfigViewAdapter(): ConfigViewAdapterInterface
    {
        return new ConfigViewAdapter(
            $this->configContainer
        );
    }

    public function createStatsViewAdapter(): StatsViewAdapterInterface
    {
        return new StatsViewAdapter(
            $this->configContainer,
            $this,
            $this->videoRepository,
            $this->catalogRepository
        );
    }

    public function createCatalogDetails(
        Catalog $catalog
    ): CatalogDetailsInterface {
        return new CatalogDetails(
            $this,
            $catalog
        );
    }

    public function createCatalogStats(array $stats): CatalogStatsInterface
    {
        return new CatalogStats($stats);
    }

    public function createUpdateViewAdapter(): UpdateViewAdapterInterface
    {
        return new UpdateViewAdapter(
            $this->configContainer
        );
    }

    public function createNewPlaylistDialogAdapter(
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        int $object_id
    ): NewPlaylistDialogAdapterInterface {
        return new NewPlaylistDialogAdapter(
            $this->playlistLoader,
            $this->ajaxUriRetriever,
            $gatekeeper,
            $object_type,
            $object_id
        );
    }
}
