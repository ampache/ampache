<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

use Lib\ApiMethods;

/**
 * API Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 *
 */
class Api
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     */
    public const METHOD_LIST = [
        'handshake' => ApiMethods\HandshakeMethod::class,
        'ping' => ApiMethods\PingMethod::class,
        'goodbye' => ApiMethods\GoodbyeMethod::class,
        'url_to_song' => ApiMethods\UrlToSongMethod::class,
        'get_indexes' => ApiMethods\GetIndexesMethod::class,
        'get_similar' => ApiMethods\GetSimilarMethod::class,
        'advanced_search' => ApiMethods\AdvancedSearchMethod::class,
        'artists' => ApiMethods\ArtistsMethod::class,
        'artist' => ApiMethods\ArtistMethod::class,
        'artist_albums' => ApiMethods\ArtistAlbumsMethod::class,
        'artist_songs' => ApiMethods\ArtistSongsMethod::class,
        'albums' => ApiMethods\AlbumsMethod::class,
        'album' => ApiMethods\AlbumMethod::class,
        'album_songs' => ApiMethods\AlbumSongsMethod::class,
        'licenses' => ApiMethods\LicensesMethod::class,
        'license' => ApiMethods\LicenseMethod::class,
        'license_songs' => ApiMethods\LicenseSongsMethod::class,
        'tags' => ApiMethods\TagsMethod::class,
        'tag' => ApiMethods\TagMethod::class,
        'tag_artists' => ApiMethods\TagArtistsMethod::class,
        'tag_albums' => ApiMethods\TagAlbumsMethod::class,
        'tag_songs' => ApiMethods\TagSongsMethod::class,
        'genres' => ApiMethods\GenresMethod::class,
        'genre' => ApiMethods\GenreMethod::class,
        'genre_artists' => ApiMethods\GenreArtistsMethod::class,
        'genre_albums' => ApiMethods\GenreAlbumsMethod::class,
        'genre_songs' => ApiMethods\GenreSongsMethod::class,
        'songs' => ApiMethods\SongsMethod::class,
        'song' => ApiMethods\SongMethod::class,
        'song_delete' => ApiMethods\SongDeleteMethod::class,
        'playlists' => ApiMethods\PlaylistsMethod::class,
        'playlist' => ApiMethods\PlaylistMethod::class,
        'playlist_songs' => ApiMethods\PlaylistSongsMethod::class,
        'playlist_create' => ApiMethods\PlaylistCreateMethod::class,
        'playlist_edit' => ApiMethods\PlaylistEditMethod::class,
        'playlist_delete' => ApiMethods\PlaylistDeleteMethod::class,
        'playlist_add_song' => ApiMethods\PlaylistAddSongMethod::class,
        'playlist_remove_song' => ApiMethods\PlaylistRemoveSongMethod::class,
        'playlist_generate' => ApiMethods\PlaylistGenerateMethod::class,
        'search_songs' => ApiMethods\SearchSongsMethod::class,
        'shares' => ApiMethods\SharesMethod::class,
        'share' => ApiMethods\ShareMethod::class,
        'share_create' => ApiMethods\ShareCreateMethod::class,
        'share_delete' => ApiMethods\ShareDeleteMethod::class,
        'share_edit' => ApiMethods\ShareEditMethod::class,
        'videos' => ApiMethods\VideosMethod::class,
        'video' => ApiMethods\VideoMethod::class,
        'stats' => ApiMethods\StatsMethod::class,
        'podcasts' => ApiMethods\PodcastsMethod::class,
        'podcast' => ApiMethods\PodcastMethod::class,
        'podcast_create' => ApiMethods\PodcastCreateMethod::class,
        'podcast_delete' => ApiMethods\PodcastDeleteMethod::class,
        'podcast_edit' => ApiMethods\PodcastEditMethod::class,
        'podcast_episodes' => ApiMethods\PodcastEpisodesMethod::class,
        'podcast_episode' => ApiMethods\PodcastEpisodeMethod::class,
        'podcast_episode_delete' => ApiMethods\PodcastEpisodeDeleteMethod::class,
        'users' => ApiMethods\UsersMethod::class,
        'user' => ApiMethods\UserMethod::class,
        'user_preferences' => ApiMethods\UserPreferencesMethod::class,
        'user_preference' => ApiMethods\UserPreferenceMethod::class,
        'user_create' => ApiMethods\UserCreateMethod::class,
        'user_update' => ApiMethods\UserUpdateMethod::class,
        'user_delete' => ApiMethods\UserDeleteMethod::class,
        'followers' => ApiMethods\FollowersMethod::class,
        'following' => ApiMethods\FollowingMethod::class,
        'toggle_follow' => ApiMethods\ToggleFollowMethod::class,
        'last_shouts' => ApiMethods\LastShoutsMethod::class,
        'rate' => ApiMethods\RateMethod::class,
        'flag' => ApiMethods\FlagMethod::class,
        'record_play' => ApiMethods\RecordPlayMethod::class,
        'scrobble' => ApiMethods\ScrobbleMethod::class,
        'catalogs' => ApiMethods\CatalogsMethod::class,
        'catalog' => ApiMethods\CatalogMethod::class,
        'catalog_action' => ApiMethods\CatalogActionMethod::class,
        'catalog_file' => ApiMethods\CatalogFileMethod::class,
        'timeline' => ApiMethods\TimelineMethod::class,
        'friends_timeline' => ApiMethods\FriendsTimelineMethod::class,
        'update_from_tags' => ApiMethods\UpdateFromTagsMethod::class,
        'update_artist_info' => ApiMethods\UpdateArtistInfoMethod::class,
        'update_art' => ApiMethods\UpdateArtMethod::class,
        'update_podcast' => ApiMethods\UpdatePodcastMethod::class,
        'stream' => ApiMethods\StreamMethod::class,
        'download' => ApiMethods\DownloadMethod::class,
        'get_art' => ApiMethods\GetArtMethod::class,
        'localplay' => ApiMethods\LocalplayMethod::class,
        'democratic' => ApiMethods\DemocraticMethod::class,
        'system_update' => ApiMethods\SystemUpdateMethod::class,
        'system_preferences' => ApiMethods\SystemPreferencesMethod::class,
        'system_preference' => ApiMethods\SystemPreferenceMethod::class,
    ];

    /**
     *  @var string $auth_version
     */
    public static $auth_version = '350001';

    /**
     *  @var string $version
     */
    public static $version = '430000';

    /**
     *  @var Browse $browse
     */
    public static $browse = null;

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
        // Rien a faire
    } // constructor

    /**
     * _auto_init
     * Automatically called when this class is loaded.
     */
    public static function _auto_init()
    {
        if (self::$browse === null) {
            self::$browse = new Browse(null, false);
        }
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
                    echo JSON_Data::error($error_code, $message, $return_data);
                    break;
                default:
                    echo XML_Data::error($error_code, $message, $return_data);
            }
        }
        if ($type === 'success') {
            switch ($format) {
                case 'json':
                    echo JSON_Data::success($message, $return_data);
                    break;
                default:
                    echo XML_Data::success($message, $return_data);
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
        if (!strlen((string) $value)) {
            return false;
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    self::$browse->set_filter('add_lt', strtotime($elements['1']));
                    self::$browse->set_filter('add_gt', strtotime($elements['0']));
                } else {
                    self::$browse->set_filter('add_gt', strtotime($value));
                }
                break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    self::$browse->set_filter('update_lt', strtotime($elements['1']));
                    self::$browse->set_filter('update_gt', strtotime($elements['0']));
                } else {
                    self::$browse->set_filter('update_gt', strtotime($value));
                }
                break;
            case 'alpha_match':
                self::$browse->set_filter('alpha_match', $value);
                break;
            case 'exact_match':
                self::$browse->set_filter('exact_match', $value);
                break;
            case 'enabled':
                self::$browse->set_filter('enabled', $value);
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
