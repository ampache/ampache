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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\Dba;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Api5 Class
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
    public const array METHOD_LIST = [
        Method\Api5\Handshake5Method::ACTION => Method\Api5\Handshake5Method::class,
        Method\PingMethod::ACTION => Method\PingMethod::class,
        Method\GoodbyeMethod::ACTION => Method\GoodbyeMethod::class,
        Method\Api5\UrlToSong5Method::ACTION => Method\Api5\UrlToSong5Method::class,
        Method\Api5\GetIndexes5Method::ACTION => Method\Api5\GetIndexes5Method::class,
        Method\Api5\GetBookmark5Method::ACTION => Method\Api5\GetBookmark5Method::class,
        Method\Api5\GetSimilar5Method::ACTION => Method\Api5\GetSimilar5Method::class,
        Method\Api5\AdvancedSearch5Method::ACTION => Method\Api5\AdvancedSearch5Method::class,
        Method\Api5\Artists5Method::ACTION => Method\Api5\Artists5Method::class,
        Method\ArtistMethod::ACTION => Method\ArtistMethod::class,
        Method\Api5\ArtistAlbums5Method::ACTION => Method\Api5\ArtistAlbums5Method::class,
        Method\Api5\ArtistSongs5Method::ACTION => Method\Api5\ArtistSongs5Method::class,
        Method\Api5\Albums5Method::ACTION => Method\Api5\Albums5Method::class,
        Method\Api5\Album5Method::ACTION => Method\Api5\Album5Method::class,
        Method\Api5\AlbumSongs5Method::ACTION => Method\Api5\AlbumSongs5Method::class,
        Method\Api5\Licenses5Method::ACTION => Method\Api5\Licenses5Method::class,
        Method\LicenseMethod::ACTION => Method\LicenseMethod::class,
        Method\Api5\LicenseSongs5Method::ACTION => Method\Api5\LicenseSongs5Method::class,
        Method\Api5\Genres5Method::ACTION => Method\Api5\Genres5Method::class,
        Method\GenreMethod::ACTION => Method\GenreMethod::class,
        Method\Api5\GenreArtists5Method::ACTION => Method\Api5\GenreArtists5Method::class,
        Method\Api5\GenreAlbums5Method::ACTION => Method\Api5\GenreAlbums5Method::class,
        Method\Api5\GenreSongs5Method::ACTION => Method\Api5\GenreSongs5Method::class,
        Method\Api5\Labels5Method::ACTION => Method\Api5\Labels5Method::class,
        Method\LabelMethod::ACTION => Method\LabelMethod::class,
        Method\Api5\LabelArtists5Method::ACTION => Method\Api5\LabelArtists5Method::class,
        Method\Api5\LiveStreams5Method::ACTION => Method\Api5\LiveStreams5Method::class,
        Method\LiveStreamMethod::ACTION => Method\LiveStreamMethod::class,
        Method\Api5\Songs5Method::ACTION => Method\Api5\Songs5Method::class,
        Method\SongMethod::ACTION => Method\SongMethod::class,
        Method\SongDeleteMethod::ACTION => Method\SongDeleteMethod::class,
        Method\Api5\Playlists5Method::ACTION => Method\Api5\Playlists5Method::class,
        Method\PlaylistMethod::ACTION => Method\PlaylistMethod::class,
        Method\PlaylistSongsMethod::ACTION => Method\PlaylistSongsMethod::class,
        Method\Api5\PlaylistCreate5Method::ACTION => Method\Api5\PlaylistCreate5Method::class,
        Method\Api5\PlaylistEdit5Method::ACTION => Method\Api5\PlaylistEdit5Method::class,
        Method\PlaylistDeleteMethod::ACTION => Method\PlaylistDeleteMethod::class,
        Method\Api5\PlaylistAddSong5Method::ACTION => Method\Api5\PlaylistAddSong5Method::class,
        Method\PlaylistRemoveSongMethod::ACTION => Method\PlaylistRemoveSongMethod::class,
        Method\PlaylistGenerateMethod::ACTION => Method\PlaylistGenerateMethod::class,
        Method\Api5\Search5Method::ACTION => Method\Api5\Search5Method::class,
        Method\Api5\SearchSongs5Method::ACTION => Method\Api5\SearchSongs5Method::class,
        Method\Api5\Shares5Method::ACTION => Method\Api5\Shares5Method::class,
        Method\ShareMethod::ACTION => Method\ShareMethod::class,
        Method\Api5\ShareCreate5Method::ACTION => Method\Api5\ShareCreate5Method::class,
        Method\ShareDeleteMethod::ACTION => Method\ShareDeleteMethod::class,
        Method\ShareEditMethod::ACTION => Method\ShareEditMethod::class,
        Method\Api5\Bookmarks5Method::ACTION => Method\Api5\Bookmarks5Method::class,
        Method\Api5\BookmarkCreate5Method::ACTION => Method\Api5\BookmarkCreate5Method::class,
        Method\Api5\BookmarkEdit5Method::ACTION => Method\Api5\BookmarkEdit5Method::class,
        Method\Api5\BookmarkDelete5Method::ACTION => Method\Api5\BookmarkDelete5Method::class,
        Method\Api5\Videos5Method::ACTION => Method\Api5\Videos5Method::class,
        Method\VideoMethod::ACTION => Method\VideoMethod::class,
        Method\Api5\Stats5Method::ACTION => Method\Api5\Stats5Method::class,
        Method\Api5\Podcasts5Method::ACTION => Method\Api5\Podcasts5Method::class,
        Method\PodcastMethod::ACTION => Method\PodcastMethod::class,
        Method\PodcastCreateMethod::ACTION => Method\PodcastCreateMethod::class,
        Method\PodcastDeleteMethod::ACTION => Method\PodcastDeleteMethod::class,
        Method\PodcastEditMethod::ACTION => Method\PodcastEditMethod::class,
        Method\Api5\PodcastEpisodes5Method::ACTION => Method\Api5\PodcastEpisodes5Method::class,
        Method\PodcastEpisodeMethod::ACTION => Method\PodcastEpisodeMethod::class,
        Method\PodcastEpisodeDeleteMethod::ACTION => Method\PodcastEpisodeDeleteMethod::class,
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
        Method\ScrobbleMethod::ACTION => Method\ScrobbleMethod::class,
        Method\Api5\Catalogs5Method::ACTION => Method\Api5\Catalogs5Method::class,
        Method\CatalogMethod::ACTION => Method\CatalogMethod::class,
        Method\Api5\CatalogAction5Method::ACTION => Method\Api5\CatalogAction5Method::class,
        Method\Api5\CatalogFile5Method::ACTION => Method\Api5\CatalogFile5Method::class,
        Method\Api5\Timeline5Method::ACTION => Method\Api5\Timeline5Method::class,
        Method\FriendsTimelineMethod::ACTION => Method\FriendsTimelineMethod::class,
        Method\Api5\UpdateFromTags5Method::ACTION => Method\Api5\UpdateFromTags5Method::class,
        Method\Api5\UpdateArtistInfo5Method::ACTION => Method\Api5\UpdateArtistInfo5Method::class,
        Method\Api5\UpdateArt5Method::ACTION => Method\Api5\UpdateArt5Method::class,
        Method\UpdatePodcastMethod::ACTION => Method\UpdatePodcastMethod::class,
        Method\Api5\Stream5Method::ACTION => Method\Api5\Stream5Method::class,
        Method\Api5\Download5Method::ACTION => Method\Api5\Download5Method::class,
        Method\Api5\GetArt5Method::ACTION => Method\Api5\GetArt5Method::class,
        Method\Api5\Localplay5Method::ACTION => Method\Api5\Localplay5Method::class,
        Method\LocalplaySongsMethod::ACTION => Method\LocalplaySongsMethod::class,
        Method\DemocraticMethod::ACTION => Method\DemocraticMethod::class,
        Method\SystemUpdateMethod::ACTION => Method\SystemUpdateMethod::class,
        Method\Api5\SystemPreferences5Method::ACTION => Method\Api5\SystemPreferences5Method::class,
        Method\Api5\SystemPreference5Method::ACTION => Method\Api5\SystemPreference5Method::class,
        Method\Api5\PreferenceCreate5Method::ACTION => Method\Api5\PreferenceCreate5Method::class,
        Method\Api5\PreferenceEdit5Method::ACTION => Method\Api5\PreferenceEdit5Method::class,
        Method\PreferenceDeleteMethod::ACTION => Method\PreferenceDeleteMethod::class,
        Method\DeletedSongsMethod::ACTION => Method\DeletedSongsMethod::class,
        Method\DeletedVideosMethod::ACTION => Method\DeletedVideosMethod::class,
        Method\DeletedPodcastEpisodesMethod::ACTION => Method\DeletedPodcastEpisodesMethod::class,
    ];

    public static string $auth_version    = '350001';
    public static string $version         = '5.5.6'; // AMPACHE_VERSION
    public static string $version_numeric = '556000'; // AMPACHE_VERSION

    /**
     * check_access
     *
     * This function checks the user can perform the function requested
     * 'interface', 100, $user->id
     */
    public static function check_access(AccessTypeEnum $type, AccessLevelEnum $level, int $user_id, string $method, string $format = 'xml'): bool
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event(self::class, $type->value . " '" . $level->value . "' required on " . $method . " function call.", 2);
            self::error('4742', sprintf('Require: %s', $level->value), $method, 'account', $format);

            return false;
        }

        return true;
    }

    /**
     * check_parameter
     *
     * This function checks the $input actually has the parameter.
     * Parameters must be an array of required elements as a string
     *
     * @param array<string, mixed> $input
     * @param string[] $parameters e.g. array('auth', type')
     */
    public static function check_parameter(array $input, array $parameters, string $method): bool
    {
        $parameter = Api::parameter_exists($input, $parameters);
        if ($parameter === true) {
            return true;
        }

        debug_event(self::class, "'" . $parameter . "' required on " . $method . " function call.", 2);

        self::error('4710', sprintf('Bad Request: %s', $parameter), $method, 'system', $input['api_format']);

        return false;
    }

    /**
     * empty
     * call the correct empty message depending on format
     */
    public static function empty(string $empty_type, string $format = 'xml'): void
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
     * error
     * call the correct error message depending on format
     */
    public static function error(int|string $code, string $message, string $method, string $error_type, string $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json5_Data::error($code, $message, $method, $error_type);
                break;
            default:
                echo Xml5_Data::error($code, $message, $method, $error_type);
        }
    }

    /**
     * message
     * call the correct success message depending on format
     * @param array<string, string> $return_data
     */
    public static function message(string $message, string $format = 'xml', array $return_data = []): void
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
     * server_details
     *
     * get the server counts for pings and handshakes
     *
     * @return array{
     *     auth?: ?string,
     *     api?: string,
     *     session_expire?: int|string,
     *     update?: string,
     *     add?: string,
     *     clean?: string,
     *     songs?: int,
     *     albums?: int,
     *     artists?: int,
     *     genres?: int,
     *     playlists?: int,
     *     searches?: int,
     *     playlists_searches?: int,
     *     users?: int,
     *     catalogs?: int,
     *     videos?: int,
     *     podcasts?: int,
     *     podcast_episodes?: int,
     *     shares?: int,
     *     licenses?: int,
     *     live_streams?: int,
     *     labels?: int,
     * }
     */
    public static function server_details(string $token = ''): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
        $db_results = Dba::read($sql);
        $details    = Dba::fetch_assoc($db_results);

        // Now we need to quickly get the totals
        $client    = self::getUserRepository()->findByApiKey(trim($token));
        $counts    = Catalog::get_server_counts($client->id ?? 0);
        $playlists = (AmpConfig::get('hide_search', false))
            ? ($counts['playlist'])
            : ($counts['playlist'] + $counts['search']);
        $autharray = (!empty($token)) ? ['auth' => $token] : [];
        // perpetual sessions do not expire
        $perpetual      = (bool) AmpConfig::get('perpetual_api_session', false);
        $session_expire = ($perpetual)
            ? 0
            : date("c", time() + AmpConfig::get('session_length', 3600) - 60);

        // send the totals
        $outarray = [
            'api' => self::$version,
            'session_expire' => $session_expire,
            'update' => date("c", (int) $details['update']),
            'add' => date("c", (int) $details['add']),
            'clean' => date("c", (int) $details['clean']),
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
            'labels' => $counts['label'],
        ];

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
