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

namespace Ampache\Gui;

use Ampache\Gui\Album\AlbumRowView;
use Ampache\Gui\Album\AlbumViewAdapterInterface;
use Ampache\Gui\AlbumDisk\AlbumDiskRowView;
use Ampache\Gui\AlbumDisk\AlbumDiskViewAdapterInterface;
use Ampache\Gui\Catalog\CatalogDetailsInterface;
use Ampache\Gui\Collection\CollectionViewAdapterInterface;
use Ampache\Gui\Folder\FolderRowView;
use Ampache\Gui\Folder\FolderViewAdapterInterface;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapterInterface;
use Ampache\Gui\Playlist\PlaylistRowView;
use Ampache\Gui\Playlist\PlaylistViewAdapterInterface;
use Ampache\Gui\Song\SongRowView;
use Ampache\Gui\Song\SongViewAdapterInterface;
use Ampache\Gui\Stats\CatalogStatsInterface;
use Ampache\Gui\Stats\StatsViewAdapterInterface;
use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\System\UpdateViewAdapterInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

interface GuiFactoryInterface
{
    public function createAlbumDiskRowView(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        AlbumDisk $albumDisk,
        bool $usingRatings,
        bool $isHideGenre,
        bool $isHideMood,
        bool $isShowPlayedTimes,
        bool $isShowPlaylistAdd,
        string $classCover,
        string $classAlbum,
        string $classArtist,
        string $classTags,
        string $classMoods,
        string $classCounter,
    ): AlbumDiskRowView;

    public function createAlbumDiskViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        AlbumDisk $albumDisk,
    ): AlbumDiskViewAdapterInterface;

    public function createAlbumRowView(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        Album $album,
        bool $usingRatings,
        bool $isHideGenre,
        bool $isHideMood,
        bool $isShowPlayedTimes,
        bool $isShowPlaylistAdd,
        string $classCover,
        string $classAlbum,
        string $classArtist,
        string $classTags,
        string $classMoods,
        string $classCounter,
    ): AlbumRowView;

    public function createAlbumViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        Album $album,
    ): AlbumViewAdapterInterface;

    public function createCatalogDetails(
        Catalog $catalog,
    ): CatalogDetailsInterface;

    /**
     * @param array<string, int|string> $stats
     */
    public function createCatalogStats(array $stats): CatalogStatsInterface;

    /**
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}> $objectIds
     */
    public function createCollectionViewAdapter(
        BrowseFactoryInterface $browseFactory,
        Collection $collection,
        ?User $user,
        array $objectIds,
    ): CollectionViewAdapterInterface;

    public function createConfigViewAdapter(): ConfigViewAdapterInterface;

    public function createFolderRowView(
        GuiGatekeeperInterface $gatekeeper,
        Folder $folder,
        Podcast_Episode|Video|Song|Folder $object,
        string $object_type,
        bool $usingRatings,
        bool $isShowPlayedTimes,
        bool $isShowPlaylistAdd,
        bool $isShowListAdd,
        string $classCover,
        string $classFolder,
        string $classCounter,
    ): FolderRowView;

    public function createFolderViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Folder $folder,
        Podcast_Episode|Video|Song|Folder $object,
        string $object_type,
    ): FolderViewAdapterInterface;

    public function createNewPlaylistDialogAdapter(
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        string $object_id,
        string $object_groups = '',
    ): NewPlaylistDialogAdapterInterface;

    public function createPlaylistRowView(
        GuiGatekeeperInterface $gatekeeper,
        Playlist $playlist,
        bool $usingRatings,
        bool $isShowArt,
        bool $isShowPlaylistAdd,
        string $classCover,
    ): PlaylistRowView;

    public function createPlaylistViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Playlist $playlist,
    ): PlaylistViewAdapterInterface;

    public function createSongRowView(
        GuiGatekeeperInterface $gatekeeper,
        Song $song,
        string $argumentParam,
        bool $usingRatings,
        bool $isTableView,
        bool $isAlbumGroup,
        bool $isShowTrack,
        bool $isShowLicense,
        bool $isShowComposer,
        bool $isHideGenre,
        bool $isHideMood,
        bool $isHideArtist,
        bool $isHideAlbum,
        bool $isHideYear,
        bool $isHideDrag,
    ): SongRowView;

    public function createSongViewAdapter(
        GuiGatekeeperInterface $gatekeeper,
        Song $song,
    ): SongViewAdapterInterface;

    public function createStatsViewAdapter(): StatsViewAdapterInterface;

    public function createUpdateViewAdapter(): UpdateViewAdapterInterface;
}
