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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Authorization\Access;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Dba;
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
     *
     * @var array<string, class-string<object>>
     */
    public const METHOD_LIST = [
        Method\AdvancedSearchMethod::ACTION => Method\AdvancedSearchMethod::class,
        Method\AlbumMethod::ACTION => Method\AlbumMethod::class,
        Method\AlbumsMethod::ACTION => Method\AlbumsMethod::class,
        Method\AlbumSongsMethod::ACTION => Method\AlbumSongsMethod::class,
        Method\ArtistAlbumsMethod::ACTION => Method\ArtistAlbumsMethod::class,
        Method\ArtistMethod::ACTION => Method\ArtistMethod::class,
        Method\ArtistsMethod::ACTION => Method\ArtistsMethod::class,
        Method\ArtistSongsMethod::ACTION => Method\ArtistSongsMethod::class,
        Method\BookmarkCreateMethod::ACTION => Method\BookmarkCreateMethod::class,
        Method\BookmarkDeleteMethod::ACTION => Method\BookmarkDeleteMethod::class,
        Method\BookmarkEditMethod::ACTION => Method\BookmarkEditMethod::class,
        Method\BookmarkMethod::ACTION => Method\BookmarkMethod::class,
        Method\BookmarksMethod::ACTION => Method\BookmarksMethod::class,
        Method\BrowseMethod::ACTION => Method\BrowseMethod::class,
        Method\CatalogActionMethod::ACTION => Method\CatalogActionMethod::class,
        Method\CatalogAddMethod::ACTION => Method\CatalogAddMethod::class,
        Method\CatalogDeleteMethod::ACTION => Method\CatalogDeleteMethod::class,
        Method\CatalogFileMethod::ACTION => Method\CatalogFileMethod::class,
        Method\CatalogFolderMethod::ACTION => Method\CatalogFolderMethod::class,
        Method\CatalogMethod::ACTION => Method\CatalogMethod::class,
        Method\CatalogsMethod::ACTION => Method\CatalogsMethod::class,
        Method\DeletedPodcastEpisodesMethod::ACTION => Method\DeletedPodcastEpisodesMethod::class,
        Method\DeletedSongsMethod::ACTION => Method\DeletedSongsMethod::class,
        Method\DeletedVideosMethod::ACTION => Method\DeletedVideosMethod::class,
        Method\DemocraticMethod::ACTION => Method\DemocraticMethod::class,
        Method\DownloadMethod::ACTION => Method\DownloadMethod::class,
        Method\FlagMethod::ACTION => Method\FlagMethod::class,
        Method\FollowersMethod::ACTION => Method\FollowersMethod::class,
        Method\FollowingMethod::ACTION => Method\FollowingMethod::class,
        Method\LostPasswordMethod::ACTION => Method\LostPasswordMethod::class,
        Method\FriendsTimelineMethod::ACTION => Method\FriendsTimelineMethod::class,
        Method\GenreAlbumsMethod::ACTION => Method\GenreAlbumsMethod::class,
        Method\GenreArtistsMethod::ACTION => Method\GenreArtistsMethod::class,
        Method\GenreMethod::ACTION => Method\GenreMethod::class,
        Method\GenresMethod::ACTION => Method\GenresMethod::class,
        Method\GenreSongsMethod::ACTION => Method\GenreSongsMethod::class,
        Method\GetArtMethod::ACTION => Method\GetArtMethod::class,
        Method\GetBookmarkMethod::ACTION => Method\GetBookmarkMethod::class,
        Method\GetIndexesMethod::ACTION => Method\GetIndexesMethod::class,
        Method\GetSimilarMethod::ACTION => Method\GetSimilarMethod::class,
        Method\GoodbyeMethod::ACTION => Method\GoodbyeMethod::class,
        Method\HandshakeMethod::ACTION => Method\HandshakeMethod::class,
        Method\IndexMethod::ACTION => Method\IndexMethod::class,
        Method\LabelArtistsMethod::ACTION => Method\LabelArtistsMethod::class,
        Method\LabelMethod::ACTION => Method\LabelMethod::class,
        Method\LabelsMethod::ACTION => Method\LabelsMethod::class,
        Method\LastShoutsMethod::ACTION => Method\LastShoutsMethod::class,
        Method\LicenseMethod::ACTION => Method\LicenseMethod::class,
        Method\LicensesMethod::ACTION => Method\LicensesMethod::class,
        Method\LicenseSongsMethod::ACTION => Method\LicenseSongsMethod::class,
        Method\ListMethod::ACTION => Method\ListMethod::class,
        Method\LiveStreamMethod::ACTION => Method\LiveStreamMethod::class,
        Method\LiveStreamCreateMethod::ACTION => Method\LiveStreamCreateMethod::class,
        Method\LiveStreamDeleteMethod::ACTION => Method\LiveStreamDeleteMethod::class,
        Method\LiveStreamEditMethod::ACTION => Method\LiveStreamEditMethod::class,
        Method\LiveStreamsMethod::ACTION => Method\LiveStreamsMethod::class,
        Method\LocalplayMethod::ACTION => Method\LocalplayMethod::class,
        Method\LocalplaySongsMethod::ACTION => Method\LocalplaySongsMethod::class,
        Method\NowPlayingMethod::ACTION => Method\NowPlayingMethod::class,
        Method\PingMethod::ACTION => Method\PingMethod::class,
        Method\PlaylistAddMethod::ACTION => Method\PlaylistAddMethod::class,
        Method\PlaylistAddSongMethod::ACTION => Method\PlaylistAddSongMethod::class,
        Method\PlaylistCreateMethod::ACTION => Method\PlaylistCreateMethod::class,
        Method\PlaylistDeleteMethod::ACTION => Method\PlaylistDeleteMethod::class,
        Method\PlaylistEditMethod::ACTION => Method\PlaylistEditMethod::class,
        Method\PlaylistGenerateMethod::ACTION => Method\PlaylistGenerateMethod::class,
        Method\PlaylistHashMethod::ACTION => Method\PlaylistHashMethod::class,
        Method\PlaylistMethod::ACTION => Method\PlaylistMethod::class,
        Method\PlaylistRemoveSongMethod::ACTION => Method\PlaylistRemoveSongMethod::class,
        Method\PlaylistsMethod::ACTION => Method\PlaylistsMethod::class,
        Method\PlaylistSongsMethod::ACTION => Method\PlaylistSongsMethod::class,
        Method\PodcastCreateMethod::ACTION => Method\PodcastCreateMethod::class,
        Method\PodcastDeleteMethod::ACTION => Method\PodcastDeleteMethod::class,
        Method\PodcastEditMethod::ACTION => Method\PodcastEditMethod::class,
        Method\PodcastEpisodeDeleteMethod::ACTION => Method\PodcastEpisodeDeleteMethod::class,
        Method\PodcastEpisodeMethod::ACTION => Method\PodcastEpisodeMethod::class,
        Method\PodcastEpisodesMethod::ACTION => Method\PodcastEpisodesMethod::class,
        Method\PodcastMethod::ACTION => Method\PodcastMethod::class,
        Method\PodcastsMethod::ACTION => Method\PodcastsMethod::class,
        Method\PreferenceCreateMethod::ACTION => Method\PreferenceCreateMethod::class,
        Method\PreferenceDeleteMethod::ACTION => Method\PreferenceDeleteMethod::class,
        Method\PreferenceEditMethod::ACTION => Method\PreferenceEditMethod::class,
        Method\PlayerMethod::ACTION => Method\PlayerMethod::class,
        Method\RateMethod::ACTION => Method\RateMethod::class,
        Method\RecordPlayMethod::ACTION => Method\RecordPlayMethod::class,
        Method\RegisterMethod::ACTION => Method\RegisterMethod::class,
        Method\ScrobbleMethod::ACTION => Method\ScrobbleMethod::class,
        Method\SearchMethod::ACTION => Method\SearchMethod::class,
        Method\SearchGroupMethod::ACTION => Method\SearchGroupMethod::class,
        Method\SearchSongsMethod::ACTION => Method\SearchSongsMethod::class,
        Method\ShareCreateMethod::ACTION => Method\ShareCreateMethod::class,
        Method\ShareDeleteMethod::ACTION => Method\ShareDeleteMethod::class,
        Method\ShareEditMethod::ACTION => Method\ShareEditMethod::class,
        Method\ShareMethod::ACTION => Method\ShareMethod::class,
        Method\SharesMethod::ACTION => Method\SharesMethod::class,
        Method\SongDeleteMethod::ACTION => Method\SongDeleteMethod::class,
        Method\SongMethod::ACTION => Method\SongMethod::class,
        Method\SongsMethod::ACTION => Method\SongsMethod::class,
        Method\StatsMethod::ACTION => Method\StatsMethod::class,
        Method\StreamMethod::ACTION => Method\StreamMethod::class,
        Method\SystemPreferenceMethod::ACTION => Method\SystemPreferenceMethod::class,
        Method\SystemPreferencesMethod::ACTION => Method\SystemPreferencesMethod::class,
        Method\SystemUpdateMethod::ACTION => Method\SystemUpdateMethod::class,
        Method\TimelineMethod::ACTION => Method\TimelineMethod::class,
        Method\ToggleFollowMethod::ACTION => Method\ToggleFollowMethod::class,
        Method\UpdateArtistInfoMethod::ACTION => Method\UpdateArtistInfoMethod::class,
        Method\UpdateArtMethod::ACTION => Method\UpdateArtMethod::class,
        Method\UpdateFromTagsMethod::ACTION => Method\UpdateFromTagsMethod::class,
        Method\UpdatePodcastMethod::ACTION => Method\UpdatePodcastMethod::class,
        Method\UrlToSongMethod::ACTION => Method\UrlToSongMethod::class,
        Method\UserCreateMethod::ACTION => Method\UserCreateMethod::class,
        Method\UserEditMethod::ACTION => Method\UserEditMethod::class,
        Method\UserDeleteMethod::ACTION => Method\UserDeleteMethod::class,
        Method\UserMethod::ACTION => Method\UserMethod::class,
        Method\UserPlaylistsMethod::ACTION => Method\UserPlaylistsMethod::class,
        Method\UserPreferenceMethod::ACTION => Method\UserPreferenceMethod::class,
        Method\UserPreferencesMethod::ACTION => Method\UserPreferencesMethod::class,
        Method\UserSmartlistsMethod::ACTION => Method\UserSmartlistsMethod::class,
        Method\UsersMethod::ACTION => Method\UsersMethod::class,
        Method\UserUpdateMethod::ACTION => Method\UserUpdateMethod::class,
        Method\VideoMethod::ACTION => Method\VideoMethod::class,
        Method\VideosMethod::ACTION => Method\VideosMethod::class,
    ];

    public const API_VERSIONS = [
        3,
        4,
        5,
        6
    ];

    public const DEFAULT_VERSION = 6; // AMPACHE_VERSION

    public static string $auth_version    = '350001';
    public static string $version         = '6.6.3'; // AMPACHE_VERSION
    public static string $version_numeric = '663000'; // AMPACHE_VERSION

    public static ?Browse $browse = null;

    public static function getBrowse(User $user): Browse
    {
        if (self::$browse === null) {
            // create new browse
            self::$browse = new Browse(null, false);
        } else {
            // reset existing browse
            self::$browse->reset();
            // ensure _state offset is 0
            self::$browse->set_offset(0);
        }

        // ensure user_id is set
        self::$browse->set_user_id($user);

        return self::$browse;
    }

    /**
     * message
     * call the correct success message depending on format
     * @param string $message
     * @param string $format
     * @param array $return_data
     */
    public static function message($message, $format = 'xml', $return_data = []): void
    {
        switch ($format) {
            case 'json':
                echo Json_Data::success($message, $return_data);
                break;
            default:
                echo Xml_Data::success($message, $return_data);
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
                echo Json_Data::error($error_code, $message, $method, $error_type);
                break;
            default:
                echo Xml_Data::error($error_code, $message, $method, $error_type);
        }
    }

    /**
     * empty
     * call the correct empty message depending on format
     * @param string|null $empty_type
     * @param string $format
     */
    public static function empty(?string $empty_type, $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json_Data::empty($empty_type);
                break;
            default:
                echo Xml_Data::empty();
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
                debug_event(self::class, "'" . $parameter . "' required on " . $method . " function call.", 2);

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
     * @param int $user_id
     * @param string $method
     * @param string $format
     */
    public static function check_access(AccessTypeEnum $type, AccessLevelEnum $level, $user_id, $method, $format = 'xml'): bool
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event(self::class, $type->value . " '" . $level->value . "' required on " . $method . " function call.", 2);
            /* HINT: Access level, eg 75, 100 */
            self::error(sprintf(T_('Require: %s'), $level->value), '4742', $method, 'account', $format);

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
     * @return array
     */
    public static function server_details($token = ''): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql        = 'SELECT `catalog`.`update`, `catalog`.`add`, `catalog`.`clean`, `maxid`.`max_song`, `maxid`.`max_album`, `maxid`.`max_artist`, `maxid`.`max_video`, `maxid`.`max_podcast`, `maxid`.`max_podcast_episode` FROM (SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`) AS `catalog` LEFT JOIN (SELECT (SELECT MAX(`id`) FROM `song`) AS `max_song`, (SELECT MAX(`id`) FROM `album`) AS `max_album`, (SELECT MAX(`id`) FROM `artist`) AS `max_artist`, (SELECT MAX(`id`) FROM `video`) AS `max_video`, (SELECT MAX(`id`) FROM `podcast`) AS `max_podcast`, (SELECT MAX(`id`) FROM `podcast_episode`) AS `max_podcast_episode`) AS `maxid` ON 1=1;';
        $db_results = Dba::read($sql);
        $details    = Dba::fetch_assoc($db_results);

        // Now we need to quickly get the totals
        $client = self::getUserRepository()->findByApiKey(trim($token));
        if (!$client instanceof User || $client->isNew()) {
            return [];
        }

        $counts    = Catalog::get_server_counts($client->id ?? 0);
        $playlists = (AmpConfig::get('hide_search', false))
            ? $counts['playlist']
            : $counts['playlist'] + $counts['search'];
        $autharray = (!empty($token)) ? ['auth' => $token] : [];
        // perpetual sessions do not expire
        $perpetual      = (bool)AmpConfig::get('perpetual_api_session', false);
        $session_expire = ($perpetual)
            ? 0
            : date("c", time() + AmpConfig::get('session_length', 3600) - 60);

        // send the totals
        $outarray = [
            'api' => self::$version,
            'session_expire' => $session_expire,
            'update' => date("c", (int)$details['update']),
            'add' => date("c", (int)$details['add']),
            'clean' => date("c", (int)$details['clean']),
            'max_song' => (int)$details['max_song'],
            'max_album' => (int)$details['max_album'],
            'max_artist' => (int)$details['max_artist'],
            'max_video' => (int)$details['max_video'],
            'max_podcast' => (int)$details['max_podcast'],
            'max_podcast_episode' => (int)$details['max_podcast_episode'],
            'songs' => $counts['song'],
            'albums' => $counts['album'],
            'artists' => $counts['artist'],
            'genres' => $counts['tag'],
            'playlists' => $counts['playlist'],
            'searches' => $counts['search'],
            'playlists_searches' => $playlists,
            'users' => $counts['user'],
            'catalogs' => $counts['catalog'],
            'videos' => $counts['video'],
            'podcasts' => $counts['podcast'],
            'podcast_episodes' => $counts['podcast_episode'],
            'shares' => $counts['share'],
            'licenses' => $counts['license'],
            'live_streams' => $counts['live_stream'],
            'labels' => $counts['label'],
            'username' => $client->getUsername(),
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
