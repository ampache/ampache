<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Dba;
use Ampache\Repository\UserRepositoryInterface;

/**
 * API Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 */
class Api5
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     *
     * @var array<string, class-string<object>>
     */
    public const METHOD_LIST = [
        Method\Api5\Handshake5Method::ACTION => Method\Api5\Handshake5Method::class,
        Method\Api5\Ping5Method::ACTION => Method\Api5\Ping5Method::class,
        Method\Api5\Goodbye5Method::ACTION => Method\Api5\Goodbye5Method::class,
        Method\Api5\UrlToSong5Method::ACTION => Method\Api5\UrlToSong5Method::class,
        Method\Api5\GetIndexes5Method::ACTION => Method\Api5\GetIndexes5Method::class,
        Method\Api5\GetBookmark5Method::ACTION => Method\Api5\GetBookmark5Method::class,
        Method\Api5\GetSimilar5Method::ACTION => Method\Api5\GetSimilar5Method::class,
        Method\Api5\AdvancedSearch5Method::ACTION => Method\Api5\AdvancedSearch5Method::class,
        Method\Api5\Artists5Method::ACTION => Method\Api5\Artists5Method::class,
        Method\Api5\Artist5Method::ACTION => Method\Api5\Artist5Method::class,
        Method\Api5\ArtistAlbums5Method::ACTION => Method\Api5\ArtistAlbums5Method::class,
        Method\Api5\ArtistSongs5Method::ACTION => Method\Api5\ArtistSongs5Method::class,
        Method\Api5\Albums5Method::ACTION => Method\Api5\Albums5Method::class,
        Method\Api5\Album5Method::ACTION => Method\Api5\Album5Method::class,
        Method\Api5\AlbumSongs5Method::ACTION => Method\Api5\AlbumSongs5Method::class,
        Method\Api5\Licenses5Method::ACTION => Method\Api5\Licenses5Method::class,
        Method\Api5\License5Method::ACTION => Method\Api5\License5Method::class,
        Method\Api5\LicenseSongs5Method::ACTION => Method\Api5\LicenseSongs5Method::class,
        Method\Api5\Tags5Method::ACTION => Method\Api5\Tags5Method::class,
        Method\Api5\Tag5Method::ACTION => Method\Api5\Tag5Method::class,
        Method\Api5\TagArtists5Method::ACTION => Method\Api5\TagArtists5Method::class,
        Method\Api5\TagAlbums5Method::ACTION => Method\Api5\TagAlbums5Method::class,
        Method\Api5\TagSongs5Method::ACTION => Method\Api5\TagSongs5Method::class,
        Method\Api5\Genres5Method::ACTION => Method\Api5\Genres5Method::class,
        Method\Api5\Genre5Method::ACTION => Method\Api5\Genre5Method::class,
        Method\Api5\GenreArtists5Method::ACTION => Method\Api5\GenreArtists5Method::class,
        Method\Api5\GenreAlbums5Method::ACTION => Method\Api5\GenreAlbums5Method::class,
        Method\Api5\GenreSongs5Method::ACTION => Method\Api5\GenreSongs5Method::class,
        Method\Api5\Labels5Method::ACTION => Method\Api5\Labels5Method::class,
        Method\Api5\Label5Method::ACTION => Method\Api5\Label5Method::class,
        Method\Api5\LabelArtists5Method::ACTION => Method\Api5\LabelArtists5Method::class,
        Method\Api5\LiveStreams5Method::ACTION => Method\Api5\LiveStreams5Method::class,
        Method\Api5\LiveStream5Method::ACTION => Method\Api5\LiveStream5Method::class,
        Method\Api5\Songs5Method::ACTION => Method\Api5\Songs5Method::class,
        Method\Api5\Song5Method::ACTION => Method\Api5\Song5Method::class,
        Method\Api5\SongDelete5Method::ACTION => Method\Api5\SongDelete5Method::class,
        Method\Api5\Playlists5Method::ACTION => Method\Api5\Playlists5Method::class,
        Method\Api5\Playlist5Method::ACTION => Method\Api5\Playlist5Method::class,
        Method\Api5\PlaylistSongs5Method::ACTION => Method\Api5\PlaylistSongs5Method::class,
        Method\Api5\PlaylistCreate5Method::ACTION => Method\Api5\PlaylistCreate5Method::class,
        Method\Api5\PlaylistEdit5Method::ACTION => Method\Api5\PlaylistEdit5Method::class,
        Method\Api5\PlaylistDelete5Method::ACTION => Method\Api5\PlaylistDelete5Method::class,
        Method\Api5\PlaylistAddSong5Method::ACTION => Method\Api5\PlaylistAddSong5Method::class,
        Method\Api5\PlaylistRemoveSong5Method::ACTION => Method\Api5\PlaylistRemoveSong5Method::class,
        Method\Api5\PlaylistGenerate5Method::ACTION => Method\Api5\PlaylistGenerate5Method::class,
        Method\Api5\SearchSongs5Method::ACTION => Method\Api5\SearchSongs5Method::class,
        Method\Api5\Shares5Method::ACTION => Method\Api5\Shares5Method::class,
        Method\Api5\Share5Method::ACTION => Method\Api5\Share5Method::class,
        Method\Api5\ShareCreate5Method::ACTION => Method\Api5\ShareCreate5Method::class,
        Method\Api5\ShareDelete5Method::ACTION => Method\Api5\ShareDelete5Method::class,
        Method\Api5\ShareEdit5Method::ACTION => Method\Api5\ShareEdit5Method::class,
        Method\Api5\Bookmarks5Method::ACTION => Method\Api5\Bookmarks5Method::class,
        Method\Api5\BookmarkCreate5Method::ACTION => Method\Api5\BookmarkCreate5Method::class,
        Method\Api5\BookmarkEdit5Method::ACTION => Method\Api5\BookmarkEdit5Method::class,
        Method\Api5\BookmarkDelete5Method::ACTION => Method\Api5\BookmarkDelete5Method::class,
        Method\Api5\Videos5Method::ACTION => Method\Api5\Videos5Method::class,
        Method\Api5\Video5Method::ACTION => Method\Api5\Video5Method::class,
        Method\Api5\Stats5Method::ACTION => Method\Api5\Stats5Method::class,
        Method\Api5\Podcasts5Method::ACTION => Method\Api5\Podcasts5Method::class,
        Method\Api5\Podcast5Method::ACTION => Method\Api5\Podcast5Method::class,
        Method\Api5\PodcastCreate5Method::ACTION => Method\Api5\PodcastCreate5Method::class,
        Method\Api5\PodcastDelete5Method::ACTION => Method\Api5\PodcastDelete5Method::class,
        Method\Api5\PodcastEdit5Method::ACTION => Method\Api5\PodcastEdit5Method::class,
        Method\Api5\PodcastEpisodes5Method::ACTION => Method\Api5\PodcastEpisodes5Method::class,
        Method\Api5\PodcastEpisode5Method::ACTION => Method\Api5\PodcastEpisode5Method::class,
        Method\Api5\PodcastEpisodeDelete5Method::ACTION => Method\Api5\PodcastEpisodeDelete5Method::class,
        Method\Api5\Users5Method::ACTION => Method\Api5\Users5Method::class,
        Method\Api5\User5Method::ACTION => Method\Api5\User5Method::class,
        Method\Api5\UserPreferences5Method::ACTION => Method\Api5\UserPreferences5Method::class,
        Method\Api5\UserPreference5Method::ACTION => Method\Api5\UserPreference5Method::class,
        Method\Api5\UserCreate5Method::ACTION => Method\Api5\UserCreate5Method::class,
        Method\Api5\UserUpdate5Method::ACTION => Method\Api5\UserUpdate5Method::class,
        Method\Api5\UserEdit5Method::ACTION => Method\Api5\UserEdit5Method::class,
        Method\Api5\UserDelete5Method::ACTION => Method\Api5\UserDelete5Method::class,
        Method\Api5\Followers5Method::ACTION => Method\Api5\Followers5Method::class,
        Method\Api5\Following5Method::ACTION => Method\Api5\Following5Method::class,
        Method\Api5\ToggleFollow5Method::ACTION => Method\Api5\ToggleFollow5Method::class,
        Method\Api5\LastShouts5Method::ACTION => Method\Api5\LastShouts5Method::class,
        Method\Api5\Rate5Method::ACTION => Method\Api5\Rate5Method::class,
        Method\Api5\Flag5Method::ACTION => Method\Api5\Flag5Method::class,
        Method\Api5\RecordPlay5Method::ACTION => Method\Api5\RecordPlay5Method::class,
        Method\Api5\Scrobble5Method::ACTION => Method\Api5\Scrobble5Method::class,
        Method\Api5\Catalogs5Method::ACTION => Method\Api5\Catalogs5Method::class,
        Method\Api5\Catalog5Method::ACTION => Method\Api5\Catalog5Method::class,
        Method\Api5\CatalogAction5Method::ACTION => Method\Api5\CatalogAction5Method::class,
        Method\Api5\CatalogFile5Method::ACTION => Method\Api5\CatalogFile5Method::class,
        Method\Api5\Timeline5Method::ACTION => Method\Api5\Timeline5Method::class,
        Method\Api5\FriendsTimeline5Method::ACTION => Method\Api5\FriendsTimeline5Method::class,
        Method\Api5\UpdateFromTags5Method::ACTION => Method\Api5\UpdateFromTags5Method::class,
        Method\Api5\UpdateArtistInfo5Method::ACTION => Method\Api5\UpdateArtistInfo5Method::class,
        Method\Api5\UpdateArt5Method::ACTION => Method\Api5\UpdateArt5Method::class,
        Method\Api5\UpdatePodcast5Method::ACTION => Method\Api5\UpdatePodcast5Method::class,
        Method\Api5\Stream5Method::ACTION => Method\Api5\Stream5Method::class,
        Method\Api5\Download5Method::ACTION => Method\Api5\Download5Method::class,
        Method\Api5\GetArt5Method::ACTION => Method\Api5\GetArt5Method::class,
        Method\Api5\Localplay5Method::ACTION => Method\Api5\Localplay5Method::class,
        Method\Api5\LocalplaySongs5Method::ACTION => Method\Api5\LocalplaySongs5Method::class,
        Method\Api5\Democratic5Method::ACTION => Method\Api5\Democratic5Method::class,
        Method\Api5\SystemUpdate5Method::ACTION => Method\Api5\SystemUpdate5Method::class,
        Method\Api5\SystemPreferences5Method::ACTION => Method\Api5\SystemPreferences5Method::class,
        Method\Api5\SystemPreference5Method::ACTION => Method\Api5\SystemPreference5Method::class,
        Method\Api5\PreferenceCreate5Method::ACTION => Method\Api5\PreferenceCreate5Method::class,
        Method\Api5\PreferenceEdit5Method::ACTION => Method\Api5\PreferenceEdit5Method::class,
        Method\Api5\PreferenceDelete5Method::ACTION => Method\Api5\PreferenceDelete5Method::class,
        Method\Api5\DeletedSongs5Method::ACTION => Method\Api5\DeletedSongs5Method::class,
        Method\Api5\DeletedVideos5Method::ACTION => Method\Api5\DeletedVideos5Method::class,
        Method\Api5\DeletedPodcastEpisodes5Method::ACTION => Method\Api5\DeletedPodcastEpisodes5Method::class,
    ];

    public static string $auth_version    = '350001';
    public static string $version         = '5.5.6'; // AMPACHE_VERSION
    public static string $version_numeric = '556000'; // AMPACHE_VERSION

    /**
     * message
     * call the correct success message depending on format
     * @param string $message
     * @param string $format
     * @param array $return_data
     */
    public static function message($message, $format = 'xml', $return_data = array()): void
    {
        switch ($format) {
            case 'json':
                echo Json5_Data::success($message, $return_data);
                break;
            default:
                echo Xml5_Data::success($message, $return_data);
        }
    }

    /**
     * error
     * call the correct error message depending on format
     * @param string $message
     * @param int|string $error_code
     * @param string $method
     * @param string $error_type
     * @param string $format
     */
    public static function error($message, $error_code, $method, $error_type, $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json5_Data::error($error_code, $message, $method, $error_type);
                break;
            default:
                echo Xml5_Data::error($error_code, $message, $method, $error_type);
        }
    }

    /**
     * empty
     * call the correct empty message depending on format
     * @param string $empty_type
     * @param string $format
     */
    public static function empty($empty_type, $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json5_Data::empty($empty_type);
                break;
            default:
                echo Xml5_Data::empty();
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
    public static function check_parameter($input, $parameters, $method): bool
    {
        foreach ($parameters as $parameter) {
            if (array_key_exists($parameter, $input) && ($input[$parameter] === 0 || $input[$parameter] === '0')) {
                continue;
            }
            if (!array_key_exists($parameter, $input)) {
                debug_event(__CLASS__, "'" . $parameter . "' required on " . $method . " function call.", 2);

                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                self::error(sprintf(T_('Bad Request: %s'), $parameter), '4710', $method, 'system', $input['api_format']);

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
    public static function check_access($type, $level, $user_id, $method, $format = 'xml'): bool
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event(self::class, $type . " '" . $level . "' required on " . $method . " function call.", 2);
            /* HINT: Access level, eg 75, 100 */
            self::error(sprintf(T_('Require: %s'), $level), '4742', $method, 'account', $format);

            return false;
        }

        return true;
    }

    /**
     * server_details
     *
     * get the server counts for pings and handshakes
     *
     * @param string $token
     */
    public static function server_details($token = ''): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
        $db_results = Dba::read($sql);
        $details    = Dba::fetch_assoc($db_results);

        // Now we need to quickly get the totals
        $client    = static::getUserRepository()->findByApiKey(trim($token));
        $counts    = Catalog::get_server_counts($client->id ?? 0);
        $playlists = (AmpConfig::get('hide_search', false))
            ? ($counts['playlist'])
            : ($counts['playlist'] + $counts['search']);
        $autharray = (!empty($token)) ? array('auth' => $token) : array();
        // perpetual sessions do not expire
        $perpetual      = (bool)AmpConfig::get('perpetual_api_session', false);
        $session_expire = ($perpetual)
            ? 0
            : date("c", time() + AmpConfig::get('session_length', 3600) - 60);

        // send the totals
        $outarray = array(
            'api' => self::$version,
            'session_expire' => $session_expire,
            'update' => date("c", (int)$details['update']),
            'add' => date("c", (int)$details['add']),
            'clean' => date("c", (int)$details['clean']),
            'songs' => $counts['song'],
            'albums' => $counts['album'],
            'artists' => $counts['artist'],
            'genres' => $counts['tag'],
            'playlists' => $counts['playlist'],
            'searches' => $counts['search'],
            'playlists_searches' => $playlists,
            'users' => ($counts['user']),
            'catalogs' => $counts['catalog'],
            'videos' => $counts['video'],
            'podcasts' => $counts['podcast'],
            'podcast_episodes' => $counts['podcast_episode'],
            'shares' => $counts['share'],
            'licenses' => $counts['license'],
            'live_streams' => $counts['live_stream'],
            'labels' => $counts['label']
        );

        return array_merge($autharray, $outarray);
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
