<?php

declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
*/

namespace Ampache\Module\Api;

/**
 * API Class
 *
 * This handles functions relating to the API written for ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 *
 */
class Api3
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     */
    public const METHOD_LIST = [
        'advanced_search' => Method\Api3\AdvancedSearch3Method::class,
        'album' => Method\Api3\Album3Method::class,
        'album_songs' => Method\Api3\AlbumSongs3Method::class,
        'albums' => Method\Api3\Albums3Method::class,
        'artist' => Method\Api3\Artist3Method::class,
        'artist_albums' => Method\Api3\ArtistAlbums3Method::class,
        'artist_songs' => Method\Api3\ArtistSongs3Method::class,
        'artists' => Method\Api3\Artists3Method::class,
        'democratic' => Method\Api3\Democratic3Method::class,
        'followers' => Method\Api3\Followers3Method::class,
        'following' => Method\Api3\Following3Method::class,
        'friends_timeline' => Method\Api3\FriendsTimeline3Method::class,
        'handshake' => Method\Api3\Handshake3Method::class,
        'last_shouts' => Method\Api3\LastShouts3Method::class,
        'localplay' => Method\Api3\Localplay3Method::class,
        'ping' => Method\Api3\Ping3Method::class,
        'playlist' => Method\Api3\Playlist3Method::class,
        'playlist_add_song' => Method\Api3\PlaylistAddSong3Method::class,
        'playlist_create' => Method\Api3\PlaylistCreate3Method::class,
        'playlist_delete' => Method\Api3\PlaylistDelete3Method::class,
        'playlist_remove_song' => Method\Api3\PlaylistRemoveSong3Method::class,
        'playlist_songs' => Method\Api3\PlaylistSongs3Method::class,
        'playlists' => Method\Api3\Playlists3Method::class,
        'rate' => Method\Api3\Rate3Method::class,
        'search_songs' => Method\Api3\SearchSongs3Method::class,
        'song' => Method\Api3\Song3Method::class,
        'songs' => Method\Api3\Songs3Method::class,
        'stats' => Method\Api3\Stats3Method::class,
        'tag' => Method\Api3\Tag3Method::class,
        'genre' => Method\Api3\Tag3Method::class,
        'tag_albums' => Method\Api3\TagAlbums3Method::class,
        'genre_albums' => Method\Api3\TagAlbums3Method::class,
        'tag_artists' => Method\Api3\TagArtists3Method::class,
        'genre_artists' => Method\Api3\TagArtists3Method::class,
        'tag_songs' => Method\Api3\TagSongs3Method::class,
        'genre_songs' => Method\Api3\TagSongs3Method::class,
        'tags' => Method\Api3\Tags3Method::class,
        'genres' => Method\Api3\Tags3Method::class,
        'timeline' => Method\Api3\Timeline3Method::class,
        'toggle_follow' => Method\Api3\ToggleFollow3Method::class,
        'url_to_song' => Method\Api3\UrlToSong3Method::class,
        'user' => Method\Api3\User3Method::class,
        'video' => Method\Api3\Video3Method::class,
        'videos' => Method\Api3\Videos3Method::class
    ];

    public static string $auth_version = '350001';
    public static string $version      = '390001'; // AMPACHE_VERSION

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
        // Rien a faire
    } // constructor
} // API3 class
