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
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Api6 Class
 *
 * This handles functions relating to the Api6 written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 */
class Api6
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     *
     * @var array<string, class-string<object>>
     */
    public const array METHOD_LIST = [
        Method\AdvancedSearchMethod::ACTION => Method\AdvancedSearchMethod::class,
        Method\AlbumMethod::ACTION => Method\AlbumMethod::class,
        Method\AlbumsMethod::ACTION => Method\AlbumsMethod::class,
        Method\AlbumSongsMethod::ACTION => Method\AlbumSongsMethod::class,
        Method\ArtistAlbumsMethod::ACTION => Method\ArtistAlbumsMethod::class,
        Method\ArtistMethod::ACTION => Method\ArtistMethod::class,
        Method\ArtistsMethod::ACTION => Method\ArtistsMethod::class,
        Method\ArtistSongsMethod::ACTION => Method\ArtistSongsMethod::class,
        Method\BookmarkCreateMethod::ACTION => Method\BookmarkCreateMethod::class,
        Method\BookmarkCreateMethod::REST_ACTION => Method\BookmarkCreateMethod::class,
        Method\BookmarkDeleteMethod::ACTION => Method\BookmarkDeleteMethod::class,
        Method\BookmarkDeleteMethod::REST_ACTION => Method\BookmarkDeleteMethod::class,
        Method\BookmarkEditMethod::ACTION => Method\BookmarkEditMethod::class,
        Method\BookmarkEditMethod::REST_ACTION => Method\BookmarkEditMethod::class,
        Method\BookmarkMethod::ACTION => Method\BookmarkMethod::class,
        Method\BookmarksMethod::ACTION => Method\BookmarksMethod::class,
        Method\BrowseMethod::ACTION => Method\BrowseMethod::class,
        Method\Api6\CatalogAction6Method::ACTION => Method\Api6\CatalogAction6Method::class,
        Method\Api6\CatalogAction6Method::REST_ACTION => Method\Api6\CatalogAction6Method::class,
        Method\CatalogAddMethod::ACTION => Method\CatalogAddMethod::class,
        Method\CatalogCreateMethod::ACTION => Method\CatalogCreateMethod::class,
        Method\CatalogCreateMethod::REST_ACTION => Method\CatalogCreateMethod::class,
        Method\CatalogDeleteMethod::ACTION => Method\CatalogDeleteMethod::class,
        Method\CatalogDeleteMethod::REST_ACTION => Method\CatalogDeleteMethod::class,
        Method\Api6\CatalogFile6Method::ACTION => Method\Api6\CatalogFile6Method::class,
        Method\Api6\CatalogFile6Method::REST_ACTION => Method\Api6\CatalogFile6Method::class,
        Method\Api6\CatalogFolder6Method::ACTION => Method\Api6\CatalogFolder6Method::class,
        Method\CatalogMethod::ACTION => Method\CatalogMethod::class,
        Method\CatalogsMethod::ACTION => Method\CatalogsMethod::class,
        Method\DeletedPodcastEpisodesMethod::ACTION => Method\DeletedPodcastEpisodesMethod::class,
        Method\DeletedSongsMethod::ACTION => Method\DeletedSongsMethod::class,
        Method\DeletedVideosMethod::ACTION => Method\DeletedVideosMethod::class,
        Method\DemocraticMethod::ACTION => Method\DemocraticMethod::class,
        Method\Api6\Download6Method::ACTION => Method\Api6\Download6Method::class,
        Method\Api6\Flag6Method::ACTION => Method\Api6\Flag6Method::class,
        Method\FollowersMethod::ACTION => Method\FollowersMethod::class,
        Method\FollowingMethod::ACTION => Method\FollowingMethod::class,
        Method\LostPasswordMethod::ACTION => Method\LostPasswordMethod::class,
        Method\FriendsTimelineMethod::ACTION => Method\FriendsTimelineMethod::class,
        Method\GenreAlbumsMethod::ACTION => Method\GenreAlbumsMethod::class,
        Method\GenreArtistsMethod::ACTION => Method\GenreArtistsMethod::class,
        Method\GenreMethod::ACTION => Method\GenreMethod::class,
        Method\GenresMethod::ACTION => Method\GenresMethod::class,
        Method\GenreSongsMethod::ACTION => Method\GenreSongsMethod::class,
        Method\Api6\GetArt6Method::ACTION => Method\Api6\GetArt6Method::class,
        Method\GetBookmarkMethod::ACTION => Method\GetBookmarkMethod::class,
        Method\Api6\GetIndexes6Method::ACTION => Method\Api6\GetIndexes6Method::class,
        Method\GetExternalMetadataMethod::ACTION => Method\GetExternalMetadataMethod::class,
        Method\GetLyricsMethod::ACTION => Method\GetLyricsMethod::class,
        Method\GetSimilarMethod::ACTION => Method\GetSimilarMethod::class,
        Method\GoodbyeMethod::ACTION => Method\GoodbyeMethod::class,
        Method\Api6\Handshake6Method::ACTION => Method\Api6\Handshake6Method::class,
        Method\IndexMethod::ACTION => Method\IndexMethod::class,
        Method\LabelArtistsMethod::ACTION => Method\LabelArtistsMethod::class,
        Method\LabelMethod::ACTION => Method\LabelMethod::class,
        Method\LabelsMethod::ACTION => Method\LabelsMethod::class,
        Method\Api6\LastShouts6Method::ACTION => Method\Api6\LastShouts6Method::class,
        Method\LicenseMethod::ACTION => Method\LicenseMethod::class,
        Method\LicensesMethod::ACTION => Method\LicensesMethod::class,
        Method\LicenseSongsMethod::ACTION => Method\LicenseSongsMethod::class,
        Method\ListMethod::ACTION => Method\ListMethod::class,
        Method\LiveStreamMethod::ACTION => Method\LiveStreamMethod::class,
        Method\LiveStreamCreateMethod::ACTION => Method\LiveStreamCreateMethod::class,
        Method\LiveStreamCreateMethod::REST_ACTION => Method\LiveStreamCreateMethod::class,
        Method\LiveStreamDeleteMethod::ACTION => Method\LiveStreamDeleteMethod::class,
        Method\LiveStreamDeleteMethod::REST_ACTION => Method\LiveStreamDeleteMethod::class,
        Method\LiveStreamEditMethod::ACTION => Method\LiveStreamEditMethod::class,
        Method\LiveStreamEditMethod::REST_ACTION => Method\LiveStreamEditMethod::class,
        Method\LiveStreamsMethod::ACTION => Method\LiveStreamsMethod::class,
        Method\LocalplayMethod::ACTION => Method\LocalplayMethod::class,
        Method\LocalplaySongsMethod::ACTION => Method\LocalplaySongsMethod::class,
        Method\NowPlayingMethod::ACTION => Method\NowPlayingMethod::class,
        Method\PingMethod::ACTION => Method\PingMethod::class,
        Method\Api6\PlaylistAdd6Method::ACTION => Method\Api6\PlaylistAdd6Method::class,
        Method\Api6\PlaylistAdd6Method::REST_ACTION => Method\Api6\PlaylistAdd6Method::class,
        Method\Api6\PlaylistAddSong6Method::ACTION => Method\Api6\PlaylistAddSong6Method::class,
        Method\Api6\PlaylistAddSong6Method::REST_ACTION => Method\Api6\PlaylistAddSong6Method::class,
        Method\PlaylistCreateMethod::ACTION => Method\PlaylistCreateMethod::class,
        Method\PlaylistCreateMethod::REST_ACTION => Method\PlaylistCreateMethod::class,
        Method\PlaylistDeleteMethod::ACTION => Method\PlaylistDeleteMethod::class,
        Method\PlaylistDeleteMethod::REST_ACTION => Method\PlaylistDeleteMethod::class,
        Method\PlaylistEditMethod::ACTION => Method\PlaylistEditMethod::class,
        Method\PlaylistEditMethod::REST_ACTION => Method\PlaylistEditMethod::class,
        Method\PlaylistGenerateMethod::ACTION => Method\PlaylistGenerateMethod::class,
        Method\PlaylistHashMethod::ACTION => Method\PlaylistHashMethod::class,
        Method\PlaylistMethod::ACTION => Method\PlaylistMethod::class,
        Method\PlaylistRemoveSongMethod::ACTION => Method\PlaylistRemoveSongMethod::class,
        Method\PlaylistRemoveSongMethod::REST_ACTION => Method\PlaylistRemoveSongMethod::class,
        Method\PlaylistsMethod::ACTION => Method\PlaylistsMethod::class,
        Method\PlaylistSongsMethod::ACTION => Method\PlaylistSongsMethod::class,
        Method\PodcastCreateMethod::ACTION => Method\PodcastCreateMethod::class,
        Method\PodcastCreateMethod::REST_ACTION => Method\PodcastCreateMethod::class,
        Method\PodcastDeleteMethod::ACTION => Method\PodcastDeleteMethod::class,
        Method\PodcastDeleteMethod::REST_ACTION => Method\PodcastDeleteMethod::class,
        Method\PodcastEditMethod::ACTION => Method\PodcastEditMethod::class,
        Method\PodcastEditMethod::REST_ACTION => Method\PodcastEditMethod::class,
        Method\PodcastUpdateMethod::ACTION => Method\PodcastUpdateMethod::class,
        Method\PodcastEpisodeDeleteMethod::ACTION => Method\PodcastEpisodeDeleteMethod::class,
        Method\PodcastEpisodeDeleteMethod::REST_ACTION => Method\PodcastEpisodeDeleteMethod::class,
        Method\PodcastEpisodeMethod::ACTION => Method\PodcastEpisodeMethod::class,
        Method\PodcastEpisodesMethod::ACTION => Method\PodcastEpisodesMethod::class,
        Method\PodcastMethod::ACTION => Method\PodcastMethod::class,
        Method\PodcastsMethod::ACTION => Method\PodcastsMethod::class,
        Method\PreferenceCreateMethod::ACTION => Method\PreferenceCreateMethod::class,
        Method\PreferenceCreateMethod::REST_ACTION => Method\PreferenceCreateMethod::class,
        Method\PreferenceDeleteMethod::ACTION => Method\PreferenceDeleteMethod::class,
        Method\PreferenceDeleteMethod::REST_ACTION => Method\PreferenceDeleteMethod::class,
        Method\PreferenceEditMethod::ACTION => Method\PreferenceEditMethod::class,
        Method\PreferenceEditMethod::REST_ACTION => Method\PreferenceEditMethod::class,
        Method\PlayerMethod::ACTION => Method\PlayerMethod::class,
        Method\PlayerMethod::REST_ACTION => Method\PlayerMethod::class,
        Method\Api6\Rate6Method::ACTION => Method\Api6\Rate6Method::class,
        Method\Api6\RecordPlay6Method::ACTION => Method\Api6\RecordPlay6Method::class,
        Method\RegisterMethod::ACTION => Method\RegisterMethod::class,
        Method\ScrobbleMethod::ACTION => Method\ScrobbleMethod::class,
        Method\SearchMethod::ACTION => Method\SearchMethod::class,
        Method\SearchGroupMethod::ACTION => Method\SearchGroupMethod::class,
        Method\SearchGroupMethod::REST_ACTION => Method\SearchGroupMethod::class,
        Method\SearchRulesMethod::ACTION => Method\SearchRulesMethod::class,
        Method\SearchRulesMethod::REST_ACTION => Method\SearchRulesMethod::class,
        Method\Api6\SearchSongs6Method::ACTION => Method\Api6\SearchSongs6Method::class,
        Method\Api6\ShareCreate6Method::ACTION => Method\Api6\ShareCreate6Method::class,
        Method\Api6\ShareCreate6Method::REST_ACTION => Method\Api6\ShareCreate6Method::class,
        Method\ShareDeleteMethod::ACTION => Method\ShareDeleteMethod::class,
        Method\ShareDeleteMethod::REST_ACTION => Method\ShareDeleteMethod::class,
        Method\ShareEditMethod::ACTION => Method\ShareEditMethod::class,
        Method\ShareEditMethod::REST_ACTION => Method\ShareEditMethod::class,
        Method\ShareMethod::ACTION => Method\ShareMethod::class,
        Method\SharesMethod::ACTION => Method\SharesMethod::class,
        Method\SmartlistDeleteMethod::ACTION => Method\SmartlistDeleteMethod::class,
        Method\SmartlistDeleteMethod::REST_ACTION => Method\SmartlistDeleteMethod::class,
        Method\SmartlistMethod::ACTION => Method\SmartlistMethod::class,
        Method\SmartlistsMethod::ACTION => Method\SmartlistsMethod::class,
        Method\SmartlistSongsMethod::ACTION => Method\SmartlistSongsMethod::class,
        Method\SongDeleteMethod::ACTION => Method\SongDeleteMethod::class,
        Method\SongDeleteMethod::REST_ACTION => Method\SongDeleteMethod::class,
        Method\SongMethod::ACTION => Method\SongMethod::class,
        Method\SongTagsMethod::ACTION => Method\SongTagsMethod::class,
        Method\SongsMethod::ACTION => Method\SongsMethod::class,
        Method\StatsMethod::ACTION => Method\StatsMethod::class,
        Method\Api6\Stream6Method::ACTION => Method\Api6\Stream6Method::class,
        Method\SystemPreferenceMethod::ACTION => Method\SystemPreferenceMethod::class,
        Method\SystemPreferencesMethod::ACTION => Method\SystemPreferencesMethod::class,
        Method\SystemUpdateMethod::ACTION => Method\SystemUpdateMethod::class,
        Method\Api6\Timeline6Method::ACTION => Method\Api6\Timeline6Method::class,
        Method\Api6\ToggleFollow6Method::ACTION => Method\Api6\ToggleFollow6Method::class,
        Method\SystemUpdateMethod::REST_ACTION => Method\SystemUpdateMethod::class,
        Method\Api6\UpdateArtistInfo6Method::ACTION => Method\Api6\UpdateArtistInfo6Method::class,
        Method\Api6\UpdateArt6Method::ACTION => Method\Api6\UpdateArt6Method::class,
        Method\Api6\UpdateFromTags6Method::ACTION => Method\Api6\UpdateFromTags6Method::class,
        Method\UpdatePodcastMethod::ACTION => Method\UpdatePodcastMethod::class,
        Method\UpdatePodcastMethod::REST_ACTION => Method\UpdatePodcastMethod::class,
        Method\UrlToSongMethod::ACTION => Method\UrlToSongMethod::class,
        Method\UserCreateMethod::ACTION => Method\UserCreateMethod::class,
        Method\UserCreateMethod::REST_ACTION => Method\UserCreateMethod::class,
        Method\Api6\UserEdit6Method::ACTION => Method\Api6\UserEdit6Method::class,
        Method\Api6\UserEdit6Method::REST_ACTION => Method\Api6\UserEdit6Method::class,
        Method\Api6\UserDelete6Method::ACTION => Method\Api6\UserDelete6Method::class,
        Method\Api6\UserDelete6Method::REST_ACTION => Method\Api6\UserDelete6Method::class,
        Method\UserMethod::ACTION => Method\UserMethod::class,
        Method\UserPlaylistsMethod::ACTION => Method\UserPlaylistsMethod::class,
        Method\UserPreferenceMethod::ACTION => Method\UserPreferenceMethod::class,
        Method\UserPreferencesMethod::ACTION => Method\UserPreferencesMethod::class,
        Method\UserPreferencesMethod::REST_ACTION => Method\UserPreferencesMethod::class,
        Method\UserSmartlistsMethod::ACTION => Method\UserSmartlistsMethod::class,
        Method\UsersMethod::ACTION => Method\UsersMethod::class,
        Method\Api6\UserUpdate6Method::ACTION => Method\Api6\UserUpdate6Method::class,
        Method\VideoMethod::ACTION => Method\VideoMethod::class,
        Method\VideosMethod::ACTION => Method\VideosMethod::class,
    ];

    public static string $auth_version    = '350001';
    public static ?Browse $browse         = null;
    public static string $version         = '6.9.2'; // AMPACHE_VERSION
    public static string $version_numeric = '692003'; // AMPACHE_VERSION

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
     * Return an error for missing parameters for API6
     *
     * @param array<string, mixed> $input
     * @param string[] $parameters e.g. array('auth', type')
     */
    public static function check_parameter(array $input, array $parameters, string $method): bool
    {
        $parameter = self::parameter_exists($input, $parameters);
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
    public static function empty(?string $empty_type, string $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json6_Data::empty($empty_type);
                break;
            default:
                echo Xml6_Data::empty();
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
                echo Json6_Data::error($code, $message, $method, $error_type);
                break;
            default:
                echo Xml6_Data::error($code, $message, $method, $error_type);
        }
    }

    public static function getBrowse(User $user): Browse
    {
        if (self::$browse === null) {
            // create new browse
            self::$browse = self::getBrowseFactory()->create(null, false);
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
     * @param array<string, string> $return_data
     */
    public static function message(string $message, string $format = 'xml', array $return_data = []): void
    {
        switch ($format) {
            case 'json':
                echo Json6_Data::success($message, $return_data);
                break;
            default:
                echo Xml6_Data::success($message, $return_data);
        }
    }

    /**
     * parameter_exists
     *
     * This function checks the $input actually has the parameter.
     * Parameters must be an array of required elements as a string
     *
     * @param array<string, mixed> $input
     * @param string[] $parameters e.g. array('auth', type')
     */
    public static function parameter_exists(array $input, array $parameters): bool|string
    {
        foreach ($parameters as $parameter) {
            if (
                array_key_exists($parameter, $input)
                && $input[$parameter] !== null
                && $input[$parameter] !== ''
                && $input[$parameter] !== []
            ) {
                continue;
            }

            return $parameter;
        }

        return true;
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
     *     max_song?: int,
     *     max_album?: int,
     *     max_artist?: int,
     *     max_video?: int,
     *     max_podcast?: int,
     *     max_podcast_episode?: int,
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
     *     username?: string,
     * }
     */
    public static function server_details(string $token = ''): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql = <<<SQL
            SELECT `catalog`.`update`, `catalog`.`add`, `catalog`.`clean`, `maxid`.`max_song`, `maxid`.`max_album`, `maxid`.`max_artist`, `maxid`.`max_video`, `maxid`.`max_podcast`, `maxid`.`max_podcast_episode`
            FROM (
               SELECT MAX(`last_update`) AS `update`,
                      MAX(`last_add`) AS `add`,
                      MAX(`last_clean`) AS `clean`
               FROM `catalog`
            ) AS `catalog`
            LEFT JOIN (
                SELECT (SELECT MAX(`id`) FROM `song`) AS `max_song`,
                       (SELECT MAX(`id`) FROM `album`) AS `max_album`,
                       (SELECT MAX(`id`) FROM `artist`) AS `max_artist`,
                       (SELECT MAX(`id`) FROM `video`) AS `max_video`,
                       (SELECT MAX(`id`) FROM `podcast`) AS `max_podcast`,
                       (SELECT MAX(`id`) FROM `podcast_episode`) AS `max_podcast_episode`
            ) AS `maxid` ON 1=1;
            SQL;
        $db_results = Dba::read($sql);
        $details    = Dba::fetch_assoc($db_results);

        // Now we need to quickly get the totals
        $client = self::getUserRepository()->findByApiKey(trim($token));
        if (!$client instanceof User || $client->isNew()) {
            return [];
        }

        $counts    = Catalog::get_server_counts($client->id);
        $playlists = (AmpConfig::get('hide_search', false))
            ? $counts['playlist']
            : $counts['playlist'] + $counts['search'];
        $autharray = (!empty($token))
            ? [
                'auth' => $token,
                'streamtoken' => $client->streamtoken
            ]
            : [];
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
            'max_song' => (int) $details['max_song'],
            'max_album' => (int) $details['max_album'],
            'max_artist' => (int) $details['max_artist'],
            'max_video' => (int) $details['max_video'],
            'max_podcast' => (int) $details['max_podcast'],
            'max_podcast_episode' => (int) $details['max_podcast_episode'],
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
     * @deprecated inject dependency
     */
    private static function getBrowseFactory(): BrowseFactoryInterface
    {
        global $dic;

        return $dic->get(BrowseFactoryInterface::class);
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
