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
        'handshake' => ApiMethods\Handshake::class,
        'ping' => ApiMethods\Ping::class,
        'goodbye' => ApiMethods\Goodbye::class,
        'url_to_song' => ApiMethods\UrlToSong::class,
        'get_indexes' => ApiMethods\GetIndexes::class,
        'get_similar' => ApiMethods\GetSimilar::class,
        'advanced_search' => ApiMethods\AdvancedSearch::class,
        'artists' => ApiMethods\Artists::class,
        'artist' => ApiMethods\Artist::class,
        'artist_albums' => ApiMethods\ArtistAlbums::class,
        'artist_songs' => ApiMethods\ArtistSongs::class,
        'albums' => ApiMethods\Albums::class,
        'album' => ApiMethods\Album::class,
        'album_songs' => ApiMethods\AlbumSongs::class,
        'licenses' => ApiMethods\Licenses::class,
        'license' => ApiMethods\License::class,
        'license_songs' => ApiMethods\LicenseSongs::class,
        'tags' => ApiMethods\Genres::class,
        'tag' => ApiMethods\Genre::class,
        'tag_artists' => ApiMethods\GenreArtists::class,
        'tag_albums' => ApiMethods\GenreAlbums::class,
        'tag_songs' => ApiMethods\GenreSongs::class,
        'genres' => ApiMethods\Genres::class,
        'genre' => ApiMethods\Genre::class,
        'genre_artists' => ApiMethods\GenreArtists::class,
        'genre_albums' => ApiMethods\GenreAlbums::class,
        'genre_songs' => ApiMethods\GenreSongs::class,
        'songs' => ApiMethods\Songs::class,
        'song' => ApiMethods\Song::class,
        'song_delete' => ApiMethods\SongDelete::class,
        'playlists' => ApiMethods\Playlists::class,
        'playlist' => ApiMethods\Playlist::class,
        'playlist_songs' => ApiMethods\PlaylistSongs::class,
        'playlist_create' => ApiMethods\PlaylistCreate::class,
        'playlist_edit' => ApiMethods\PlaylistEdit::class,
        'playlist_delete' => ApiMethods\PlaylistDelete::class,
        'playlist_add_song' => ApiMethods\PlaylistAddSong::class,
        'playlist_remove_song' => ApiMethods\PlaylistRemoveSong::class,
        'playlist_generate' => ApiMethods\PlaylistGenerate::class,
        'search_songs' => ApiMethods\SearchSongs::class,
        'shares' => ApiMethods\Shares::class,
        'share' => ApiMethods\Share::class,
        'share_create' => ApiMethods\ShareCreate::class,
        'share_delete' => ApiMethods\ShareDelete::class,
        'share_edit' => ApiMethods\ShareEdit::class,
        'videos' => ApiMethods\Videos::class,
        'video' => ApiMethods\Video::class,
        'stats' => ApiMethods\Stats::class,
        'podcasts' => ApiMethods\Podcasts::class,
        'podcast' => ApiMethods\Podcast::class,
        'podcast_create' => ApiMethods\PodcastCreate::class,
        'podcast_delete' => ApiMethods\PodcastDelete::class,
        'podcast_edit' => ApiMethods\PodcastEdit::class,
        'podcast_episodes' => ApiMethods\PodcastEpisodes::class,
        'podcast_episode' => ApiMethods\PodcastEpisode::class,
        'podcast_episode_delete' => ApiMethods\PodcastEpisodeDelete::class,
        'users' => ApiMethods\Users::class,
        'user' => ApiMethods\User::class,
        'user_preferences' => ApiMethods\UserPreferences::class,
        'user_create' => ApiMethods\UserCreate::class,
        'user_update' => ApiMethods\UserUpdate::class,
        'user_delete' => ApiMethods\UserDelete::class,
        'followers' => ApiMethods\Followers::class,
        'following' => ApiMethods\Following::class,
        'toggle_follow' => ApiMethods\ToggleFollow::class,
        'last_shouts' => ApiMethods\LastShouts::class,
        'rate' => ApiMethods\Rate::class,
        'flag' => ApiMethods\Flag::class,
        'record_play' => ApiMethods\RecordPlay::class,
        'scrobble' => ApiMethods\Scrobble::class,
        'catalogs' => ApiMethods\Catalogs::class,
        'catalog' => ApiMethods\Catalog::class,
        'catalog_action' => ApiMethods\CatalogAction::class,
        'catalog_file' => ApiMethods\CatalogFile::class,
        'timeline' => ApiMethods\Timeline::class,
        'friends_timeline' => ApiMethods\FriendsTimeline::class,
        'update_from_tags' => ApiMethods\UpdateFromTags::class,
        'update_artist_info' => ApiMethods\UpdateArtistInfo::class,
        'update_art' => ApiMethods\UpdateArt::class,
        'update_podcast' => ApiMethods\UpdatePodcast::class,
        'stream' => ApiMethods\Stream::class,
        'download' => ApiMethods\Download::class,
        'get_art' => ApiMethods\GetArt::class,
        'localplay' => ApiMethods\Localplay::class,
        'democratic' => ApiMethods\Democratic::class,
        'system_update' => ApiMethods\SystemUpdate::class,
        'system_preferences' => ApiMethods\SystemPreferences::class,
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
