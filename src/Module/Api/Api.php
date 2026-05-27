<?php

declare(strict_types=0);

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
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Api Class
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
        Method\Api8\AdvancedSearch8Method::ACTION => Method\Api8\AdvancedSearch8Method::class,
        Method\Api8\Album8Method::ACTION => Method\Api8\Album8Method::class,
        Method\Api8\Albums8Method::ACTION => Method\Api8\Albums8Method::class,
        Method\Api8\AlbumSongs8Method::ACTION => Method\Api8\AlbumSongs8Method::class,
        Method\Api8\ArtistAlbums8Method::ACTION => Method\Api8\ArtistAlbums8Method::class,
        Method\Api8\Artist8Method::ACTION => Method\Api8\Artist8Method::class,
        Method\Api8\Artists8Method::ACTION => Method\Api8\Artists8Method::class,
        Method\Api8\ArtistSongs8Method::ACTION => Method\Api8\ArtistSongs8Method::class,
        Method\Api8\BookmarkCreate8Method::ACTION => Method\Api8\BookmarkCreate8Method::class,
        Method\Api8\BookmarkCreate8Method::REST_ACTION => Method\Api8\BookmarkCreate8Method::class,
        Method\Api8\BookmarkDelete8Method::ACTION => Method\Api8\BookmarkDelete8Method::class,
        Method\Api8\BookmarkDelete8Method::REST_ACTION => Method\Api8\BookmarkDelete8Method::class,
        Method\Api8\BookmarkEdit8Method::ACTION => Method\Api8\BookmarkEdit8Method::class,
        Method\Api8\BookmarkEdit8Method::REST_ACTION => Method\Api8\BookmarkEdit8Method::class,
        Method\Api8\Bookmark8Method::ACTION => Method\Api8\Bookmark8Method::class,
        Method\Api8\Bookmarks8Method::ACTION => Method\Api8\Bookmarks8Method::class,
        Method\Api8\Browse8Method::ACTION => Method\Api8\Browse8Method::class,
        Method\Api8\CatalogAction8Method::ACTION => Method\Api8\CatalogAction8Method::class,
        Method\Api8\CatalogAdd8Method::ACTION => Method\Api8\CatalogAdd8Method::class,
        Method\Api8\CatalogCreate8Method::ACTION => Method\Api8\CatalogCreate8Method::class,
        Method\Api8\CatalogCreate8Method::REST_ACTION => Method\Api8\CatalogCreate8Method::class,
        Method\Api8\CatalogDelete8Method::ACTION => Method\Api8\CatalogDelete8Method::class,
        Method\Api8\CatalogDelete8Method::REST_ACTION => Method\Api8\CatalogDelete8Method::class,
        Method\Api8\CatalogFile8Method::ACTION => Method\Api8\CatalogFile8Method::class,
        Method\Api8\CatalogFolder8Method::ACTION => Method\Api8\CatalogFolder8Method::class,
        Method\Api8\Catalog8Method::ACTION => Method\Api8\Catalog8Method::class,
        Method\Api8\Catalogs8Method::ACTION => Method\Api8\Catalogs8Method::class,
        Method\Api8\DeletedPodcastEpisodes8Method::ACTION => Method\Api8\DeletedPodcastEpisodes8Method::class,
        Method\Api8\DeletedSongs8Method::ACTION => Method\Api8\DeletedSongs8Method::class,
        Method\Api8\DeletedVideos8Method::ACTION => Method\Api8\DeletedVideos8Method::class,
        Method\Api8\Democratic8Method::ACTION => Method\Api8\Democratic8Method::class,
        Method\Api8\Download8Method::ACTION => Method\Api8\Download8Method::class,
        Method\Api8\Flag8Method::ACTION => Method\Api8\Flag8Method::class,
        Method\Api8\Followers8Method::ACTION => Method\Api8\Followers8Method::class,
        Method\Api8\Following8Method::ACTION => Method\Api8\Following8Method::class,
        Method\Api8\LostPassword8Method::ACTION => Method\Api8\LostPassword8Method::class,
        Method\Api8\FriendsTimeline8Method::ACTION => Method\Api8\FriendsTimeline8Method::class,
        Method\Api8\GenreAlbums8Method::ACTION => Method\Api8\GenreAlbums8Method::class,
        Method\Api8\GenreArtists8Method::ACTION => Method\Api8\GenreArtists8Method::class,
        Method\Api8\Genre8Method::ACTION => Method\Api8\Genre8Method::class,
        Method\Api8\Genres8Method::ACTION => Method\Api8\Genres8Method::class,
        Method\Api8\GenreSongs8Method::ACTION => Method\Api8\GenreSongs8Method::class,
        Method\Api8\GetArt8Method::ACTION => Method\Api8\GetArt8Method::class,
        Method\Api8\GetBookmark8Method::ACTION => Method\Api8\GetBookmark8Method::class,
        Method\Api8\GetIndexes8Method::ACTION => Method\Api8\GetIndexes8Method::class,
        Method\Api8\GetExternalMetadata8Method::ACTION => Method\Api8\GetExternalMetadata8Method::class,
        Method\Api8\GetLyrics8Method::ACTION => Method\Api8\GetLyrics8Method::class,
        Method\Api8\GetSimilar8Method::ACTION => Method\Api8\GetSimilar8Method::class,
        Method\Api8\Goodbye8Method::ACTION => Method\Api8\Goodbye8Method::class,
        Method\Api8\Handshake8Method::ACTION => Method\Api8\Handshake8Method::class,
        Method\Api8\Index8Method::ACTION => Method\Api8\Index8Method::class,
        Method\Api8\LabelArtists8Method::ACTION => Method\Api8\LabelArtists8Method::class,
        Method\Api8\Label8Method::ACTION => Method\Api8\Label8Method::class,
        Method\Api8\Labels8Method::ACTION => Method\Api8\Labels8Method::class,
        Method\Api8\LastShouts8Method::ACTION => Method\Api8\LastShouts8Method::class,
        Method\Api8\License8Method::ACTION => Method\Api8\License8Method::class,
        Method\Api8\Licenses8Method::ACTION => Method\Api8\Licenses8Method::class,
        Method\Api8\LicenseSongs8Method::ACTION => Method\Api8\LicenseSongs8Method::class,
        Method\Api8\List8Method::ACTION => Method\Api8\List8Method::class,
        Method\Api8\LiveStream8Method::ACTION => Method\Api8\LiveStream8Method::class,
        Method\Api8\LiveStreamCreate8Method::ACTION => Method\Api8\LiveStreamCreate8Method::class,
        Method\Api8\LiveStreamCreate8Method::REST_ACTION => Method\Api8\LiveStreamCreate8Method::class,
        Method\Api8\LiveStreamDelete8Method::ACTION => Method\Api8\LiveStreamDelete8Method::class,
        Method\Api8\LiveStreamDelete8Method::REST_ACTION => Method\Api8\LiveStreamDelete8Method::class,
        Method\Api8\LiveStreamEdit8Method::ACTION => Method\Api8\LiveStreamEdit8Method::class,
        Method\Api8\LiveStreamEdit8Method::REST_ACTION => Method\Api8\LiveStreamEdit8Method::class,
        Method\Api8\LiveStreams8Method::ACTION => Method\Api8\LiveStreams8Method::class,
        Method\Api8\Localplay8Method::ACTION => Method\Api8\Localplay8Method::class,
        Method\Api8\LocalplaySongs8Method::ACTION => Method\Api8\LocalplaySongs8Method::class,
        Method\Api8\NowPlaying8Method::ACTION => Method\Api8\NowPlaying8Method::class,
        Method\Api8\Ping8Method::ACTION => Method\Api8\Ping8Method::class,
        Method\Api8\PlaylistAdd8Method::ACTION => Method\Api8\PlaylistAdd8Method::class,
        Method\Api8\PlaylistAdd8Method::REST_ACTION => Method\Api8\PlaylistAdd8Method::class,
        Method\Api8\PlaylistAddSong8Method::ACTION => Method\Api8\PlaylistAddSong8Method::class,
        Method\Api8\PlaylistAddSong8Method::REST_ACTION => Method\Api8\PlaylistAddSong8Method::class,
        Method\Api8\PlaylistCreate8Method::ACTION => Method\Api8\PlaylistCreate8Method::class,
        Method\Api8\PlaylistCreate8Method::REST_ACTION => Method\Api8\PlaylistCreate8Method::class,
        Method\Api8\PlaylistDelete8Method::ACTION => Method\Api8\PlaylistDelete8Method::class,
        Method\Api8\PlaylistDelete8Method::REST_ACTION => Method\Api8\PlaylistDelete8Method::class,
        Method\Api8\PlaylistEdit8Method::ACTION => Method\Api8\PlaylistEdit8Method::class,
        Method\Api8\PlaylistEdit8Method::REST_ACTION => Method\Api8\PlaylistEdit8Method::class,
        Method\Api8\PlaylistGenerate8Method::ACTION => Method\Api8\PlaylistGenerate8Method::class,
        Method\Api8\PlaylistHash8Method::ACTION => Method\Api8\PlaylistHash8Method::class,
        Method\Api8\Playlist8Method::ACTION => Method\Api8\Playlist8Method::class,
        Method\Api8\PlaylistRemoveSong8Method::ACTION => Method\Api8\PlaylistRemoveSong8Method::class,
        Method\Api8\PlaylistRemoveSong8Method::REST_ACTION => Method\Api8\PlaylistRemoveSong8Method::class,
        Method\Api8\Playlists8Method::ACTION => Method\Api8\Playlists8Method::class,
        Method\Api8\PlaylistSongs8Method::ACTION => Method\Api8\PlaylistSongs8Method::class,
        Method\Api8\PodcastCreate8Method::ACTION => Method\Api8\PodcastCreate8Method::class,
        Method\Api8\PodcastCreate8Method::REST_ACTION => Method\Api8\PodcastCreate8Method::class,
        Method\Api8\PodcastDelete8Method::ACTION => Method\Api8\PodcastDelete8Method::class,
        Method\Api8\PodcastDelete8Method::REST_ACTION => Method\Api8\PodcastDelete8Method::class,
        Method\Api8\PodcastEdit8Method::ACTION => Method\Api8\PodcastEdit8Method::class,
        Method\Api8\PodcastEdit8Method::REST_ACTION => Method\Api8\PodcastEdit8Method::class,
        Method\Api8\PodcastUpdate8Method::ACTION => Method\Api8\PodcastUpdate8Method::class,
        Method\Api8\PodcastEpisodeDelete8Method::ACTION => Method\Api8\PodcastEpisodeDelete8Method::class,
        Method\Api8\PodcastEpisodeDelete8Method::REST_ACTION => Method\Api8\PodcastEpisodeDelete8Method::class,
        Method\Api8\PodcastEpisode8Method::ACTION => Method\Api8\PodcastEpisode8Method::class,
        Method\Api8\PodcastEpisodes8Method::ACTION => Method\Api8\PodcastEpisodes8Method::class,
        Method\Api8\Podcast8Method::ACTION => Method\Api8\Podcast8Method::class,
        Method\Api8\Podcasts8Method::ACTION => Method\Api8\Podcasts8Method::class,
        Method\Api8\PreferenceCreate8Method::ACTION => Method\Api8\PreferenceCreate8Method::class,
        Method\Api8\PreferenceCreate8Method::REST_ACTION => Method\Api8\PreferenceCreate8Method::class,
        Method\Api8\PreferenceDelete8Method::ACTION => Method\Api8\PreferenceDelete8Method::class,
        Method\Api8\PreferenceDelete8Method::REST_ACTION => Method\Api8\PreferenceDelete8Method::class,
        Method\Api8\PreferenceEdit8Method::ACTION => Method\Api8\PreferenceEdit8Method::class,
        Method\Api8\PreferenceEdit8Method::REST_ACTION => Method\Api8\PreferenceEdit8Method::class,
        Method\Api8\Player8Method::ACTION => Method\Api8\Player8Method::class,
        Method\Api8\Rate8Method::ACTION => Method\Api8\Rate8Method::class,
        Method\Api8\RecordPlay8Method::ACTION => Method\Api8\RecordPlay8Method::class,
        Method\Api8\Register8Method::ACTION => Method\Api8\Register8Method::class,
        Method\Api8\Scrobble8Method::ACTION => Method\Api8\Scrobble8Method::class,
        Method\Api8\Search8Method::ACTION => Method\Api8\Search8Method::class,
        Method\Api8\SearchGroup8Method::ACTION => Method\Api8\SearchGroup8Method::class,
        Method\Api8\SearchRules8Method::ACTION => Method\Api8\SearchRules8Method::class,
        Method\Api8\SearchSongs8Method::ACTION => Method\Api8\SearchSongs8Method::class,
        Method\Api8\ShareCreate8Method::ACTION => Method\Api8\ShareCreate8Method::class,
        Method\Api8\ShareCreate8Method::REST_ACTION => Method\Api8\ShareCreate8Method::class,
        Method\Api8\ShareDelete8Method::ACTION => Method\Api8\ShareDelete8Method::class,
        Method\Api8\ShareDelete8Method::REST_ACTION => Method\Api8\ShareDelete8Method::class,
        Method\Api8\ShareEdit8Method::ACTION => Method\Api8\ShareEdit8Method::class,
        Method\Api8\ShareEdit8Method::REST_ACTION => Method\Api8\ShareEdit8Method::class,
        Method\Api8\Share8Method::ACTION => Method\Api8\Share8Method::class,
        Method\Api8\Shares8Method::ACTION => Method\Api8\Shares8Method::class,
        Method\Api8\SmartlistDelete8Method::ACTION => Method\Api8\SmartlistDelete8Method::class,
        Method\Api8\SmartlistDelete8Method::REST_ACTION => Method\Api8\SmartlistDelete8Method::class,
        Method\Api8\Smartlist8Method::ACTION => Method\Api8\Smartlist8Method::class,
        Method\Api8\Smartlists8Method::ACTION => Method\Api8\Smartlists8Method::class,
        Method\Api8\SmartlistSongs8Method::ACTION => Method\Api8\SmartlistSongs8Method::class,
        Method\Api8\SongDelete8Method::ACTION => Method\Api8\SongDelete8Method::class,
        Method\Api8\SongDelete8Method::REST_ACTION => Method\Api8\SongDelete8Method::class,
        Method\Api8\Song8Method::ACTION => Method\Api8\Song8Method::class,
        Method\Api8\SongTags8Method::ACTION => Method\Api8\SongTags8Method::class,
        Method\Api8\Songs8Method::ACTION => Method\Api8\Songs8Method::class,
        Method\Api8\Stats8Method::ACTION => Method\Api8\Stats8Method::class,
        Method\Api8\Stream8Method::ACTION => Method\Api8\Stream8Method::class,
        Method\Api8\SystemPreference8Method::ACTION => Method\Api8\SystemPreference8Method::class,
        Method\Api8\SystemPreferences8Method::ACTION => Method\Api8\SystemPreferences8Method::class,
        Method\Api8\SystemUpdate8Method::ACTION => Method\Api8\SystemUpdate8Method::class,
        Method\Api8\Timeline8Method::ACTION => Method\Api8\Timeline8Method::class,
        Method\Api8\ToggleFollow8Method::ACTION => Method\Api8\ToggleFollow8Method::class,
        Method\Api8\SystemUpdate8Method::REST_ACTION => Method\Api8\SystemUpdate8Method::class,
        Method\Api8\UpdateArtistInfo8Method::ACTION => Method\Api8\UpdateArtistInfo8Method::class,
        Method\Api8\UpdateArt8Method::ACTION => Method\Api8\UpdateArt8Method::class,
        Method\Api8\UpdateFromTags8Method::ACTION => Method\Api8\UpdateFromTags8Method::class,
        Method\Api8\UpdatePodcast8Method::ACTION => Method\Api8\UpdatePodcast8Method::class,
        Method\Api8\UrlToSong8Method::ACTION => Method\Api8\UrlToSong8Method::class,
        Method\Api8\UserCreate8Method::ACTION => Method\Api8\UserCreate8Method::class,
        Method\Api8\UserCreate8Method::REST_ACTION => Method\Api8\UserCreate8Method::class,
        Method\Api8\UserEdit8Method::ACTION => Method\Api8\UserEdit8Method::class,
        Method\Api8\UserEdit8Method::REST_ACTION => Method\Api8\UserEdit8Method::class,
        Method\Api8\UserDelete8Method::ACTION => Method\Api8\UserDelete8Method::class,
        Method\Api8\UserDelete8Method::REST_ACTION => Method\Api8\UserDelete8Method::class,
        Method\Api8\User8Method::ACTION => Method\Api8\User8Method::class,
        Method\Api8\UserPlaylists8Method::ACTION => Method\Api8\UserPlaylists8Method::class,
        Method\Api8\UserPreference8Method::ACTION => Method\Api8\UserPreference8Method::class,
        Method\Api8\UserPreferences8Method::ACTION => Method\Api8\UserPreferences8Method::class,
        Method\Api8\UserSmartlists8Method::ACTION => Method\Api8\UserSmartlists8Method::class,
        Method\Api8\Users8Method::ACTION => Method\Api8\Users8Method::class,
        Method\Api8\UserUpdate8Method::ACTION => Method\Api8\UserUpdate8Method::class,
        Method\Api8\Video8Method::ACTION => Method\Api8\Video8Method::class,
        Method\Api8\Videos8Method::ACTION => Method\Api8\Videos8Method::class,
    ];

    public const API_VERSIONS = [
        3,
        4,
        5,
        6,
        8
    ];

    public const DEFAULT_VERSION = 6; // AMPACHE_VERSION

    public static string $auth_version = '350001';

    public static string $version = '8.0.0'; // AMPACHE_VERSION

    public static string $version_numeric = '800000'; // AMPACHE_VERSION

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
     * @param array<string, string> $return_data
     */
    public static function message(string $message, string $format = 'xml', array $return_data = []): void
    {
        switch ($format) {
            case 'json':
                echo Json8_Data::success($message, $return_data);
                break;
            default:
                echo Xml8_Data::success($message, $return_data);
        }
    }

    /**
     * error
     * call the correct error message depending on format
     */
    public static function error(string $message, int|string $error_code, string $method, string $error_type, string $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json8_Data::error($error_code, $message, $method, $error_type);
                break;
            default:
                echo Xml8_Data::error($error_code, $message, $method, $error_type);
        }
    }

    /**
     * empty
     * call the correct empty message depending on format
     */
    public static function empty(?string $empty_type, string $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json8_Data::empty($empty_type);
                break;
            default:
                echo Xml8_Data::empty();
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
     * @return bool|string
     */
    public static function parameter_exists(array $input, array $parameters): bool|string
    {
        foreach ($parameters as $parameter) {
            if (array_key_exists($parameter, $input)) {
                continue;
            }

            return $parameter;
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
     * @param string $method
     * @return bool
     */
    public static function check_parameter(array $input, array $parameters, string $method): bool
    {
        $parameter = self::parameter_exists($input, $parameters);
        if ($parameter === true) {
            return true;
        }

        debug_event(self::class, "'" . $parameter . "' required on " . $method . " function call.", 2);

        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        self::error(sprintf(T_('Bad Request: %s'), $parameter), '4710', $method, 'system', $input['api_format']);

        return false;
    }

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

        $counts    = Catalog::get_server_counts($client->id ?? 0);
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
