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
    public const METHOD_LIST = [
        Method\Api6\AdvancedSearch6Method::ACTION => Method\Api6\AdvancedSearch6Method::class,
        Method\Api6\Album6Method::ACTION => Method\Api6\Album6Method::class,
        Method\Api6\Albums6Method::ACTION => Method\Api6\Albums6Method::class,
        Method\Api6\AlbumSongs6Method::ACTION => Method\Api6\AlbumSongs6Method::class,
        Method\Api6\ArtistAlbums6Method::ACTION => Method\Api6\ArtistAlbums6Method::class,
        Method\Api6\Artist6Method::ACTION => Method\Api6\Artist6Method::class,
        Method\Api6\Artists6Method::ACTION => Method\Api6\Artists6Method::class,
        Method\Api6\ArtistSongs6Method::ACTION => Method\Api6\ArtistSongs6Method::class,
        Method\Api6\BookmarkCreate6Method::ACTION => Method\Api6\BookmarkCreate6Method::class,
        Method\Api6\BookmarkCreate6Method::REST_ACTION => Method\Api6\BookmarkCreate6Method::class,
        Method\Api6\BookmarkDelete6Method::ACTION => Method\Api6\BookmarkDelete6Method::class,
        Method\Api6\BookmarkDelete6Method::REST_ACTION => Method\Api6\BookmarkDelete6Method::class,
        Method\Api6\BookmarkEdit6Method::ACTION => Method\Api6\BookmarkEdit6Method::class,
        Method\Api6\BookmarkEdit6Method::REST_ACTION => Method\Api6\BookmarkEdit6Method::class,
        Method\Api6\Bookmark6Method::ACTION => Method\Api6\Bookmark6Method::class,
        Method\Api6\Bookmarks6Method::ACTION => Method\Api6\Bookmarks6Method::class,
        Method\Api6\Browse6Method::ACTION => Method\Api6\Browse6Method::class,
        Method\Api6\CatalogAction6Method::ACTION => Method\Api6\CatalogAction6Method::class,
        Method\Api6\CatalogAdd6Method::ACTION => Method\Api6\CatalogAdd6Method::class,
        Method\Api6\CatalogCreate6Method::ACTION => Method\Api6\CatalogCreate6Method::class,
        Method\Api6\CatalogCreate6Method::REST_ACTION => Method\Api6\CatalogCreate6Method::class,
        Method\Api6\CatalogDelete6Method::ACTION => Method\Api6\CatalogDelete6Method::class,
        Method\Api6\CatalogDelete6Method::REST_ACTION => Method\Api6\CatalogDelete6Method::class,
        Method\Api6\CatalogFile6Method::ACTION => Method\Api6\CatalogFile6Method::class,
        Method\Api6\CatalogFolder6Method::ACTION => Method\Api6\CatalogFolder6Method::class,
        Method\Api6\Catalog6Method::ACTION => Method\Api6\Catalog6Method::class,
        Method\Api6\Catalogs6Method::ACTION => Method\Api6\Catalogs6Method::class,
        Method\Api6\DeletedPodcastEpisodes6Method::ACTION => Method\Api6\DeletedPodcastEpisodes6Method::class,
        Method\Api6\DeletedSongs6Method::ACTION => Method\Api6\DeletedSongs6Method::class,
        Method\Api6\DeletedVideos6Method::ACTION => Method\Api6\DeletedVideos6Method::class,
        Method\Api6\Democratic6Method::ACTION => Method\Api6\Democratic6Method::class,
        Method\Api6\Download6Method::ACTION => Method\Api6\Download6Method::class,
        Method\Api6\Flag6Method::ACTION => Method\Api6\Flag6Method::class,
        Method\Api6\Followers6Method::ACTION => Method\Api6\Followers6Method::class,
        Method\Api6\Following6Method::ACTION => Method\Api6\Following6Method::class,
        Method\Api6\LostPassword6Method::ACTION => Method\Api6\LostPassword6Method::class,
        Method\Api6\FriendsTimeline6Method::ACTION => Method\Api6\FriendsTimeline6Method::class,
        Method\Api6\GenreAlbums6Method::ACTION => Method\Api6\GenreAlbums6Method::class,
        Method\Api6\GenreArtists6Method::ACTION => Method\Api6\GenreArtists6Method::class,
        Method\Api6\Genre6Method::ACTION => Method\Api6\Genre6Method::class,
        Method\Api6\Genres6Method::ACTION => Method\Api6\Genres6Method::class,
        Method\Api6\GenreSongs6Method::ACTION => Method\Api6\GenreSongs6Method::class,
        Method\Api6\GetArt6Method::ACTION => Method\Api6\GetArt6Method::class,
        Method\Api6\GetBookmark6Method::ACTION => Method\Api6\GetBookmark6Method::class,
        Method\Api6\GetIndexes6Method::ACTION => Method\Api6\GetIndexes6Method::class,
        Method\Api6\GetExternalMetadata6Method::ACTION => Method\Api6\GetExternalMetadata6Method::class,
        Method\Api6\GetLyrics6Method::ACTION => Method\Api6\GetLyrics6Method::class,
        Method\Api6\GetSimilar6Method::ACTION => Method\Api6\GetSimilar6Method::class,
        Method\Api6\Goodbye6Method::ACTION => Method\Api6\Goodbye6Method::class,
        Method\Api6\Handshake6Method::ACTION => Method\Api6\Handshake6Method::class,
        Method\Api6\Index6Method::ACTION => Method\Api6\Index6Method::class,
        Method\Api6\LabelArtists6Method::ACTION => Method\Api6\LabelArtists6Method::class,
        Method\Api6\Label6Method::ACTION => Method\Api6\Label6Method::class,
        Method\Api6\Labels6Method::ACTION => Method\Api6\Labels6Method::class,
        Method\Api6\LastShouts6Method::ACTION => Method\Api6\LastShouts6Method::class,
        Method\Api6\License6Method::ACTION => Method\Api6\License6Method::class,
        Method\Api6\Licenses6Method::ACTION => Method\Api6\Licenses6Method::class,
        Method\Api6\LicenseSongs6Method::ACTION => Method\Api6\LicenseSongs6Method::class,
        Method\Api6\List6Method::ACTION => Method\Api6\List6Method::class,
        Method\Api6\LiveStream6Method::ACTION => Method\Api6\LiveStream6Method::class,
        Method\Api6\LiveStreamCreate6Method::ACTION => Method\Api6\LiveStreamCreate6Method::class,
        Method\Api6\LiveStreamCreate6Method::REST_ACTION => Method\Api6\LiveStreamCreate6Method::class,
        Method\Api6\LiveStreamDelete6Method::ACTION => Method\Api6\LiveStreamDelete6Method::class,
        Method\Api6\LiveStreamDelete6Method::REST_ACTION => Method\Api6\LiveStreamDelete6Method::class,
        Method\Api6\LiveStreamEdit6Method::ACTION => Method\Api6\LiveStreamEdit6Method::class,
        Method\Api6\LiveStreamEdit6Method::REST_ACTION => Method\Api6\LiveStreamEdit6Method::class,
        Method\Api6\LiveStreams6Method::ACTION => Method\Api6\LiveStreams6Method::class,
        Method\Api6\Localplay6Method::ACTION => Method\Api6\Localplay6Method::class,
        Method\Api6\LocalplaySongs6Method::ACTION => Method\Api6\LocalplaySongs6Method::class,
        Method\Api6\NowPlaying6Method::ACTION => Method\Api6\NowPlaying6Method::class,
        Method\Api6\Ping6Method::ACTION => Method\Api6\Ping6Method::class,
        Method\Api6\PlaylistAdd6Method::ACTION => Method\Api6\PlaylistAdd6Method::class,
        Method\Api6\PlaylistAdd6Method::REST_ACTION => Method\Api6\PlaylistAdd6Method::class,
        Method\Api6\PlaylistAddSong6Method::ACTION => Method\Api6\PlaylistAddSong6Method::class,
        Method\Api6\PlaylistAddSong6Method::REST_ACTION => Method\Api6\PlaylistAddSong6Method::class,
        Method\Api6\PlaylistCreate6Method::ACTION => Method\Api6\PlaylistCreate6Method::class,
        Method\Api6\PlaylistCreate6Method::REST_ACTION => Method\Api6\PlaylistCreate6Method::class,
        Method\Api6\PlaylistDelete6Method::ACTION => Method\Api6\PlaylistDelete6Method::class,
        Method\Api6\PlaylistDelete6Method::REST_ACTION => Method\Api6\PlaylistDelete6Method::class,
        Method\Api6\PlaylistEdit6Method::ACTION => Method\Api6\PlaylistEdit6Method::class,
        Method\Api6\PlaylistEdit6Method::REST_ACTION => Method\Api6\PlaylistEdit6Method::class,
        Method\Api6\PlaylistGenerate6Method::ACTION => Method\Api6\PlaylistGenerate6Method::class,
        Method\Api6\PlaylistHash6Method::ACTION => Method\Api6\PlaylistHash6Method::class,
        Method\Api6\Playlist6Method::ACTION => Method\Api6\Playlist6Method::class,
        Method\Api6\PlaylistRemoveSong6Method::ACTION => Method\Api6\PlaylistRemoveSong6Method::class,
        Method\Api6\PlaylistRemoveSong6Method::REST_ACTION => Method\Api6\PlaylistRemoveSong6Method::class,
        Method\Api6\Playlists6Method::ACTION => Method\Api6\Playlists6Method::class,
        Method\Api6\PlaylistSongs6Method::ACTION => Method\Api6\PlaylistSongs6Method::class,
        Method\Api6\PodcastCreate6Method::ACTION => Method\Api6\PodcastCreate6Method::class,
        Method\Api6\PodcastCreate6Method::REST_ACTION => Method\Api6\PodcastCreate6Method::class,
        Method\Api6\PodcastDelete6Method::ACTION => Method\Api6\PodcastDelete6Method::class,
        Method\Api6\PodcastDelete6Method::REST_ACTION => Method\Api6\PodcastDelete6Method::class,
        Method\Api6\PodcastEdit6Method::ACTION => Method\Api6\PodcastEdit6Method::class,
        Method\Api6\PodcastEdit6Method::REST_ACTION => Method\Api6\PodcastEdit6Method::class,
        Method\Api6\PodcastUpdate6Method::ACTION => Method\Api6\PodcastUpdate6Method::class,
        Method\Api6\PodcastEpisodeDelete6Method::ACTION => Method\Api6\PodcastEpisodeDelete6Method::class,
        Method\Api6\PodcastEpisodeDelete6Method::REST_ACTION => Method\Api6\PodcastEpisodeDelete6Method::class,
        Method\Api6\PodcastEpisode6Method::ACTION => Method\Api6\PodcastEpisode6Method::class,
        Method\Api6\PodcastEpisodes6Method::ACTION => Method\Api6\PodcastEpisodes6Method::class,
        Method\Api6\Podcast6Method::ACTION => Method\Api6\Podcast6Method::class,
        Method\Api6\Podcasts6Method::ACTION => Method\Api6\Podcasts6Method::class,
        Method\Api6\PreferenceCreate6Method::ACTION => Method\Api6\PreferenceCreate6Method::class,
        Method\Api6\PreferenceCreate6Method::REST_ACTION => Method\Api6\PreferenceCreate6Method::class,
        Method\Api6\PreferenceDelete6Method::ACTION => Method\Api6\PreferenceDelete6Method::class,
        Method\Api6\PreferenceDelete6Method::REST_ACTION => Method\Api6\PreferenceDelete6Method::class,
        Method\Api6\PreferenceEdit6Method::ACTION => Method\Api6\PreferenceEdit6Method::class,
        Method\Api6\PreferenceEdit6Method::REST_ACTION => Method\Api6\PreferenceEdit6Method::class,
        Method\Api6\Player6Method::ACTION => Method\Api6\Player6Method::class,
        Method\Api6\Rate6Method::ACTION => Method\Api6\Rate6Method::class,
        Method\Api6\RecordPlay6Method::ACTION => Method\Api6\RecordPlay6Method::class,
        Method\Api6\Register6Method::ACTION => Method\Api6\Register6Method::class,
        Method\Api6\Scrobble6Method::ACTION => Method\Api6\Scrobble6Method::class,
        Method\Api6\Search6Method::ACTION => Method\Api6\Search6Method::class,
        Method\Api6\SearchGroup6Method::ACTION => Method\Api6\SearchGroup6Method::class,
        Method\Api6\SearchRules6Method::ACTION => Method\Api6\SearchRules6Method::class,
        Method\Api6\SearchSongs6Method::ACTION => Method\Api6\SearchSongs6Method::class,
        Method\Api6\ShareCreate6Method::ACTION => Method\Api6\ShareCreate6Method::class,
        Method\Api6\ShareCreate6Method::REST_ACTION => Method\Api6\ShareCreate6Method::class,
        Method\Api6\ShareDelete6Method::ACTION => Method\Api6\ShareDelete6Method::class,
        Method\Api6\ShareDelete6Method::REST_ACTION => Method\Api6\ShareDelete6Method::class,
        Method\Api6\ShareEdit6Method::ACTION => Method\Api6\ShareEdit6Method::class,
        Method\Api6\ShareEdit6Method::REST_ACTION => Method\Api6\ShareEdit6Method::class,
        Method\Api6\Share6Method::ACTION => Method\Api6\Share6Method::class,
        Method\Api6\Shares6Method::ACTION => Method\Api6\Shares6Method::class,
        Method\Api6\SmartlistDelete6Method::ACTION => Method\Api6\SmartlistDelete6Method::class,
        Method\Api6\SmartlistDelete6Method::REST_ACTION => Method\Api6\SmartlistDelete6Method::class,
        Method\Api6\Smartlist6Method::ACTION => Method\Api6\Smartlist6Method::class,
        Method\Api6\Smartlists6Method::ACTION => Method\Api6\Smartlists6Method::class,
        Method\Api6\SmartlistSongs6Method::ACTION => Method\Api6\SmartlistSongs6Method::class,
        Method\Api6\SongDelete6Method::ACTION => Method\Api6\SongDelete6Method::class,
        Method\Api6\SongDelete6Method::REST_ACTION => Method\Api6\SongDelete6Method::class,
        Method\Api6\Song6Method::ACTION => Method\Api6\Song6Method::class,
        Method\Api6\SongTags6Method::ACTION => Method\Api6\SongTags6Method::class,
        Method\Api6\Songs6Method::ACTION => Method\Api6\Songs6Method::class,
        Method\Api6\Stats6Method::ACTION => Method\Api6\Stats6Method::class,
        Method\Api6\Stream6Method::ACTION => Method\Api6\Stream6Method::class,
        Method\Api6\SystemPreference6Method::ACTION => Method\Api6\SystemPreference6Method::class,
        Method\Api6\SystemPreferences6Method::ACTION => Method\Api6\SystemPreferences6Method::class,
        Method\Api6\SystemUpdate6Method::ACTION => Method\Api6\SystemUpdate6Method::class,
        Method\Api6\Timeline6Method::ACTION => Method\Api6\Timeline6Method::class,
        Method\Api6\ToggleFollow6Method::ACTION => Method\Api6\ToggleFollow6Method::class,
        Method\Api6\SystemUpdate6Method::REST_ACTION => Method\Api6\SystemUpdate6Method::class,
        Method\Api6\UpdateArtistInfo6Method::ACTION => Method\Api6\UpdateArtistInfo6Method::class,
        Method\Api6\UpdateArt6Method::ACTION => Method\Api6\UpdateArt6Method::class,
        Method\Api6\UpdateFromTags6Method::ACTION => Method\Api6\UpdateFromTags6Method::class,
        Method\Api6\UpdatePodcast6Method::ACTION => Method\Api6\UpdatePodcast6Method::class,
        Method\Api6\UrlToSong6Method::ACTION => Method\Api6\UrlToSong6Method::class,
        Method\Api6\UserCreate6Method::ACTION => Method\Api6\UserCreate6Method::class,
        Method\Api6\UserCreate6Method::REST_ACTION => Method\Api6\UserCreate6Method::class,
        Method\Api6\UserEdit6Method::ACTION => Method\Api6\UserEdit6Method::class,
        Method\Api6\UserEdit6Method::REST_ACTION => Method\Api6\UserEdit6Method::class,
        Method\Api6\UserDelete6Method::ACTION => Method\Api6\UserDelete6Method::class,
        Method\Api6\UserDelete6Method::REST_ACTION => Method\Api6\UserDelete6Method::class,
        Method\Api6\User6Method::ACTION => Method\Api6\User6Method::class,
        Method\Api6\UserPlaylists6Method::ACTION => Method\Api6\UserPlaylists6Method::class,
        Method\Api6\UserPreference6Method::ACTION => Method\Api6\UserPreference6Method::class,
        Method\Api6\UserPreferences6Method::ACTION => Method\Api6\UserPreferences6Method::class,
        Method\Api6\UserSmartlists6Method::ACTION => Method\Api6\UserSmartlists6Method::class,
        Method\Api6\Users6Method::ACTION => Method\Api6\Users6Method::class,
        Method\Api6\UserUpdate66Method::ACTION => Method\Api6\UserUpdate66Method::class,
        Method\Api6\Video6Method::ACTION => Method\Api6\Video6Method::class,
        Method\Api6\Videos6Method::ACTION => Method\Api6\Videos6Method::class,
    ];

    public static string $auth_version = '350001';

    public static string $version = '6.9.1'; // AMPACHE_VERSION

    public static string $version_numeric = '691015'; // AMPACHE_VERSION

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
     * error
     * call the correct error message depending on format
     */
    public static function error(string $message, int|string $error_code, string $method, string $error_type, string $format = 'xml'): void
    {
        switch ($format) {
            case 'json':
                echo Json6_Data::error($error_code, $message, $method, $error_type);
                break;
            default:
                echo Xml6_Data::error($error_code, $message, $method, $error_type);
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
                echo Json6_Data::empty($empty_type);
                break;
            default:
                echo Xml6_Data::empty();
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
