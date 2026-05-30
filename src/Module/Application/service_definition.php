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

namespace Ampache\Module\Application;

use Ampache\Module\Application\Admin\Access\AddHostAction;
use Ampache\Module\Application\Admin\Access\DeleteRecordAction;
use Ampache\Module\Application\Admin\Access\ShowAddAction;
use Ampache\Module\Application\Admin\Access\ShowAddAdvancedAction;
use Ampache\Module\Application\Admin\Access\ShowDeleteRecordAction;
use Ampache\Module\Application\Admin\Access\ShowEditRecordAction;
use Ampache\Module\Application\Admin\Access\UpdateRecordAction;
use Ampache\Module\Application\Admin\Catalog\AddCatalogAction;
use Ampache\Module\Application\Admin\Catalog\AddToAllCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\AddToCatalogAction;
use Ampache\Module\Application\Admin\Catalog\CleanAllCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\CleanCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ClearNowPlayingAction;
use Ampache\Module\Application\Admin\Catalog\ClearStatsAction;
use Ampache\Module\Application\Admin\Catalog\DeleteCatalogAction;
use Ampache\Module\Application\Admin\Catalog\EnableDisabledAction;
use Ampache\Module\Application\Admin\Catalog\FullServiceAction;
use Ampache\Module\Application\Admin\Catalog\GarbageCollectAction;
use Ampache\Module\Application\Admin\Catalog\GatherMediaArtAction;
use Ampache\Module\Application\Admin\Catalog\ImportToCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowAddCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\ShowCustomizeCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowDeleteCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowDisabledAction;
use Ampache\Module\Application\Admin\Catalog\UpdateAllCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\UpdateAllFileTagsActions;
use Ampache\Module\Application\Admin\Catalog\UpdateCatalogAction;
use Ampache\Module\Application\Admin\Catalog\UpdateCatalogSettingsAction;
use Ampache\Module\Application\Admin\Catalog\UpdateFileTagsAction;
use Ampache\Module\Application\Admin\Catalog\UpdateFromAction;
use Ampache\Module\Application\Admin\Export\ExportAction;
use Ampache\Module\Application\Admin\Filter\AbstractFilterAction;
use Ampache\Module\Application\Admin\Filter\AddFilterAction;
use Ampache\Module\Application\Admin\Filter\ShowAddFilterAction;
use Ampache\Module\Application\Admin\Filter\ShowEditAction;
use Ampache\Module\Application\Admin\Filter\UpdateFilterAction;
use Ampache\Module\Application\Admin\License\EditAction;
use Ampache\Module\Application\Admin\Mail\SendMailAction;
use Ampache\Module\Application\Admin\Modules\ConfirmInstallCatalogType;
use Ampache\Module\Application\Admin\Modules\ConfirmInstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\ConfirmInstallPluginAction;
use Ampache\Module\Application\Admin\Modules\ConfirmUninstallCatalogType;
use Ampache\Module\Application\Admin\Modules\ConfirmUninstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\ConfirmUninstallPluginAction;
use Ampache\Module\Application\Admin\Modules\InstallCatalogTypeAction;
use Ampache\Module\Application\Admin\Modules\InstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\InstallPluginAction;
use Ampache\Module\Application\Admin\Modules\ShowCatalogTypesAction;
use Ampache\Module\Application\Admin\Modules\ShowLocalplayAction;
use Ampache\Module\Application\Admin\Modules\ShowPluginsAction;
use Ampache\Module\Application\Admin\Modules\UninstallCatalogTypeAction;
use Ampache\Module\Application\Admin\Modules\UninstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\UninstallPluginAction;
use Ampache\Module\Application\Admin\Modules\UpgradePluginAction;
use Ampache\Module\Application\Admin\Shout\EditShoutAction;
use Ampache\Module\Application\Admin\System\ClearCacheAction;
use Ampache\Module\Application\Admin\System\GenerateConfigAction;
use Ampache\Module\Application\Admin\System\ResetDbCharsetAction;
use Ampache\Module\Application\Admin\System\ShowDebugAction;
use Ampache\Module\Application\Admin\System\WriteConfigAction;
use Ampache\Module\Application\Admin\User\DeleteApiKeyAction;
use Ampache\Module\Application\Admin\User\DeleteAvatarAction;
use Ampache\Module\Application\Admin\User\DeleteRssTokenAction;
use Ampache\Module\Application\Admin\User\DeleteStreamTokenAction;
use Ampache\Module\Application\Admin\User\DisableAction;
use Ampache\Module\Application\Admin\User\EnableAction;
use Ampache\Module\Application\Admin\User\GenerateApiKeyAction;
use Ampache\Module\Application\Admin\User\GenerateRssTokenAction;
use Ampache\Module\Application\Admin\User\GenerateStreamTokenAction;
use Ampache\Module\Application\Admin\User\ShowDeleteApiKeyAction;
use Ampache\Module\Application\Admin\User\ShowDeleteAvatarAction;
use Ampache\Module\Application\Admin\User\ShowDeleteRssTokenAction;
use Ampache\Module\Application\Admin\User\ShowDeleteStreamTokenAction;
use Ampache\Module\Application\Admin\User\ShowGenerateApiKeyAction;
use Ampache\Module\Application\Admin\User\ShowGenerateRssTokenAction;
use Ampache\Module\Application\Admin\User\ShowGenerateStreamTokenAction;
use Ampache\Module\Application\Admin\User\ShowIpHistoryAction;
use Ampache\Module\Application\Admin\User\ShowPreferencesAction;
use Ampache\Module\Application\Album\SetTrackNumbersAction;
use Ampache\Module\Application\Album\ShowAction;
use Ampache\Module\Application\Album\ShowMissingAction;
use Ampache\Module\Application\Album\UpdateFromTagsAction;
use Ampache\Module\Application\Art\ClearArtAction;
use Ampache\Module\Application\Art\FindArtAction;
use Ampache\Module\Application\Art\SelectArtAction;
use Ampache\Module\Application\Art\ShowArtDlgAction;
use Ampache\Module\Application\Art\UploadArtAction;
use Ampache\Module\Application\Artist\ShowAllSongsAction;
use Ampache\Module\Application\Artist\ShowSongsAction;
use Ampache\Module\Application\Artist\UpdateFromMusicBrainzAction;
use Ampache\Module\Application\Browse\AlbumAction;
use Ampache\Module\Application\Browse\AlbumArtistAction;
use Ampache\Module\Application\Browse\ArtistAction;
use Ampache\Module\Application\Browse\BroadcastAction;
use Ampache\Module\Application\Browse\CatalogAction;
use Ampache\Module\Application\Browse\FileAction;
use Ampache\Module\Application\Browse\LabelAction;
use Ampache\Module\Application\Browse\LiveStreamAction;
use Ampache\Module\Application\Browse\PlaylistAction;
use Ampache\Module\Application\Browse\PodcastAction;
use Ampache\Module\Application\Browse\PodcastEpisodeAction;
use Ampache\Module\Application\Browse\PrivateMessageAction;
use Ampache\Module\Application\Browse\SmartPlaylistAction;
use Ampache\Module\Application\Browse\SongAction;
use Ampache\Module\Application\Browse\TagAction;
use Ampache\Module\Application\Browse\VideoAction;
use Ampache\Module\Application\DemocraticPlayback\CreateAction;
use Ampache\Module\Application\DemocraticPlayback\ManageAction;
use Ampache\Module\Application\DemocraticPlayback\ManagePlaylistsAction;
use Ampache\Module\Application\DemocraticPlayback\ShowCreateAction;
use Ampache\Module\Application\DemocraticPlayback\ShowPlaylistAction;
use Ampache\Module\Application\Image\ShowUserAvatarAction;
use Ampache\Module\Application\Label\AddLabelAction;
use Ampache\Module\Application\Label\ShowAddLabelAction;
use Ampache\Module\Application\LocalPlay\AddInstanceAction;
use Ampache\Module\Application\LocalPlay\EditInstanceAction;
use Ampache\Module\Application\LocalPlay\ShowAddInstanceAction;
use Ampache\Module\Application\LocalPlay\ShowInstancesAction;
use Ampache\Module\Application\LocalPlay\UpdateInstanceAction;
use Ampache\Module\Application\Logout\LogoutAction;
use Ampache\Module\Application\LostPassword\SendAction;
use Ampache\Module\Application\Mashup\WrappedAction;
use Ampache\Module\Application\Playback\PlayAction;
use Ampache\Module\Application\Playlist\AddSongAction;
use Ampache\Module\Application\Playlist\ImportPlaylistAction;
use Ampache\Module\Application\Playlist\RemoveDuplicatesAction;
use Ampache\Module\Application\Playlist\ShowImportPlaylistAction;
use Ampache\Module\Application\Playlist\SortTrackAction;
use Ampache\Module\Application\Podcast\ExportPodcastsAction;
use Ampache\Module\Application\Preferences\AdminAction;
use Ampache\Module\Application\Preferences\AdminUpdatePreferencesAction;
use Ampache\Module\Application\Preferences\GrantAction;
use Ampache\Module\Application\Preferences\UpdatePreferencesAction;
use Ampache\Module\Application\Preferences\UpdateUserAction;
use Ampache\Module\Application\Preferences\UserAction;
use Ampache\Module\Application\PrivateMessage\AddMessageAction;
use Ampache\Module\Application\PrivateMessage\SetIsReadAction;
use Ampache\Module\Application\PrivateMessage\ShowAddMessageAction;
use Ampache\Module\Application\Random\AdvancedAction;
use Ampache\Module\Application\Random\GetAdvancedAction;
use Ampache\Module\Application\Register\AddUserAction;
use Ampache\Module\Application\Register\ShowAddUserAction;
use Ampache\Module\Application\Register\ValidateAction;
use Ampache\Module\Application\Search\DescriptorAction;
use Ampache\Module\Application\Search\SaveAsPlaylistAction;
use Ampache\Module\Application\Search\SaveAsSmartPlaylistAction;
use Ampache\Module\Application\Search\SearchAction;
use Ampache\Module\Application\Share\CleanAction;
use Ampache\Module\Application\Share\ConsumeAction;
use Ampache\Module\Application\Share\ExternalShareAction;
use Ampache\Module\Application\Share\ShowDeleteAction;
use Ampache\Module\Application\Shout\AddShoutAction;
use Ampache\Module\Application\Shout\ShowAddShoutAction;
use Ampache\Module\Application\SmartPlaylist\CreatePlaylistAction;
use Ampache\Module\Application\SmartPlaylist\DeletePlaylistAction;
use Ampache\Module\Application\SmartPlaylist\RefreshPlaylistAction;
use Ampache\Module\Application\SmartPlaylist\UpdatePlaylistAction;
use Ampache\Module\Application\Song\ConfirmDeleteAction;
use Ampache\Module\Application\Song\DeleteAction;
use Ampache\Module\Application\Song\ShowLyricsAction;
use Ampache\Module\Application\Song\ShowSongAction;
use Ampache\Module\Application\Stats\GraphAction;
use Ampache\Module\Application\Stats\HighestAlbumAction;
use Ampache\Module\Application\Stats\HighestAlbumArtistAction;
use Ampache\Module\Application\Stats\HighestAlbumDiskAction;
use Ampache\Module\Application\Stats\HighestArtistAction;
use Ampache\Module\Application\Stats\HighestPlaylistAction;
use Ampache\Module\Application\Stats\HighestPodcastEpisodeAction;
use Ampache\Module\Application\Stats\HighestSongAction;
use Ampache\Module\Application\Stats\HighestVideoAction;
use Ampache\Module\Application\Stats\NewestAlbumAction;
use Ampache\Module\Application\Stats\NewestAlbumArtistAction;
use Ampache\Module\Application\Stats\NewestAlbumDiskAction;
use Ampache\Module\Application\Stats\NewestArtistAction;
use Ampache\Module\Application\Stats\NewestPlaylistAction;
use Ampache\Module\Application\Stats\NewestPodcastEpisodeAction;
use Ampache\Module\Application\Stats\NewestSongAction;
use Ampache\Module\Application\Stats\NewestVideoAction;
use Ampache\Module\Application\Stats\PopularAlbumAction;
use Ampache\Module\Application\Stats\PopularAlbumArtistAction;
use Ampache\Module\Application\Stats\PopularAlbumDiskAction;
use Ampache\Module\Application\Stats\PopularArtistAction;
use Ampache\Module\Application\Stats\PopularPlaylistAction;
use Ampache\Module\Application\Stats\PopularPodcastEpisodeAction;
use Ampache\Module\Application\Stats\PopularSongAction;
use Ampache\Module\Application\Stats\PopularVideoAction;
use Ampache\Module\Application\Stats\RecentAlbumArtistAction;
use Ampache\Module\Application\Stats\RecentAlbumDiskAction;
use Ampache\Module\Application\Stats\RecentArtistAction;
use Ampache\Module\Application\Stats\RecentPlaylistAction;
use Ampache\Module\Application\Stats\RecentPodcastEpisodeAction;
use Ampache\Module\Application\Stats\RecentSongAction;
use Ampache\Module\Application\Stats\RecentVideoAction;
use Ampache\Module\Application\Stats\ShareAction;
use Ampache\Module\Application\Stats\ShowUserAction;
use Ampache\Module\Application\Stats\UploadAction;
use Ampache\Module\Application\Stats\UserflagAlbumAction;
use Ampache\Module\Application\Stats\UserflagAlbumArtistAction;
use Ampache\Module\Application\Stats\UserflagAlbumDiskAction;
use Ampache\Module\Application\Stats\UserflagArtistAction;
use Ampache\Module\Application\Stats\UserflagPlaylistAction;
use Ampache\Module\Application\Stats\UserflagPodcastEpisodeAction;
use Ampache\Module\Application\Stats\UserflagSongAction;
use Ampache\Module\Application\Stats\UserflagVideoAction;
use Ampache\Module\Application\Stream\BasketAction;
use Ampache\Module\Application\Stream\DemocraticAction;
use Ampache\Module\Application\Stream\DownloadAction;
use Ampache\Module\Application\Stream\PlayItemAction;
use Ampache\Module\Application\Stream\PlaylistRandomAction;
use Ampache\Module\Application\Stream\RandomAction;
use Ampache\Module\Application\Stream\StreamItemAction;
use Ampache\Module\Application\Stream\TmpPlaylistAction;
use Ampache\Module\Application\Test\ConfigAction;
use Ampache\Module\Application\Update\ClearAction;
use Ampache\Module\Application\Update\UpdateAction;
use Ampache\Module\Application\Update\UpdatePluginsAction;
use Ampache\Module\Application\Upload\DefaultAction;
use Ampache\Module\Application\Video\ShowVideoAction;
use Ampache\Module\Application\WebPlayer\ShowEmbeddedAction;

use function DI\autowire;

return [
    ApplicationRunner::class => autowire(ApplicationRunner::class),
    DeleteAction::class => autowire(DeleteAction::class),
    ConfirmDeleteAction::class => autowire(ConfirmDeleteAction::class),
    ShowLyricsAction::class => autowire(ShowLyricsAction::class),
    ShowSongAction::class => autowire(ShowSongAction::class),
    Album\DeleteAction::class => autowire(Album\DeleteAction::class),
    Album\ConfirmDeleteAction::class => autowire(Album\ConfirmDeleteAction::class),
    UpdateFromTagsAction::class => autowire(UpdateFromTagsAction::class),
    SetTrackNumbersAction::class => autowire(SetTrackNumbersAction::class),
    ShowMissingAction::class => autowire(ShowMissingAction::class),
    ShowAction::class => autowire(ShowAction::class),
    Artist\DeleteAction::class => autowire(Artist\DeleteAction::class),
    Artist\ConfirmDeleteAction::class => autowire(Artist\ConfirmDeleteAction::class),
    Artist\ShowAction::class => autowire(Artist\ShowAction::class),
    ShowAllSongsAction::class => autowire(ShowAllSongsAction::class),
    ShowSongsAction::class => autowire(ShowSongsAction::class),
    UpdateFromMusicBrainzAction::class => autowire(UpdateFromMusicBrainzAction::class),
    Artist\UpdateFromTagsAction::class => autowire(Artist\UpdateFromTagsAction::class),
    Artist\ShowMissingAction::class => autowire(Artist\ShowMissingAction::class),
    ShowUserAction::class => autowire(ShowUserAction::class),
    NewestAlbumAction::class => autowire(NewestAlbumAction::class),
    NewestAlbumDiskAction::class => autowire(NewestAlbumDiskAction::class),
    NewestAlbumArtistAction::class => autowire(NewestAlbumArtistAction::class),
    NewestArtistAction::class => autowire(NewestArtistAction::class),
    NewestPlaylistAction::class => autowire(NewestPlaylistAction::class),
    NewestPodcastEpisodeAction::class => autowire(NewestPodcastEpisodeAction::class),
    NewestSongAction::class => autowire(NewestSongAction::class),
    NewestVideoAction::class => autowire(NewestVideoAction::class),
    PopularAlbumAction::class => autowire(PopularAlbumAction::class),
    PopularAlbumArtistAction::class => autowire(PopularAlbumArtistAction::class),
    PopularAlbumDiskAction::class => autowire(PopularAlbumDiskAction::class),
    PopularArtistAction::class => autowire(PopularArtistAction::class),
    PopularPlaylistAction::class => autowire(PopularPlaylistAction::class),
    PopularPodcastEpisodeAction::class => autowire(PopularPodcastEpisodeAction::class),
    PopularSongAction::class => autowire(PopularSongAction::class),
    PopularVideoAction::class => autowire(PopularVideoAction::class),
    HighestAlbumAction::class => autowire(HighestAlbumAction::class),
    HighestAlbumArtistAction::class => autowire(HighestAlbumArtistAction::class),
    HighestAlbumDiskAction::class => autowire(HighestAlbumDiskAction::class),
    HighestArtistAction::class => autowire(HighestArtistAction::class),
    HighestPlaylistAction::class => autowire(HighestPlaylistAction::class),
    HighestPodcastEpisodeAction::class => autowire(HighestPodcastEpisodeAction::class),
    HighestSongAction::class => autowire(HighestSongAction::class),
    HighestVideoAction::class => autowire(HighestVideoAction::class),
    UserflagAlbumAction::class => autowire(UserflagAlbumAction::class),
    UserflagAlbumArtistAction::class => autowire(UserflagAlbumArtistAction::class),
    UserflagAlbumDiskAction::class => autowire(UserflagAlbumDiskAction::class),
    UserflagArtistAction::class => autowire(UserflagArtistAction::class),
    UserflagPlaylistAction::class => autowire(UserflagPlaylistAction::class),
    UserflagPodcastEpisodeAction::class => autowire(UserflagPodcastEpisodeAction::class),
    UserflagSongAction::class => autowire(UserflagSongAction::class),
    UserflagVideoAction::class => autowire(UserflagVideoAction::class),
    RecentAlbumArtistAction::class => autowire(RecentAlbumArtistAction::class),
    RecentAlbumDiskAction::class => autowire(RecentAlbumDiskAction::class),
    RecentArtistAction::class => autowire(RecentArtistAction::class),
    RecentPlaylistAction::class => autowire(RecentPlaylistAction::class),
    RecentPodcastEpisodeAction::class => autowire(RecentPodcastEpisodeAction::class),
    RecentSongAction::class => autowire(RecentSongAction::class),
    RecentVideoAction::class => autowire(RecentVideoAction::class),
    ShareAction::class => autowire(ShareAction::class),
    UploadAction::class => autowire(UploadAction::class),
    GraphAction::class => autowire(GraphAction::class),
    Stats\ShowAction::class => autowire(Stats\ShowAction::class),
    LogoutAction::class => autowire(LogoutAction::class),
    Rss\ShowAction::class => autowire(Rss\ShowAction::class),
    AddShoutAction::class => autowire(AddShoutAction::class),
    ShowAddShoutAction::class => autowire(ShowAddShoutAction::class),
    Shout\ShowAction::class => autowire(Shout\ShowAction::class),
    Waveform\ShowAction::class => autowire(Waveform\ShowAction::class),
    SearchAction::class => autowire(SearchAction::class),
    SaveAsSmartPlaylistAction::class => autowire(SaveAsSmartPlaylistAction::class),
    SaveAsPlaylistAction::class => autowire(SaveAsPlaylistAction::class),
    DescriptorAction::class => autowire(DescriptorAction::class),
    Search\ShowAction::class => autowire(Search\ShowAction::class),
    CookieDisclaimer\ShowAction::class => autowire(CookieDisclaimer\ShowAction::class),
    ManageAction::class => autowire(ManageAction::class),
    ShowCreateAction::class => autowire(ShowCreateAction::class),
    DemocraticPlayback\DeleteAction::class => autowire(DemocraticPlayback\DeleteAction::class),
    CreateAction::class => autowire(CreateAction::class),
    ManagePlaylistsAction::class => autowire(ManagePlaylistsAction::class),
    ShowPlaylistAction::class => autowire(ShowPlaylistAction::class),
    WebPlayer\ShowAction::class => autowire(WebPlayer\ShowAction::class),
    ShowEmbeddedAction::class => autowire(ShowEmbeddedAction::class),
    Index\ShowAction::class => autowire(Index\ShowAction::class),
    Utility\ShowAction::class => autowire(Utility\ShowAction::class),
    ClearAction::class => autowire(ClearAction::class),
    Update\ShowAction::class => autowire(Update\ShowAction::class),
    UpdateAction::class => autowire(UpdateAction::class),
    UpdatePluginsAction::class => autowire(UpdatePluginsAction::class),
    Video\DeleteAction::class => autowire(Video\DeleteAction::class),
    Video\ConfirmDeleteAction::class => autowire(Video\ConfirmDeleteAction::class),
    ShowVideoAction::class => autowire(ShowVideoAction::class),
    Label\DeleteAction::class => autowire(Label\DeleteAction::class),
    Label\ConfirmDeleteAction::class => autowire(Label\ConfirmDeleteAction::class),
    AddLabelAction::class => autowire(AddLabelAction::class),
    ShowAddLabelAction::class => autowire(ShowAddLabelAction::class),
    Label\ShowAction::class => autowire(Label\ShowAction::class),
    Share\ShowCreateAction::class => autowire(Share\ShowCreateAction::class),
    Share\CreateAction::class => autowire(Share\CreateAction::class),
    ShowDeleteAction::class => autowire(ShowDeleteAction::class),
    Share\DeleteAction::class => autowire(Share\DeleteAction::class),
    CleanAction::class => autowire(CleanAction::class),
    ExternalShareAction::class => autowire(ExternalShareAction::class),
    ConsumeAction::class => autowire(ConsumeAction::class),
    Broadcast\ShowDeleteAction::class => autowire(Broadcast\ShowDeleteAction::class),
    Broadcast\DeleteAction::class => autowire(Broadcast\DeleteAction::class),
    Radio\ShowCreateAction::class => autowire(Radio\ShowCreateAction::class),
    Radio\CreateAction::class => autowire(Radio\CreateAction::class),
    Radio\ShowAction::class => autowire(Radio\ShowAction::class),
    Image\ShowAction::class => autowire(Image\ShowAction::class),
    ShowUserAvatarAction::class => autowire(),
    Mashup\ShowAction::class => autowire(Mashup\ShowAction::class),
    WrappedAction::class => autowire(WrappedAction::class),
    Podcast\ShowCreateAction::class => autowire(Podcast\ShowCreateAction::class),
    Podcast\CreateAction::class => autowire(Podcast\CreateAction::class),
    Podcast\DeleteAction::class => autowire(Podcast\DeleteAction::class),
    Podcast\ConfirmDeleteAction::class => autowire(Podcast\ConfirmDeleteAction::class),
    Podcast\ShowAction::class => autowire(Podcast\ShowAction::class),
    ExportPodcastsAction::class => autowire(),
    PodcastEpisode\DeleteAction::class => autowire(PodcastEpisode\DeleteAction::class),
    PodcastEpisode\ConfirmDeleteAction::class => autowire(PodcastEpisode\ConfirmDeleteAction::class),
    PodcastEpisode\ShowAction::class => autowire(PodcastEpisode\ShowAction::class),
    DefaultAction::class => autowire(DefaultAction::class),
    NowPlaying\ShowAction::class => autowire(NowPlaying\ShowAction::class),
    LostPassword\ShowAction::class => autowire(LostPassword\ShowAction::class),
    SendAction::class => autowire(SendAction::class),
    PrivateMessage\ShowAction::class => autowire(PrivateMessage\ShowAction::class),
    PrivateMessage\ConfirmDeleteAction::class => autowire(PrivateMessage\ConfirmDeleteAction::class),
    PrivateMessage\DeleteAction::class => autowire(PrivateMessage\DeleteAction::class),
    SetIsReadAction::class => autowire(SetIsReadAction::class),
    AddMessageAction::class => autowire(AddMessageAction::class),
    ShowAddMessageAction::class => autowire(ShowAddMessageAction::class),
    Test\ShowAction::class => autowire(Test\ShowAction::class),
    ConfigAction::class => autowire(ConfigAction::class),
    DownloadAction::class => autowire(DownloadAction::class),
    DemocraticAction::class => autowire(DemocraticAction::class),
    PlaylistRandomAction::class => autowire(PlaylistRandomAction::class),
    PlayItemAction::class => autowire(PlayItemAction::class),
    StreamItemAction::class => autowire(StreamItemAction::class),
    RandomAction::class => autowire(RandomAction::class),
    TmpPlaylistAction::class => autowire(TmpPlaylistAction::class),
    BasketAction::class => autowire(BasketAction::class),
    PhpInfo\ShowAction::class => autowire(PhpInfo\ShowAction::class),
    ShowGet\ShowAction::class => autowire(ShowGet\ShowAction::class),
    SearchData\ShowAction::class => autowire(SearchData\ShowAction::class),
    ValidateAction::class => autowire(ValidateAction::class),
    ShowAddUserAction::class => autowire(ShowAddUserAction::class),
    AddUserAction::class => autowire(AddUserAction::class),
    StatisticGraph\ShowAction::class => autowire(StatisticGraph\ShowAction::class),
    AdvancedAction::class => autowire(AdvancedAction::class),
    GetAdvancedAction::class => autowire(GetAdvancedAction::class),
    Batch\DefaultAction::class => autowire(Batch\DefaultAction::class),
    SmartPlaylist\ShowAction::class => autowire(SmartPlaylist\ShowAction::class),
    RefreshPlaylistAction::class => autowire(RefreshPlaylistAction::class),
    UpdatePlaylistAction::class => autowire(UpdatePlaylistAction::class),
    DeletePlaylistAction::class => autowire(DeletePlaylistAction::class),
    CreatePlaylistAction::class => autowire(CreatePlaylistAction::class),
    Playlist\ShowAction::class => autowire(Playlist\ShowAction::class),
    SortTrackAction::class => autowire(SortTrackAction::class),
    RemoveDuplicatesAction::class => autowire(RemoveDuplicatesAction::class),
    AddSongAction::class => autowire(AddSongAction::class),
    Playlist\SetTrackNumbersAction::class => autowire(Playlist\SetTrackNumbersAction::class),
    ImportPlaylistAction::class => autowire(ImportPlaylistAction::class),
    ShowImportPlaylistAction::class => autowire(ShowImportPlaylistAction::class),
    Playlist\DeletePlaylistAction::class => autowire(Playlist\DeletePlaylistAction::class),
    Playlist\RefreshPlaylistAction::class => autowire(Playlist\RefreshPlaylistAction::class),
    Playlist\CreatePlaylistAction::class => autowire(Playlist\CreatePlaylistAction::class),
    Installation\DefaultAction::class => autowire(Installation\DefaultAction::class),
    UpdateUserAction::class => autowire(UpdateUserAction::class),
    UserAction::class => autowire(UserAction::class),
    Preferences\ShowAction::class => autowire(Preferences\ShowAction::class),
    AdminAction::class => autowire(AdminAction::class),
    AdminUpdatePreferencesAction::class => autowire(AdminUpdatePreferencesAction::class),
    UpdatePreferencesAction::class => autowire(UpdatePreferencesAction::class),
    GrantAction::class => autowire(GrantAction::class),
    Login\DefaultAction::class => autowire(Login\DefaultAction::class),
    ShowAddInstanceAction::class => autowire(ShowAddInstanceAction::class),
    LocalPlay\ShowPlaylistAction::class => autowire(LocalPlay\ShowPlaylistAction::class),
    AddInstanceAction::class => autowire(AddInstanceAction::class),
    UpdateInstanceAction::class => autowire(UpdateInstanceAction::class),
    EditInstanceAction::class => autowire(EditInstanceAction::class),
    ShowInstancesAction::class => autowire(ShowInstancesAction::class),
    TagAction::class => autowire(TagAction::class),
    FileAction::class => autowire(FileAction::class),
    AlbumAction::class => autowire(AlbumAction::class),
    AlbumArtistAction::class => autowire(AlbumArtistAction::class),
    ArtistAction::class => autowire(ArtistAction::class),
    SongAction::class => autowire(SongAction::class),
    PlaylistAction::class => autowire(PlaylistAction::class),
    SmartPlaylistAction::class => autowire(SmartPlaylistAction::class),
    PodcastEpisodeAction::class => autowire(PodcastEpisodeAction::class),
    CatalogAction::class => autowire(CatalogAction::class),
    PrivateMessageAction::class => autowire(PrivateMessageAction::class),
    LiveStreamAction::class => autowire(LiveStreamAction::class),
    LabelAction::class => autowire(LabelAction::class),
    BroadcastAction::class => autowire(BroadcastAction::class),
    VideoAction::class => autowire(VideoAction::class),
    PodcastAction::class => autowire(PodcastAction::class),
    ClearArtAction::class => autowire(ClearArtAction::class),
    ShowArtDlgAction::class => autowire(ShowArtDlgAction::class),
    FindArtAction::class => autowire(FindArtAction::class),
    UploadArtAction::class => autowire(UploadArtAction::class),
    SelectArtAction::class => autowire(SelectArtAction::class),
    PlayAction::class => autowire(PlayAction::class),
    Admin\Mail\ShowAction::class => autowire(Admin\Mail\ShowAction::class),
    SendMailAction::class => autowire(SendMailAction::class),
    Admin\Export\ShowAction::class => autowire(Admin\Export\ShowAction::class),
    ExportAction::class => autowire(ExportAction::class),
    Admin\Access\ShowAction::class => autowire(Admin\Access\ShowAction::class),
    ShowAddAdvancedAction::class => autowire(ShowAddAdvancedAction::class),
    ShowDeleteRecordAction::class => autowire(ShowDeleteRecordAction::class),
    UpdateRecordAction::class => autowire(UpdateRecordAction::class),
    AddHostAction::class => autowire(AddHostAction::class),
    DeleteRecordAction::class => autowire(DeleteRecordAction::class),
    ShowEditRecordAction::class => autowire(ShowEditRecordAction::class),
    ShowAddAction::class => autowire(ShowAddAction::class),
    ShowAddCatalogAction::class => autowire(ShowAddCatalogAction::class),
    ShowDisabledAction::class => autowire(ShowDisabledAction::class),
    ShowCustomizeCatalogAction::class => autowire(ShowCustomizeCatalogAction::class),
    ShowCatalogsAction::class => autowire(ShowCatalogsAction::class),
    ClearStatsAction::class => autowire(ClearStatsAction::class),
    ClearNowPlayingAction::class => autowire(ClearNowPlayingAction::class),
    DeleteCatalogAction::class => autowire(DeleteCatalogAction::class),
    ShowDeleteCatalogAction::class => autowire(ShowDeleteCatalogAction::class),
    AddToAllCatalogsAction::class => autowire(AddToAllCatalogsAction::class),
    UpdateCatalogAction::class => autowire(UpdateCatalogAction::class),
    FullServiceAction::class => autowire(FullServiceAction::class),
    AddToCatalogAction::class => autowire(AddToCatalogAction::class),
    CleanAllCatalogsAction::class => autowire(CleanAllCatalogsAction::class),
    CleanCatalogAction::class => autowire(CleanCatalogAction::class),
    GarbageCollectAction::class => autowire(GarbageCollectAction::class),
    UpdateFileTagsAction::class => autowire(UpdateFileTagsAction::class),
    UpdateAllFileTagsActions::class => autowire(UpdateAllFileTagsActions::class),
    GatherMediaArtAction::class => autowire(GatherMediaArtAction::class),
    ImportToCatalogAction::class => autowire(ImportToCatalogAction::class),
    AddCatalogAction::class => autowire(AddCatalogAction::class),
    UpdateFromAction::class => autowire(UpdateFromAction::class),
    UpdateAllCatalogsAction::class => autowire(UpdateAllCatalogsAction::class),
    EnableDisabledAction::class => autowire(EnableDisabledAction::class),
    UpdateCatalogSettingsAction::class => autowire(UpdateCatalogSettingsAction::class),
    AbstractFilterAction::class => autowire(AbstractFilterAction::class),
    AddFilterAction::class => autowire(AddFilterAction::class),
    Admin\Filter\ConfirmDeleteAction::class => autowire(Admin\Filter\ConfirmDeleteAction::class),
    Admin\Filter\DeleteAction::class => autowire(Admin\Filter\DeleteAction::class),
    Admin\Filter\ShowAction::class => autowire(Admin\Filter\ShowAction::class),
    ShowAddFilterAction::class => autowire(ShowAddFilterAction::class),
    ShowEditAction::class => autowire(ShowEditAction::class),
    UpdateFilterAction::class => autowire(UpdateFilterAction::class),
    Admin\Index\ShowAction::class => autowire(Admin\Index\ShowAction::class),
    Admin\License\ShowAction::class => autowire(Admin\License\ShowAction::class),
    Admin\License\DeleteAction::class => autowire(Admin\License\DeleteAction::class),
    Admin\License\ShowCreateAction::class => autowire(Admin\License\ShowCreateAction::class),
    Admin\License\ShowEditAction::class => autowire(Admin\License\ShowEditAction::class),
    EditAction::class => autowire(EditAction::class),
    Admin\Shout\ShowAction::class => autowire(Admin\Shout\ShowAction::class),
    Admin\Shout\DeleteAction::class => autowire(Admin\Shout\DeleteAction::class),
    Admin\Shout\ShowEditAction::class => autowire(Admin\Shout\ShowEditAction::class),
    EditShoutAction::class => autowire(EditShoutAction::class),
    InstallLocalplayAction::class => autowire(InstallLocalplayAction::class),
    Admin\Modules\ShowAction::class => autowire(Admin\Modules\ShowAction::class),
    InstallCatalogTypeAction::class => autowire(InstallCatalogTypeAction::class),
    ConfirmUninstallCatalogType::class => autowire(ConfirmUninstallCatalogType::class),
    ConfirmUninstallLocalplayAction::class => autowire(ConfirmUninstallLocalplayAction::class),
    ConfirmUninstallPluginAction::class => autowire(ConfirmUninstallPluginAction::class),
    ConfirmInstallCatalogType::class => autowire(ConfirmInstallCatalogType::class),
    ConfirmInstallLocalplayAction::class => autowire(ConfirmInstallLocalplayAction::class),
    ConfirmInstallPluginAction::class => autowire(ConfirmInstallPluginAction::class),
    UninstallLocalplayAction::class => autowire(UninstallLocalplayAction::class),
    UninstallCatalogTypeAction::class => autowire(UninstallCatalogTypeAction::class),
    InstallPluginAction::class => autowire(InstallPluginAction::class),
    UninstallPluginAction::class => autowire(UninstallPluginAction::class),
    UpgradePluginAction::class => autowire(UpgradePluginAction::class),
    ShowPluginsAction::class => autowire(ShowPluginsAction::class),
    ShowLocalplayAction::class => autowire(ShowLocalplayAction::class),
    ShowCatalogTypesAction::class => autowire(ShowCatalogTypesAction::class),
    GenerateConfigAction::class => autowire(GenerateConfigAction::class),
    WriteConfigAction::class => autowire(WriteConfigAction::class),
    ResetDbCharsetAction::class => autowire(ResetDbCharsetAction::class),
    ShowDebugAction::class => autowire(ShowDebugAction::class),
    ClearCacheAction::class => autowire(ClearCacheAction::class),
    Admin\User\ShowAction::class => autowire(Admin\User\ShowAction::class),
    ShowPreferencesAction::class => autowire(ShowPreferencesAction::class),
    Admin\User\ShowAddUserAction::class => autowire(Admin\User\ShowAddUserAction::class),
    ShowIpHistoryAction::class => autowire(ShowIpHistoryAction::class),
    GenerateRssTokenAction::class => autowire(GenerateRssTokenAction::class),
    ShowGenerateRssTokenAction::class => autowire(ShowGenerateRssTokenAction::class),
    GenerateStreamTokenAction::class => autowire(GenerateStreamTokenAction::class),
    ShowGenerateStreamTokenAction::class => autowire(ShowGenerateStreamTokenAction::class),
    DeleteStreamTokenAction::class => autowire(DeleteStreamTokenAction::class),
    DeleteRssTokenAction::class => autowire(DeleteRssTokenAction::class),
    ShowDeleteStreamTokenAction::class => autowire(ShowDeleteStreamTokenAction::class),
    ShowDeleteRssTokenAction::class => autowire(ShowDeleteRssTokenAction::class),
    GenerateApiKeyAction::class => autowire(GenerateApiKeyAction::class),
    ShowGenerateApiKeyAction::class => autowire(ShowGenerateApiKeyAction::class),
    DeleteApiKeyAction::class => autowire(DeleteApiKeyAction::class),
    ShowDeleteApiKeyAction::class => autowire(ShowDeleteApiKeyAction::class),
    DeleteAvatarAction::class => autowire(DeleteAvatarAction::class),
    ShowDeleteAvatarAction::class => autowire(ShowDeleteAvatarAction::class),
    Admin\User\ShowDeleteAction::class => autowire(Admin\User\ShowDeleteAction::class),
    Admin\User\ConfirmDeleteAction::class => autowire(Admin\User\ConfirmDeleteAction::class),
    Admin\User\ShowEditAction::class => autowire(Admin\User\ShowEditAction::class),
    DisableAction::class => autowire(DisableAction::class),
    EnableAction::class => autowire(EnableAction::class),
    Admin\User\AddUserAction::class => autowire(Admin\User\AddUserAction::class),
    Admin\User\UpdateUserAction::class => autowire(Admin\User\UpdateUserAction::class),
];
