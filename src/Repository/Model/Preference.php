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

namespace Ampache\Repository\Model;

use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;

/**
 * This handles all of the preference stuff for Ampache
 */
class Preference extends database_object
{
    protected const DB_TABLENAME = 'preference';

    /**
     * This array contains System preferences that can (should) not be edited or deleted from the api
     */
    public const SYSTEM_LIST = [
        'ajax_load',
        'album_group',
        'album_release_type_sort',
        'album_release_type',
        'album_sort',
        'allow_democratic_playback',
        'allow_localplay_playback',
        'allow_personal_info_agent',
        'allow_personal_info_now',
        'allow_personal_info_recent',
        'allow_personal_info_time',
        'allow_stream_playback',
        'allow_upload',
        'allow_video',
        'api_always_download',
        'api_enable_3',
        'api_enable_4',
        'api_enable_5',
        'api_enable_6',
        'api_force_version',
        'api_hidden_playlists',
        'api_hide_dupe_searches',
        'autoupdate_lastcheck',
        'autoupdate_lastversion_new',
        'autoupdate_lastversion',
        'autoupdate',
        'bookmark_latest',
        'broadcast_by_default',
        'browse_album_disk_grid_view',
        'browse_album_grid_view',
        'browse_artist_grid_view',
        'browse_filter',
        'browse_live_stream_grid_view',
        'browse_playlist_grid_view',
        'browse_podcast_episode_grid_view',
        'browse_podcast_grid_view',
        'browse_song_grid_view',
        'browse_video_grid_view',
        'browser_notify_timeout',
        'browser_notify',
        'catalog_check_duplicate',
        'cron_cache',
        'custom_blankalbum',
        'custom_datetime',
        'custom_favicon',
        'custom_login_background',
        'custom_login_logo',
        'custom_logo_user',
        'custom_logo',
        'custom_text_footer',
        'custom_timezone',
        'daap_backend',
        'daap_pass',
        'demo_clear_sessions',
        'demo_use_search',
        'direct_play_limit',
        'disabled_custom_metadata_fields_input',
        'disabled_custom_metadata_fields',
        'download',
        'extended_playlist_links',
        'external_links_bandcamp',
        'external_links_discogs',
        'external_links_duckduckgo',
        'external_links_google',
        'external_links_lastfm',
        'external_links_musicbrainz',
        'external_links_wikipedia',
        'force_http_play',
        'geolocation',
        'hide_genres',
        'hide_single_artist',
        'home_moment_albums',
        'home_moment_videos',
        'home_now_playing',
        'home_recently_played_all',
        'home_recently_played',
        'httpq_active',
        'index_dashboard_form',
        'jp_volume',
        'lang',
        'lastfm_challenge',
        'lastfm_grant_link',
        'libitem_browse_alpha',
        'libitem_contextmenu',
        'localplay_controller',
        'localplay_level',
        'lock_songs',
        'notify_email',
        'now_playing_per_user',
        'of_the_moment',
        'offset_limit',
        'perpetual_api_session',
        'play_type',
        'playlist_method',
        'playlist_type',
        'podcast_keep',
        'podcast_new_download',
        'popular_threshold',
        'rate_limit',
        'share_expire',
        'share',
        'show_album_artist',
        'show_artist',
        'show_donate',
        'show_header_login',
        'show_license',
        'show_lyrics',
        'show_original_year',
        'show_played_times',
        'show_playlist_media_parent',
        'show_playlist_username',
        'show_skipped_times',
        'show_subtitle',
        'show_wrapped',
        'sidebar_hide_browse',
        'sidebar_hide_dashboard',
        'sidebar_hide_information',
        'sidebar_hide_playlist',
        'sidebar_hide_search',
        'sidebar_hide_switcher',
        'sidebar_hide_video',
        'sidebar_light',
        'sidebar_order_browse',
        'sidebar_order_dashboard',
        'sidebar_order_information',
        'sidebar_order_playlist',
        'sidebar_order_search',
        'sidebar_order_video',
        'site_title',
        'slideshow_time',
        'song_page_title',
        'stats_threshold',
        'stream_beautiful_url',
        'subsonic_always_download',
        'subsonic_backend',
        'subsonic_force_album_artist',
        'subsonic_legacy',
        'subsonic_single_user_data',
        'theme_color',
        'theme_name',
        'topmenu',
        'transcode_bitrate',
        'transcode',
        'ui_fixed',
        'unique_playlist',
        'upload_access_level',
        'upload_allow_edit',
        'upload_allow_remove',
        'upload_catalog_pattern',
        'upload_catalog',
        'upload_script',
        'upload_subdir',
        'upload_user_artist',
        'upnp_backend',
        'use_original_year',
        'use_play2',
        'webdav_backend',
        'webplayer_aurora',
        'webplayer_confirmclose',
        'webplayer_flash',
        'webplayer_html5',
        'webplayer_pausetabs',
        'webplayer_removeplayed',
    ];

    /**
     * plugin and module preferences might not be there but they need to be kept if you're using them
     */
    public const PLUGIN_LIST = [
        '7digital_api_key',
        '7digital_secret_api_key',
        'amazon_base_url',
        'amazon_developer_associate_tag',
        'amazon_developer_private_api_key',
        'amazon_developer_public_key',
        'amazon_max_results_pages',
        'bitly_api_key',
        'bitly_username',
        'catalogfav_gridview',
        'catalogfav_max_items',
        'catalogfav_compact',
        'catalogfav_order',
        'discogs_api_key',
        'discogs_secret_api_key',
        'flickr_api_key',
        'ftl_max_items',
        'ftl_order',
        'gmaps_api_key',
        'googleanalytics_tracking_id',
        'headphones_api_key',
        'headphones_api_url',
        'homedash_max_items',
        'homedash_newest',
        'homedash_order',
        'homedash_popular',
        'homedash_random',
        'homedash_recent',
        'homedash_trending',
        'httpq_active',
        'index_dashboard_form',
        'lastfm_challenge',
        'lastfm_grant_link',
        'librefm_challenge',
        'librefm_grant_link',
        'listenbrainz_token',
        'matomo_site_id',
        'matomo_url',
        'mb_overwrite_name',
        'mpd_active',
        'paypal_business',
        'paypal_currency_code',
        'personalfav_display',
        'personalfav_order',
        'personalfav_playlist',
        'personalfav_smartlist',
        'piwik_site_id',
        'piwik_url',
        'ratingmatch_flag_rule',
        'ratingmatch_flags',
        'ratingmatch_star1_rule',
        'ratingmatch_star2_rule',
        'ratingmatch_star3_rule',
        'ratingmatch_star4_rule',
        'ratingmatch_star5_rule',
        'ratingmatch_stars',
        'ratingmatch_write_tags',
        'rssview_feed_url',
        'rssview_max_items',
        'rssview_order',
        'shouthome_max_items',
        'shouthome_order',
        'stream_control_bandwidth_days',
        'stream_control_bandwidth_max',
        'stream_control_hits_days',
        'stream_control_hits_max',
        'stream_control_time_days',
        'stream_control_time_max',
        'tadb_api_key',
        'tadb_overwrite_name',
        'upnp_active',
        'vlc_active',
        'xbmc_active',
        'yourls_api_key',
        'yourls_domain',
        'yourls_use_idn',
    ];

    /**
     * __constructor
     * This does nothing... amazing isn't it!
     */
    private function __construct()
    {
        // Rien a faire
    }

    /**
     * get_by_user
     * Return a preference for specific user identifier
     * Get all preference the first time and add them to the cache
     * @see User::getPreferenceValue()
     */
    public static function get_by_user(int $user_id, string $pref_name): int|string|null
    {
        //debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '}...', 5);
        if (parent::is_cached('get_by_user-' . $pref_name, $user_id)) {
            return (parent::get_from_cache('get_by_user-' . $pref_name, $user_id)[0]);
        }

        $column_name = 'name'; // Ampache 7
        if (!Dba::read('SELECT `name` FROM `user_preference` LIMIT 1;', [], true)) {
            $column_name = 'preference'; // Backward compatibility for Ampache < 7
            $pref_name   = self::id_from_name($pref_name);
        }
        //debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '} -- no cache, need to do one', 5);

        // Get default preferences from user -1
        $db_results  = Dba::read("SELECT * FROM `user_preference` WHERE `user` = '-1' ORDER BY `$column_name`;");
        $pref_default=[];
        while ($row = Dba::fetch_assoc($db_results)) {
            $pref_default[ $row[$column_name] ] = $row['value'];
        }

        // Get user specific preferences
        $db_results = Dba::read("SELECT * FROM `user_preference` WHERE `user` = ? ORDER BY `$column_name`;", [ $user_id ]);
        $pref_user  =[];
        while ($row = Dba::fetch_assoc($db_results)) {
            $pref_user[ $row[$column_name] ] = $row['value'];
        }

        // Merge them (override default with user-specific preference)
        $pref = array_replace($pref_default, $pref_user);

        // Now cache all of them
        foreach ($pref as $key => $value) {
            parent::add_to_cache('get_by_user-' . $key, $user_id, [$value]);
        }

        // Handle if a parameters is missing
        if (
            empty($pref_name) ||
            !array_key_exists($pref_name, $pref)
        ) {
            debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '} -- this preference is missing, return default value', 5);

            return '';
        }

        return $pref[$pref_name];
    }

    /**
     * update
     * This updates a single preference from the given name or id
     */
    public static function update(
        int|string $preference,
        int $user_id,
        array|int|float|string|bool|null $value,
        ?bool $applytoall = false,
        ?bool $applytodefault = false
    ): bool {
        if ($user_id === 0) {
            return false;
        }
        $access100 = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
        // First prepare
        if (!is_numeric($preference)) {
            $pref_id = self::id_from_name($preference);
            $name    = (string)$preference;
        } else {
            $pref_id = (int)$preference;
            $name    = self::name_from_id($preference);
        }

        if (
            (
                $pref_id === null ||
                $pref_id === 0
            ) ||
            (
                $name === null ||
                $name === '' ||
                $name === '0'
            )
        ) {
            return false;
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $ampacheSeven = true;
        if (!Dba::read('SELECT `name` FROM `user_preference` LIMIT 1;', [], true)) {
            $ampacheSeven = false;
        }

        $params = ($ampacheSeven)
            ? [$value, $name]
            : [$value, $pref_id];

        if ($applytoall && $access100) {
            $user_check = "";
        } else {
            $user_check = "AND `user` = ?";
            $params[]   = $user_id;
        }

        if ($applytodefault && $access100) {
            $sql = ($ampacheSeven)
                ? "UPDATE `preference` SET `value` = ? WHERE `name` = ?;"
                : "UPDATE `preference` SET `value` = ? WHERE `preference` = ?;";
            Dba::write($sql, $params);
        }

        if (self::has_access($name)) {
            $sql = ($ampacheSeven)
                ? 'UPDATE `user_preference` SET `value` = ? WHERE `name` = ? ' . $user_check
                : 'UPDATE `user_preference` SET `value` = ? WHERE `preference` = ? ' . $user_check;
            Dba::write($sql, $params);
            self::clear_from_session();

            parent::remove_from_cache('get_by_user', $user_id);

            return true;
        } else {
            debug_event(self::class, (Core::get_global('user')?->username ?? T_('Unknown')) . ' attempted to update ' . $name . ' but does not have sufficient permissions', 3);
        }

        return false;
    }

    /**
     * update_level
     * This takes a preference ID and updates the level required to update it (performed by an admin)
     */
    public static function update_level(int|string $preference, int $level): bool
    {
        // First prepare
        $preference_id = (is_numeric($preference))
            ? $preference
            : self::id_from_name($preference);

        $sql = "UPDATE `preference` SET `level` = ? WHERE `id` = ?;";
        Dba::write($sql, [$level, $preference_id]);

        return true;
    }

    /**
     * update_all
     * This takes a preference id and a value and updates all users with the new info
     */
    public static function update_all(string $preference, int|string|null $value): bool
    {
        $ampacheSeven = true;
        if (!Dba::read('SELECT `name` FROM `user_preference` LIMIT 1;', [], true)) {
            $ampacheSeven = false;
            $preference   = self::id_from_name($preference);
        }

        $sql = ($ampacheSeven)
            ? "UPDATE `user_preference` SET `value` = ? WHERE `name` = ?"
            : "UPDATE `user_preference` SET `value` = ? WHERE `preference` = ?";
        Dba::write($sql, [$value, $preference]);

        parent::clear_cache();
        self::clear_from_session();

        return true;
    }

    /**
     * exists
     * This just checks to see if a preference currently exists
     */
    public static function exists(int|string $preference): int
    {
        // Don't assume it's the name
        if (!is_numeric($preference)) {
            $sql = "SELECT * FROM `preference` WHERE `name` = ?";
        } else {
            $sql = "SELECT * FROM `preference` WHERE `id` = ?";
        }

        $db_results = Dba::read($sql, [$preference]);

        return Dba::num_rows($db_results);
    }

    /**
     * has_access
     * This checks to see if the current user has access to modify this preference
     * as defined by the preference name
     */
    public static function has_access(string $preference): bool
    {
        // Nothing for those demo thugs
        if (AmpConfig::get('demo_mode')) {
            return false;
        }

        $sql        = "SELECT `level` FROM `preference` WHERE `name` = ?;";
        $db_results = Dba::read($sql, [$preference]);
        $data       = Dba::fetch_assoc($db_results);

        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::from((int) $data['level']));
    }

    /**
     * id_from_name
     * This takes a name and returns the id
     */
    public static function id_from_name(string $name): ?int
    {
        if (parent::is_cached('id_from_name', $name)) {
            return (int)(parent::get_from_cache('id_from_name', $name))[0];
        }

        $sql        = "SELECT `id` FROM `preference` WHERE `name` = ?";
        $db_results = Dba::read($sql, [$name]);
        $results    = Dba::fetch_assoc($db_results);
        if (array_key_exists('id', $results)) {
            parent::add_to_cache('id_from_name', $name, [$results['id']]);

            return (int)$results['id'];
        }

        return null;
    }

    /**
     * name_from_id
     * This returns the name from an id, it's the exact opposite
     * of the function above it, amazing!
     */
    public static function name_from_id(int|string $pref_id): ?string
    {
        $pref_id    = Dba::escape($pref_id);
        $sql        = "SELECT `name` FROM `preference` WHERE `id` = ?";
        $db_results = Dba::read($sql, [$pref_id]);
        $results    = Dba::fetch_assoc($db_results);
        if ($results === []) {
            return null;
        }

        return (string)$results['name'];
    }

    /**
     * get_categories
     * This returns an array of the names of the different possible sections
     * it ignores the 'internal' category
     * @return string[]
     */
    public static function get_categories(): array
    {
        $sql = "SELECT `preference`.`category` FROM `preference` GROUP BY `category` ORDER BY `category`";

        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['category'] != 'internal') {
                $results[] = $row['category'];
            }
        }

        return $results;
    }

    /**
     * get_special_values
     * This returns an array of the values for special preferences which are not kept in the database
     * @return array<int|string>|null
     */
    public static function get_special_values(string $name, User $user): ?array
    {
        switch ($name) {
            case 'upload_catalog':
                return $user->get_catalogs('music');
            case 'playlist_type':
                return [
                    'simple_m3u',
                    'pls',
                    'asx',
                    'ram',
                    'xspf',
                    'm3u'
                ];
            case 'lang':
                return array_keys(get_languages());
            case 'localplay_controller':
                return array_keys(LocalPlayTypeEnum::TYPE_MAPPING);
            case 'api_force_version':
                return [
                    0,
                    3,
                    4,
                    5,
                    6
                ];
            case 'ratingmatch_stars':
                return [
                    '0',
                    '1',
                    '2',
                    '3',
                    '4',
                    '5',
                ];
            case 'localplay_level':
            case 'upload_access_level':
                return [
                    '0',
                    '5',
                    '25',
                    '50',
                    '75',
                    '100',
                ];
            case 'webplayer_removeplayed':
                return [
                    '0',
                    '1',
                    '2',
                    '3',
                    '5',
                    '10',
                    '999',
                ];
            case 'transcode':
                return [
                    'never',
                    'default',
                    'always',
                ];
            case 'album_sort':
                return [
                    'default',
                    'year_asc',
                    'year_desc',
                    'name_asc',
                    'name_desc',
                ];
        }

        return null;
    }

    /**
     * get
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @param string $pref_name
     * @param int $user_id
     * @return list<array{
     *     id: int,
     *     name: string,
     *     level: int,
     *     description: string,
     *     value: mixed,
     *     type: string,
     *     category: string,
     *     subcategory: ?string
     * }>
     */
    public static function get(string $pref_name, int $user_id): array
    {
        $user_limit = ($user_id != -1) ? "AND `preference`.`category` != 'system'" : "";

        $sql = sprintf('SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`, `preference`.`type`, `preference`.`category`, `preference`.`subcategory`, `user_preference`.`value` FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` WHERE `preference`.`name` = ? AND `user_preference`.`user` = ? AND `preference`.`category` != \'internal\' %s ORDER BY `preference`.`subcategory`, `preference`.`description`', $user_limit);

        $db_results = Dba::read($sql, [$pref_name, $user_id]);
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['category'],
                'subcategory' => $row['subcategory'],
            ];
        }

        return $results;
    }

    /**
     * insert
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     */
    public static function insert(
        string $name,
        string $description,
        float|int|string $default,
        int $level,
        string $type,
        string $category,
        ?string $subcategory = null,
        bool $replace = false
    ): bool {
        if ($replace) {
            self::delete($name);
        }

        if (!$replace && self::exists($name)) {
            return true;
        }

        if ($subcategory !== null) {
            $subcategory = strtolower((string)$subcategory);
        }

        // Work around ampache 5 preference insert < Ampache\Module\System\Update\Migration\V6\Migration600051
        $sql = (Dba::read('SELECT `category` FROM `preference` LIMIT 1;', [], true))
            ? "INSERT INTO `preference` (`name`, `description`, `value`, `level`, `type`, `category`, `subcategory`) VALUES (?, ?, ?, ?, ?, ?, ?)"
            : "INSERT INTO `preference` (`name`, `description`, `value`, `level`, `type`, `catagory`, `subcatagory`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, [$name, $description, $default, (int)$level, $type, $category, $subcategory]);
        if (!$db_results) {
            return false;
        }

        $pref_id = Dba::insert_id();
        if (
            !$pref_id ||
            (int)$pref_id < 1
        ) {
            return false;
        }

        // Check for databases < Ampache\Module\System\Update\Migration\V7\Migration700020
        $ampacheSeven = true;
        if (!Dba::read('SELECT `name` FROM `user_preference` LIMIT 1;', [], true)) {
            $ampacheSeven = false;
        }

        if ($ampacheSeven) {
            $params = [$pref_id, $name, $default];
            $sql    = "INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) VALUES (-1, ?, ?, ?)";
        } else {
            $params = [$pref_id, $default];
            $sql    = "INSERT INTO `user_preference` (`user`, `preference`, `value`) VALUES (-1, ?, ?);";
        }

        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        if ($category !== "system") {
            $sql = ($ampacheSeven)
                ? "INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) SELECT `user`.`id`, ?, ?, ? FROM `user`;"
                : "INSERT INTO `user_preference` (`user`, `preference`, `value`) SELECT `user`.`id`, ?, ? FROM `user`;";
            $db_results = Dba::write($sql, $params);
            if (!$db_results) {
                return false;
            }
        }

        debug_event(self::class, 'Inserted preference: ' . $name, 3);

        // clear current user preferences
        self::clear_from_session();

        return true;
    }

    /**
     * delete
     * This deletes the specified preference, a name or an ID can be passed
     */
    public static function delete(int|string $preference): bool
    {
        if (self::exists($preference) === 0) {
            return true;
        }

        // First prepare
        if (!is_numeric($preference)) {
            $sql = "DELETE FROM `preference` WHERE `name` = ?";
        } else {
            $sql = "DELETE FROM `preference` WHERE `id` = ?";
        }

        if (Dba::write($sql, [$preference]) !== false) {
            self::clean_preferences();

            return true;
        }

        return false;
    }

    /**
     * rename
     * This renames a preference in the database
     */
    public static function rename(string $old, string $new): void
    {
        $sql = "UPDATE `preference` SET `name` = ? WHERE `name` = ?";
        Dba::write($sql, [$new, $old]);
    }

    /**
     * clean_preferences
     * This removes any garbage
     */
    public static function clean_preferences(): void
    {
        // First remove garbage
        $sql = "DELETE FROM `user_preference` USING `user_preference` LEFT JOIN `preference` ON `preference`.`id`=`user_preference`.`preference` WHERE `preference`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * fix_preferences
     * This takes the preferences, explodes what needs to
     * become an array and boolean everything
     * @param array<string, mixed> $results
     */
    public static function fix_preferences(array $results): array
    {
        $arrays = [
            'allow_zip_types',
            'art_order',
            'auth_methods',
            'getid3_tag_order',
            'metadata_order_video',
            'metadata_order',
            'registration_display_fields',
            'registration_mandatory_fields',
            'wanted_types',
        ];

        foreach ($arrays as $item) {
            $results[$item] = (array_key_exists($item, $results) && trim((string)$results[$item]))
                ? explode(',', (string) $results[$item])
                : [];
        }

        foreach ($results as $key => $data) {
            if (!is_array($data)) {
                if (strcasecmp((string)$data, "true") == "0") {
                    $results[$key] = 1;
                }

                if (strcasecmp((string)$data, "false") == "0") {
                    $results[$key] = 0;
                }
            }
        }

        return $results;
    }

    /**
     * set_defaults
     * Make sure the default prefs are set! (taken from the default DB file `resources/sql/ampache.sql`)
     */
    public static function set_defaults(): void
    {
        $sql = "SELECT `item` FROM (";
        foreach (self::SYSTEM_LIST as $preference) {
            $sql .= "SELECT '$preference' AS `item` UNION ALL ";
        }

        $sql = rtrim($sql, " UNION ALL ");
        $sql .= ") AS `items` LEFT JOIN `preference` ON `items`.`item` = `preference`.`name` WHERE `preference`.`name` IS NULL;";

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event(self::class, 'Insert preference: ' . $row['item'], 2);
            $pref_sql = "INSERT IGNORE INTO `preference` (`id`, `name`, `value`, `description`, `level`, `type`, `category`, `subcategory`) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
            switch ($row['item']) {
                case 'download':
                    Dba::write($pref_sql, [1, 'download', '1', 'Allow Downloads', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature']);
                    break;
                case 'popular_threshold':
                    Dba::write($pref_sql, [4, 'popular_threshold', '10', 'Popular Threshold', AccessLevelEnum::USER->value, 'integer', 'interface', 'query']);
                    break;
                case 'transcode_bitrate':
                    Dba::write($pref_sql, [19, 'transcode_bitrate', '128', 'Transcode Bitrate', AccessLevelEnum::USER->value, 'string', 'streaming', 'transcoding']);
                    break;
                case 'site_title':
                    Dba::write($pref_sql, [22, 'site_title', 'Ampache :: For the Love of Music', 'Website Title', AccessLevelEnum::ADMIN->value, 'string', 'interface', 'custom']);
                    break;
                case 'lock_songs':
                    Dba::write($pref_sql, [23, 'lock_songs', '0', 'Lock Songs', AccessLevelEnum::ADMIN->value, 'boolean', 'system', null]);
                    break;
                case 'force_http_play':
                    Dba::write($pref_sql, [24, 'force_http_play', '0', 'Force HTTP playback regardless of port', AccessLevelEnum::ADMIN->value, 'boolean', 'system', null]);
                    break;
                case 'play_type':
                    Dba::write($pref_sql, [29, 'play_type', 'web_player', 'Playback Type', AccessLevelEnum::USER->value, 'special', 'streaming', null]);
                    break;
                case 'lang':
                    Dba::write($pref_sql, [31, 'lang', 'en_US', 'Language', AccessLevelEnum::ADMIN->value, 'special', 'interface', null]);
                    break;
                case 'playlist_type':
                    Dba::write($pref_sql, [32, 'playlist_type', 'm3u', 'Playlist Type', AccessLevelEnum::ADMIN->value, 'special', 'playlist', null]);
                    break;
                case 'theme_name':
                    Dba::write($pref_sql, [33, 'theme_name', 'reborn', 'Theme', AccessLevelEnum::DEFAULT->value, 'special', 'interface', 'theme']);
                    break;
                case 'localplay_level':
                    Dba::write($pref_sql, [40, 'localplay_level', '0', 'Localplay Access', AccessLevelEnum::ADMIN->value, 'special', 'options', 'localplay']);
                    break;
                case 'localplay_controller':
                    Dba::write($pref_sql, [41, 'localplay_controller', '0', 'Localplay Type', AccessLevelEnum::ADMIN->value, 'special', 'options', 'localplay']);
                    break;
                case 'allow_stream_playback':
                    Dba::write($pref_sql, [44, 'allow_stream_playback', '1', 'Allow Streaming', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature']);
                    break;
                case 'allow_democratic_playback':
                    Dba::write($pref_sql, [45, 'allow_democratic_playback', '0', 'Allow Democratic Play', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature']);
                    break;
                case 'allow_localplay_playback':
                    Dba::write($pref_sql, [46, 'allow_localplay_playback', '0', 'Allow Localplay Play', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'localplay']);
                    break;
                case 'stats_threshold':
                    Dba::write($pref_sql, [47, 'stats_threshold', '7', 'Statistics Day Threshold', AccessLevelEnum::USER->value, 'integer', 'interface', 'query']);
                    break;
                case 'offset_limit':
                    Dba::write($pref_sql, [51, 'offset_limit', '50', 'Offset Limit', AccessLevelEnum::DEFAULT->value, 'integer', 'interface', 'query']);
                    break;
                case 'rate_limit':
                    Dba::write($pref_sql, [52, 'rate_limit', '8192', 'Rate Limit', AccessLevelEnum::ADMIN->value, 'integer', 'streaming', 'transcoding']);
                    break;
                case 'playlist_method':
                    Dba::write($pref_sql, [53, 'playlist_method', 'default', 'Playlist Method', AccessLevelEnum::DEFAULT->value, 'string', 'playlist', null]);
                    break;
                case 'transcode':
                    Dba::write($pref_sql, [55, 'transcode', 'default', 'Allow Transcoding', AccessLevelEnum::USER->value, 'string', 'streaming', 'transcoding']);
                    break;
                case 'show_lyrics':
                    Dba::write($pref_sql, [69, 'show_lyrics', '0', 'Show lyrics', AccessLevelEnum::DEFAULT->value, 'boolean', 'interface', 'player']);
                    break;
                case 'lastfm_grant_link':
                    Dba::write($pref_sql, [77, 'lastfm_grant_link', '', 'Last.FM Grant URL', AccessLevelEnum::USER->value, 'string', 'internal', 'lastfm']);
                    break;
                case 'lastfm_challenge':
                    Dba::write($pref_sql, [78, 'lastfm_challenge', '', 'Last.FM Submit Challenge', AccessLevelEnum::USER->value, 'string', 'internal', 'lastfm']);
                    break;
                case 'now_playing_per_user':
                    Dba::write($pref_sql, [82, 'now_playing_per_user', '1', 'Now Playing filtered per user', AccessLevelEnum::CONTENT_MANAGER->value, 'boolean', 'interface', 'home']);
                    break;
                case 'album_sort':
                    Dba::write($pref_sql, [83, 'album_sort', 'default', 'Album - Default sort', AccessLevelEnum::USER->value, 'string', 'interface', 'library']);
                    break;
                case 'show_played_times':
                    Dba::write($pref_sql, [84, 'show_played_times', '0', 'Show # played', AccessLevelEnum::USER->value, 'string', 'interface', 'browse']);
                    break;
                case 'song_page_title':
                    Dba::write($pref_sql, [85, 'song_page_title', '1', 'Show current song in Web player page title', AccessLevelEnum::USER->value, 'boolean', 'interface', 'player']);
                    break;
                case 'subsonic_backend':
                    Dba::write($pref_sql, [86, 'subsonic_backend', '1', 'Use Subsonic backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend']);
                    break;
                case 'webplayer_flash':
                    Dba::write($pref_sql, [88, 'webplayer_flash', '1', 'Authorize Flash Web Player', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player']);
                    break;
                case 'webplayer_html5':
                    Dba::write($pref_sql, [89, 'webplayer_html5', '1', 'Authorize HTML5 Web Player', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player']);
                    break;
                case 'allow_personal_info_now':
                    Dba::write($pref_sql, [90, 'allow_personal_info_now', '1', 'Share Now Playing information', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy']);
                    break;
                case 'allow_personal_info_recent':
                    Dba::write($pref_sql, [91, 'allow_personal_info_recent', '1', 'Share Recently Played information', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy']);
                    break;
                case 'allow_personal_info_time':
                    Dba::write($pref_sql, [92, 'allow_personal_info_time', '1', 'Share Recently Played information - Allow access to streaming date/time', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy']);
                    break;
                case 'allow_personal_info_agent':
                    Dba::write($pref_sql, [93, 'allow_personal_info_agent', '1', 'Share Recently Played information - Allow access to streaming agent', AccessLevelEnum::USER->value, 'boolean', 'interface', 'privacy']);
                    break;
                case 'ui_fixed':
                    Dba::write($pref_sql, [94, 'ui_fixed', '0', 'Fix header position on compatible themes', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme']);
                    break;
                case 'autoupdate':
                    Dba::write($pref_sql, [95, 'autoupdate', '1', 'Check for Ampache updates automatically', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'update']);
                    break;
                case 'autoupdate_lastcheck':
                    Dba::write($pref_sql, [96, 'autoupdate_lastcheck', '', 'AutoUpdate last check time', AccessLevelEnum::USER->value, 'string', 'internal', 'update']);
                    break;
                case 'autoupdate_lastversion':
                    Dba::write($pref_sql, [97, 'autoupdate_lastversion', '', 'AutoUpdate last version from last check', AccessLevelEnum::USER->value, 'string', 'internal', 'update']);
                    break;
                case 'autoupdate_lastversion_new':
                    Dba::write($pref_sql, [98, 'autoupdate_lastversion_new', '', 'AutoUpdate last version from last check is newer', AccessLevelEnum::USER->value, 'boolean', 'internal', 'update']);
                    break;
                case 'webplayer_confirmclose':
                    Dba::write($pref_sql, [99, 'webplayer_confirmclose', '0', 'Confirmation when closing current playing window', AccessLevelEnum::USER->value, 'boolean', 'interface', 'player']);
                    break;
                case 'webplayer_pausetabs':
                    Dba::write($pref_sql, [100, 'webplayer_pausetabs', '1', 'Auto-pause between tabs', AccessLevelEnum::USER->value, 'boolean', 'interface', 'player']);
                    break;
                case 'stream_beautiful_url':
                    Dba::write($pref_sql, [101, 'stream_beautiful_url', '0', 'Enable URL Rewriting', AccessLevelEnum::ADMIN->value, 'boolean', 'streaming', null]);
                    break;
                case 'share':
                    Dba::write($pref_sql, [102, 'share', '0', 'Allow Share', AccessLevelEnum::ADMIN->value, 'boolean', 'options', 'feature']);
                    break;
                case 'share_expire':
                    Dba::write($pref_sql, [103, 'share_expire', '7', 'Share links default expiration days (0=never)', AccessLevelEnum::ADMIN->value, 'integer', 'system', 'share']);
                    break;
                case 'slideshow_time':
                    Dba::write($pref_sql, [104, 'slideshow_time', '0', 'Artist slideshow inactivity time', AccessLevelEnum::USER->value, 'integer', 'interface', 'player']);
                    break;
                case 'broadcast_by_default':
                    Dba::write($pref_sql, [105, 'broadcast_by_default', '0', 'Broadcast web player by default', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player']);
                    break;
                case 'album_group':
                    Dba::write($pref_sql, [108, 'album_group', '1', 'Album - Group multiple disks', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'topmenu':
                    Dba::write($pref_sql, [109, 'topmenu', '0', 'Top menu', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme']);
                    break;
                case 'demo_clear_sessions':
                    Dba::write($pref_sql, [110, 'demo_clear_sessions', '0', 'Democratic - Clear votes for expired user sessions', AccessLevelEnum::USER->value, 'boolean', 'playlist', null]);
                    break;
                case 'show_donate':
                    Dba::write($pref_sql, [111, 'show_donate', '1', 'Show donate button in footer', AccessLevelEnum::USER->value, 'boolean', 'interface', null]);
                    break;
                case 'upload_catalog':
                    Dba::write($pref_sql, [112, 'upload_catalog', '-1', 'Destination catalog', AccessLevelEnum::ADMIN->value, 'integer', 'options', 'upload']);
                    break;
                case 'allow_upload':
                    Dba::write($pref_sql, [113, 'allow_upload', '0', 'Allow user uploads', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload']);
                    break;
                case 'upload_subdir':
                    Dba::write($pref_sql, [114, 'upload_subdir', '1', 'Create a subdirectory per user', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload']);
                    break;
                case 'upload_user_artist':
                    Dba::write($pref_sql, [115, 'upload_user_artist', '0', 'Consider the user sender as the track\'s artist', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload']);
                    break;
                case 'upload_script':
                    Dba::write($pref_sql, [116, 'upload_script', '', 'Post-upload script (current directory = upload target directory)', AccessLevelEnum::ADMIN->value, 'string', 'system', 'upload']);
                    break;
                case 'upload_allow_edit':
                    Dba::write($pref_sql, [117, 'upload_allow_edit', '1', 'Allow users to edit uploaded songs', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload']);
                    break;
                case 'daap_backend':
                    Dba::write($pref_sql, [118, 'daap_backend', '0', 'Use DAAP backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend']);
                    break;
                case 'daap_pass':
                    Dba::write($pref_sql, [119, 'daap_pass', '', 'DAAP backend password', AccessLevelEnum::ADMIN->value, 'string', 'system', 'backend']);
                    break;
                case 'upnp_backend':
                    Dba::write($pref_sql, [120, 'upnp_backend', '0', 'Use UPnP backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend']);
                    break;
                case 'allow_video':
                    Dba::write($pref_sql, [121, 'allow_video', '0', 'Allow Video Features', AccessLevelEnum::MANAGER->value, 'integer', 'options', 'feature']);
                    break;
                case 'album_release_type':
                    Dba::write($pref_sql, [122, 'album_release_type', '1', 'Album - Group per release type', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'ajax_load':
                    Dba::write($pref_sql, [123, 'ajax_load', '1', 'Ajax page load', AccessLevelEnum::USER->value, 'boolean', 'interface', null]);
                    break;
                case 'direct_play_limit':
                    Dba::write($pref_sql, [124, 'direct_play_limit', '0', 'Limit direct play to maximum media count', AccessLevelEnum::USER->value, 'integer', 'interface', 'player']);
                    break;
                case 'home_moment_albums':
                    Dba::write($pref_sql, [125, 'home_moment_albums', '1', 'Show Albums of the Moment', AccessLevelEnum::USER->value, 'integer', 'interface', 'home']);
                    break;
                case 'home_moment_videos':
                    Dba::write($pref_sql, [126, 'home_moment_videos', '0', 'Show Videos of the Moment', AccessLevelEnum::USER->value, 'integer', 'interface', 'home']);
                    break;
                case 'home_recently_played':
                    Dba::write($pref_sql, [127, 'home_recently_played', '1', 'Show Recently Played', AccessLevelEnum::USER->value, 'integer', 'interface', 'home']);
                    break;
                case 'home_now_playing':
                    Dba::write($pref_sql, [128, 'home_now_playing', '1', 'Show Now Playing', AccessLevelEnum::USER->value, 'integer', 'interface', 'home']);
                    break;
                case 'custom_logo':
                    Dba::write($pref_sql, [129, 'custom_logo', '', 'Custom URL - Logo', AccessLevelEnum::USER->value, 'string', 'interface', 'custom']);
                    break;
                case 'album_release_type_sort':
                    Dba::write($pref_sql, [130, 'album_release_type_sort', 'album,ep,live,single', 'Album - Group per release type sort', AccessLevelEnum::USER->value, 'string', 'interface', 'library']);
                    break;
                case 'browser_notify':
                    Dba::write($pref_sql, [131, 'browser_notify', '1', 'Web Player browser notifications', AccessLevelEnum::USER->value, 'integer', 'interface', 'notification']);
                    break;
                case 'browser_notify_timeout':
                    Dba::write($pref_sql, [132, 'browser_notify_timeout', '10', 'Web Player browser notifications timeout (seconds)', AccessLevelEnum::USER->value, 'integer', 'interface', 'notification']);
                    break;
                case 'geolocation':
                    Dba::write($pref_sql, [133, 'geolocation', '0', 'Allow Geolocation', AccessLevelEnum::USER->value, 'integer', 'options', 'feature']);
                    break;
                case 'webplayer_aurora':
                    Dba::write($pref_sql, [134, 'webplayer_aurora', '1', 'Authorize JavaScript decoder (Aurora.js) in Web Player', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player']);
                    break;
                case 'upload_allow_remove':
                    Dba::write($pref_sql, [135, 'upload_allow_remove', '1', 'Allow users to remove uploaded songs', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload']);
                    break;
                case 'custom_login_logo':
                    Dba::write($pref_sql, [136, 'custom_login_logo', '', 'Custom URL - Login page logo', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface']);
                    break;
                case 'custom_favicon':
                    Dba::write($pref_sql, [137, 'custom_favicon', '', 'Custom URL - Favicon', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface']);
                    break;
                case 'custom_text_footer':
                    Dba::write($pref_sql, [138, 'custom_text_footer', '', 'Custom text footer', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface']);
                    break;
                case 'webdav_backend':
                    Dba::write($pref_sql, [139, 'webdav_backend', '0', 'Use WebDAV backend', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend']);
                    break;
                case 'notify_email':
                    Dba::write($pref_sql, [140, 'notify_email', '0', 'Allow E-mail notifications', AccessLevelEnum::USER->value, 'boolean', 'options', null]);
                    break;
                case 'theme_color':
                    Dba::write($pref_sql, [141, 'theme_color', 'dark', 'Theme color', AccessLevelEnum::DEFAULT->value, 'special', 'interface', 'theme']);
                    break;
                case 'disabled_custom_metadata_fields':
                    Dba::write($pref_sql, [142, 'disabled_custom_metadata_fields', '', 'Custom metadata - Disable these fields', AccessLevelEnum::ADMIN->value, 'string', 'system', 'metadata']);
                    break;
                case 'disabled_custom_metadata_fields_input':
                    Dba::write($pref_sql, [143, 'disabled_custom_metadata_fields_input', '', 'Custom metadata - Define field list', AccessLevelEnum::ADMIN->value, 'string', 'system', 'metadata']);
                    break;
                case 'podcast_keep':
                    Dba::write($pref_sql, [144, 'podcast_keep', '0', '# latest episodes to keep', AccessLevelEnum::ADMIN->value, 'integer', 'system', 'podcast']);
                    break;
                case 'podcast_new_download':
                    Dba::write($pref_sql, [145, 'podcast_new_download', '0', '# episodes to download when new episodes are available', AccessLevelEnum::ADMIN->value, 'integer', 'system', 'podcast']);
                    break;
                case 'libitem_contextmenu':
                    Dba::write($pref_sql, [146, 'libitem_contextmenu', '1', 'Library item context menu', AccessLevelEnum::DEFAULT->value, 'boolean', 'interface', 'library']);
                    break;
                case 'upload_catalog_pattern':
                    Dba::write($pref_sql, [147, 'upload_catalog_pattern', '0', 'Rename uploaded file according to catalog pattern', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'upload']);
                    break;
                case 'catalog_check_duplicate':
                    Dba::write($pref_sql, [148, 'catalog_check_duplicate', '0', 'Check library item at import time and disable duplicates', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'catalog']);
                    break;
                case 'browse_filter':
                    Dba::write($pref_sql, [149, 'browse_filter', '0', 'Show filter box on browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'sidebar_light':
                    Dba::write($pref_sql, [150, 'sidebar_light', '0', 'Light sidebar by default', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme']);
                    break;
                case 'custom_blankalbum':
                    Dba::write($pref_sql, [151, 'custom_blankalbum', '', 'Custom blank album default image', AccessLevelEnum::MANAGER->value, 'string', 'interface', 'custom']);
                    break;
                case 'libitem_browse_alpha':
                    Dba::write($pref_sql, [153, 'libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)', AccessLevelEnum::MANAGER->value, 'string', 'interface', 'browse']);
                    break;
                case 'show_skipped_times':
                    Dba::write($pref_sql, [154, 'show_skipped_times', '0', 'Show # skipped', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'custom_datetime':
                    Dba::write($pref_sql, [155, 'custom_datetime', '', 'Custom datetime', AccessLevelEnum::USER->value, 'string', 'interface', 'custom']);
                    break;
                case 'cron_cache':
                    Dba::write($pref_sql, [156, 'cron_cache', '0', 'Cache computed SQL data (eg. media hits stats) using a cron', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'catalog']);
                    break;
                case 'unique_playlist':
                    Dba::write($pref_sql, [157, 'unique_playlist', '0', 'Only add unique items to playlists', AccessLevelEnum::USER->value, 'boolean', 'playlist', null]);
                    break;
                case 'of_the_moment':
                    Dba::write($pref_sql, [158, 'of_the_moment', '6', 'Set the amount of items Album/Video of the Moment will display', AccessLevelEnum::USER->value, 'integer', 'interface', 'home']);
                    break;
                case 'custom_login_background':
                    Dba::write($pref_sql, [159, 'custom_login_background', '', 'Custom URL - Login page background', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface']);
                    break;
                case 'show_license':
                    Dba::write($pref_sql, [160, 'show_license', '1', 'Show License', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'use_original_year':
                    Dba::write($pref_sql, [161, 'use_original_year', '0', 'Browse by Original Year for albums (falls back to Year)', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'hide_single_artist':
                    Dba::write($pref_sql, [162, 'hide_single_artist', '0', 'Hide the Song Artist column for Albums with one Artist', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'hide_genres':
                    Dba::write($pref_sql, [163, 'hide_genres', '0', 'Hide the Genre column in browse table rows', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'subsonic_always_download':
                    Dba::write($pref_sql, [164, 'subsonic_always_download', '0', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', AccessLevelEnum::USER->value, 'boolean', 'options', 'subsonic']);
                    break;
                case 'api_enable_3':
                    Dba::write($pref_sql, [165, 'api_enable_3', '1', 'Allow Ampache API3 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'ampache']);
                    break;
                case 'api_enable_4':
                    Dba::write($pref_sql, [166, 'api_enable_4', '1', 'Allow Ampache API3 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'ampache']);
                    break;
                case 'api_enable_5':
                    Dba::write($pref_sql, [167, 'api_enable_5', '1', 'Allow Ampache API3 responses', AccessLevelEnum::USER->value, 'boolean', 'options', 'ampache']);
                    break;
                case 'api_force_version':
                    Dba::write($pref_sql, [168, 'api_force_version', '0', 'Force a specific API response no matter what version you send', AccessLevelEnum::USER->value, 'special', 'options', 'ampache']);
                    break;
                case 'show_playlist_username':
                    Dba::write($pref_sql, [169, 'show_playlist_username', '1', 'Show playlist owner username in titles', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'api_hidden_playlists':
                    Dba::write($pref_sql, [170, 'api_hidden_playlists', '', 'Hide playlists in Subsonic and API clients that start with this string', AccessLevelEnum::USER->value, 'string', 'options', null]);
                    break;
                case 'api_hide_dupe_searches':
                    Dba::write($pref_sql, [171, 'api_hide_dupe_searches', '0', 'Hide smartlists that match playlist names in Subsonic and API clients', AccessLevelEnum::USER->value, 'boolean', 'options', null]);
                    break;
                case 'show_album_artist':
                    Dba::write($pref_sql, [172, 'show_album_artist', '1', 'Show \'Album Artists\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme']);
                    break;
                case 'show_artist':
                    Dba::write($pref_sql, [173, 'show_artist', '0', 'Show \'Artists\' link in the main sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme']);
                    break;
                case 'demo_use_search':
                    Dba::write($pref_sql, [175, 'demo_use_search', '0', 'Democratic - Use smartlists for base playlist', AccessLevelEnum::ADMIN->value, 'boolean', 'system', null]);
                    break;
                case 'webplayer_removeplayed':
                    Dba::write($pref_sql, [176, 'webplayer_removeplayed', '0', 'Remove tracks before the current playlist item in the webplayer when played', AccessLevelEnum::USER->value, 'special', 'streaming', 'player']);
                    break;
                case 'api_enable_6':
                    Dba::write($pref_sql, [177, 'api_enable_6', '1', 'Allow Ampache API6 responses', AccessLevelEnum::USER->value, 'boolean', 'options', null]);
                    break;
                case 'upload_access_level':
                    Dba::write($pref_sql, [178, 'upload_access_level', '25', 'Upload Access Level', AccessLevelEnum::ADMIN->value, 'special', 'system', 'upload']);
                    break;
                case 'show_subtitle':
                    Dba::write($pref_sql, [179, 'show_subtitle', '1', 'Show Album subtitle on links (if available)', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'show_original_year':
                    Dba::write($pref_sql, [180, 'show_original_year', '1', 'Show Album original year on links (if available)', AccessLevelEnum::USER->value, 'boolean', 'interface', 'browse']);
                    break;
                case 'show_header_login':
                    Dba::write($pref_sql, [181, 'show_header_login', '1', 'Show the login / registration links in the site header', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'interface']);
                    break;
                case 'use_play2':
                    Dba::write($pref_sql, [182, 'use_play2', '0', 'Use an alternative playback action for streaming if you have issues with playing music', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player']);
                    break;
                case 'custom_timezone':
                    Dba::write($pref_sql, [183, 'custom_timezone', '', 'Custom timezone (Override PHP date.timezone)', AccessLevelEnum::USER->value, 'string', 'interface', 'custom']);
                    break;
                case 'bookmark_latest':
                    Dba::write($pref_sql, [184, 'bookmark_latest', '0', 'Only keep the latest media bookmark', AccessLevelEnum::USER->value, 'boolean', 'options', null]);
                    break;
                case 'jp_volume':
                    Dba::write($pref_sql, [185, 'jp_volume', '0.8', 'Default webplayer volume', AccessLevelEnum::USER->value, 'special', 'streaming', 'player']);
                    break;
                case 'perpetual_api_session':
                    Dba::write($pref_sql, [186, 'perpetual_api_session', '0', 'API sessions do not expire', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'backend']);
                    break;
                case 'home_recently_played_all':
                    Dba::write($pref_sql, [187, 'home_recently_played_all', '1', 'Show all media types in Recently Played', AccessLevelEnum::USER->value, 'bool', 'interface', 'home']);
                    break;
                case 'show_wrapped':
                    Dba::write($pref_sql, [188, 'show_wrapped', '1', 'Enable access to your personal "Spotify Wrapped" from your user page', AccessLevelEnum::USER->value, 'bool', 'interface', 'privacy']);
                    break;
                case 'sidebar_hide_switcher':
                    Dba::write($pref_sql, [189, 'sidebar_hide_switcher', '0', 'Hide sidebar switcher arrows', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'sidebar_hide_browse':
                    Dba::write($pref_sql, [190, 'sidebar_hide_browse', '0', 'Hide the Browse menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'sidebar_hide_dashboard':
                    Dba::write($pref_sql, [191, 'sidebar_hide_dashboard', '0', 'Hide the Dashboard menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'sidebar_hide_video':
                    Dba::write($pref_sql, [192, 'sidebar_hide_video', '0', 'Hide the Video menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'sidebar_hide_search':
                    Dba::write($pref_sql, [193, 'sidebar_hide_search', '0', 'Hide the Search menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'sidebar_hide_playlist':
                    Dba::write($pref_sql, [194, 'sidebar_hide_playlist', '0', 'Hide the Playlist menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'sidebar_hide_information':
                    Dba::write($pref_sql, [195, 'sidebar_hide_information', '0', 'Hide the Information menu in the sidebar', AccessLevelEnum::USER->value, 'boolean', 'interface', 'sidebar']);
                    break;
                case 'custom_logo_user':
                    Dba::write($pref_sql, [197, 'custom_logo_user', '0', 'Custom URL - Use your avatar for header logo', AccessLevelEnum::USER->value, 'boolean', 'interface', 'custom']);
                    break;
                case 'index_dashboard_form':
                    Dba::write($pref_sql, [198, 'index_dashboard_form', '0', 'Use Dashboard links for the index page header', AccessLevelEnum::USER->value, 'boolean', 'interface', 'home']);
                    break;
                case 'sidebar_order_browse':
                    Dba::write($pref_sql, [199, 'sidebar_order_browse', '10', 'Custom CSS Order - Browse', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar']);
                    break;
                case 'sidebar_order_dashboard':
                    Dba::write($pref_sql, [200, 'sidebar_order_dashboard', '15', 'Custom CSS Order - Dashboard', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar']);
                    break;
                case 'sidebar_order_video':
                    Dba::write($pref_sql, [201, 'sidebar_order_video', '20', 'Custom CSS Order - Video', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar']);
                    break;
                case 'sidebar_order_playlist':
                    Dba::write($pref_sql, [202, 'sidebar_order_playlist', '30', 'Custom CSS Order - Playlist', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar']);
                    break;
                case 'sidebar_order_search':
                    Dba::write($pref_sql, [203, 'sidebar_order_search', '40', 'Custom CSS Order - Search', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar']);
                    break;
                case 'sidebar_order_information':
                    Dba::write($pref_sql, [204, 'sidebar_order_information', '60', 'Custom CSS Order - Information', AccessLevelEnum::USER->value, 'integer', 'interface', 'sidebar']);
                    break;
                case 'api_always_download':
                    Dba::write($pref_sql, [189, 'api_always_download', 'Force API streams to download. (Enable scrobble in your client to record stats)', '0', AccessLevelEnum::USER->value, 'boolean', 'options', 'api']);
                    break;
                case 'external_links_google':
                    Dba::write($pref_sql, [206, 'external_links_google', '1', 'Show Google search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'external_links_duckduckgo':
                    Dba::write($pref_sql, [207, 'external_links_duckduckgo', '1', 'Show DuckDuckGo search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'external_links_wikipedia':
                    Dba::write($pref_sql, [208, 'external_links_wikipedia', '1', 'Show Wikipedia search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'external_links_lastfm':
                    Dba::write($pref_sql, [209, 'external_links_lastfm', '1', 'Show Last.fm search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'external_links_bandcamp':
                    Dba::write($pref_sql, [210, 'external_links_bandcamp', '1', 'Show Bandcamp search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'external_links_musicbrainz':
                    Dba::write($pref_sql, [211, 'external_links_musicbrainz', '1', 'Show MusicBrainz search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'extended_playlist_links':
                    Dba::write($pref_sql, [219, 'extended_playlist_links', '0', 'Show extended links for playlist media', AccessLevelEnum::USER->value, 'boolean', 'playlist']);
                    break;
                case 'external_links_discogs':
                    Dba::write($pref_sql, [220, 'external_links_discogs', '1', 'Show Discogs search icon on library items', AccessLevelEnum::USER->value, 'boolean', 'interface', 'library']);
                    break;
                case 'browse_song_grid_view':
                    Dba::write($pref_sql, [221, 'browse_song_grid_view', '0', 'Force Grid View on Song browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_album_grid_view':
                    Dba::write($pref_sql, [222, 'browse_album_grid_view', '0', 'Force Grid View on Album browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_album_disk_grid_view':
                    Dba::write($pref_sql, [223, 'browse_album_disk_grid_view', '0', 'Force Grid View on Album Disk browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_artist_grid_view':
                    Dba::write($pref_sql, [224, 'browse_artist_grid_view', '0', 'Force Grid View on Artist browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_live_stream_grid_view':
                    Dba::write($pref_sql, [225, 'browse_live_stream_grid_view', '0', 'Force Grid View on Radio Station browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_playlist_grid_view':
                    Dba::write($pref_sql, [226, 'browse_playlist_grid_view', '0', 'Force Grid View on Playlist browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_video_grid_view':
                    Dba::write($pref_sql, [227, 'browse_video_grid_view', '0', 'Force Grid View on Video browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_podcast_grid_view':
                    Dba::write($pref_sql, [228, 'browse_podcast_grid_view', '0', 'Force Grid View on Podcast browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'browse_podcast_episode_grid_view':
                    Dba::write($pref_sql, [229, 'browse_podcast_episode_grid_view', '0', 'Force Grid View on Podcast Episode browse', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies']);
                    break;
                case 'show_playlist_media_parent':
                    Dba::write($pref_sql, [230, 'show_playlist_media_parent', '0', 'Show Artist column on playlist media rows', AccessLevelEnum::USER->value, 'boolean', 'playlist', null]);
                    break;
                case 'subsonic_legacy':
                    Dba::write($pref_sql, [231, 'subsonic_legacy', '1', 'Enable legacy Subsonic API responses for compatibility issues', AccessLevelEnum::USER->value, 'boolean', 'options', 'api']);
                    break;
                default:
                    debug_event(self::class, 'ERROR: missing preference insert code for: ' . $row['item'], 1);
            }
        }

        // Ensure valid prefs are set
        User::rebuild_all_preferences();
    }

    /**
     * translate_db
     * Make sure the default prefs are readable by the users
     */
    public static function translate_db(): void
    {
        $sql        = "UPDATE `preference` SET `preference`.`description` = ? WHERE `preference`.`name` = ? AND `preference`.`description` != ?;";
        $pref_array = [
            '7digital_api_key' => '7digital consumer key',
            '7digital_secret_api_key' => '7digital secret',
            'ajax_load' => 'Ajax page load',
            'album_group' => 'Album - Group multiple disks',
            'album_release_type_sort' => 'Album - Group per release type sort',
            'album_release_type' => 'Album - Group per release type',
            'album_sort' => 'Album - Default sort',
            'allow_democratic_playback' => 'Allow Democratic Play',
            'allow_localplay_playback' => 'Allow Localplay Play',
            'allow_personal_info_agent' => 'Share Recently Played information - Allow access to streaming agent',
            'allow_personal_info_now' => 'Share Now Playing information',
            'allow_personal_info_recent' => 'Share Recently Played information',
            'allow_personal_info_time' => 'Share Recently Played information - Allow access to streaming date/time',
            'allow_stream_playback' => 'Allow Streaming',
            'allow_upload' => 'Allow user uploads',
            'allow_video' => 'Allow Video Features',
            'amazon_base_url' => 'Amazon base url',
            'amazon_developer_associate_tag' => 'Amazon associate tag',
            'amazon_developer_private_api_key' => 'Amazon Secret Access Key',
            'amazon_developer_public_key' => 'Amazon Access Key ID',
            'amazon_max_results_pages' => 'Amazon max results pages',
            'api_always_download' => 'Force API streams to download. (Enable scrobble in your client to record stats)',
            'api_enable_3' => 'Allow Ampache API3 responses',
            'api_enable_4' => 'Allow Ampache API4 responses',
            'api_enable_5' => 'Allow Ampache API5 responses',
            'api_enable_6' => 'Allow Ampache API6 responses',
            'api_force_version' => 'Force a specific API response no matter what version you send',
            'api_hidden_playlists' => 'Hide playlists in Subsonic and API clients that start with this string',
            'api_hide_dupe_searches' => 'Hide smartlists that match playlist names in Subsonic and API clients',
            'autoupdate_lastcheck' => 'AutoUpdate last check time',
            'autoupdate_lastversion_new' => 'AutoUpdate last version from last check is newer',
            'autoupdate_lastversion' => 'AutoUpdate last version from last check',
            'autoupdate' => 'Check for Ampache updates automatically',
            'bitly_api_key' => 'Bit.ly API key',
            'bitly_username' => 'Bit.ly Username',
            'bookmark_latest' => 'Only keep the latest media bookmark',
            'broadcast_by_default' => 'Broadcast web player by default',
            'browse_album_disk_grid_view' => 'Force Grid View on Album Disk browse',
            'browse_album_grid_view' => 'Force Grid View on Album browse',
            'browse_artist_grid_view' => 'Force Grid View on Artist browse',
            'browse_filter' => 'Show filter box on browse',
            'browse_live_stream_grid_view' => 'Force Grid View on Radio Station browse',
            'browse_playlist_grid_view' => 'Force Grid View on Playlist browse',
            'browse_podcast_episode_grid_view' => 'Force Grid View on Podcast Episode browse',
            'browse_podcast_grid_view' => 'Force Grid View on Podcast browse',
            'browse_song_grid_view' => 'Force Grid View on Song browse',
            'browse_video_grid_view' => 'Force Grid View on Video browse',
            'browser_notify_timeout' => 'Web Player browser notifications timeout (seconds)',
            'browser_notify' => 'Web Player browser notifications',
            'catalog_check_duplicate' => 'Check library item at import time and disable duplicates',
            'catalogfav_gridview' => 'Catalog favorites grid view display',
            'catalogfav_max_items' => 'Catalog favorites max items',
            'catalogfav_compact' => 'Catalog favorites media row display',
            'catalogfav_order' => 'Plugin CSS order',
            'cron_cache' => 'Cache computed SQL data (eg. media hits stats) using a cron',
            'custom_blankalbum' => 'Custom blank album default image',
            'custom_datetime' => 'Custom datetime',
            'custom_favicon' => 'Custom URL - Favicon',
            'custom_login_background' => 'Custom URL - Login page background',
            'custom_login_logo' => 'Custom URL - Login page logo',
            'custom_logo' => 'Custom URL - Logo',
            'custom_logo_user' => 'Custom URL - Use your avatar for header logo',
            'custom_text_footer' => 'Custom text footer',
            'custom_timezone' => 'Custom timezone (Override PHP date.timezone)',
            'daap_backend' => 'Use DAAP backend',
            'daap_pass' => 'DAAP backend password',
            'demo_clear_sessions' => 'Democratic - Clear votes for expired user sessions',
            'demo_use_search' => 'Democratic - Use smartlists for base playlist',
            'direct_play_limit' => 'Limit direct play to maximum media count',
            'disabled_custom_metadata_fields_input' => 'Custom metadata - Define field list',
            'disabled_custom_metadata_fields' => 'Custom metadata - Disable these fields',
            'discogs_api_key' => 'Discogs consumer key',
            'discogs_secret_api_key' => 'Discogs secret',
            'download' => 'Allow Downloads',
            'extended_playlist_links' => 'Show extended links for playlist media',
            'external_links_google' => 'Show Google search icon on library items',
            'external_links_discogs' => 'Show Discogs search icon on library items',
            'external_links_duckduckgo' => 'Show DuckDuckGo search icon on library items',
            'external_links_wikipedia' => 'Show Wikipedia search icon on library items',
            'external_links_lastfm' => 'Show Last.fm search icon on library items',
            'external_links_bandcamp' => 'Show Bandcamp search icon on library items',
            'external_links_musicbrainz' => 'Show MusicBrainz search icon on library items',
            'flickr_api_key' => 'Flickr API key',
            'force_http_play' => 'Force HTTP playback regardless of port',
            'ftl_max_items' => 'Friends timeline max items',
            'ftl_order' => 'Plugin CSS order',
            'geolocation' => 'Allow Geolocation',
            'gmaps_api_key' => 'Google Maps API key',
            'googleanalytics_tracking_id' => 'Google Analytics Tracking ID',
            'headphones_api_key' => 'Headphones API key',
            'headphones_api_url' => 'Headphones URL',
            'hide_genres' => 'Hide the Genre column in browse table rows',
            'hide_single_artist' => 'Hide the Song Artist column for Albums with one Artist',
            'home_moment_albums' => 'Show Albums of the Moment',
            'home_moment_videos' => 'Show Videos of the Moment',
            'home_now_playing' => 'Show Now Playing',
            'home_recently_played' => 'Show Recently Played',
            'home_recently_played_all' => 'Show all media types in Recently Played',
            'homedash_max_items' => 'Home Dashboard max items',
            'homedash_random' => 'Random',
            'homedash_newest' => 'Newest',
            'homedash_recent' => 'Recent',
            'homedash_trending' => 'Trending',
            'homedash_popular' => 'Popular',
            'homedash_order' => 'Plugin CSS order',
            'httpq_active' => 'HTTPQ Active Instance',
            'index_dashboard_form' => 'Use Dashboard links for the index page header',
            'jp_volume' => 'Default webplayer volume',
            'lang' => 'Language',
            'lastfm_challenge' => 'Last.FM Submit Challenge',
            'lastfm_grant_link' => 'Last.FM Grant URL',
            'libitem_browse_alpha' => 'Alphabet browsing by default for following library items (album,artist,...)',
            'libitem_contextmenu' => 'Library item context menu',
            'librefm_challenge' => 'Libre.FM Submit Challenge',
            'listenbrainz_token' => 'ListenBrainz User Token',
            'localplay_controller' => 'Localplay Type',
            'localplay_level' => 'Localplay Access',
            'lock_songs' => 'Lock Songs',
            'matomo_site_id' => 'Matomo Site ID',
            'matomo_url' => 'Matomo URL',
            'mb_overwrite_name' => 'Overwrite Artist names that match an mbid',
            'mpd_active' => 'MPD Active Instance',
            'notify_email' => 'Allow E-mail notifications',
            'now_playing_per_user' => 'Now Playing filtered per user',
            'offset_limit' => 'Offset Limit',
            'of_the_moment' => 'Set the amount of items Album/Video of the Moment will display',
            'paypal_business' => 'PayPal ID',
            'paypal_currency_code' => 'PayPal Currency Code',
            'perpetual_api_session' => 'API sessions do not expire',
            'personalfav_display' => 'Personal favorites on the homepage',
            'personalfav_playlist' => 'Favorite Playlists',
            'personalfav_smartlist' => 'Favorite Smartlists',
            'personalfav_order' => 'Plugin CSS order',
            'piwik_site_id' => 'Piwik Site ID',
            'piwik_url' => 'Piwik URL',
            'playlist_method' => 'Playlist Method',
            'playlist_type' => 'Playlist Type',
            'play_type' => 'Playback Type',
            'podcast_keep' => '# latest episodes to keep',
            'podcast_new_download' => '# episodes to download when new episodes are available',
            'popular_threshold' => 'Popular Threshold',
            'rate_limit' => 'Rate Limit',
            'ratingmatch_flag_rule' => 'Match rule for Flags',
            'ratingmatch_flags' => 'When you love a track, flag the album and artist',
            'ratingmatch_star1_rule' => 'Match rule for 1 Star ($play,$skip)',
            'ratingmatch_star2_rule' => 'Match rule for 2 Stars',
            'ratingmatch_star3_rule' => 'Match rule for 3 Stars',
            'ratingmatch_star4_rule' => 'Match rule for 4 Stars',
            'ratingmatch_star5_rule' => 'Match rule for 5 Stars',
            'ratingmatch_stars' => 'Minimum star rating to match',
            'rssview_feed_url' => 'RSS Feed URL',
            'rssview_max_items' => 'RSS Feed max items',
            'rssview_order' => 'Plugin CSS order',
            'share_expire' => 'Share links default expiration days (0=never)',
            'share' => 'Allow Share',
            'shouthome_max_items' => 'Shoutbox on homepage max items',
            'shouthome_order' => 'Plugin CSS order',
            'show_album_artist' => "Show 'Album Artists' link in the main sidebar",
            'show_artist' => "Show 'Artists' link in the main sidebar",
            'show_donate' => 'Show donate button in footer',
            'show_header_login' => 'Show the login / registration links in the site header',
            'show_license' => 'Show License',
            'show_lyrics' => 'Show lyrics',
            'show_original_year' => 'Show Album original year on links (if available)',
            'show_played_times' => 'Show # played',
            'show_playlist_media_parent' => 'Show Artist column on playlist media rows',
            'show_playlist_username' => 'Show playlist owner username in titles',
            'show_skipped_times' => 'Show # skipped',
            'show_subtitle' => 'Show Album subtitle on links (if available)',
            'show_wrapped' => 'Enable access to your personal "Spotify Wrapped" from your user page',
            'sidebar_light' => 'Light sidebar by default',
            'sidebar_hide_browse' => 'Hide the Browse menu in the sidebar',
            'sidebar_hide_dashboard' => 'Hide the Dashboard menu in the sidebar',
            'sidebar_hide_information' => 'Hide the Information menu in the sidebar',
            'sidebar_hide_playlist' => 'Hide the Playlist menu in the sidebar',
            'sidebar_hide_search' => 'Hide the Search menu in the sidebar',
            'sidebar_hide_switcher' => 'Hide sidebar switcher arrows',
            'sidebar_hide_video' => 'Hide the Video menu in the sidebar',
            'sidebar_order_browse' => 'Custom CSS Order - Browse',
            'sidebar_order_dashboard' => 'Custom CSS Order - Dashboard',
            'sidebar_order_information' => 'Custom CSS Order - Information',
            'sidebar_order_playlist' => 'Custom CSS Order - Playlist',
            'sidebar_order_search' => 'Custom CSS Order - Search',
            'sidebar_order_video' => 'Custom CSS Order - Video',
            'site_title' => 'Website Title',
            'slideshow_time' => 'Artist slideshow inactivity time',
            'song_page_title' => 'Show current song in Web player page title',
            'stats_threshold' => 'Statistics Day Threshold',
            'stream_beautiful_url' => 'Enable URL Rewriting',
            'stream_control_bandwidth_days' => 'Stream control bandwidth history (days)',
            'stream_control_bandwidth_max' => 'Stream control maximal bandwidth (month)',
            'stream_control_hits_days' => 'Stream control hits history (days)',
            'stream_control_hits_max' => 'Stream control maximal hits',
            'stream_control_time_days' => 'Stream control time history (days)',
            'stream_control_time_max' => 'Stream control maximal time (minutes)',
            'subsonic_always_download' => 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)',
            'subsonic_backend' => 'Use Subsonic backend',
            'tadb_api_key' => 'TheAudioDb API key',
            'tadb_overwrite_name' => 'Overwrite Artist names that match an mbid',
            'theme_color' => 'Theme color',
            'theme_name' => 'Theme',
            'topmenu' => 'Top menu',
            'transcode_bitrate' => 'Transcode Bitrate',
            'transcode' => 'Allow Transcoding',
            'ui_fixed' => 'Fix header position on compatible themes',
            'unique_playlist' => 'Only add unique items to playlists',
            'upload_access_level' => 'Upload Access Level',
            'upload_allow_edit' => 'Allow users to edit uploaded songs',
            'upload_allow_remove' => 'Allow users to remove uploaded songs',
            'upload_catalog_pattern' => 'Rename uploaded file according to catalog pattern',
            'upload_catalog' => 'Destination catalog',
            'upload_script' => 'Post-upload script (current directory = upload target directory)',
            'upload_subdir' => 'Create a subdirectory per user',
            'upload_user_artist' => "Consider the user sender as the track's artist",
            'upnp_active' => 'UPnP Active Instance',
            'upnp_backend' => 'Use UPnP backend',
            'use_original_year' => 'Browse by Original Year for albums (falls back to Year)',
            'use_play2' => 'Use an alternative playback action for streaming if you have issues with playing music',
            'vlc_active' => 'VLC Active Instance',
            'webdav_backend' => 'Use WebDAV backend',
            'webplayer_aurora' => 'Authorize JavaScript decoder (Aurora.js) in Web Player',
            'webplayer_confirmclose' => 'Confirmation when closing current playing window',
            'webplayer_flash' => 'Authorize Flash Web Player',
            'webplayer_html5' => 'Authorize HTML5 Web Player',
            'webplayer_pausetabs' => 'Auto-pause between tabs',
            'webplayer_removeplayed' => 'Remove tracks before the current playlist item in the webplayer when played',
            'xbmc_active' => 'XBMC Active Instance',
            'yourls_api_key' => 'YOURLS API key',
            'yourls_domain' => 'YOURLS domain name',
            'yourls_use_idn' => 'YOURLS use IDN',
        ];
        foreach ($pref_array as $key => $value) {
            Dba::write($sql, [$value, $key, $value]);
        }
    }

    /**
     * load_from_session
     * This loads the preferences from the session rather then creating a connection to the database
     */
    public static function load_from_session(int $uid = -1): bool
    {
        if (!isset($_SESSION)) {
            return false;
        }

        if (
            array_key_exists('userdata', $_SESSION) &&
            array_key_exists('preferences', $_SESSION['userdata']) &&
            is_array($_SESSION['userdata']['preferences']) &&
            $_SESSION['userdata']['uid'] == $uid
        ) {
            AmpConfig::set_by_array($_SESSION['userdata']['preferences'], true);

            return true;
        }

        return false;
    }

    /**
     * set_level
     * Set access level to change preferences, useful for locked down sites and for resetting to the default values
     */
    public static function set_level(string $level = 'default'): bool
    {
        switch ($level) {
            case 'guest':
                return (Dba::write('UPDATE `preference` SET `level` = ?;', [AccessLevelEnum::GUEST->value]) !== false);
            case 'user':
                return (Dba::write('UPDATE `preference` SET `level` = ?;', [AccessLevelEnum::USER->value]) !== false);
            case 'content_manager':
                return (Dba::write('UPDATE `preference` SET `level` = ?;', [AccessLevelEnum::CONTENT_MANAGER->value]) !== false);
            case 'manager':
                return (Dba::write('UPDATE `preference` SET `level` = ?;', [AccessLevelEnum::MANAGER->value]) !== false);
            case 'admin':
                return (Dba::write('UPDATE `preference` SET `level` = ?;', [AccessLevelEnum::ADMIN->value]) !== false);
            case 'default':
                return (
                    Dba::write(
                        "UPDATE `preference` SET `level` = ? WHERE `name` IN ('libitem_contextmenu', 'show_lyrics', 'theme_color', 'theme_name');",
                        [AccessLevelEnum::DEFAULT->value]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `preference` SET `level` = ? WHERE `name` IN ('offset_limit', 'playlist_method');",
                        [AccessLevelEnum::GUEST->value]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `preference` SET `level` = ? WHERE `name` IN (" .
                        "'ajax_load', 'album_group', 'album_release_type', 'album_release_type_sort', 'album_sort'," .
                        " 'allow_personal_info_agent', 'allow_personal_info_now', 'allow_personal_info_recent'" .
                        " 'allow_personal_info_time', 'api_always_download', 'api_enable_3', 'api_enable_4'" .
                        " 'api_enable_5', 'api_enable_6', 'api_force_version', 'api_hidden_playlists'" .
                        " 'api_hide_dupe_searches', 'autoupdate_lastcheck', 'autoupdate_lastversion_new'" .
                        " 'autoupdate_lastversion', 'bookmark_latest', 'broadcast_by_default', 'browse_filter'" .
                        " 'browser_notify_timeout', 'browser_notify', 'custom_datetime', 'custom_logo_user'" .
                        " 'custom_logo', 'custom_timezone', 'demo_clear_sessions', 'direct_play_limit'" .
                        " 'geolocation', 'hide_genres', 'hide_single_artist', 'home_moment_albums', 'home_moment_videos'" .
                        " 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'httpq_active'" .
                        " 'index_dashboard_form', 'jp_volume', 'lastfm_challenge', 'lastfm_grant_link', 'mpd_active'" .
                        " 'notify_email', 'of_the_moment', 'play_type', 'popular_threshold', 'show_album_artist'" .
                        " 'show_artist', 'show_donate', 'show_license', 'show_original_year', 'show_played_times'" .
                        " 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'show_subtitle', 'show_wrapped'" .
                        " 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information'" .
                        " 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video'" .
                        " 'sidebar_light', 'sidebar_order_browse', 'sidebar_order_dashboard', 'sidebar_order_information'" .
                        " 'sidebar_order_playlist', 'sidebar_order_search', 'sidebar_order_video', 'slideshow_time'" .
                        " 'song_page_title', 'subsonic_always_download', 'topmenu', 'transcode_bitrate', 'transcode'" .
                        " 'ui_fixed', 'unique_playlist', 'use_original_year', 'use_play2', 'webplayer_aurora'" .
                        " 'webplayer_confirmclose', 'webplayer_flash', 'webplayer_html5', 'webplayer_pausetabs'" .
                        " 'webplayer_removeplayed'" .
                        ");",
                        [AccessLevelEnum::USER->value]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `preference` SET `level` = ? WHERE `name` IN ('now_playing_per_user');",
                        [AccessLevelEnum::CONTENT_MANAGER->value]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `preference` SET `level` = ? WHERE `name` IN (" .
                        "'allow_video', 'custom_blankalbum', 'custom_favicon' 'custom_login_background'," .
                        " 'custom_login_logo', 'custom_text_footer', 'libitem_browse_alpha', 'stats_threshold'" .
                        ");",
                        [AccessLevelEnum::MANAGER->value]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `preference` SET `level` = ? WHERE `name` IN (" .
                        "'allow_democratic_playback', 'allow_localplay_playback', 'allow_stream_playback', 'allow_upload'" .
                        " 'autoupdate', 'catalog_check_duplicate', 'cron_cache', 'daap_backend', 'daap_pass'" .
                        " 'demo_use_search', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields'" .
                        " 'download', 'force_http_play', 'lang', 'localplay_controller', 'localplay_level', 'lock_songs'" .
                        " 'perpetual_api_session', 'playlist_type', 'podcast_keep', 'podcast_new_download', 'rate_limit'" .
                        " 'share_expire', 'share', 'show_header_login', 'site_title', 'stream_beautiful_url'" .
                        " 'subsonic_backend', 'upload_access_level', 'upload_allow_edit', 'upload_allow_remove'" .
                        " 'upload_catalog_pattern', 'upload_catalog', 'upload_script', 'upload_subdir', 'upload_user_artist'" .
                        " 'upnp_backend', 'webdav_backend'" .
                        ");",
                        [AccessLevelEnum::ADMIN->value]
                    ) !== false
                );
        }

        return false;
    }

    /**
     * set_preset
     * Set user preferences to configured preset values ('system', 'default', 'minimalist', 'community')
     */
    public static function set_preset(string $username, string $preset): bool
    {
        $user = User::get_from_username($username);
        if ($user === null) {
            return false;
        }

        debug_event(self::class, 'Apply preference preset ' . $preset . ' to: ' . $username, 3);

        switch ($preset) {
            case 'system':
                // Get current system preferences
                $sql        = "SELECT `value`, `name` FROM `user_preference` WHERE `user` = -1;";
                $db_results = Dba::read($sql);

                while ($row = Dba::fetch_assoc($db_results)) {
                    $pref_sql = "UPDATE `user_preference` SET `value` = ? WHERE `user` = ? AND `name` = ?;";
                    if (Dba::write($pref_sql, [$row['value'], $user->getId(), $row['name']]) === false) {
                        return false;
                    }
                }

                return true;
            case 'default':
                return (
                    Dba::write("UPDATE `user_preference` SET `value` = '-1' WHERE `name` IN ('upload_catalog') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '' WHERE `name` IN (" .
                        "'api_hidden_playlists', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'custom_blankalbum'," .
                        " 'custom_datetime', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_logo'," .
                        " 'custom_text_footer', 'custom_timezone', 'daap_pass', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields'," .
                        " 'lastfm_challenge', 'lastfm_grant_link', 'libitem_browse_alpha', 'upload_script') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '0.8' WHERE `name` IN ('jp_volume') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '0' WHERE `name` IN (" .
                        "'album_sort', 'allow_upload', 'allow_video', 'api_force_version', 'api_hide_dupe_searches', 'bookmark_latest', 'broadcast_by_default', 'browse_filter'," .
                        " 'catalog_check_duplicate', 'cron_cache', 'custom_logo_user', 'daap_backend', 'demo_clear_sessions'," .
                        " 'demo_use_search', 'direct_play_limit', 'force_http_play', 'geolocation', 'hide_genres', 'hide_single_artist', 'home_moment_videos'," .
                        " 'httpq_active', 'index_dashboard_form', 'lock_songs', 'mpd_active', 'notify_email', 'perpetual_api_session'," .
                        " 'share', 'show_album_artist', 'show_lyrics', 'show_played_times', 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times'," .
                        " 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information', 'sidebar_hide_playlist'," .
                        " 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'slideshow_time', 'stream_beautiful_url'," .
                        " 'subsonic_always_download', 'topmenu', 'ui_fixed', 'unique_playlist', 'upload_catalog_pattern', 'upload_user_artist', 'upnp_backend'," .
                        " 'use_original_year', 'use_play2', 'webdav_backend', 'webplayer_confirmclose', 'webplayer_removeplayed', 'api_always_download') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '1' WHERE `name` IN (" .
                        "'ajax_load', 'album_group', 'album_release_type', 'allow_democratic_playback', 'allow_localplay_playback'," .
                        " 'allow_personal_info_agent', 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time'," .
                        " 'allow_stream_playback', 'api_enable_3', 'api_enable_4', 'api_enable_5', 'api_enable_6'," .
                        " 'autoupdate', 'browser_notify', 'download', 'home_moment_albums'," .
                        " 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'libitem_contextmenu', 'now_playing_per_user'," .
                        " 'podcast_new_download', 'show_artist', 'show_donate', 'show_header_login', 'show_license', 'show_original_year'," .
                        " 'show_subtitle', 'show_wrapped', 'song_page_title', 'subsonic_backend', 'upload_allow_edit', 'upload_allow_remove', 'upload_subdir'," .
                        " 'webplayer_aurora', 'webplayer_flash', 'webplayer_html5', 'webplayer_pausetabs') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '10' WHERE `name` IN ('browser_notify_timeout', 'podcast_keep', 'popular_threshold', 'sidebar_order_browse') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '100' WHERE `name` IN ('localplay_level') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '15' WHERE `name` IN ('sidebar_order_dashboard') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '20' WHERE `name` IN ('sidebar_order_video') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '25' WHERE `name` IN ('upload_access_level') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '30' WHERE `name` IN ('sidebar_order_playlist') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '32' WHERE `name` IN ('transcode_bitrate') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '40' WHERE `name` IN ('sidebar_order_search') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '50' WHERE `name` IN ('offset_limit') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '6' WHERE `name` IN ('of_the_moment') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '60' WHERE `name` IN ('sidebar_order_information') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '7' WHERE `name` IN ('share_expire', 'stats_threshold') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '8192' WHERE `name` IN ('rate_limit') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'album,ep,live,single' WHERE `name` IN ('album_release_type_sort') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'Ampache :: For the Love of Music' WHERE `name` IN ('site_title') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'dark' WHERE `name` IN ('theme_color') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'default' WHERE `name` IN ('playlist_method', 'transcode') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'en_US' WHERE `name` IN ('lang') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'm3u' WHERE `name` IN ('playlist_type') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'mpd' WHERE `name` IN ('localplay_controller') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'reborn' WHERE `name` IN ('theme_name') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'web_player' WHERE `name` IN ('play_type') AND `user` = ?;", [$user->getId()]) !== false
                );
            case 'minimalist':
                return (
                    Dba::write("UPDATE `user_preference` SET `value` = '-1' WHERE `name` IN ('upload_catalog') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '' WHERE `name` IN (" .
                        "'api_hidden_playlists', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'custom_blankalbum'," .
                        " 'custom_datetime', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_logo'," .
                        " 'custom_text_footer', 'custom_timezone', 'daap_pass', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields'," .
                        " 'lastfm_challenge', 'lastfm_grant_link', 'libitem_browse_alpha', 'upload_script') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '0.8' WHERE `name` IN ('jp_volume') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '0' WHERE `name` IN (" .
                        "'album_sort', 'allow_upload', 'allow_video', 'api_force_version', 'api_hide_dupe_searches', 'bookmark_latest', 'broadcast_by_default', 'browse_filter', 'catalog_check_duplicate'," .
                        " 'cron_cache', 'custom_logo_user', 'daap_backend', 'demo_clear_sessions', 'demo_use_search', 'direct_play_limit', 'download'," .
                        " 'force_http_play', 'geolocation', 'hide_genres', 'hide_single_artist', 'home_moment_videos', 'httpq_active', 'index_dashboard_form', 'lock_songs'," .
                        " 'mpd_active', 'notify_email', 'perpetual_api_session', 'share', 'show_album_artist', 'show_lyrics', 'show_played_times'," .
                        " 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'show_wrapped', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information'," .
                        " 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'slideshow_time'," .
                        " 'stream_beautiful_url', 'subsonic_always_download', 'topmenu', 'ui_fixed', 'unique_playlist', 'upload_catalog_pattern', 'upload_user_artist'," .
                        " 'upnp_backend', 'use_original_year', 'use_play2', 'webdav_backend', 'webplayer_confirmclose', 'webplayer_removeplayed'," .
                        " 'api_always_download') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '1' WHERE `name` IN (" .
                        "'ajax_load', 'album_group', 'album_release_type', 'allow_democratic_playback', 'allow_localplay_playback', 'allow_personal_info_agent'," .
                        " 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_stream_playback', 'api_enable_3'," .
                        " 'api_enable_4', 'api_enable_5', 'api_enable_6', 'autoupdate', 'browser_notify'," .
                        " 'home_moment_albums', 'home_now_playing', 'home_recently_played_all', 'home_recently_played'," .
                        " 'libitem_contextmenu', 'now_playing_per_user', 'podcast_new_download', 'show_artist', 'show_donate', 'show_header_login'," .
                        " 'show_license', 'show_original_year', 'show_subtitle', 'song_page_title', 'subsonic_backend', 'upload_allow_edit', 'upload_allow_remove'," .
                        " 'upload_subdir', 'webplayer_aurora', 'webplayer_flash', 'webplayer_html5', 'webplayer_pausetabs') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '10' WHERE `name` IN ('browser_notify_timeout', 'podcast_keep', 'popular_threshold', 'sidebar_order_browse') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '100' WHERE `name` IN ('localplay_level') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '15' WHERE `name` IN ('sidebar_order_dashboard') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '20' WHERE `name` IN ('sidebar_order_video') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '25' WHERE `name` IN ('upload_access_level') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '30' WHERE `name` IN ('sidebar_order_playlist') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '32' WHERE `name` IN ('transcode_bitrate') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '40' WHERE `name` IN ('sidebar_order_search') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '50' WHERE `name` IN ('offset_limit') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '6' WHERE `name` IN ('of_the_moment') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '60' WHERE `name` IN ('sidebar_order_information') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '7' WHERE `name` IN ('share_expire', 'stats_threshold') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '8192' WHERE `name` IN ('rate_limit') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'album,ep,live,single' WHERE `name` IN ('album_release_type_sort') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'Ampache :: For the Love of Music' WHERE `name` IN ('site_title') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'dark' WHERE `name` IN ('theme_color') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'default' WHERE `name` IN ('playlist_method', 'transcode') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'en_US' WHERE `name` IN ('lang') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'm3u' WHERE `name` IN ('playlist_type') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'mpd' WHERE `name` IN ('localplay_controller') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'reborn' WHERE `name` IN ('theme_name') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'web_player' WHERE `name` IN ('play_type') AND `user` = ?;", [$user->getId()]) !== false
                );
            case 'community':
                return (
                    Dba::write("UPDATE `user_preference` SET `value` = '-1' WHERE `name` IN ('upload_catalog') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '' WHERE `name` IN (" .
                        "'api_hidden_playlists', 'autoupdate_lastcheck', 'autoupdate_lastversion_new', 'autoupdate_lastversion', 'custom_blankalbum'," .
                        " 'custom_datetime', 'custom_favicon', 'custom_login_background', 'custom_login_logo', 'custom_logo', 'custom_text_footer', 'custom_timezone'," .
                        " 'daap_pass', 'disabled_custom_metadata_fields_input', 'disabled_custom_metadata_fields', 'lastfm_challenge', 'lastfm_grant_link', 'libitem_browse_alpha'," .
                        " 'upload_script') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '0.8' WHERE `name` IN ('jp_volume') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '0' WHERE `name` IN (" .
                        "'album_sort', 'allow_upload', 'allow_video', 'api_force_version', 'api_hide_dupe_searches', 'bookmark_latest', 'broadcast_by_default', 'browse_filter', 'catalog_check_duplicate', 'cron_cache'," .
                        " 'custom_logo_user', 'daap_backend', 'demo_clear_sessions', 'demo_use_search', 'direct_play_limit', 'download', 'force_http_play', 'geolocation'," .
                        " 'hide_genres', 'hide_single_artist', 'home_moment_videos', 'home_now_playing', 'home_recently_played_all', 'home_recently_played', 'httpq_active', 'index_dashboard_form'," .
                        " 'lock_songs', 'mpd_active', 'notify_email', 'perpetual_api_session', 'show_album_artist', 'show_lyrics', 'show_played_times'," .
                        " 'show_playlist_media_parent', 'show_playlist_username', 'show_skipped_times', 'show_wrapped', 'sidebar_hide_browse', 'sidebar_hide_dashboard', 'sidebar_hide_information'," .
                        " 'sidebar_hide_playlist', 'sidebar_hide_search', 'sidebar_hide_switcher', 'sidebar_hide_video', 'sidebar_light', 'slideshow_time', 'stream_beautiful_url'," .
                        " 'subsonic_always_download', 'topmenu', 'ui_fixed', 'unique_playlist', 'upload_catalog_pattern', 'upload_user_artist', 'upnp_backend', 'use_original_year'," .
                        " 'use_play2', 'webdav_backend', 'webplayer_confirmclose', 'webplayer_removeplayed', 'api_always_download') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write(
                        "UPDATE `user_preference` SET `value` = '1' WHERE `name` IN (" .
                        "'ajax_load', 'album_group', 'album_release_type', 'allow_democratic_playback', 'allow_localplay_playback', 'allow_personal_info_agent'," .
                        " 'allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_stream_playback', 'api_enable_3', 'api_enable_4'," .
                        " 'api_enable_5', 'api_enable_6', 'autoupdate', 'browser_notify', 'home_moment_albums'," .
                        " 'libitem_contextmenu', 'now_playing_per_user', 'podcast_new_download', 'share', 'show_artist', 'show_donate'," .
                        " 'show_header_login', 'show_license', 'show_original_year', 'show_subtitle', 'song_page_title', 'subsonic_backend', 'upload_allow_edit'," .
                        " 'upload_allow_remove', 'upload_subdir', 'webplayer_aurora', 'webplayer_flash', 'webplayer_html5', 'webplayer_pausetabs') AND `user` = ?;",
                        [$user->getId()]
                    ) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '10' WHERE `name` IN ('browser_notify_timeout', 'podcast_keep', 'popular_threshold', 'sidebar_order_browse') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '100' WHERE `name` IN ('localplay_level') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '15' WHERE `name` IN ('sidebar_order_dashboard') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '20' WHERE `name` IN ('sidebar_order_video') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '25' WHERE `name` IN ('upload_access_level') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '30' WHERE `name` IN ('sidebar_order_playlist') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '32' WHERE `name` IN ('transcode_bitrate') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '40' WHERE `name` IN ('sidebar_order_search') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '50' WHERE `name` IN ('offset_limit') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '6' WHERE `name` IN ('of_the_moment') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '60' WHERE `name` IN ('sidebar_order_information') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '7' WHERE `name` IN ('share_expire', 'stats_threshold') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = '8192' WHERE `name` IN ('rate_limit') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'album,ep,live,single' WHERE `name` IN ('album_release_type_sort') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'Ampache :: For the Love of Music' WHERE `name` IN ('site_title') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'dark' WHERE `name` IN ('theme_color') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'default' WHERE `name` IN ('playlist_method', 'transcode') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'en_US' WHERE `name` IN ('lang') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'm3u' WHERE `name` IN ('playlist_type') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'mpd' WHERE `name` IN ('localplay_controller') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'reborn' WHERE `name` IN ('theme_name') AND `user` = ?;", [$user->getId()]) !== false &&
                    Dba::write("UPDATE `user_preference` SET `value` = 'web_player' WHERE `name` IN ('play_type') AND `user` = ?;", [$user->getId()]) !== false
                );
        }

        return false;
    }

    /**
     * clear_from_session
     * This clears the users preferences, this is done whenever modifications are made to the preferences
     * or the admin resets something
     */
    public static function clear_from_session(): void
    {
        if (
            isset($_SESSION) &&
            array_key_exists('userdata', $_SESSION) &&
            array_key_exists('preferences', $_SESSION['userdata'])
        ) {
            unset($_SESSION['userdata']['preferences']);
        }
    }

    /**
     * is_boolean
     * This returns true / false if the preference in question is a boolean preference
     * This is currently only used by the debug view, could be used other places.. wouldn't be a half
     * bad idea
     */
    public static function is_boolean(string $key): bool
    {
        $boolean_array = [
            'access_control',
            'access_list',
            'admin_enable_required',
            'admin_notify_reg',
            'ajax_load',
            'album_art_store_disk',
            'album_group',
            'album_release_type',
            'allow_democratic_playback',
            'allow_localplay_playback',
            'allow_personal_info_agent',
            'allow_personal_info_now',
            'allow_personal_info_recent',
            'allow_personal_info_time',
            'allow_php_themes',
            'allow_public_registration',
            'allow_stream_playback',
            'allow_upload_scripts',
            'allow_upload',
            'allow_video',
            'allow_zip_download',
            'api_always_download',
            'api_debug_handler',
            'api_enable_3',
            'api_enable_4',
            'api_enable_5',
            'api_enable_6',
            'api_hide_dupe_searches',
            'art_zip_add',
            'auth_password_save',
            'auto_create',
            'autoupdate_lastversion_new',
            'autoupdate',
            'bookmark_latest',
            'broadcast_by_default',
            'broadcast',
            'browse_album_disk_grid_view',
            'browse_album_grid_view',
            'browse_artist_grid_view',
            'browse_filter',
            'browse_live_stream_grid_view',
            'browse_playlist_grid_view',
            'browse_podcast_episode_grid_view',
            'browse_podcast_grid_view',
            'browse_song_grid_view',
            'browse_video_grid_view',
            'browser_notify',
            'cache_aif',
            'cache_aiff',
            'cache_ape',
            'cache_flac',
            'cache_m4a',
            'cache_mp3',
            'cache_mpc',
            'cache_oga',
            'cache_ogg',
            'cache_opus',
            'cache_remote',
            'cache_shn',
            'cache_wav',
            'cache_wma',
            'captcha_public_reg',
            'catalog_check_duplicate',
            'catalog_disable',
            'catalog_filter',
            'catalog_verify_by_album',
            'catalog_verify_by_time',
            'catalogfav_compact',
            'catalogfav_gridview',
            'composer_no_dev',
            'condPL',
            'cookie_disclaimer',
            'cookie_secure',
            'cron_cache',
            'custom_logo_user',
            'daap_backend',
            'debug',
            'deferred_ext_metadata',
            'delete_from_disk',
            'demo_clear_sessions',
            'demo_mode',
            'demo_use_search',
            'direct_link',
            'directplay',
            'disable_xframe_sameorigin',
            'display_menu',
            'download',
            'downsample_remote',
            'enable_custom_metadata',
            'extended_playlist_links',
            'external_auto_update',
            'external_links_bandcamp',
            'external_links_discogs',
            'external_links_duckduckgo',
            'external_links_google',
            'external_links_lastfm',
            'external_links_musicbrainz',
            'external_links_wikipedia',
            'force_http_play',
            'force_ssl',
            'gather_song_art',
            'generate_video_preview',
            'geolocation',
            'getid3_detect_id3v2_encoding',
            'hide_ampache_messages',
            'hide_genres',
            'hide_search',
            'hide_single_artist',
            'home_moment_albums',
            'home_moment_videos',
            'home_now_playing',
            'home_recently_played_all',
            'home_recently_played',
            'homedash_max_items',
            'homedash_newest',
            'homedash_popular',
            'homedash_random',
            'homedash_recent',
            'homedash_trending',
            'index_dashboard_form',
            'label',
            'ldap_start_tls',
            'libitem_contextmenu',
            'licensing',
            'live_stream',
            'lock_songs',
            'mail_auth',
            'mail_enable',
            'mb_overwrite_name',
            'memory_cache',
            'no_symlinks',
            'notify_email',
            'now_playing_per_user',
            'perpetual_api_session',
            'personalfav_display',
            'playlist_art',
            'podcast',
            'prevent_multiple_logins',
            'quarantine',
            'rating_browse_filter',
            'rating_browse_minimum_stars',
            'ratingmatch_flags',
            'ratingmatch_write_tags',
            'ratings',
            'require_localnet_session',
            'require_session',
            'resize_images',
            'rio_global_stats',
            'rio_track_stats',
            'send_full_stream',
            'session_cookiesecure',
            'share_social',
            'share',
            'show_album_artist',
            'show_artist',
            'show_donate',
            'show_footer_statistics',
            'show_header_login',
            'show_license',
            'show_lyrics',
            'show_original_year',
            'show_played_times',
            'show_playlist_media_parent',
            'show_playlist_username',
            'show_similar',
            'show_skipped_times',
            'show_song_art',
            'show_subtitle',
            'show_wrapped',
            'sidebar_hide_browse',
            'sidebar_hide_dashboard',
            'sidebar_hide_information',
            'sidebar_hide_playlist',
            'sidebar_hide_search',
            'sidebar_hide_switcher',
            'sidebar_hide_video',
            'sidebar_light',
            'simple_user_mode',
            'sociable',
            'song_page_title',
            'statistical_graphs',
            'stream_beautiful_url',
            'subsonic_always_download',
            'subsonic_backend',
            'subsonic_force_album_artist',
            'subsonic_legacy',
            'subsonic_single_user_data',
            'tadb_overwrite_name',
            'topmenu',
            'track_user_ip',
            'transcode_player_customize',
            'ui_fixed',
            'unique_playlist',
            'upload_allow_edit',
            'upload_allow_remove',
            'upload_catalog_pattern',
            'upload_script',
            'upload_subdir',
            'upload_user_artist',
            'upload',
            'upnp_backend',
            'use_auth',
            'use_now_playing_embedded',
            'use_original_year',
            'use_play2',
            'use_rss',
            'user_agreement',
            'user_create_streamtoken',
            'user_no_email_confirm',
            'vite_dev',
            'wanted_auto_accept',
            'wanted',
            'waveform',
            'webdav_backend',
            'webplayer_aurora',
            'webplayer_confirmclose',
            'webplayer_debug',
            'webplayer_flash',
            'webplayer_html5',
            'webplayer_pausetabs',
            'write_tags',
            'xml_rpc',
        ];

        return in_array($key, $boolean_array);
    }

    /**
     * init
     * This grabs the preferences and then loads them into conf it should be run on page load
     * to initialize the needed variables
     */
    public static function init(): bool
    {
        $user    = Core::get_global('user');
        $user_id = $user?->id ?? -1;

        // First go ahead and try to load it from the preferences
        if (self::load_from_session($user_id)) {
            return true;
        }

        /* Get Global Preferences */
        $sql = (Dba::read('SELECT `category` FROM `preference` LIMIT 1;', [], true))
            ? "SELECT `preference`.`name`, `user_preference`.`value`, `syspref`.`value` AS `system_value` FROM `preference` LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`category`='system' LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user` = ? AND `preference`.`category` !='system'"
            : "SELECT `preference`.`name`, `user_preference`.`value`, `syspref`.`value` AS `system_value` FROM `preference` LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`catagory`='system' LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user` = ? AND `preference`.`catagory` !='system'";
        $db_results = Dba::read($sql, [$user_id]);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $value          = $row['system_value'] ?? $row['value'];
            $name           = $row['name'];
            $results[$name] = $value;
        }

        /* Set the Theme mojo */
        if (array_key_exists('theme_name', $results) && strlen((string)$results['theme_name']) > 0) {
            // In case the theme was removed
            if (!Core::is_readable(__DIR__ . '/../../../public/themes/' . $results['theme_name'])) {
                unset($results['theme_name']);
            }
        } else {
            unset($results['theme_name']);
        }

        // Default theme if we don't get anything from their
        // preferences because we're going to want at least something otherwise
        // the page is going to be really ugly
        if (empty($results['theme_name'])) {
            $results['theme_name'] = 'reborn';
        }

        $results['theme_path'] = '/themes/' . $results['theme_name'];

        // Load theme settings
        $theme_cfg                 = get_theme($results['theme_name']);
        $results['theme_css_base'] = $theme_cfg['base'] ?? null;

        // Default theme color fallback
        if (!isset($results['theme_color'])) {
            $results['theme_color'] = 'dark';
        }

        if (array_key_exists('theme_color', $results) && strlen((string)$results['theme_color']) > 0) {
            // In case the color was removed
            if (!Core::is_readable(__DIR__ . '/../../../public/themes/' . $results['theme_name'] . '/templates/' . $results['theme_color'] . '.css')) {
                unset($results['theme_color']);
            }
        } else {
            unset($results['theme_color']);
        }

        if (!isset($results['theme_color'])) {
            $results['theme_color'] = (isset($theme_cfg['colors']))
                ? strtolower((string)$theme_cfg['colors'][0])
                : 'dark';
        }

        AmpConfig::set_by_array($results, true);
        $_SESSION['userdata']['preferences'] = $results;
        $_SESSION['userdata']['uid']         = $user_id;

        return true;
    }
}
