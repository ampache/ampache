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

namespace Ampache\Module\Api;

use Ampache\Module\Api\Ajax\Handler\PodcastAjaxHandler;
use Ampache\Module\Api\Ajax\AjaxApplication;
use Ampache\Module\Api\Ajax\Handler\BrowseAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\CatalogAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\DefaultAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\DemocraticPlaybackAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\IndexAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\LocalPlayAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\PlayerAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\PlaylistAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\RandomAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\SearchAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\SongAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\StatsAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\StreamAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\TagAjaxHandler;
use Ampache\Module\Api\Ajax\Handler\UserAjaxHandler;
use Ampache\Module\Api\Gui\ApiHandler;
use Ampache\Module\Api\Gui\ApiHandlerInterface;
use Ampache\Module\Api\Gui\JsonApiApplication;
use Ampache\Module\Api\Gui\XmlApiApplication;
use Ampache\Module\Api\Gui\Output\ApiOutputFactory;
use Ampache\Module\Api\Gui\Output\ApiOutputFactoryInterface;
use Ampache\Module\Api\SubSonic\SubsonicApiApplication;
use function DI\autowire;

return [
    XmlApiApplication::class => autowire(),
    JsonApiApplication::class => autowire(),
    SubsonicApiApplication::class => autowire(),
    DaapApiApplication::class => autowire(),
    SseApiApplication::class => autowire(),
    ApiOutputFactoryInterface::class => autowire(ApiOutputFactory::class),
    ApiHandlerInterface::class => autowire(ApiHandler::class),
    Gui\Method\AlbumsMethod::class => autowire(),
    Gui\Method\AlbumMethod::class => autowire(),
    Gui\Method\AlbumSongsMethod::class => autowire(),
    Gui\Method\ArtistAlbumsMethod::class => autowire(),
    Gui\Method\ArtistMethod::class => autowire(),
    Gui\Method\ArtistsMethod::class => autowire(),
    Gui\Method\ArtistSongsMethod::class => autowire(),
    Gui\Method\FollowersMethod::class => autowire(),
    Gui\Method\SongMethod::class => autowire(),
    Gui\Method\LastShoutsMethod::class => autowire(),
    Gui\Method\UsersMethod::class => autowire(),
    Gui\Method\UserMethod::class => autowire(),
    Gui\Method\GenreMethod::class => autowire(),
    Gui\Method\GenresMethod::class => autowire(),
    Gui\Method\SongsMethod::class => autowire(),
    Gui\Method\VideosMethod::class => autowire(),
    Gui\Method\VideoMethod::class => autowire(),
    Gui\Method\UrlToSongMethod::class => autowire(),
    Gui\Method\ToggleFollowMethod::class => autowire(),
    Gui\Method\LicenseMethod::class => autowire(),
    Gui\Method\LicensesMethod::class => autowire(),
    Gui\Method\LicenseSongsMethod::class => autowire(),
    Gui\Method\GenreSongsMethod::class => autowire(),
    Gui\Method\GenreArtistsMethod::class => autowire(),
    Gui\Method\LabelArtistsMethod::class => autowire(),
    Gui\Method\LabelsMethod::class => autowire(),
    Gui\Method\LabelMethod::class => autowire(),
    Gui\Method\PodcastMethod::class => autowire(),
    Gui\Method\PodcastsMethod::class => autowire(),
    Gui\Method\PodcastEpisodesMethod::class => autowire(),
    Gui\Method\PodcastEpisodeMethod::class => autowire(),
    Gui\Method\PlaylistMethod::class => autowire(),
    Gui\Method\HandshakeMethod::class => autowire(),
    Gui\Method\PingMethod::class => autowire(),
    Gui\Method\CatalogMethod::class => autowire(),
    Gui\Method\CatalogsMethod::class => autowire(),
    Gui\Method\FriendsTimelineMethod::class => autowire(),
    Gui\Method\BookmarksMethod::class => autowire(),
    Gui\Method\TimelineMethod::class => autowire(),
    Gui\Method\PlaylistAddSongMethod::class => autowire(),
    Gui\Method\ShareMethod::class => autowire(),
    Gui\Method\SharesMethod::class => autowire(),
    Gui\Method\SongDeleteMethod::class => autowire(),
    Gui\Method\PodcastEpisodeDeleteMethod::class => autowire(),
    Gui\Method\UserPreferencesMethod::class => autowire(),
    Gui\Method\SystemPreferencesMethod::class => autowire(),
    Gui\Method\UserDeleteMethod::class => autowire(),
    Gui\Method\UserPreferenceMethod::class => autowire(),
    Gui\Method\PreferenceDeleteMethod::class => autowire(),
    Gui\Method\CatalogActionMethod::class => autowire(),
    Gui\Method\SystemPreferenceMethod::class => autowire(),
    Gui\Method\PlaylistSongsMethod::class => autowire(),
    Gui\Method\StreamMethod::class => autowire(),
    Gui\Method\ShareDeleteMethod::class => autowire(),
    Gui\Method\PodcastCreateMethod::class => autowire(),
    Gui\Method\PlaylistsMethod::class => autowire(),
    Gui\Method\PlaylistRemoveSongMethod::class => autowire(),
    Gui\Method\DownloadMethod::class => autowire(),
    Gui\Method\BookmarkCreateMethod::class => autowire(),
    Gui\Method\FlagMethod::class => autowire(),
    Gui\Method\BookmarkDeleteMethod::class => autowire(),
    Gui\Method\UpdateFromTagsMethod::class => autowire(),
    Gui\Method\UserCreateMethod::class => autowire(),
    Gui\Method\UserUpdateMethod::class => autowire(),
    Gui\Method\UpdatePodcastMethod::class => autowire(),
    Gui\Method\UpdateArtistInfoMethod::class => autowire(),
    Gui\Method\SystemUpdateMethod::class => autowire(),
    Gui\Method\SearchSongsMethod::class => autowire(),
    Gui\Method\AdvancedSearchMethod::class => autowire(),
    Gui\Method\BookmarkEditMethod::class => autowire(),
    Gui\Method\UpdateArtMethod::class => autowire(),
    Gui\Method\GetBookmarkMethod::class => autowire(),
    Gui\Method\LocalplayMethod::class => autowire(),
    Gui\Method\PreferenceEditMethod::class => autowire(),
    Gui\Method\RateMethod::class => autowire(),
    Gui\Method\RecordPlayMethod::class => autowire(),
    Gui\Method\GetSimilarMethod::class => autowire(),
    Gui\Method\PlaylistEditMethod::class => autowire(),
    Gui\Method\DemocraticMethod::class => autowire(),
    Gui\Method\ShareCreateMethod::class => autowire(),
    Gui\Method\ScrobbleMethod::class => autowire(),
    Gui\Method\PodcastEditMethod::class => autowire(),
    Gui\Method\GetIndexesMethod::class => autowire(),
    Gui\Method\StatsMethod::class => autowire(),
    Gui\Method\GetArtMethod::class => autowire(),
    Gui\Method\PlaylistGenerateMethod::class => autowire(),
    Gui\Method\CatalogFileMethod::class => autowire(),
    Gui\Method\Lib\LocalPlayCommandMapperInterface::class => autowire(Gui\Method\Lib\LocalPlayCommandMapper::class),
    Gui\Method\Lib\ServerDetailsRetrieverInterface::class => autowire(Gui\Method\Lib\ServerDetailsRetriever::class),
    Gui\Method\Lib\DemocraticControlMapperInterface::class => autowire(Gui\Method\Lib\DemocraticControlMapper::class),
    Gui\Method\Lib\ItemToplistMapperInterface::class => autowire(Gui\Method\Lib\ItemToplistMapper::class),
    Gui\Method\Lib\ArtItemRetrieverInterface::class => autowire(Gui\Method\Lib\ArtItemRetriever::class),
    Edit\EditObjectAction::class => autowire(),
    Edit\RefreshUpdatedAction::class => autowire(),
    Edit\ShowEditObjectAction::class => autowire(),
    Edit\ShowEditPlaylistAction::class => autowire(),
    Gui\Authentication\HandshakeInterface::class => autowire(Gui\Authentication\Handshake::class),
    SubSonic\Method\GetPodcastsMethod::class => autowire(),
    SubSonic\Method\GetNewestPodcastsMethod::class => autowire(),
    SubSonic\Method\DownloadPodcastEpisodeMethod::class => autowire(),
    SubSonic\Method\DeletePodcastEpisode::class => autowire(),
    SubSonic\Method\RefreshPodcastsMethod::class => autowire(),
    AjaxApplication::class => autowire(AjaxApplication::class),
    BrowseAjaxHandler::class => autowire(BrowseAjaxHandler::class),
    DefaultAjaxHandler::class => autowire(DefaultAjaxHandler::class),
    CatalogAjaxHandler::class => autowire(CatalogAjaxHandler::class),
    DemocraticPlaybackAjaxHandler::class => autowire(DemocraticPlaybackAjaxHandler::class),
    IndexAjaxHandler::class => autowire(IndexAjaxHandler::class),
    LocalPlayAjaxHandler::class => autowire(LocalPlayAjaxHandler::class),
    PlayerAjaxHandler::class => autowire(PlayerAjaxHandler::class),
    PlaylistAjaxHandler::class => autowire(PlaylistAjaxHandler::class),
    PodcastAjaxHandler::class => autowire(PodcastAjaxHandler::class),
    RandomAjaxHandler::class => autowire(RandomAjaxHandler::class),
    SearchAjaxHandler::class => autowire(SearchAjaxHandler::class),
    SongAjaxHandler::class => autowire(SongAjaxHandler::class),
    StatsAjaxHandler::class => autowire(StatsAjaxHandler::class),
    StreamAjaxHandler::class => autowire(StreamAjaxHandler::class),
    TagAjaxHandler::class => autowire(TagAjaxHandler::class),
    UserAjaxHandler::class => autowire(UserAjaxHandler::class),
];
