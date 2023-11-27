<?php

declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api;

use Ampache\Module\Authorization\Access;

/**
 * API Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 *
 */
class Api4
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     */
    public const METHOD_LIST = [
        'advanced_search' => Method\Api4\AdvancedSearch4Method::class,
        'album' => Method\Api4\Album4Method::class,
        'albums' => Method\Api4\Albums4Method::class,
        'album_songs' => Method\Api4\AlbumSongs4Method::class,
        'artist' => Method\Api4\Artist4Method::class,
        'artists' => Method\Api4\Artists4Method::class,
        'artist_albums' => Method\Api4\ArtistAlbums4Method::class,
        'artist_songs' => Method\Api4\ArtistSongs4Method::class,
        'catalog' => Method\Api4\Catalog4Method::class,
        'catalogs' => Method\Api4\Catalogs4Method::class,
        'catalog_action' => Method\Api4\CatalogAction4Method::class,
        'catalog_file' => Method\Api4\CatalogFile4Method::class,
        'democratic' => Method\Api4\Democratic4Method::class,
        'download' => Method\Api4\Download4Method::class,
        'flag' => Method\Api4\Flag4Method::class,
        'followers' => Method\Api4\Followers4Method::class,
        'following' => Method\Api4\Following4Method::class,
        'friends_timeline' => Method\Api4\FriendsTimeline4Method::class,
        'get_art' => Method\Api4\GetArt4Method::class,
        'get_indexes' => Method\Api4\GetIndexes4Method::class,
        'get_similar' => Method\Api4\GetSimilar4Method::class,
        'goodbye' => Method\Api4\Goodbye4Method::class,
        'handshake' => Method\Api4\Handshake4Method::class,
        'last_shouts' => Method\Api4\LastShouts4Method::class,
        'license' => Method\Api4\License4Method::class,
        'licenses' => Method\Api4\Licenses4Method::class,
        'license_songs' => Method\Api4\LicenseSongs4Method::class,
        'localplay' => Method\Api4\Localplay4Method::class,
        'ping' => Method\Api4\Ping4Method::class,
        'playlist' => Method\Api4\Playlist4Method::class,
        'playlist_add_song' => Method\Api4\PlaylistAddSong4Method::class,
        'playlist_create' => Method\Api4\PlaylistCreate4Method::class,
        'playlist_delete' => Method\Api4\PlaylistDelete4Method::class,
        'playlist_edit' => Method\Api4\PlaylistEdit4Method::class,
        'playlist_generate' => Method\Api4\PlaylistGenerate4Method::class,
        'playlist_remove_song' => Method\Api4\PlaylistRemoveSong4Method::class,
        'playlist_songs' => Method\Api4\PlaylistSongs4Method::class,
        'playlists' => Method\Api4\Playlists4Method::class,
        'podcast' => Method\Api4\Podcast4Method::class,
        'podcasts' => Method\Api4\Podcasts4Method::class,
        'podcast_create' => Method\Api4\PodcastCreate4Method::class,
        'podcast_delete' => Method\Api4\PodcastDelete4Method::class,
        'podcast_edit' => Method\Api4\PodcastEdit4Method::class,
        'podcast_episode' => Method\Api4\PodcastEpisode4Method::class,
        'podcast_episodes' => Method\Api4\PodcastEpisodes4Method::class,
        'podcast_episode_delete' => Method\Api4\PodcastEpisodeDelete4Method::class,
        'rate' => Method\Api4\Rate4Method::class,
        'record_play' => Method\Api4\RecordPlay4Method::class,
        'scrobble' => Method\Api4\Scrobble4Method::class,
        'search_songs' => Method\Api4\SearchSongs4Method::class,
        'share' => Method\Api4\Share4Method::class,
        'shares' => Method\Api4\Shares4Method::class,
        'share_create' => Method\Api4\ShareCreate4Method::class,
        'share_delete' => Method\Api4\ShareDelete4Method::class,
        'share_edit' => Method\Api4\ShareEdit4Method::class,
        'song' => Method\Api4\Song4Method::class,
        'songs' => Method\Api4\Songs4Method::class,
        'stats' => Method\Api4\Stats4Method::class,
        'stream' => Method\Api4\Stream4Method::class,
        'tag' => Method\Api4\Tag4Method::class,
        'tags' => Method\Api4\Tags4Method::class,
        'tag_albums' => Method\Api4\TagAlbums4Method::class,
        'tag_artists' => Method\Api4\TagArtists4Method::class,
        'tag_songs' => Method\Api4\TagSongs4Method::class,
        'genre' => Method\Api4\Genre4Method::class,
        'genres' => Method\Api4\Genres4Method::class,
        'genre_albums' => Method\Api4\GenreAlbums4Method::class,
        'genre_artists' => Method\Api4\GenreArtists4Method::class,
        'genre_songs' => Method\Api4\GenreSongs4Method::class,
        'timeline' => Method\Api4\Timeline4Method::class,
        'toggle_follow' => Method\Api4\ToggleFollow4Method::class,
        'update_art' => Method\Api4\UpdateArt4Method::class,
        'update_artist_info' => Method\Api4\UpdateArtistInfo4Method::class,
        'update_from_tags' => Method\Api4\UpdateFromTags4Method::class,
        'update_podcast' => Method\Api4\UpdatePodcast4Method::class,
        'url_to_song' => Method\Api4\UrlToSong4Method::class,
        'user' => Method\Api4\User4Method::class,
        'users' => Method\Api4\Users4Method::class,
        'user_create' => Method\Api4\UserCreate4Method::class,
        'user_delete' => Method\Api4\UserDelete4Method::class,
        'user_update' => Method\Api4\UserUpdate4Method::class,
        'video' => Method\Api4\Video4Method::class,
        'videos' => Method\Api4\Videos4Method::class
    ];

    public static string $auth_version = '350001';
    public static string $version      = '443000'; // AMPACHE_VERSION

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
        // Rien a faire
    }

    /**
     * message
     * call the correct error / success message depending on format
     * @param string $type
     * @param string $message
     * @param string $error_code
     * @param string $format
     */
    public static function message($type, $message, $error_code = null, $format = 'xml'): void
    {
        if ($type === 'error') {
            switch ($format) {
                case 'json':
                    echo Json4_Data::error($error_code, $message);
                    break;
                default:
                    echo Xml4_Data::error($error_code, $message);
            }
        }
        if ($type === 'success') {
            switch ($format) {
                case 'json':
                    echo Json4_Data::success($message);
                    break;
                default:
                    echo Xml4_Data::success($message);
            }
        }
    }

    /**
     * check_parameter
     *
     * This function checks the $input actually has the parameter.
     * Parameters must be an array of required elements as a string
     *
     * @param array $input
     * @param string[] $parameters e.g. array('auth', type')
     * @param string $method
     */
    public static function check_parameter($input, $parameters, $method = ''): bool
    {
        foreach ($parameters as $parameter) {
            if ($input[$parameter] === 0 || $input[$parameter] === '0') {
                continue;
            }
            if (empty($input[$parameter])) {
                debug_event(self::class, "'" . $parameter . "' required on " . $method . " function call.", 2);
                Api4::message('error', T_('Missing mandatory parameter') . " '" . $parameter . "'", '401', $input['api_format']);

                return false;
            }
        }

        return true;
    }

    /**
     * check_access
     *
     * This function checks the user can perform the function requested
     * 'interface', 100, $user->id
     *
     * @param string $type
     * @param int $level
     * @param int $user_id
     * @param string $method
     * @param string $format
     */
    public static function check_access($type, $level, $user_id, $method = '', $format = 'xml'): bool
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event(self::class, $type . " '" . $level . "' required on " . $method . " function call.", 2);
            Api4::message('error', 'User does not have access to this function', '400', $format);

            return false;
        }

        return true;
    }
} // end api.class
