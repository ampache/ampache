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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Authorization\Access;
use Ampache\Repository\Model\Browse;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\UserRepositoryInterface;

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
        'get_bookmark' => Method\GetBookmarkMethod::class,
        'get_similar' => Method\GetSimilarMethod::class,
        'advanced_search' => Method\AdvancedSearchMethod::class,
        'artists' => Method\ArtistsMethod::class,
        'artist' => Method\ArtistMethod::class,
        'artist_albums' => Method\ArtistAlbumsMethod::class,
        'artist_songs' => Method\ArtistSongsMethod::class,
        Method\AlbumsMethod::ACTION => Method\AlbumsMethod::class,
        Method\AlbumMethod::ACTION => Method\AlbumMethod::class,
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
        'labels' => Method\LabelsMethod::class,
        'label' => Method\LabelMethod::class,
        'label_artists' => Method\LabelArtistsMethod::class,
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
        'bookmarks' => Method\BookmarksMethod::class,
        'bookmark_create' => Method\BookmarkCreateMethod::class,
        'bookmark_edit' => Method\BookmarkEditMethod::class,
        'bookmark_delete' => Method\BookmarkDeleteMethod::class,
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
        'user_preference' => Method\UserPreferenceMethod::class,
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
        'localplay_songs' => Method\LocalplaySongsMethod::class,
        'democratic' => Method\DemocraticMethod::class,
        'system_update' => Method\SystemUpdateMethod::class,
        'system_preferences' => Method\SystemPreferencesMethod::class,
        'system_preference' => Method\SystemPreferenceMethod::class,
        'preference_create' => Method\PreferenceCreateMethod::class,
        'preference_edit' => Method\PreferenceEditMethod::class,
        'preference_delete' => Method\PreferenceDeleteMethod::class,
    ];

    /**
     * @var string $auth_version
     */
    public static $auth_version = '350001';

    /**
     * @var string $version
     */
    public static $version = '5.0.0';

    /**
     * @var Browse $browse
     */
    public static $browse = null;

    public static function getBrowse(): Browse
    {
        if (self::$browse === null) {
            self::$browse = new Browse(null, false);
        }

        return self::$browse;
    }

    /**
     * message
     * call the correct success message depending on format
     * @param string $message
     * @param string $format
     * @param array $return_data
     */
    public static function message($message, $format = 'xml', $return_data = array())
    {
        switch ($format) {
            case 'json':
                echo Json_Data::success($message, $return_data);
                break;
            default:
                echo Xml_Data::success($message, $return_data);
        }
    } // message

    /**
     * error
     * call the correct error message depending on format
     * @param string $message
     * @param string $error_code
     * @param string $method
     * @param string $error_type
     * @param string $format
     */
    public static function error($message, $error_code, $method, $error_type, $format = 'xml')
    {
        switch ($format) {
            case 'json':
                echo Json_Data::error($error_code, $message, $method, $error_type);
                break;
            default:
                echo Xml_Data::error($error_code, $message, $method, $error_type);
        }
    } // error

    /**
     * empty
     * call the correct empty message depending on format
     * @param string $empty_type
     * @param string $format
     */
    public static function empty($empty_type, $format = 'xml')
    {
        switch ($format) {
            case 'json':
                echo Json_Data::empty($empty_type);
                break;
            default:
                echo Xml_Data::empty();
        }
    } // empty

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
    public static function set_filter($filter, $value, ?Browse $browse = null)
    {
        if (!strlen((string)$value)) {
            return false;
        }

        if ($browse === null) {
            $browse = self::getBrowse();
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    $browse->set_filter('add_lt', strtotime((string)$elements['1']));
                    $browse->set_filter('add_gt', strtotime((string)$elements['0']));
                } else {
                    $browse->set_filter('add_gt', strtotime((string)$value));
                }
                break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    $browse->set_filter('update_lt', strtotime((string)$elements['1']));
                    $browse->set_filter('update_gt', strtotime((string)$elements['0']));
                } else {
                    $browse->set_filter('update_gt', strtotime((string)$value));
                }
                break;
            case 'alpha_match':
                $browse->set_filter('alpha_match', $value);
                break;
            case 'exact_match':
                $browse->set_filter('exact_match', $value);
                break;
            case 'enabled':
                $browse->set_filter('enabled', $value);
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
    public static function check_parameter($input, $parameters, $method)
    {
        foreach ($parameters as $parameter) {
            if ($input[$parameter] === 0 || $input[$parameter] === '0') {
                continue;
            }
            if (empty($input[$parameter])) {
                debug_event(__CLASS__, "'" . $parameter . "' required on " . $method . " function call.", 2);

                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                self::error(sprintf(T_('Bad Request: %s'), $parameter), '4710', $method, 'system', $input['api_format']);

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
    public static function check_access($type, $level, $user_id, $method, $format = 'xml')
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event(self::class, $type . " '" . $level . "' required on " . $method . " function call.", 2);
            /* HINT: Access level, eg 75, 100 */
            self::error(sprintf(T_('Require: %s'), $level), '4742', $method, 'account', $format);

            return false;
        }

        return true;
    } // check_access

    /**
     * server_details
     *
     * get the server counts for pings and handshakes
     *
     * @param string $token
     * @return array
     */
    public static function server_details($token = '')
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
        $db_results = Dba::read($sql);
        $details    = Dba::fetch_assoc($db_results);

        // Now we need to quickly get the totals
        $client      = static::getUserRepository()->findByApiKey(trim($token));
        $counts      = Catalog::get_server_counts($client->id);
        $album_count = (Preference::get('album_group', $client->id))
            ? (int)$counts['album_group']
            : (int)$counts['album'];
        $autharray = (!empty($token)) ? array('auth' => $token) : array();

        // send the totals
        $outarray = array('api' => Api::$version,
            'session_expire' => date("c", time() + AmpConfig::get('session_length') - 60),
            'update' => date("c", (int)$details['update']),
            'add' => date("c", (int)$details['add']),
            'clean' => date("c", (int)$details['clean']),
            'songs' => (int)$counts['song'],
            'albums' => $album_count,
            'artists' => (int)$counts['artist'],
            'genres' => (int)$counts['tag'],
            'playlists' => ((int)$counts['playlist'] + (int)$counts['search']),
            'users' => ((int)$counts['user'] + (int)$counts['user']),
            'catalogs' => (int)$counts['catalog'],
            'videos' => (int)$counts['video'],
            'podcasts' => (int)$counts['podcast'],
            'podcast_episodes' => (int)$counts['podcast_episode'],
            'shares' => (int)$counts['share'],
            'licenses' => (int)$counts['license'],
            'live_streams' => (int)$counts['live_stream'],
            'labels' => (int)$counts['label']);

        return array_merge($autharray, $outarray);
    } // server_details

    /**
     * @deprecated inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
