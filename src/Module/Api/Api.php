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

declare(strict_types=0);

namespace Ampache\Module\Api;

use Ampache\Module\Authorization\Access;
use Ampache\Model\Browse;
use Lib\ApiMethods;

/**
 * API Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 */
class Api
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     */
    public const METHOD_LIST = [
        'handshake' => Method\HandshakeMethod::class,
        'ping' => Method\PingMethod::class,
        'goodbye' => Method\GoodbyeMethod::class,
        'url_to_song' => Method\UrlToSongMethod::class,
        'get_indexes' => Method\GetIndexesMethod::class,
        'get_similar' => Method\GetSimilarMethod::class,
        'advanced_search' => Method\AdvancedSearchMethod::class,
        'artists' => Method\ArtistsMethod::class,
        'artist' => Method\ArtistMethod::class,
        'artist_albums' => Method\ArtistAlbumsMethod::class,
        'artist_songs' => Method\ArtistSongsMethod::class,
        'albums' => Method\AlbumsMethod::class,
        'album' => Method\AlbumMethod::class,
        'album_songs' => Method\AlbumSongsMethod::class,
        'licenses' => Method\LicensesMethod::class,
        'license' => Method\LicenseMethod::class,
        'license_songs' => Method\LicenseSongsMethod::class,
        'tags' => Method\TagsMethod::class,
        'tag' => Method\TagMethod::class,
        'tag_artists' => Method\TagArtistsMethod::class,
        'tag_albums' => Method\TagAlbumsMethod::class,
        'tag_songs' => Method\TagSongsMethod::class,
        'genres' => Method\GenresMethod::class,
        'genre' => Method\GenreMethod::class,
        'genre_artists' => Method\GenreArtistsMethod::class,
        'genre_albums' => Method\GenreAlbumsMethod::class,
        'genre_songs' => Method\GenreSongsMethod::class,
        'songs' => Method\SongsMethod::class,
        'song' => Method\SongMethod::class,
        'song_delete' => Method\SongDeleteMethod::class,
        'playlists' => Method\PlaylistsMethod::class,
        'playlist' => Method\PlaylistMethod::class,
        'playlist_songs' => Method\PlaylistSongsMethod::class,
        'playlist_create' => Method\PlaylistCreateMethod::class,
        'playlist_edit' => Method\PlaylistEditMethod::class,
        'playlist_delete' => Method\PlaylistDeleteMethod::class,
        'playlist_add_song' => Method\PlaylistAddSongMethod::class,
        'playlist_remove_song' => Method\PlaylistRemoveSongMethod::class,
        'playlist_generate' => Method\PlaylistGenerateMethod::class,
        'search_songs' => Method\SearchSongsMethod::class,
        'shares' => Method\SharesMethod::class,
        'share' => Method\ShareMethod::class,
        'share_create' => Method\ShareCreateMethod::class,
        'share_delete' => Method\ShareDeleteMethod::class,
        'share_edit' => Method\ShareEditMethod::class,
        'videos' => Method\VideosMethod::class,
        'video' => Method\VideoMethod::class,
        'stats' => Method\StatsMethod::class,
        'podcasts' => Method\PodcastsMethod::class,
        'podcast' => Method\PodcastMethod::class,
        'podcast_create' => Method\PodcastCreateMethod::class,
        'podcast_delete' => Method\PodcastDeleteMethod::class,
        'podcast_edit' => Method\PodcastEditMethod::class,
        'podcast_episodes' => Method\PodcastEpisodesMethod::class,
        'podcast_episode' => Method\PodcastEpisodeMethod::class,
        'podcast_episode_delete' => Method\PodcastEpisodeDeleteMethod::class,
        'users' => Method\UsersMethod::class,
        'user' => Method\UserMethod::class,
        'user_preferences' => Method\UserPreferencesMethod::class,
        'user_create' => Method\UserCreateMethod::class,
        'user_update' => Method\UserUpdateMethod::class,
        'user_delete' => Method\UserDeleteMethod::class,
        'followers' => Method\FollowersMethod::class,
        'following' => Method\FollowingMethod::class,
        'toggle_follow' => Method\ToggleFollowMethod::class,
        'last_shouts' => Method\LastShoutsMethod::class,
        'rate' => Method\RateMethod::class,
        'flag' => Method\FlagMethod::class,
        'record_play' => Method\RecordPlayMethod::class,
        'scrobble' => Method\ScrobbleMethod::class,
        'catalogs' => Method\CatalogsMethod::class,
        'catalog' => Method\CatalogMethod::class,
        'catalog_action' => Method\CatalogActionMethod::class,
        'catalog_file' => Method\CatalogFileMethod::class,
        'timeline' => Method\TimelineMethod::class,
        'friends_timeline' => Method\FriendsTimelineMethod::class,
        'update_from_tags' => Method\UpdateFromTagsMethod::class,
        'update_artist_info' => Method\UpdateArtistInfoMethod::class,
        'update_art' => Method\UpdateArtMethod::class,
        'update_podcast' => Method\UpdatePodcastMethod::class,
        'stream' => Method\StreamMethod::class,
        'download' => Method\DownloadMethod::class,
        'get_art' => Method\GetArtMethod::class,
        'localplay' => Method\LocalplayMethod::class,
        'democratic' => Method\DemocraticMethod::class,
        'system_update' => Method\SystemUpdateMethod::class,
        'system_preferences' => Method\SystemPreferencesMethod::class,
    ];

    /**
     *  @var string $auth_version
     */
    public static $auth_version = '350001';

    /**
     * @var string $version
     */
    public static $version = '430000';

    /**
     * @var \Ampache\Model\Browse $browse
     */
    public static $browse = null;

    private static function getBrowse(): Browse
    {
        if (self::$browse === null) {
            self::$browse = new Browse(null, false);
        }

        return self::$browse;
    }

    /**
     * message
     * call the correct error / success message depending on format
     * @param string $type
     * @param string $message
     * @param string $error_code
     * @param string $format
     * @param array $return_data
     */
    public static function message($type, $message, $error_code = null, $format = 'xml', $return_data = array())
    {
        if ($type === 'error') {
            switch ($format) {
                case 'json':
                    echo Json_Data::error($error_code, $message, $return_data);
                    break;
                default:
                    echo Xml_Data::error($error_code, $message, $return_data);
            }
        }
        if ($type === 'success') {
            switch ($format) {
                case 'json':
                    echo Json_Data::success($message, $return_data);
                    break;
                default:
                    echo Xml_Data::success($message, $return_data);
            }
        }
    } // message

    /**
     * set_filter
     * MINIMUM_API_VERSION=380001
     *
     * This is a play on the browse function, it's different as we expose
     * the filters in a slightly different and vastly simpler way to the
     * end users--so we have to do a little extra work to make them work
     * internally.
     * @param string $filter
     * @param integer|string|boolean|null $value
     * @return boolean
     */
    public static function set_filter($filter, $value)
    {
        if (!strlen((string)$value)) {
            return false;
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    self::getBrowse()->set_filter('add_lt', strtotime($elements['1']));
                    self::getBrowse()->set_filter('add_gt', strtotime($elements['0']));
                } else {
                    self::getBrowse()->set_filter('add_gt', strtotime($value));
                }
                break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    self::getBrowse()->set_filter('update_lt', strtotime($elements['1']));
                    self::getBrowse()->set_filter('update_gt', strtotime($elements['0']));
                } else {
                    self::getBrowse()->set_filter('update_gt', strtotime($value));
                }
                break;
            case 'alpha_match':
                self::getBrowse()->set_filter('alpha_match', $value);
                break;
            case 'exact_match':
                self::getBrowse()->set_filter('exact_match', $value);
                break;
            case 'enabled':
                self::getBrowse()->set_filter('enabled', $value);
                break;
            default:
                break;
        } // end filter

        return true;
    } // set_filter

    /**
     * check_parameter
     *
     * This function checks the $input actually has the parameter.
     * Parameters must be an array of required elements as a string
     *
     * @param array $input
     * @param string[] $parameters e.g. array('auth', type')
     * @param string $method
     * @return boolean
     */
    public static function check_parameter($input, $parameters, $method = '')
    {
        foreach ($parameters as $parameter) {
            if ($input[$parameter] === 0 || $input[$parameter] === '0') {
                continue;
            }
            if (empty($input[$parameter])) {
                debug_event('api.class', "'" . $parameter . "' required on " . $method . " function call.", 2);
                self::message('error', T_('Missing mandatory parameter') . " '" . $parameter . "'", '400', $input['api_format']);

                return false;
            }
        }

        return true;
    } // check_parameter

    /**
     * check_access
     *
     * This function checks the user can perform the function requested
     * 'interface', 100, User::get_from_username(Session::username($input['auth']))->id)
     *
     * @param string $type
     * @param integer $level
     * @param integer $user_id
     * @param string $method
     * @param string $format
     * @return boolean
     */
    public static function check_access($type, $level, $user_id, $method = '', $format = 'xml')
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event('api.class', $type . " '" . $level . "' required on " . $method . " function call.", 2);
            self::message('error', 'User does not have access to this function', '412', $format);

            return false;
        }

        return true;
    } // check_access
}
