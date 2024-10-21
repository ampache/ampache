<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Album\AlbumViewAdapter;
use Ampache\Gui\Album\AlbumViewAdapterInterface;
use Ampache\Gui\AlbumDisk\AlbumDiskViewAdapter;
use Ampache\Gui\AlbumDisk\AlbumDiskViewAdapterInterface;
use Ampache\Gui\Catalog\CatalogDetails;
use Ampache\Gui\Catalog\CatalogDetailsInterface;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapter;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapterInterface;
use Ampache\Gui\Playlist\PlaylistViewAdapter;
use Ampache\Gui\Playlist\PlaylistViewAdapterInterface;
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
use Ampache\Module\System\Update\UpdateHelperInterface;
use Ampache\Module\System\Update\UpdaterInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;

final readonly class GuiFactory implements GuiFactoryInterface
{
    public function __construct(private ConfigContainerInterface $configContainer, private ModelFactoryInterface $modelFactory, private ZipHandlerInterface $zipHandler, private FunctionCheckerInterface $functionChecker, private AjaxUriRetrieverInterface $ajaxUriRetriever, private PlaylistLoaderInterface $playlistLoader, private VideoRepositoryInterface $videoRepository, private UpdateInfoRepositoryInterface $updateInfoRepository, private UpdateHelperInterface $updateHelper, private UpdaterInterface $updater)
    {
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

    public function createAlbumViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        Album $album
    ): AlbumViewAdapterInterface {
        return new AlbumViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->zipHandler,
            $this->functionChecker,
            $gatekeeper,
            $browse,
            $album
        );
    }

    public function createAlbumDiskViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        AlbumDisk $albumDisk
    ): AlbumDiskViewAdapterInterface {
        return new AlbumDiskViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->zipHandler,
            $this->functionChecker,
            $gatekeeper,
            $browse,
            $albumDisk
        );
    }

    public function createPlaylistViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Playlist $playlist
    ): PlaylistViewAdapterInterface {
        return new PlaylistViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->zipHandler,
            $this->functionChecker,
            $gatekeeper,
            $playlist
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
            $this->videoRepository
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

    /**
     * @param array<string, int|string> $stats
     */
    public function createCatalogStats(array $stats): CatalogStatsInterface
    {
        return new CatalogStats($stats);
    }

    public function createUpdateViewAdapter(): UpdateViewAdapterInterface
    {
        return new UpdateViewAdapter(
            $this->configContainer,
            $this->updateInfoRepository,
            $this->updateHelper,
            $this->updater
        );
    }

    public function createNewPlaylistDialogAdapter(
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        string $object_id
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
