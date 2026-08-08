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

use Ampache\Gui\Browse\ListRenderer\AlbumListRenderer;
use Ampache\Gui\Browse\ListRenderer\ArtistListRenderer;
use Ampache\Gui\Browse\ListRenderer\BroadcastListRenderer;
use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocator;
use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocatorInterface;
use Ampache\Gui\Browse\ListRenderer\CatalogListRenderer;
use Ampache\Gui\Browse\ListRenderer\CollectionItemsListRenderer;
use Ampache\Gui\Browse\ListRenderer\CollectionListRenderer;
use Ampache\Gui\Browse\ListRenderer\DemocraticListRenderer;
use Ampache\Gui\Browse\ListRenderer\FolderListRenderer;
use Ampache\Gui\Browse\ListRenderer\GenreListRenderer;
use Ampache\Gui\Browse\ListRenderer\LabelListRenderer;
use Ampache\Gui\Browse\ListRenderer\LicenseListRenderer;
use Ampache\Gui\Browse\ListRenderer\LiveStreamListRenderer;
use Ampache\Gui\Browse\ListRenderer\LocalplayPlaylistListRenderer;
use Ampache\Gui\Browse\ListRenderer\PlaylistListRenderer;
use Ampache\Gui\Browse\ListRenderer\PlaylistMediaListRenderer;
use Ampache\Gui\Browse\ListRenderer\PodcastEpisodeListRenderer;
use Ampache\Gui\Browse\ListRenderer\PodcastListRenderer;
use Ampache\Gui\Browse\ListRenderer\PrivateMessageListRenderer;
use Ampache\Gui\Browse\ListRenderer\ShareListRenderer;
use Ampache\Gui\Browse\ListRenderer\ShoutboxListRenderer;
use Ampache\Gui\Browse\ListRenderer\SmartPlaylistListRenderer;
use Ampache\Gui\Browse\ListRenderer\SongListRenderer;
use Ampache\Gui\Browse\ListRenderer\SongPreviewListRenderer;
use Ampache\Gui\Browse\ListRenderer\UserListRenderer;
use Ampache\Gui\Browse\ListRenderer\VideoListRenderer;
use Ampache\Gui\Browse\ListRenderer\WantedListRenderer;
use Ampache\Gui\Form\LoginFormViewFactory;
use Ampache\Gui\Form\LoginFormViewFactoryInterface;
use Ampache\Gui\Form\StatsFormViewFactory;
use Ampache\Gui\Form\StatsFormViewFactoryInterface;
use Ampache\Gui\Preferences\PreferencesViewFactory;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;

use function DI\autowire;
use function DI\get;

return [
    GuiFactoryInterface::class => autowire(GuiFactory::class),
    // one renderer per migrated browse type; a type absent here still falls back to its .inc.php template
    BrowseListRendererLocatorInterface::class => autowire(BrowseListRendererLocator::class)
        ->constructorParameter('renderers', [
            'label' => get(LabelListRenderer::class),
            'album' => get(AlbumListRenderer::class),
            'album_disk' => get(AlbumListRenderer::class),
            'artist' => get(ArtistListRenderer::class),
            'broadcast' => get(BroadcastListRenderer::class),
            'catalog' => get(CatalogListRenderer::class),
            'license' => get(LicenseListRenderer::class),
            'license_hidden' => get(LicenseListRenderer::class),
            'live_stream' => get(LiveStreamListRenderer::class),
            'podcast' => get(PodcastListRenderer::class),
            'podcast_episode' => get(PodcastEpisodeListRenderer::class),
            'pvmsg' => get(PrivateMessageListRenderer::class),
            'share' => get(ShareListRenderer::class),
            'shoutbox' => get(ShoutboxListRenderer::class),
            'collection' => get(CollectionListRenderer::class),
            'collection_items' => get(CollectionItemsListRenderer::class),
            'democratic' => get(DemocraticListRenderer::class),
            'playlist' => get(PlaylistListRenderer::class),
            'playlist_localplay' => get(LocalplayPlaylistListRenderer::class),
            'playlist_media' => get(PlaylistMediaListRenderer::class),
            'playlist_search' => get(SmartPlaylistListRenderer::class),
            'smartplaylist' => get(SmartPlaylistListRenderer::class),
            'folder' => get(FolderListRenderer::class),
            'follower' => get(UserListRenderer::class),
            'genre' => get(GenreListRenderer::class),
            'song' => get(SongListRenderer::class),
            'song_preview' => get(SongPreviewListRenderer::class),
            'user' => get(UserListRenderer::class),
            'video' => get(VideoListRenderer::class),
            'wanted' => get(WantedListRenderer::class),
        ]),
    LoginFormViewFactoryInterface::class => autowire(LoginFormViewFactory::class),
    PreferencesViewFactoryInterface::class => autowire(PreferencesViewFactory::class),
    StatsFormViewFactoryInterface::class => autowire(StatsFormViewFactory::class),
];
