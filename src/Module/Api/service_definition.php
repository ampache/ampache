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

use Ampache\Module\Api\Output\ApiOutputFactory;
use Ampache\Module\Api\Output\ApiOutputFactoryInterface;
use function DI\autowire;

return [
    XmlApiApplication::class => autowire(),
    JsonApiApplication::class => autowire(),
    SubsonicApiApplication::class => autowire(),
    DaapApiApplication::class => autowire(),
    SseApiApplication::class => autowire(),
    ApiOutputFactoryInterface::class => autowire(ApiOutputFactory::class),
    ApiHandlerInterface::class => autowire(ApiHandler::class),
    Method\AlbumsMethod::class => autowire(),
    Method\AlbumMethod::class => autowire(),
    Method\AlbumSongsMethod::class => autowire(),
    Method\ArtistAlbumsMethod::class => autowire(),
    Method\ArtistMethod::class => autowire(),
    Method\ArtistsMethod::class => autowire(),
    Method\ArtistSongsMethod::class => autowire(),
    Method\FollowersMethod::class => autowire(),
    Method\SongMethod::class => autowire(),
    Method\LastShoutsMethod::class => autowire(),
    Method\UsersMethod::class => autowire(),
    Method\UserMethod::class => autowire(),
    Method\GenreMethod::class => autowire(),
    Method\GenresMethod::class => autowire(),
    Method\SongsMethod::class => autowire(),
    Method\VideosMethod::class => autowire(),
    Method\VideoMethod::class => autowire(),
    Method\UrlToSongMethod::class => autowire(),
    Method\ToggleFollowMethod::class => autowire(),
    Method\LicenseMethod::class => autowire(),
    Method\LicensesMethod::class => autowire(),
    Method\LicenseSongsMethod::class => autowire(),
    Method\GenreSongsMethod::class => autowire(),
    Method\GenreArtistsMethod::class => autowire(),
    Method\LabelArtistsMethod::class => autowire(),
    Method\LabelsMethod::class => autowire(),
    Method\LabelMethod::class => autowire(),
    Method\PodcastMethod::class => autowire(),
    Method\PodcastsMethod::class => autowire(),
    Method\PodcastEpisodesMethod::class => autowire(),
    Method\PodcastEpisodeMethod::class => autowire(),
    Method\PlaylistMethod::class => autowire(),
    Method\HandshakeMethod::class => autowire(),
    Method\PingMethod::class => autowire(),
    Method\CatalogMethod::class => autowire(),
    Method\CatalogsMethod::class => autowire(),
    Method\FriendsTimelineMethod::class => autowire(),
    Method\BookmarksMethod::class => autowire(),
    Method\TimelineMethod::class => autowire(),
    Method\PlaylistAddSongMethod::class => autowire(),
    Method\ShareMethod::class => autowire(),
    Method\SharesMethod::class => autowire(),
    Method\SongDeleteMethod::class => autowire(),
    Method\PodcastEpisodeDeleteMethod::class => autowire(),
    Method\UserPreferencesMethod::class => autowire(),
    Method\SystemPreferencesMethod::class => autowire(),
    Method\UserDeleteMethod::class => autowire(),
    Method\UserPreferenceMethod::class => autowire(),
    Method\PreferenceDeleteMethod::class => autowire(),
    Method\CatalogActionMethod::class => autowire(),
    Method\SystemPreferenceMethod::class => autowire(),
    Method\PlaylistSongsMethod::class => autowire(),
    Method\StreamMethod::class => autowire(),
    Method\ShareDeleteMethod::class => autowire(),
    Method\PodcastCreateMethod::class => autowire(),
    Method\PlaylistsMethod::class => autowire(),
    Method\PlaylistRemoveSongMethod::class => autowire(),
    Method\DownloadMethod::class => autowire(),
    Method\BookmarkCreateMethod::class => autowire(),
    Method\FlagMethod::class => autowire(),
    Method\BookmarkDeleteMethod::class => autowire(),
    Method\UpdateFromTagsMethod::class => autowire(),
    Method\UserCreateMethod::class => autowire(),
    Method\UserUpdateMethod::class => autowire(),
    Method\UpdatePodcastMethod::class => autowire(),
    Method\UpdateArtistInfoMethod::class => autowire(),
    Method\SystemUpdateMethod::class => autowire(),
    Edit\EditObjectAction::class => autowire(),
    Edit\RefreshUpdatedAction::class => autowire(),
    Edit\ShowEditObjectAction::class => autowire(),
    Edit\ShowEditPlaylistAction::class => autowire(),
    Authentication\HandshakeInterface::class => autowire(Authentication\Handshake::class),
];
