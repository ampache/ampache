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

namespace Ampache\Module\Api;

use Ampache\Module\Api\Output\ApiOutputFactory;
use Ampache\Module\Api\Output\ApiOutputFactoryInterface;

use function DI\autowire;

return [
    XmlApiApplication::class => autowire(),
    XmlRestApiApplication::class => autowire(),
    JsonApiApplication::class => autowire(),
    JsonRestApiApplication::class => autowire(),
    SubsonicApiApplication::class => autowire(),
    DaapApiApplication::class => autowire(),
    SseApiApplication::class => autowire(),
    ApiOutputFactoryInterface::class => autowire(ApiOutputFactory::class),
    ApiHandlerInterface::class => autowire(ApiHandler::class),
    Edit\EditObjectAction::class => autowire(),
    Edit\RefreshUpdatedAction::class => autowire(),
    Edit\ShowEditObjectAction::class => autowire(),
    Edit\ShowEditPlaylistAction::class => autowire(),
    Method\GenresMethod::class => autowire(),
    Method\AlbumMethod::class => autowire(),
    Method\AlbumsMethod::class => autowire(),
    Method\PodcastDeleteMethod::class => autowire(),
    Method\PodcastEpisodesMethod::class => autowire(),
    Method\BookmarksMethod::class => autowire(),
    Method\PlaylistsMethod::class => autowire(),
    Method\SmartlistsMethod::class => autowire(),
    Method\UserPlaylistsMethod::class => autowire(),
    Method\UserSmartlistsMethod::class => autowire(),
    Method\ArtistsMethod::class => autowire(),
    Method\CatalogsMethod::class => autowire(),
    Method\DeletedSongsMethod::class => autowire(),
    Method\NowPlayingMethod::class => autowire(),
    Method\SongsMethod::class => autowire(),
    Method\UsersMethod::class => autowire(),
    Method\RandomMethod::class => autowire(),
    Method\LabelsMethod::class => autowire(),
    Method\LicensesMethod::class => autowire(),
    Method\LiveStreamsMethod::class => autowire(),
    Method\SharesMethod::class => autowire(),
    Method\PodcastsMethod::class => autowire(),
    Method\VideosMethod::class => autowire(),
    Method\FollowersMethod::class => autowire(),
    Method\FollowingMethod::class => autowire(),
    Method\DeletedVideosMethod::class => autowire(),
    Method\DeletedPodcastEpisodesMethod::class => autowire(),
    Method\ArtistMethod::class => autowire(),
    Method\CatalogMethod::class => autowire(),
    Method\GenreMethod::class => autowire(),
    Method\LabelMethod::class => autowire(),
    Method\LicenseMethod::class => autowire(),
    Method\LiveStreamMethod::class => autowire(),
    Method\PlaylistMethod::class => autowire(),
    Method\PodcastMethod::class => autowire(),
    Method\PodcastEpisodeMethod::class => autowire(),
    Method\ShareMethod::class => autowire(),
    Method\SmartlistMethod::class => autowire(),
    Method\SongMethod::class => autowire(),
    Method\VideoMethod::class => autowire(),
    Method\BookmarkMethod::class => autowire(),
    Method\GetBookmarkMethod::class => autowire(),
    Method\UserMethod::class => autowire(),
    Method\AlbumSongsMethod::class => autowire(),
    Method\ArtistAlbumsMethod::class => autowire(),
    Method\ArtistSongsMethod::class => autowire(),
    Method\GenreAlbumsMethod::class => autowire(),
    Method\GenreArtistsMethod::class => autowire(),
    Method\GenreSongsMethod::class => autowire(),
    Method\LabelArtistsMethod::class => autowire(),
    Method\LicenseSongsMethod::class => autowire(),
    Method\PlaylistSongsMethod::class => autowire(),
    Method\SmartlistSongsMethod::class => autowire(),
    Method\IndexMethod::class => autowire(),
    Method\ListMethod::class => autowire(),
    Method\FriendsTimelineMethod::class => autowire(),
    Method\SongTagsMethod::class => autowire(),
    Method\PlayerMethod::class => autowire(),
    Method\Api3\Album3Method::class => autowire(),
    Method\Api3\Albums3Method::class => autowire(),
    Method\Api4\Album4Method::class => autowire(),
    Method\Api4\Albums4Method::class => autowire(),
    Method\Api4\PodcastDelete4Method::class => autowire(),
    Method\Api4\PodcastEpisodes4Method::class => autowire(),
    Method\Api5\Album5Method::class => autowire(),
    Method\Api5\Albums5Method::class => autowire(),
    Method\Api5\PodcastDelete5Method::class => autowire(),
    Method\Api5\PodcastEpisodes5Method::class => autowire(),
];
