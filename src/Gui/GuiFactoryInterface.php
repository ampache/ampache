<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Gui\Album\AlbumViewAdapterInterface;
use Ampache\Gui\AlbumDisk\AlbumDiskViewAdapterInterface;
use Ampache\Gui\Catalog\CatalogDetailsInterface;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapterInterface;
use Ampache\Gui\Playlist\PlaylistViewAdapterInterface;
use Ampache\Gui\Song\SongViewAdapterInterface;
use Ampache\Gui\Stats\CatalogStatsInterface;
use Ampache\Gui\Stats\StatsViewAdapterInterface;
use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\System\UpdateViewAdapterInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Module\Authorization\GuiGatekeeperInterface;

interface GuiFactoryInterface
{
    public function createSongViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Song $song
    ): SongViewAdapterInterface;

    public function createAlbumViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        Album $album
    ): AlbumViewAdapterInterface;

    public function createAlbumDiskViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        AlbumDisk $albumDisk
    ): AlbumDiskViewAdapterInterface;

    public function createPlaylistViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Playlist $playlist
    ): PlaylistViewAdapterInterface;

    public function createConfigViewAdapter(): ConfigViewAdapterInterface;

    public function createStatsViewAdapter(): StatsViewAdapterInterface;

    public function createCatalogDetails(
        Catalog $catalog
    ): CatalogDetailsInterface;

    public function createCatalogStats(array $stats): CatalogStatsInterface;

    public function createUpdateViewAdapter(): UpdateViewAdapterInterface;

    public function createNewPlaylistDialogAdapter(
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        string $object_id
    ): NewPlaylistDialogAdapterInterface;
}
