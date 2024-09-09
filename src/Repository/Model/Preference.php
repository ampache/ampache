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

namespace Ampache\Repository\Model;

use SimpleXMLElement;
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
        'album_release_type',
        'album_release_type_sort',
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
        'api_enable_3',
        'api_enable_4',
        'api_enable_5',
        'api_enable_6',
        'api_force_version',
        'api_hidden_playlists',
        'api_hide_dupe_searches',
        'autoupdate',
        'autoupdate_lastcheck',
        'autoupdate_lastversion',
        'autoupdate_lastversion_new',
        'bookmark_latest',
        'broadcast_by_default',
        'browse_filter',
        'browser_notify',
        'browser_notify_timeout',
        'catalog_check_duplicate',
        'cron_cache',
        'custom_blankalbum',
        'custom_datetime',
        'custom_favicon',
        'custom_login_background',
        'custom_login_logo',
        'custom_logo',
        'custom_logo_user',
        'custom_text_footer',
        'custom_timezone',
        'daap_backend',
        'daap_pass',
        'demo_clear_sessions',
        'demo_use_search',
        'direct_play_limit',
        'disabled_custom_metadata_fields',
        'disabled_custom_metadata_fields_input',
        'download',
        'force_http_play',
        'geolocation',
        'hide_genres',
        'hide_single_artist',
        'home_moment_albums',
        'home_moment_videos',
        'home_now_playing',
        'home_recently_played',
        'home_recently_played_all',
        'httpq_active',
        'jp_volume',
        'lang',
        'lastfm_challenge',
        'lastfm_grant_link',
        'libitem_browse_alpha',
        'libitem_contextmenu',
        'localplay_controller',
        'localplay_level',
        'lock_songs',
        'mpd_active',
        'notify_email',
        'now_playing_per_user',
        'offset_limit',
        'of_the_moment',
        'perpetual_api_session',
        'playlist_method',
        'playlist_type',
        'play_type',
        'podcast_keep',
        'podcast_new_download',
        'popular_threshold',
        'rate_limit',
        'share',
        'share_expire',
        'show_album_artist',
        'show_artist',
        'show_donate',
        'show_header_login',
        'show_license',
        'show_lyrics',
        'show_original_year',
        'show_played_times',
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
        'site_title',
        'slideshow_time',
        'song_page_title',
        'stats_threshold',
        'stream_beautiful_url',
        'subsonic_always_download',
        'subsonic_backend',
        'theme_color',
        'theme_name',
        'topmenu',
        'transcode',
        'transcode_bitrate',
        'ui_fixed',
        'unique_playlist',
        'upload_access_level',
        'upload_allow_edit',
        'upload_allow_remove',
        'upload_catalog',
        'upload_catalog_pattern',
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
        'webplayer_removeplayed'
    ];

    /**
     * plugin preferences might not be there but they need to be kept if you're using them
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
        'index_dashboard_form',
        'lastfm_challenge',
        'lastfm_grant_link',
        'librefm_challenge',
        'librefm_grant_link',
        'listenbrainz_token',
        'matomo_site_id',
        'matomo_url',
        'mb_overwrite_name',
        'paypal_business',
        'paypal_currency_code',
        'personalfav_display',
        'personalfav_playlist',
        'personalfav_smartlist',
        'personalfav_order',
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
     * @param int $user_id
     * @param string $pref_name
     * @return int|string
     *
     * @see User::getPreferenceValue()
     */
    public static function get_by_user($user_id, $pref_name)
    {
        //debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '}...', 5);
        if (parent::is_cached('get_by_user-' . $pref_name, $user_id)) {
            return (parent::get_from_cache('get_by_user-' . $pref_name, $user_id))['value'];
        }

        $sql        = "SELECT `value` FROM `user_preference` WHERE `name` = ? AND `user` = ?";
        $db_results = Dba::read($sql, [$pref_name, $user_id]);
        if (Dba::num_rows($db_results) < 1) {
            $sql        = "SELECT `value` FROM `user_preference` WHERE `name` = ? AND `user`='-1'";
            $db_results = Dba::read($sql, [$pref_name]);
        }

        $data = Dba::fetch_assoc($db_results);

        parent::add_to_cache('get_by_user-' . $pref_name, $user_id, $data);

        return $data['value'] ?? '';
    }

    /**
     * update
     * This updates a single preference from the given name or id
     * @param string|int $preference
     * @param int $user_id
     * @param array|string|int|bool|SimpleXMLElement $value
     * @param bool $applytoall
     * @param bool $applytodefault
     */
    public static function update($preference, $user_id, $value, $applytoall = false, $applytodefault = false): bool
    {
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

        $params = [$value, $name];

        if ($applytoall && $access100) {
            $user_check = "";
        } else {
            $user_check = "AND `user` = ?";
            $params[]   = $user_id;
        }

        if ($applytodefault && $access100) {
            $sql = "UPDATE `preference` SET `value` = ? WHERE `name` = ?";
            Dba::write($sql, $params);
        }

        if (self::has_access($name)) {
            $sql = 'UPDATE `user_preference` SET `value` = ? WHERE `name` = ? ' . $user_check;
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
     * @param string|int $preference
     * @param int $level
     */
    public static function update_level($preference, $level): bool
    {
        // First prepare
        $preference_id = is_numeric($preference) ? $preference : self::id_from_name($preference);

        $sql = "UPDATE `preference` SET `level` = ? WHERE `id` = ?;";
        Dba::write($sql, [$level, $preference_id]);

        return true;
    }

    /**
     * update_all
     * This takes a preference id and a value and updates all users with the new info
     * @param string $preference
     * @param string|int $value
     */
    public static function update_all($preference, $value): bool
    {

        $sql = "UPDATE `user_preference` SET `value` = ? WHERE `name` = ?";
        Dba::write($sql, [$value, $preference]);

        parent::clear_cache();
        self::clear_from_session();

        return true;
    }

    /**
     * exists
     * This just checks to see if a preference currently exists
     * @param string|int $preference
     */
    public static function exists($preference): int
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
     * @param string $preference
     */
    public static function has_access($preference): bool
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
     * @param string $name
     */
    public static function id_from_name($name): ?int
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
     * @param int|string $pref_id
     */
    public static function name_from_id($pref_id): ?string
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
     * get
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @param string $pref_name
     * @param int $user_id
     */
    public static function get($pref_name, $user_id): array
    {
        $user_id    = Dba::escape($user_id);
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
     * @param string $name
     * @param string $description
     * @param string|int|float $default
     * @param int $level
     * @param string $type
     * @param string $category
     * @param null|string $subcategory
     * @param bool $replace
     */
    public static function insert(
        $name,
        $description,
        $default,
        $level,
        $type,
        $category,
        $subcategory = null,
        $replace = false
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

        $sql        = "INSERT INTO `preference` (`name`, `description`, `value`, `level`, `type`, `category`, `subcategory`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, [$name, $description, $default, (int)$level, $type, $category, $subcategory]);

        if (!$db_results) {
            return false;
        }

        $pref_id    = Dba::insert_id();
        $params     = [$pref_id, $name, $default];
        $sql        = "INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) VALUES (-1, ?, ?, ?)";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        if ($category !== "system") {
            $sql        = "INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) (SELECT `user`.`id`, ?, ?, ? FROM `user`);";
            $db_results = Dba::write($sql, $params);
            if (!$db_results) {
                return false;
            }
        }

        debug_event(self::class, 'Inserted preference: ' . $name, 3);

        return true;
    }

    /**
     * delete
     * This deletes the specified preference, a name or an ID can be passed
     * @param string|int $preference
     */
    public static function delete($preference): bool
    {
        if (Preference::exists($preference) === 0) {
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
     * @param string $old
     * @param string $new
     */
    public static function rename($old, $new): void
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
     * @param array $results
     */
    public static function fix_preferences($results): array
    {
        $arrays = [
            'allow_zip_types',
            'art_order',
            'auth_methods',
            'getid3_tag_order',
            'metadata_order',
            'metadata_order_video',
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
        $results    = [];
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['item'];
        }

        if (!empty($results)) {
            debug_event(self::class, 'set_defaults: missing preferences found', 5);
        }

        foreach ($results as $name) {
            debug_event(self::class, 'Insert preference: ' . $name, 2);
            $sql = "INSERT IGNORE INTO `preference` (`id`, `name`, `value`, `description`, `level`, `type`, `category`, `subcategory`) VALUES ";
            switch ($name) {
                case 'download':
                    Dba::write($sql . "(1, 'download', '1', 'Allow Downloads', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'options', 'feature');");
                    break;
                case 'popular_threshold':
                    Dba::write($sql . "(4, 'popular_threshold', '10', 'Popular Threshold', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'query');");
                    break;
                case 'transcode_bitrate':
                    Dba::write($sql . "(19, 'transcode_bitrate', '128', 'Transcode Bitrate', " . AccessLevelEnum::USER->value . ", 'string', 'streaming', 'transcoding');");
                    break;
                case 'site_title':
                    Dba::write($sql . "(22, 'site_title', 'Ampache :: For the Love of Music', 'Website Title', " . AccessLevelEnum::ADMIN->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'lock_songs':
                    Dba::write($sql . "(23, 'lock_songs', '0', 'Lock Songs', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', NULL);");
                    break;
                case 'force_http_play':
                    Dba::write($sql . "(24, 'force_http_play', '0', 'Force HTTP playback regardless of port', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', NULL);");
                    break;
                case 'play_type':
                    Dba::write($sql . "(29, 'play_type', 'web_player', 'Playback Type', " . AccessLevelEnum::USER->value . ", 'special', 'streaming', NULL);");
                    break;
                case 'lang':
                    Dba::write($sql . "(31, 'lang', 'en_US', 'Language', " . AccessLevelEnum::ADMIN->value . ", 'special', 'interface', NULL);");
                    break;
                case 'playlist_type':
                    Dba::write($sql . "(32, 'playlist_type', 'm3u', 'Playlist Type', " . AccessLevelEnum::ADMIN->value . ", 'special', 'playlist', NULL);");
                    break;
                case 'theme_name':
                    Dba::write($sql . "(33, 'theme_name', 'reborn', 'Theme', " . AccessLevelEnum::DEFAULT->value . ", 'special', 'interface', 'theme');");
                    break;
                case 'localplay_level':
                    Dba::write($sql . "(40, 'localplay_level', '0', 'Localplay Access', " . AccessLevelEnum::ADMIN->value . ", 'special', 'options', 'localplay');");
                    break;
                case 'localplay_controller':
                    Dba::write($sql . "(41, 'localplay_controller', '0', 'Localplay Type', " . AccessLevelEnum::ADMIN->value . ", 'special', 'options', 'localplay');");
                    break;
                case 'allow_stream_playback':
                    Dba::write($sql . "(44, 'allow_stream_playback', '1', 'Allow Streaming', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'options', 'feature');");
                    break;
                case 'allow_democratic_playback':
                    Dba::write($sql . "(45, 'allow_democratic_playback', '0', 'Allow Democratic Play', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'options', 'feature');");
                    break;
                case 'allow_localplay_playback':
                    Dba::write($sql . "(46, 'allow_localplay_playback', '0', 'Allow Localplay Play', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'options', 'localplay');");
                    break;
                case 'stats_threshold':
                    Dba::write($sql . "(47, 'stats_threshold', '7', 'Statistics Day Threshold', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'query');");
                    break;
                case 'offset_limit':
                    Dba::write($sql . "(51, 'offset_limit', '50', 'Offset Limit', " . AccessLevelEnum::GUEST->value . ", 'integer', 'interface', 'query');");
                    break;
                case 'rate_limit':
                    Dba::write($sql . "(52, 'rate_limit', '8192', 'Rate Limit', " . AccessLevelEnum::ADMIN->value . ", 'integer', 'streaming', 'transcoding');");
                    break;
                case 'playlist_method':
                    Dba::write($sql . "(53, 'playlist_method', 'default', 'Playlist Method', " . AccessLevelEnum::GUEST->value . ", 'string', 'playlist', NULL);");
                    break;
                case 'transcode':
                    Dba::write($sql . "(55, 'transcode', 'default', 'Allow Transcoding', " . AccessLevelEnum::USER->value . ", 'string', 'streaming', 'transcoding');");
                    break;
                case 'show_lyrics':
                    Dba::write($sql . "(69, 'show_lyrics', '0', 'Show lyrics', " . AccessLevelEnum::DEFAULT->value . ", 'boolean', 'interface', 'player');");
                    break;
                case 'mpd_active':
                    Dba::write($sql . "(70, 'mpd_active', '0', 'MPD Active Instance', " . AccessLevelEnum::USER->value . ", 'integer', 'internal', 'mpd');");
                    break;
                case 'httpq_active':
                    Dba::write($sql . "(71, 'httpq_active', '0', 'httpQ Active Instance', " . AccessLevelEnum::USER->value . ", 'integer', 'internal', 'httpq');");
                    break;
                case 'lastfm_grant_link':
                    Dba::write($sql . "(77, 'lastfm_grant_link', '', 'Last.FM Grant URL', " . AccessLevelEnum::USER->value . ", 'string', 'internal', 'lastfm');");
                    break;
                case 'lastfm_challenge':
                    Dba::write($sql . "(78, 'lastfm_challenge', '', 'Last.FM Submit Challenge', " . AccessLevelEnum::USER->value . ", 'string', 'internal', 'lastfm');");
                    break;
                case 'now_playing_per_user':
                    Dba::write($sql . "(82, 'now_playing_per_user', '1', 'Now Playing filtered per user', " . AccessLevelEnum::CONTENT_MANAGER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'album_sort':
                    Dba::write($sql . "(83, 'album_sort', '0', 'Album - Default sort', " . AccessLevelEnum::USER->value . ", 'string', 'interface', 'library');");
                    break;
                case 'show_played_times':
                    Dba::write($sql . "(84, 'show_played_times', '0', 'Show # played', " . AccessLevelEnum::USER->value . ", 'string', 'interface', 'browse');");
                    break;
                case 'song_page_title':
                    Dba::write($sql . "(85, 'song_page_title', '1', 'Show current song in Web player page title', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'player');");
                    break;
                case 'subsonic_backend':
                    Dba::write($sql . "(86, 'subsonic_backend', '1', 'Use Subsonic backend', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'backend');");
                    break;
                case 'webplayer_flash':
                    Dba::write($sql . "(88, 'webplayer_flash', '1', 'Authorize Flash Web Player', " . AccessLevelEnum::USER->value . ", 'boolean', 'streaming', 'player');");
                    break;
                case 'webplayer_html5':
                    Dba::write($sql . "(89, 'webplayer_html5', '1', 'Authorize HTML5 Web Player', " . AccessLevelEnum::USER->value . ", 'boolean', 'streaming', 'player');");
                    break;
                case 'allow_personal_info_now':
                    Dba::write($sql . "(90, 'allow_personal_info_now', '1', 'Share Now Playing information', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'privacy');");
                    break;
                case 'allow_personal_info_recent':
                    Dba::write($sql . "(91, 'allow_personal_info_recent', '1', 'Share Recently Played information', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'privacy');");
                    break;
                case 'allow_personal_info_time':
                    Dba::write($sql . "(92, 'allow_personal_info_time', '1', 'Share Recently Played information - Allow access to streaming date/time', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'privacy');");
                    break;
                case 'allow_personal_info_agent':
                    Dba::write($sql . "(93, 'allow_personal_info_agent', '1', 'Share Recently Played information - Allow access to streaming agent', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'privacy');");
                    break;
                case 'ui_fixed':
                    Dba::write($sql . "(94, 'ui_fixed', '0', 'Fix header position on compatible themes', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'theme');");
                    break;
                case 'autoupdate':
                    Dba::write($sql . "(95, 'autoupdate', '1', 'Check for Ampache updates automatically', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'update');");
                    break;
                case 'autoupdate_lastcheck':
                    Dba::write($sql . "(96, 'autoupdate_lastcheck', '', 'AutoUpdate last check time', " . AccessLevelEnum::USER->value . ", 'string', 'internal', 'update');");
                    break;
                case 'autoupdate_lastversion':
                    Dba::write($sql . "(97, 'autoupdate_lastversion', '', 'AutoUpdate last version from last check', " . AccessLevelEnum::USER->value . ", 'string', 'internal', 'update');");
                    break;
                case 'autoupdate_lastversion_new':
                    Dba::write($sql . "(98, 'autoupdate_lastversion_new', '', 'AutoUpdate last version from last check is newer', " . AccessLevelEnum::USER->value . ", 'boolean', 'internal', 'update');");
                    break;
                case 'webplayer_confirmclose':
                    Dba::write($sql . "(99, 'webplayer_confirmclose', '0', 'Confirmation when closing current playing window', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'player');");
                    break;
                case 'webplayer_pausetabs':
                    Dba::write($sql . "(100, 'webplayer_pausetabs', '1', 'Auto-pause between tabs', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'player');");
                    break;
                case 'stream_beautiful_url':
                    Dba::write($sql . "(101, 'stream_beautiful_url', '0', 'Enable URL Rewriting', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'streaming', NULL);");
                    break;
                case 'share':
                    Dba::write($sql . "(102, 'share', '0', 'Allow Share', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'options', 'feature');");
                    break;
                case 'share_expire':
                    Dba::write($sql . "(103, 'share_expire', '7', 'Share links default expiration days (0=never)', " . AccessLevelEnum::ADMIN->value . ", 'integer', 'system', 'share');");
                    break;
                case 'slideshow_time':
                    Dba::write($sql . "(104, 'slideshow_time', '0', 'Artist slideshow inactivity time', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'player');");
                    break;
                case 'broadcast_by_default':
                    Dba::write($sql . "(105, 'broadcast_by_default', '0', 'Broadcast web player by default', " . AccessLevelEnum::USER->value . ", 'boolean', 'streaming', 'player');");
                    break;
                case 'album_group':
                    Dba::write($sql . "(108, 'album_group', '1', 'Album - Group multiple disks', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'library');");
                    break;
                case 'topmenu':
                    Dba::write($sql . "(109, 'topmenu', '0', 'Top menu', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'theme');");
                    break;
                case 'demo_clear_sessions':
                    Dba::write($sql . "(110, 'demo_clear_sessions', '0', 'Democratic - Clear votes for expired user sessions', " . AccessLevelEnum::USER->value . ", 'boolean', 'playlist', NULL);");
                    break;
                case 'show_donate':
                    Dba::write($sql . "(111, 'show_donate', '1', 'Show donate button in footer', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', NULL);");
                    break;
                case 'upload_catalog':
                    Dba::write($sql . "(112, 'upload_catalog', '-1', 'Destination catalog', " . AccessLevelEnum::ADMIN->value . ", 'integer', 'options', 'upload');");
                    break;
                case 'allow_upload':
                    Dba::write($sql . "(113, 'allow_upload', '0', 'Allow user uploads', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'upload');");
                    break;
                case 'upload_subdir':
                    Dba::write($sql . "(114, 'upload_subdir', '1', 'Create a subdirectory per user', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'upload');");
                    break;
                case 'upload_user_artist':
                    Dba::write($sql . "(115, 'upload_user_artist', '0', 'Consider the user sender as the track\'s artist', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'upload');");
                    break;
                case 'upload_script':
                    Dba::write($sql . "(116, 'upload_script', '', 'Post-upload script (current directory = upload target directory)', " . AccessLevelEnum::ADMIN->value . ", 'string', 'system', 'upload');");
                    break;
                case 'upload_allow_edit':
                    Dba::write($sql . "(117, 'upload_allow_edit', '1', 'Allow users to edit uploaded songs', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'upload');");
                    break;
                case 'daap_backend':
                    Dba::write($sql . "(118, 'daap_backend', '0', 'Use DAAP backend', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'backend');");
                    break;
                case 'daap_pass':
                    Dba::write($sql . "(119, 'daap_pass', '', 'DAAP backend password', " . AccessLevelEnum::ADMIN->value . ", 'string', 'system', 'backend');");
                    break;
                case 'upnp_backend':
                    Dba::write($sql . "(120, 'upnp_backend', '0', 'Use UPnP backend', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'backend');");
                    break;
                case 'allow_video':
                    Dba::write($sql . "(121, 'allow_video', '0', 'Allow Video Features', " . AccessLevelEnum::MANAGER->value . ", 'integer', 'options', 'feature');");
                    break;
                case 'album_release_type':
                    Dba::write($sql . "(122, 'album_release_type', '1', 'Album - Group per release type', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'library');");
                    break;
                case 'ajax_load':
                    Dba::write($sql . "(123, 'ajax_load', '1', 'Ajax page load', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', NULL);");
                    break;
                case 'direct_play_limit':
                    Dba::write($sql . "(124, 'direct_play_limit', '0', 'Limit direct play to maximum media count', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'player');");
                    break;
                case 'home_moment_albums':
                    Dba::write($sql . "(125, 'home_moment_albums', '1', 'Show Albums of the Moment', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'home');");
                    break;
                case 'home_moment_videos':
                    Dba::write($sql . "(126, 'home_moment_videos', '0', 'Show Videos of the Moment', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'home');");
                    break;
                case 'home_recently_played':
                    Dba::write($sql . "(127, 'home_recently_played', '1', 'Show Recently Played', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'home');");
                    break;
                case 'home_now_playing':
                    Dba::write($sql . "(128, 'home_now_playing', '1', 'Show Now Playing', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'home');");
                    break;
                case 'custom_logo':
                    Dba::write($sql . "(129, 'custom_logo', '', 'Custom URL - Logo', " . AccessLevelEnum::USER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'album_release_type_sort':
                    Dba::write($sql . "(130, 'album_release_type_sort', 'album,ep,live,single', 'Album - Group per release type sort', " . AccessLevelEnum::USER->value . ", 'string', 'interface', 'library');");
                    break;
                case 'browser_notify':
                    Dba::write($sql . "(131, 'browser_notify', '1', 'Web Player browser notifications', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'notification');");
                    break;
                case 'browser_notify_timeout':
                    Dba::write($sql . "(132, 'browser_notify_timeout', '10', 'Web Player browser notifications timeout (seconds)', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'notification');");
                    break;
                case 'geolocation':
                    Dba::write($sql . "(133, 'geolocation', '0', 'Allow Geolocation', " . AccessLevelEnum::USER->value . ", 'integer', 'options', 'feature');");
                    break;
                case 'webplayer_aurora':
                    Dba::write($sql . "(134, 'webplayer_aurora', '1', 'Authorize JavaScript decoder (Aurora.js) in Web Player', " . AccessLevelEnum::USER->value . ", 'boolean', 'streaming', 'player');");
                    break;
                case 'upload_allow_remove':
                    Dba::write($sql . "(135, 'upload_allow_remove', '1', 'Allow users to remove uploaded songs', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'upload');");
                    break;
                case 'custom_login_logo':
                    Dba::write($sql . "(136, 'custom_login_logo', '', 'Custom URL - Login page logo', " . AccessLevelEnum::MANAGER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'custom_favicon':
                    Dba::write($sql . "(137, 'custom_favicon', '', 'Custom URL - Favicon', " . AccessLevelEnum::MANAGER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'custom_text_footer':
                    Dba::write($sql . "(138, 'custom_text_footer', '', 'Custom text footer', " . AccessLevelEnum::MANAGER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'webdav_backend':
                    Dba::write($sql . "(139, 'webdav_backend', '0', 'Use WebDAV backend', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'backend');");
                    break;
                case 'notify_email':
                    Dba::write($sql . "(140, 'notify_email', '0', 'Allow E-mail notifications', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', NULL);");
                    break;
                case 'theme_color':
                    Dba::write($sql . "(141, 'theme_color', 'dark', 'Theme color', " . AccessLevelEnum::DEFAULT->value . ", 'special', 'interface', 'theme');");
                    break;
                case 'disabled_custom_metadata_fields':
                    Dba::write($sql . "(142, 'disabled_custom_metadata_fields', '', 'Custom metadata - Disable these fields', " . AccessLevelEnum::ADMIN->value . ", 'string', 'system', 'metadata');");
                    break;
                case 'disabled_custom_metadata_fields_input':
                    Dba::write($sql . "(143, 'disabled_custom_metadata_fields_input', '', 'Custom metadata - Define field list', " . AccessLevelEnum::ADMIN->value . ", 'string', 'system', 'metadata');");
                    break;
                case 'podcast_keep':
                    Dba::write($sql . "(144, 'podcast_keep', '0', '# latest episodes to keep', " . AccessLevelEnum::ADMIN->value . ", 'integer', 'system', 'podcast');");
                    break;
                case 'podcast_new_download':
                    Dba::write($sql . "(145, 'podcast_new_download', '0', '# episodes to download when new episodes are available', " . AccessLevelEnum::ADMIN->value . ", 'integer', 'system', 'podcast');");
                    break;
                case 'libitem_contextmenu':
                    Dba::write($sql . "(146, 'libitem_contextmenu', '1', 'Library item context menu', " . AccessLevelEnum::DEFAULT->value . ", 'boolean', 'interface', 'library');");
                    break;
                case 'upload_catalog_pattern':
                    Dba::write($sql . "(147, 'upload_catalog_pattern', '0', 'Rename uploaded file according to catalog pattern', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'upload');");
                    break;
                case 'catalog_check_duplicate':
                    Dba::write($sql . "(148, 'catalog_check_duplicate', '0', 'Check library item at import time and disable duplicates', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'catalog');");
                    break;
                case 'browse_filter':
                    Dba::write($sql . "(149, 'browse_filter', '0', 'Show filter box on browse', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'sidebar_light':
                    Dba::write($sql . "(150, 'sidebar_light', '0', 'Light sidebar by default', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'theme');");
                    break;
                case 'custom_blankalbum':
                    Dba::write($sql . "(151, 'custom_blankalbum', '', 'Custom blank album default image', " . AccessLevelEnum::MANAGER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'libitem_browse_alpha':
                    Dba::write($sql . "(153, 'libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)', " . AccessLevelEnum::MANAGER->value . ", 'string', 'interface', 'browse');");
                    break;
                case 'show_skipped_times':
                    Dba::write($sql . "(154, 'show_skipped_times', '0', 'Show # skipped', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'custom_datetime':
                    Dba::write($sql . "(155, 'custom_datetime', '', 'Custom datetime', " . AccessLevelEnum::USER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'cron_cache':
                    Dba::write($sql . "(156, 'cron_cache', '0', 'Cache computed SQL data (eg. media hits stats) using a cron', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'catalog');");
                    break;
                case 'unique_playlist':
                    Dba::write($sql . "(157, 'unique_playlist', '0', 'Only add unique items to playlists', " . AccessLevelEnum::USER->value . ", 'boolean', 'playlist', NULL);");
                    break;
                case 'of_the_moment':
                    Dba::write($sql . "(158, 'of_the_moment', '6', 'Set the amount of items Album/Video of the Moment will display', " . AccessLevelEnum::USER->value . ", 'integer', 'interface', 'home');");
                    break;
                case 'custom_login_background':
                    Dba::write($sql . "(159, 'custom_login_background', '', 'Custom URL - Login page background', " . AccessLevelEnum::MANAGER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'show_license':
                    Dba::write($sql . "(160, 'show_license', '1', 'Show License', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'use_original_year':
                    Dba::write($sql . "(161, 'use_original_year', '0', 'Browse by Original Year for albums (falls back to Year)', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'hide_single_artist':
                    Dba::write($sql . "(162, 'hide_single_artist', '0', 'Hide the Song Artist column for Albums with one Artist', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'hide_genres':
                    Dba::write($sql . "(163, 'hide_genres', '0', 'Hide the Genre column in browse table rows', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'subsonic_always_download':
                    Dba::write($sql . "(164, 'subsonic_always_download', '0', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', 'subsonic');");
                    break;
                case 'api_enable_3':
                    Dba::write($sql . "(165, 'api_enable_3', '1', 'Allow Ampache API3 responses', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', 'ampache');");
                    break;
                case 'api_enable_4':
                    Dba::write($sql . "(166, 'api_enable_4', '1', 'Allow Ampache API3 responses', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', 'ampache');");
                    break;
                case 'api_enable_5':
                    Dba::write($sql . "(167, 'api_enable_5', '1', 'Allow Ampache API3 responses', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', 'ampache');");
                    break;
                case 'api_force_version':
                    Dba::write($sql . "(168, 'api_force_version', '0', 'Force a specific API response no matter what version you send', " . AccessLevelEnum::USER->value . ", 'special', 'options', 'ampache');");
                    break;
                case 'show_playlist_username':
                    Dba::write($sql . "(169, 'show_playlist_username', '1', 'Show playlist owner username in titles', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'api_hidden_playlists':
                    Dba::write($sql . "(170, 'api_hidden_playlists', '', 'Hide playlists in Subsonic and API clients that start with this string', " . AccessLevelEnum::USER->value . ", 'string', 'options', NULL);");
                    break;
                case 'api_hide_dupe_searches':
                    Dba::write($sql . "(171, 'api_hide_dupe_searches', '0', 'Hide smartlists that match playlist names in Subsonic and API clients', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', NULL);");
                    break;
                case 'show_album_artist':
                    Dba::write($sql . "(172, 'show_album_artist', '1', 'Show \'Album Artists\' link in the main sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'theme');");
                    break;
                case 'show_artist':
                    Dba::write($sql . "(173, 'show_artist', '0', 'Show \'Artists\' link in the main sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'theme');");
                    break;
                case 'demo_use_search':
                    Dba::write($sql . "(175, 'demo_use_search', '0', 'Democratic - Use smartlists for base playlist', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', NULL);");
                    break;
                case 'webplayer_removeplayed':
                    Dba::write($sql . "(176, 'webplayer_removeplayed', '0', 'Remove tracks before the current playlist item in the webplayer when played', " . AccessLevelEnum::USER->value . ", 'special', 'streaming', 'player');");
                    break;
                case 'api_enable_6':
                    Dba::write($sql . "(177, 'api_enable_6', '1', 'Allow Ampache API6 responses', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', NULL);");
                    break;
                case 'upload_access_level':
                    Dba::write($sql . "(178, 'upload_access_level', '25', 'Upload Access Level', " . AccessLevelEnum::ADMIN->value . ", 'special', 'system', 'upload');");
                    break;
                case 'show_subtitle':
                    Dba::write($sql . "(179, 'show_subtitle', '1', 'Show Album subtitle on links (if available)', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'show_original_year':
                    Dba::write($sql . "(180, 'show_original_year', '1', 'Show Album original year on links (if available)', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'browse');");
                    break;
                case 'show_header_login':
                    Dba::write($sql . "(181, 'show_header_login', '1', 'Show the login / registration links in the site header', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'interface');");
                    break;
                case 'use_play2':
                    Dba::write($sql . "(182, 'use_play2', '0', 'Use an alternative playback action for streaming if you have issues with playing music', " . AccessLevelEnum::USER->value . ", 'boolean', 'streaming', 'player');");
                    break;
                case 'custom_timezone':
                    Dba::write($sql . "(183, 'custom_timezone', '', 'Custom timezone (Override PHP date.timezone)', " . AccessLevelEnum::USER->value . ", 'string', 'interface', 'custom');");
                    break;
                case 'bookmark_latest':
                    Dba::write($sql . "(184, 'bookmark_latest', '0', 'Only keep the latest media bookmark', " . AccessLevelEnum::USER->value . ", 'boolean', 'options', NULL);");
                    break;
                case 'jp_volume':
                    Dba::write($sql . "(185, 'jp_volume', '0.8', 'Default webplayer volume', " . AccessLevelEnum::USER->value . ", 'special', 'streaming', 'player');");
                    break;
                case 'perpetual_api_session':
                    Dba::write($sql . "(186, 'perpetual_api_session', '0', 'API sessions do not expire', " . AccessLevelEnum::ADMIN->value . ", 'boolean', 'system', 'backend');");
                    break;
                case 'home_recently_played_all':
                    Dba::write($sql . "(187, 'home_recently_played_all', '1', 'Show all media types in Recently Played', " . AccessLevelEnum::USER->value . ", 'bool', 'interface', 'home');");
                    break;
                case 'show_wrapped':
                    Dba::write($sql . "(188, 'show_wrapped', '1', 'Enable access to your personal \"Spotify Wrapped\" from your user page', " . AccessLevelEnum::USER->value . ", 'bool', 'interface', 'privacy');");
                    break;
                case 'sidebar_hide_switcher':
                    Dba::write($sql . "(189, 'sidebar_hide_switcher', '0', 'Hide sidebar switcher arrows', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'sidebar_hide_browse':
                    Dba::write($sql . "(190, 'sidebar_hide_browse', '0', 'Hide the Browse menu in the sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'sidebar_hide_dashboard':
                    Dba::write($sql . "(191, 'sidebar_hide_dashboard', '0', 'Hide the Dashboard menu in the sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'sidebar_hide_video':
                    Dba::write($sql . "(192, 'sidebar_hide_video', '0', 'Hide the Video menu in the sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'sidebar_hide_search':
                    Dba::write($sql . "(193, 'sidebar_hide_search', '0', 'Hide the Search menu in the sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'sidebar_hide_playlist':
                    Dba::write($sql . "(194, 'sidebar_hide_playlist', '0', 'Hide the Playlist menu in the sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                case 'sidebar_hide_information':
                    Dba::write($sql . "(195, 'sidebar_hide_information', '0', 'Hide the Information menu in the sidebar', " . AccessLevelEnum::USER->value . ", 'boolean', 'interface', 'home');");
                    break;
                default:
                    // todo custom_logo_user Amapche7 prefs i haven't done yet
                    debug_event(self::class, 'ERROR: missing preference insert code for: ' . $name, 1);
            }
            Dba::write($sql);
        }
    }

    /**
     * translate_db
     * Make sure the default prefs are readable by the users
     */
    public static function translate_db(): void
    {
        $sql        = "UPDATE `preference` SET `preference`.`description` = ? WHERE `preference`.`name` = ? AND `preference`.`description` != ?;";
        $pref_array = [
            '7digital_api_key' => T_('7digital consumer key'),
            '7digital_secret_api_key' => T_('7digital secret'),
            'ajax_load' => T_('Ajax page load'),
            'album_group' => T_('Album - Group multiple disks'),
            'album_release_type_sort' => T_('Album - Group per release type sort'),
            'album_release_type' => T_('Album - Group per release type'),
            'album_sort' => T_('Album - Default sort'),
            'allow_democratic_playback' => T_('Allow Democratic Play'),
            'allow_localplay_playback' => T_('Allow Localplay Play'),
            'allow_personal_info_agent' => T_('Share Recently Played information - Allow access to streaming agent'),
            'allow_personal_info_now' => T_('Share Now Playing information'),
            'allow_personal_info_recent' => T_('Share Recently Played information'),
            'allow_personal_info_time' => T_('Share Recently Played information - Allow access to streaming date/time'),
            'allow_stream_playback' => T_('Allow Streaming'),
            'allow_upload' => T_('Allow user uploads'),
            'allow_video' => T_('Allow Video Features'),
            'amazon_base_url' => T_('Amazon base url'),
            'amazon_developer_associate_tag' => T_('Amazon associate tag'),
            'amazon_developer_private_api_key' => T_('Amazon Secret Access Key'),
            'amazon_developer_public_key' => T_('Amazon Access Key ID'),
            'amazon_max_results_pages' => T_('Amazon max results pages'),
            'api_enable_3' => T_('Allow Ampache API3 responses'),
            'api_enable_4' => T_('Allow Ampache API4 responses'),
            'api_enable_5' => T_('Allow Ampache API5 responses'),
            'api_enable_6' => T_('Allow Ampache API6 responses'),
            'api_force_version' => T_('Force a specific API response no matter what version you send'),
            'api_hidden_playlists' => T_('Hide playlists in Subsonic and API clients that start with this string'),
            'api_hide_dupe_searches' => T_('Hide smartlists that match playlist names in Subsonic and API clients'),
            'autoupdate_lastcheck' => T_('AutoUpdate last check time'),
            'autoupdate_lastversion_new' => T_('AutoUpdate last version from last check is newer'),
            'autoupdate_lastversion' => T_('AutoUpdate last version from last check'),
            'autoupdate' => T_('Check for Ampache updates automatically'),
            'bitly_api_key' => T_('Bit.ly API key'),
            'bitly_username' => T_('Bit.ly Username'),
            'bookmark_latest' => T_('Only keep the latest media bookmark'),
            'broadcast_by_default' => T_('Broadcast web player by default'),
            'browse_filter' => T_('Show filter box on browse'),
            'browser_notify_timeout' => T_('Web Player browser notifications timeout (seconds)'),
            'browser_notify' => T_('Web Player browser notifications'),
            'catalog_check_duplicate' => T_('Check library item at import time and disable duplicates'),
            'catalogfav_gridview' => T_('Catalog favorites grid view display'),
            'catalogfav_max_items' => T_('Catalog favorites max items'),
            'catalogfav_order' => T_('Plugin CSS order'),
            'cron_cache' => T_('Cache computed SQL data (eg. media hits stats) using a cron'),
            'custom_blankalbum' => T_('Custom blank album default image'),
            'custom_datetime' => T_('Custom datetime'),
            'custom_favicon' => T_('Custom URL - Favicon'),
            'custom_login_background' => T_('Custom URL - Login page background'),
            'custom_login_logo' => T_('Custom URL - Login page logo'),
            'custom_logo' => T_('Custom URL - Logo'),
            'custom_logo_user' => T_('Custom URL - Use your avatar for header logo'),
            'custom_text_footer' => T_('Custom text footer'),
            'custom_timezone' => T_('Custom timezone (Override PHP date.timezone)'),
            'daap_backend' => T_('Use DAAP backend'),
            'daap_pass' => T_('DAAP backend password'),
            'demo_clear_sessions' => T_('Democratic - Clear votes for expired user sessions'),
            'demo_use_search' => T_('Democratic - Use smartlists for base playlist'),
            'direct_play_limit' => T_('Limit direct play to maximum media count'),
            'disabled_custom_metadata_fields_input' => T_('Custom metadata - Define field list'),
            'disabled_custom_metadata_fields' => T_('Custom metadata - Disable these fields'),
            'discogs_api_key' => T_('Discogs consumer key'),
            'discogs_secret_api_key' => T_('Discogs secret'),
            'download' => T_('Allow Downloads'),
            'flickr_api_key' => T_('Flickr API key'),
            'force_http_play' => T_('Force HTTP playback regardless of port'),
            'ftl_max_items' => T_('Friends timeline max items'),
            'ftl_order' => T_('Plugin CSS order'),
            'geolocation' => T_('Allow Geolocation'),
            'gmaps_api_key' => T_('Google Maps API key'),
            'googleanalytics_tracking_id' => T_('Google Analytics Tracking ID'),
            'headphones_api_key' => T_('Headphones API key'),
            'headphones_api_url' => T_('Headphones URL'),
            'hide_genres' => T_('Hide the Genre column in browse table rows'),
            'hide_single_artist' => T_('Hide the Song Artist column for Albums with one Artist'),
            'home_moment_albums' => T_('Show Albums of the Moment'),
            'home_moment_videos' => T_('Show Videos of the Moment'),
            'home_now_playing' => T_('Show Now Playing'),
            'home_recently_played' => T_('Show Recently Played'),
            'home_recently_played_all' => T_('Show all media types in Recently Played'),
            'homedash_max_items' => T_('Home Dashboard max items'),
            'homedash_random' => T_('Random'),
            'homedash_newest' => T_('Newest'),
            'homedash_recent' => T_('Recent'),
            'homedash_trending' => T_('Trending'),
            'homedash_popular' => T_('Popular'),
            'homedash_order' => T_('Plugin CSS order'),
            'httpq_active' => T_('HTTPQ Active Instance'),
            'index_dashboard_form' => T_('Use Dashboard links for the index page header'),
            'jp_volume' => T_('Default webplayer volume'),
            'lang' => T_('Language'),
            'lastfm_challenge' => T_('Last.FM Submit Challenge'),
            'lastfm_grant_link' => T_('Last.FM Grant URL'),
            'libitem_browse_alpha' => T_('Alphabet browsing by default for following library items (album,artist,...)'),
            'libitem_contextmenu' => T_('Library item context menu'),
            'librefm_challenge' => T_('Libre.FM Submit Challenge'),
            'listenbrainz_token' => T_('ListenBrainz User Token'),
            'localplay_controller' => T_('Localplay Type'),
            'localplay_level' => T_('Localplay Access'),
            'lock_songs' => T_('Lock Songs'),
            'matomo_site_id' => T_('Matomo Site ID'),
            'matomo_url' => T_('Matomo URL'),
            'mb_overwrite_name' => T_('Overwrite Artist names that match an mbid'),
            'mpd_active' => T_('MPD Active Instance'),
            'notify_email' => T_('Allow E-mail notifications'),
            'now_playing_per_user' => T_('Now Playing filtered per user'),
            'offset_limit' => T_('Offset Limit'),
            'of_the_moment' => T_('Set the amount of items Album/Video of the Moment will display'),
            'paypal_business' => T_('PayPal ID'),
            'paypal_currency_code' => T_('PayPal Currency Code'),
            'perpetual_api_session' => T_('API sessions do not expire'),
            'personalfav_display' => T_('Personal favorites on the homepage'),
            'personalfav_playlist' => T_('Favorite Playlists'),
            'personalfav_smartlist' => T_('Favorite Smartlists'),
            'personalfav_order' => T_('Plugin CSS order'),
            'piwik_site_id' => T_('Piwik Site ID'),
            'piwik_url' => T_('Piwik URL'),
            'playlist_method' => T_('Playlist Method'),
            'playlist_type' => T_('Playlist Type'),
            'play_type' => T_('Playback Type'),
            'podcast_keep' => T_('# latest episodes to keep'),
            'podcast_new_download' => T_('# episodes to download when new episodes are available'),
            'popular_threshold' => T_('Popular Threshold'),
            'rate_limit' => T_('Rate Limit'),
            'ratingmatch_flag_rule' => T_('Match rule for Flags'),
            'ratingmatch_flags' => T_('When you love a track, flag the album and artist'),
            'ratingmatch_star1_rule' => T_('Match rule for 1 Star ($play,$skip)'),
            'ratingmatch_star2_rule' => T_('Match rule for 2 Stars'),
            'ratingmatch_star3_rule' => T_('Match rule for 3 Stars'),
            'ratingmatch_star4_rule' => T_('Match rule for 4 Stars'),
            'ratingmatch_star5_rule' => T_('Match rule for 5 Stars'),
            'ratingmatch_stars' => T_('Minimum star rating to match'),
            'rssview_feed_url' => T_('RSS Feed URL'),
            'rssview_max_items' => T_('RSS Feed max items'),
            'rssview_order' => T_('Plugin CSS order'),
            'share_expire' => T_('Share links default expiration days (0=never)'),
            'share' => T_('Allow Share'),
            'shouthome_max_items' => T_('Shoutbox on homepage max items'),
            'shouthome_order' => T_('Plugin CSS order'),
            'show_album_artist' => T_("Show 'Album Artists' link in the main sidebar"),
            'show_artist' => T_("Show 'Artists' link in the main sidebar"),
            'show_donate' => T_('Show donate button in footer'),
            'show_header_login' => T_('Show the login / registration links in the site header'),
            'show_license' => T_('Show License'),
            'show_lyrics' => T_('Show lyrics'),
            'show_original_year' => T_('Show Album original year on links (if available)'),
            'show_played_times' => T_('Show # played'),
            'show_playlist_username' => T_('Show playlist owner username in titles'),
            'show_skipped_times' => T_('Show # skipped'),
            'show_subtitle' => T_('Show Album subtitle on links (if available)'),
            'show_wrapped' => T_('Enable access to your personal "Spotify Wrapped" from your user page'),
            'sidebar_light' => T_('Light sidebar by default'),
            'sidebar_hide_browse' => T_('Hide the Browse menu in the sidebar'),
            'sidebar_hide_dashboard' => T_('Hide the Dashboard menu in the sidebar'),
            'sidebar_hide_information' => T_('Hide the Information menu in the sidebar'),
            'sidebar_hide_playlist' => T_('Hide the Playlist menu in the sidebar'),
            'sidebar_hide_search' => T_('Hide the Search menu in the sidebar'),
            'sidebar_hide_switcher' => T_('Hide sidebar switcher arrows'),
            'sidebar_hide_video' => T_('Hide the Video menu in the sidebar'),
            'sidebar_order_browse' => T_('Custom CSS Order - Browse'),
            'sidebar_order_dashboard' => T_('Custom CSS Order - Dashboard'),
            'sidebar_order_information' => T_('Custom CSS Order - Information'),
            'sidebar_order_playlist' => T_('Custom CSS Order - Playlist'),
            'sidebar_order_search' => T_('Custom CSS Order - Search'),
            'sidebar_order_video' => T_('Custom CSS Order - Video'),
            'site_title' => T_('Website Title'),
            'slideshow_time' => T_('Artist slideshow inactivity time'),
            'song_page_title' => T_('Show current song in Web player page title'),
            'stats_threshold' => T_('Statistics Day Threshold'),
            'stream_beautiful_url' => T_('Enable URL Rewriting'),
            'stream_control_bandwidth_days' => T_('Stream control bandwidth history (days)'),
            'stream_control_bandwidth_max' => T_('Stream control maximal bandwidth (month)'),
            'stream_control_hits_days' => T_('Stream control hits history (days)'),
            'stream_control_hits_max' => T_('Stream control maximal hits'),
            'stream_control_time_days' => T_('Stream control time history (days)'),
            'stream_control_time_max' => T_('Stream control maximal time (minutes)'),
            'subsonic_always_download' => T_('Force Subsonic streams to download. (Enable scrobble in your client to record stats)'),
            'subsonic_backend' => T_('Use Subsonic backend'),
            'tadb_api_key' => T_('TheAudioDb API key'),
            'tadb_overwrite_name' => T_('Overwrite Artist names that match an mbid'),
            'theme_color' => T_('Theme color'),
            'theme_name' => T_('Theme'),
            'topmenu' => T_('Top menu'),
            'transcode_bitrate' => T_('Transcode Bitrate'),
            'transcode' => T_('Allow Transcoding'),
            'ui_fixed' => T_('Fix header position on compatible themes'),
            'unique_playlist' => T_('Only add unique items to playlists'),
            'upload_access_level' => T_('Upload Access Level'),
            'upload_allow_edit' => T_('Allow users to edit uploaded songs'),
            'upload_allow_remove' => T_('Allow users to remove uploaded songs'),
            'upload_catalog_pattern' => T_('Rename uploaded file according to catalog pattern'),
            'upload_catalog' => T_('Destination catalog'),
            'upload_script' => T_('Post-upload script (current directory = upload target directory)'),
            'upload_subdir' => T_('Create a subdirectory per user'),
            'upload_user_artist' => T_("Consider the user sender as the track's artist"),
            'upnp_active' => T_('UPnP Active Instance'),
            'upnp_backend' => T_('Use UPnP backend'),
            'use_original_year' => T_('Browse by Original Year for albums (falls back to Year)'),
            'use_play2' => T_('Use an alternative playback action for streaming if you have issues with playing music'),
            'vlc_active' => T_('VLC Active Instance'),
            'webdav_backend' => T_('Use WebDAV backend'),
            'webplayer_aurora' => T_('Authorize JavaScript decoder (Aurora.js) in Web Player'),
            'webplayer_confirmclose' => T_('Confirmation when closing current playing window'),
            'webplayer_flash' => T_('Authorize Flash Web Player'),
            'webplayer_html5' => T_('Authorize HTML5 Web Player'),
            'webplayer_pausetabs' => T_('Auto-pause between tabs'),
            'webplayer_removeplayed' => T_('Remove tracks before the current playlist item in the webplayer when played'),
            'xbmc_active' => T_('XBMC Active Instance'),
            'yourls_api_key' => T_('YOURLS API key'),
            'yourls_domain' => T_('YOURLS domain name'),
            'yourls_use_idn' => T_('YOURLS use IDN'),
        ];
        foreach ($pref_array as $key => $value) {
            Dba::write($sql, [$value, $key, $value]);
        }
    }

    /**
     * load_from_session
     * This loads the preferences from the session rather then creating a connection to the database
     * @param int $uid
     */
    public static function load_from_session($uid = -1): bool
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
     * clear_from_session
     * This clears the users preferences, this is done whenever modifications are made to the preferences
     * or the admin resets something
     */
    public static function clear_from_session(): void
    {
        if (isset($_SESSION) && array_key_exists('userdata', $_SESSION) && array_key_exists('preferences', $_SESSION['userdata'])) {
            unset($_SESSION['userdata']['preferences']);
        }
    }

    /**
     * is_boolean
     * This returns true / false if the preference in question is a boolean preference
     * This is currently only used by the debug view, could be used other places.. wouldn't be a half
     * bad idea
     * @param string $key
     */
    public static function is_boolean($key): bool
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
            'allow_upload',
            'allow_upload_scripts',
            'allow_video',
            'allow_zip_download',
            'api_enable_3',
            'api_enable_4',
            'api_enable_5',
            'api_enable_6',
            'api_hide_dupe_searches',
            'art_zip_add',
            'auth_password_save',
            'auto_create',
            'autoupdate',
            'autoupdate_lastversion_new',
            'bookmark_latest',
            'broadcast',
            'broadcast_by_default',
            'browse_filter',
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
            'catalogfav_gridview',
            'catalog_filter',
            'catalog_verify_by_time',
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
            'external_auto_update',
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
            'home_recently_played',
            'home_recently_played_all',
            'homedash_max_items',
            'homedash_random',
            'homedash_newest',
            'homedash_recent',
            'homedash_trending',
            'homedash_popular',
            'httpq_active',
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
            'mpd_active',
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
            'share',
            'share_social',
            'show_album_artist',
            'show_artist',
            'show_donate',
            'show_footer_statistics',
            'show_header_login',
            'show_license',
            'show_lyrics',
            'show_original_year',
            'show_played_times',
            'show_playlist_username',
            'show_similar',
            'show_skipped_times',
            'show_song_art',
            'show_subtitle',
            'show_wrapped',
            'sidebar_light',
            'sidebar_hide_browse',
            'sidebar_hide_dashboard',
            'sidebar_hide_information',
            'sidebar_hide_playlist',
            'sidebar_hide_search',
            'sidebar_hide_switcher',
            'sidebar_hide_video',
            'simple_user_mode',
            'sociable',
            'song_page_title',
            'statistical_graphs',
            'stream_beautiful_url',
            'subsonic_always_download',
            'subsonic_backend',
            'tadb_overwrite_name',
            'topmenu',
            'track_user_ip',
            'transcode_player_customize',
            'ui_fixed',
            'unique_playlist',
            'upload',
            'upload_allow_edit',
            'upload_allow_remove',
            'upload_catalog_pattern',
            'upload_script',
            'upload_subdir',
            'upload_user_artist',
            'upnp_active',
            'upnp_backend',
            'use_auth',
            'use_now_playing_embedded',
            'use_original_year',
            'use_play2',
            'user_agreement',
            'user_create_streamtoken',
            'user_no_email_confirm',
            'use_rss',
            'wanted',
            'wanted_auto_accept',
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
        $user_id = $user->id ?? -1;

        // First go ahead and try to load it from the preferences
        if (self::load_from_session($user_id)) {
            return true;
        }

        /* Get Global Preferences */
        $sql        = "SELECT `preference`.`name`, `user_preference`.`value`, `syspref`.`value` AS `system_value` FROM `preference` LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`category`='system' LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user` = ? AND `preference`.`category` !='system'";
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
        if (!isset($results['theme_name'])) {
            $results['theme_name'] = 'reborn';
        }

        $results['theme_path'] = '/themes/' . $results['theme_name'];

        // Load theme settings
        $theme_cfg                 = get_theme($results['theme_name']);
        $results['theme_css_base'] = $theme_cfg['base'];

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
            $results['theme_color'] = strtolower((string)$theme_cfg['colors'][0]);
        }

        AmpConfig::set_by_array($results, true);
        $_SESSION['userdata']['preferences'] = $results;
        $_SESSION['userdata']['uid']         = $user_id;

        return true;
    }
}
