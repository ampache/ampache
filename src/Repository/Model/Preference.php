<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Repository\Model;

use Ampache\Module\Authorization\Access;
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
    public const SYSTEM_LIST = array(
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
        'custom_blankmovie',
        'custom_datetime',
        'custom_favicon',
        'custom_login_background',
        'custom_login_logo',
        'custom_logo',
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
        'httpq_active',
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
        'show_license',
        'show_lyrics',
        'show_played_times',
        'show_playlist_username',
        'show_skipped_times',
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
        'upnp_active',
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
        // plugin preferences might not be there but they need to be kept if you're using them
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
        'discogs_api_key',
        'discogs_secret_api_key',
        'flattr_user_id',
        'flickr_api_key',
        'ftl_max_items',
        'gmaps_api_key',
        'googleanalytics_tracking_id',
        'headphones_api_key',
        'headphones_api_url',
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
        'shouthome_max_items',
        'stream_control_bandwidth_days',
        'stream_control_bandwidth_max',
        'stream_control_hits_days',
        'stream_control_hits_max',
        'stream_control_time_days',
        'stream_control_time_max',
        'tadb_api_key',
        'tadb_overwrite_name',
        'tvdb_api_key',
        'yourls_api_key',
        'yourls_domain',
        'yourls_use_idn'
    );

    /**
     * __constructor
     * This does nothing... amazing isn't it!
     */
    private function __construct()
    {
        // Rien a faire
    } // __construct

    /**
     * get_by_user
     * Return a preference for specific user identifier
     * @param int $user_id
     * @param string $pref_name
     * @return int|string
     */
    public static function get_by_user($user_id, $pref_name)
    {
        //debug_event(self::class, 'Getting preference {' . $pref_name . '} for user identifier {' . $user_id . '}...', 5);
        $user_id   = (int) Dba::escape($user_id);
        $pref_name = Dba::escape($pref_name);
        $pref_id   = self::id_from_name($pref_name);

        if (parent::is_cached('get_by_user-' . $pref_name, $user_id) && is_array((parent::get_from_cache('get_by_user-' . $pref_name, $user_id)))) {
            return (parent::get_from_cache('get_by_user-' . $pref_name, $user_id))['value'];
        }

        $sql        = "SELECT `value` FROM `user_preference` WHERE `preference`='$pref_id' AND `user`='$user_id'";
        $db_results = Dba::read($sql);
        if (Dba::num_rows($db_results) < 1) {
            $sql        = "SELECT `value` FROM `user_preference` WHERE `preference`='$pref_id' AND `user`='-1'";
            $db_results = Dba::read($sql);
        }
        $data = Dba::fetch_assoc($db_results);

        parent::add_to_cache('get_by_user-' . $pref_name, $user_id, $data);

        return $data['value'] ?? '';
    } // get_by_user

    /**
     * update
     * This updates a single preference from the given name or id
     * @param string $preference
     * @param int $user_id
     * @param array|string $value
     * @param bool $applytoall
     * @param bool $applytodefault
     * @return bool
     */
    public static function update($preference, $user_id, $value, $applytoall = false, $applytodefault = false)
    {
        $access100 = Access::check('interface', 100);
        // First prepare
        if (!is_numeric($preference)) {
            $pref_id = self::id_from_name($preference);
            $name    = $preference;
        } else {
            $pref_id = $preference;
            $name    = self::name_from_id($preference);
        }
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $params = array($value, $pref_id);

        if ($applytoall && $access100) {
            $user_check = "";
        } else {
            $user_check = "AND `user` = ?";
            $params[]   = $user_id;
        }

        if ($applytodefault && $access100) {
            $sql = "UPDATE `preference` SET `value` = ? WHERE `id`= ?";
            Dba::write($sql, $params);
        }

        if (self::has_access($name)) {
            $sql = "UPDATE `user_preference` SET `value` = ? WHERE `preference` = ? $user_check";
            Dba::write($sql, $params);
            self::clear_from_session();

            parent::remove_from_cache('get_by_user', $user_id);

            return true;
        } else {
            debug_event(self::class, Core::get_global('user') ? Core::get_global('user')->username : '???' . ' attempted to update ' . $name . ' but does not have sufficient permissions', 3);
        }

        return false;
    } // update

    /**
     * update_level
     * This takes a preference ID and updates the level required to update it (performed by an admin)
     * @param $preference
     * @param $level
     * @return bool
     */
    public static function update_level($preference, $level)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $preference_id = self::id_from_name($preference);
        } else {
            $preference_id = $preference;
        }

        $preference_id = Dba::escape($preference_id);
        $level         = Dba::escape($level);

        $sql = "UPDATE `preference` SET `level`='$level' WHERE `id`='$preference_id'";
        Dba::write($sql);

        return true;
    } // update_level

    /**
     * update_all
     * This takes a preference id and a value and updates all users with the new info
     * @param int $preference_id
     * @param string $value
     * @return bool
     */
    public static function update_all($preference_id, $value)
    {
        if ((int)$preference_id == 0) {
            return false;
        }
        $preference_id = Dba::escape($preference_id);
        $value         = Dba::escape($value);

        $sql = "UPDATE `user_preference` SET `value` = ? WHERE `preference` = ?";
        Dba::write($sql, array($value, $preference_id));

        parent::clear_cache();

        return true;
    } // update_all

    /**
     * exists
     * This just checks to see if a preference currently exists
     * @param string $preference
     * @return int
     */
    public static function exists($preference)
    {
        // We assume it's the name
        $name       = Dba::escape($preference);
        $sql        = "SELECT * FROM `preference` WHERE `name`='$name'";
        $db_results = Dba::read($sql);

        return Dba::num_rows($db_results);
    } // exists

    /**
     * has_access
     * This checks to see if the current user has access to modify this preference
     * as defined by the preference name
     * @param $preference
     * @return bool
     */
    public static function has_access($preference)
    {
        // Nothing for those demo thugs
        if (AmpConfig::get('demo_mode')) {
            return false;
        }

        $preference = Dba::escape($preference);

        $sql        = "SELECT `level` FROM `preference` WHERE `name`='$preference'";
        $db_results = Dba::read($sql);
        $data       = Dba::fetch_assoc($db_results);

        if (Access::check('interface', $data['level'])) {
            return true;
        }

        return false;
    } // has_access

    /**
     * id_from_name
     * This takes a name and returns the id
     * @param string $name
     * @return int|false
     */
    public static function id_from_name($name)
    {
        $name = Dba::escape($name);

        if (parent::is_cached('id_from_name', $name)) {
            return (int)(parent::get_from_cache('id_from_name', $name))[0];
        }

        $sql        = "SELECT `id` FROM `preference` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($name));
        $results    = Dba::fetch_assoc($db_results);
        if (array_key_exists('id', $results)) {
            parent::add_to_cache('id_from_name', $name, array($results['id']));

            return (int)$results['id'];
        }

        return false;
    } // id_from_name

    /**
     * name_from_id
     * This returns the name from an id, it's the exact opposite
     * of the function above it, amazing!
     * @param $pref_id
     * @return mixed
     */
    public static function name_from_id($pref_id)
    {
        $pref_id = Dba::escape($pref_id);

        $sql        = "SELECT `name` FROM `preference` WHERE `id`='$pref_id'";
        $db_results = Dba::read($sql);

        $row = Dba::fetch_assoc($db_results);

        return $row['name'];
    } // name_from_id

    /**
     * get_categories
     * This returns an array of the names of the different possible sections
     * it ignores the 'internal' category
     */
    public static function get_categories()
    {
        $sql = "SELECT `preference`.`catagory` FROM `preference` GROUP BY `catagory` ORDER BY `catagory`";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['catagory'] != 'internal') {
                $results[] = $row['catagory'];
            }
        } // end while

        return $results;
    } // get_categories

    /**
     * get_all
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @param int $user_id
     * @return array
     */
    public static function get_all($user_id)
    {
        $user_id    = Dba::escape($user_id);
        $user_limit = ($user_id != -1) ? "AND `preference`.`catagory` != 'system'" : "";

        $sql = "SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`, `preference`.`type`, `preference`.`catagory`, `preference`.`subcatagory`, `user_preference`.`value` FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` WHERE `user_preference`.`user` = ? AND `preference`.`catagory` != 'internal' $user_limit ORDER BY `preference`.`subcatagory`, `preference`.`description`";

        $db_results = Dba::read($sql, array($user_id));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['catagory'],
                'subcategory' => $row['subcatagory']
            );
        }

        return $results;
    } // get_all

    /**
     * get
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @param string $pref_name
     * @param int $user_id
     * @return array
     */
    public static function get($pref_name, $user_id)
    {
        $user_id    = Dba::escape($user_id);
        $user_limit = ($user_id != -1) ? "AND `preference`.`catagory` != 'system'" : "";

        $sql = "SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`, `preference`.`type`, `preference`.`catagory`, `preference`.`subcatagory`, `user_preference`.`value` FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` WHERE `preference`.`name` = ? AND `user_preference`.`user`= ? AND `preference`.`catagory` != 'internal' $user_limit ORDER BY `preference`.`subcatagory`, `preference`.`description`";

        $db_results = Dba::read($sql, array($pref_name, $user_id));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['catagory'],
                'subcategory' => $row['subcatagory']
            );
        }

        return $results;
    } // get

    /**
     * insert
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     * @param string $name
     * @param string $description
     * @param string|int $default
     * @param int $level
     * @param string $type
     * @param string $category
     * @param string $subcategory
     * @return bool
     */
    public static function insert($name, $description, $default, $level, $type, $category, $subcategory = null)
    {
        if ($subcategory !== null) {
            $subcategory = strtolower((string)$subcategory);
        }
        $sql        = "INSERT INTO `preference` (`name`, `description`, `value`, `level`, `type`, `catagory`, `subcatagory`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($name, $description, $default, (int) $level, $type, $category, $subcategory));

        if (!$db_results) {
            return false;
        }
        $pref_id    = Dba::insert_id();
        $params     = array($pref_id, $default);
        $sql        = "INSERT INTO `user_preference` VALUES (-1, ?, ?)";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }
        if ($category !== "system") {
            $sql        = "INSERT INTO `user_preference` SELECT `user`.`id`, ?, ? FROM `user`";
            $db_results = Dba::write($sql, $params);
            if (!$db_results) {
                return false;
            }
        }
        debug_event(self::class, 'Inserted preference: ' . $name, 3);

        return true;
    } // insert

    /**
     * delete
     * This deletes the specified preference, a name or a ID can be passed
     * @param string|int $preference
     */
    public static function delete($preference)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $sql = "DELETE FROM `preference` WHERE `name` = ?";
        } else {
            $sql = "DELETE FROM `preference` WHERE `id` = ?";
        }

        Dba::write($sql, array($preference));

        self::clean_preferences();
    } // delete

    /**
     * rename
     * This renames a preference in the database
     * @param $old
     * @param $new
     */
    public static function rename($old, $new)
    {
        $sql = "UPDATE `preference` SET `name` = ? WHERE `name` = ?";
        Dba::write($sql, array($new, $old));
    }

    /**
     * clean_preferences
     * This removes any garbage
     */
    public static function clean_preferences()
    {
        // First remove garbage
        $sql = "DELETE FROM `user_preference` USING `user_preference` LEFT JOIN `preference` ON `preference`.`id`=`user_preference`.`preference` WHERE `preference`.`id` IS NULL";
        Dba::write($sql);
    } // rebuild_preferences

    /**
     * fix_preferences
     * This takes the preferences, explodes what needs to
     * become an array and boolean everything
     * @param $results
     * @return array
     */
    public static function fix_preferences($results)
    {
        $arrays = array(
            'allow_zip_types',
            'art_order',
            'auth_methods',
            'getid3_tag_order',
            'metadata_order',
            'metadata_order_video',
            'registration_display_fields',
            'registration_mandatory_fields',
            'wanted_types'
        );

        foreach ($arrays as $item) {
            $results[$item] = (array_key_exists($item, $results) && trim((string)$results[$item]))
                ? explode(',', $results[$item])
                : array();
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
    } // fix_preferences

    /**
     * set_defaults
     * Make sure the default prefs are set! (taken from the default DB file `resources/sql/ampache.sql`)
     */
    public static function set_defaults()
    {
        $sql = "INSERT IGNORE INTO `preference` (`id`, `name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES " .
            "(1, 'download', '1', 'Allow Downloads', 100, 'boolean', 'options', 'feature'), " .
            "(4, 'popular_threshold', '10', 'Popular Threshold', 25, 'integer', 'interface', 'query'), " .
            "(19, 'transcode_bitrate', '128', 'Transcode Bitrate', 25, 'string', 'streaming', 'transcoding'), " .
            "(22, 'site_title', 'Ampache :: For the Love of Music', 'Website Title', 100, 'string', 'interface', 'custom'), " .
            "(23, 'lock_songs', '0', 'Lock Songs', 100, 'boolean', 'system', null), " .
            "(24, 'force_http_play', '0', 'Force HTTP playback regardless of port', 100, 'boolean', 'system', null), " .
            "(29, 'play_type', 'web_player', 'Playback Type', 25, 'special', 'streaming', null), " .
            "(31, 'lang', 'en_US', 'Language', 100, 'special', 'interface', null), " .
            "(32, 'playlist_type', 'm3u', 'Playlist Type', 100, 'special', 'playlist', null), " .
            "(33, 'theme_name', 'reborn', 'Theme', 0, 'special', 'interface', 'theme'), " .
            "(40, 'localplay_level', '0', 'Localplay Access', 100, 'special', 'options', 'localplay'), " .
            "(41, 'localplay_controller', '0', 'Localplay Type', 100, 'special', 'options', 'localplay'), " .
            "(44, 'allow_stream_playback', '1', 'Allow Streaming', 100, 'boolean', 'options', 'feature'), " .
            "(45, 'allow_democratic_playback', '0', 'Allow Democratic Play', 100, 'boolean', 'options', 'feature'), " .
            "(46, 'allow_localplay_playback', '0', 'Allow Localplay Play', 100, 'boolean', 'options', 'localplay'), " .
            "(47, 'stats_threshold', '7', 'Statistics Day Threshold', 25, 'integer', 'interface', 'query'), " .
            "(51, 'offset_limit', '50', 'Offset Limit', 5, 'integer', 'interface', 'query'), " .
            "(52, 'rate_limit', '8192', 'Rate Limit', 100, 'integer', 'streaming', 'transcoding'), " .
            "(53, 'playlist_method', 'default', 'Playlist Method', 5, 'string', 'playlist', null), " .
            "(55, 'transcode', 'default', 'Allow Transcoding', 25, 'string', 'streaming', 'transcoding'), " .
            "(69, 'show_lyrics', '0', 'Show lyrics', 0, 'boolean', 'interface', 'player'), " .
            "(70, 'mpd_active', '0', 'MPD Active Instance', 25, 'integer', 'internal', 'mpd'), " .
            "(71, 'httpq_active', '0', 'httpQ Active Instance', 25, 'integer', 'internal', 'httpq'), " .
            "(77, 'lastfm_grant_link', '', 'Last.FM Grant URL', 25, 'string', 'internal', 'lastfm'), " .
            "(78, 'lastfm_challenge', '', 'Last.FM Submit Challenge', 25, 'string', 'internal', 'lastfm'), " .
            "(82, 'now_playing_per_user', '1', 'Now Playing filtered per user', 50, 'boolean', 'interface', 'home'), " .
            "(83, 'album_sort', '0', 'Album - Default sort', 25, 'string', 'interface', 'library'), " .
            "(84, 'show_played_times', '0', 'Show # played', 25, 'string', 'interface', 'browse'), " .
            "(85, 'song_page_title', '1', 'Show current song in Web player page title', 25, 'boolean', 'interface', 'player'), " .
            "(86, 'subsonic_backend', '1', 'Use Subsonic backend', 100, 'boolean', 'system', 'backend'), " .
            "(88, 'webplayer_flash', '1', 'Authorize Flash Web Player', 25, 'boolean', 'streaming', 'player'), " .
            "(89, 'webplayer_html5', '1', 'Authorize HTML5 Web Player', 25, 'boolean', 'streaming', 'player'), " .
            "(90, 'allow_personal_info_now', '1', 'Share Now Playing information', 25, 'boolean', 'interface', 'privacy'), " .
            "(91, 'allow_personal_info_recent', '1', 'Share Recently Played information', 25, 'boolean', 'interface', 'privacy'), " .
            "(92, 'allow_personal_info_time', '1', 'Share Recently Played information - Allow access to streaming date/time', 25, 'boolean', 'interface', 'privacy'), " .
            "(93, 'allow_personal_info_agent', '1', 'Share Recently Played information - Allow access to streaming agent', 25, 'boolean', 'interface', 'privacy'), " .
            "(94, 'ui_fixed', '0', 'Fix header position on compatible themes', 25, 'boolean', 'interface', 'theme'), " .
            "(95, 'autoupdate', '1', 'Check for Ampache updates automatically', 100, 'boolean', 'system', 'update'), " .
            "(96, 'autoupdate_lastcheck', '', 'AutoUpdate last check time', 25, 'string', 'internal', 'update'), " .
            "(97, 'autoupdate_lastversion', '', 'AutoUpdate last version from last check', 25, 'string', 'internal', 'update'), " .
            "(98, 'autoupdate_lastversion_new', '', 'AutoUpdate last version from last check is newer', 25, 'boolean', 'internal', 'update'), " .
            "(99, 'webplayer_confirmclose', '0', 'Confirmation when closing current playing window', 25, 'boolean', 'interface', 'player'), " .
            "(100, 'webplayer_pausetabs', '1', 'Auto-pause between tabs', 25, 'boolean', 'interface', 'player'), " .
            "(101, 'stream_beautiful_url', '0', 'Enable URL Rewriting', 100, 'boolean', 'streaming', null), " .
            "(102, 'share', '0', 'Allow Share', 100, 'boolean', 'options', 'feature'), " .
            "(103, 'share_expire', '7', 'Share links default expiration days (0=never)', 100, 'integer', 'system', 'share'), " .
            "(104, 'slideshow_time', '0', 'Artist slideshow inactivity time', 25, 'integer', 'interface', 'player'), " .
            "(105, 'broadcast_by_default', '0', 'Broadcast web player by default', 25, 'boolean', 'streaming', 'player'), " .
            "(108, 'album_group', '1', 'Album - Group multiple disks', 25, 'boolean', 'interface', 'library'), " .
            "(109, 'topmenu', '0', 'Top menu', 25, 'boolean', 'interface', 'theme'), " .
            "(110, 'demo_clear_sessions', '0', 'Democratic - Clear votes for expired user sessions', 25, 'boolean', 'playlist', null), " .
            "(111, 'show_donate', '1', 'Show donate button in footer', 25, 'boolean', 'interface', null), " .
            "(112, 'upload_catalog', '-1', 'Destination catalog', 100, 'integer', 'system', 'upload'), " .
            "(113, 'allow_upload', '0', 'Allow user uploads', 100, 'boolean', 'system', 'upload'), " .
            "(114, 'upload_subdir', '1', 'Create a subdirectory per user', 100, 'boolean', 'system', 'upload'), " .
            "(115, 'upload_user_artist', '0', 'Consider the user sender as the track\'s artist', 100, 'boolean', 'system', 'upload'), " .
            "(116, 'upload_script', '', 'Post-upload script (current directory = upload target directory)', 100, 'string', 'system', 'upload'), " .
            "(117, 'upload_allow_edit', '1', 'Allow users to edit uploaded songs', 100, 'boolean', 'system', 'upload'), " .
            "(118, 'daap_backend', '0', 'Use DAAP backend', 100, 'boolean', 'system', 'backend'), " .
            "(119, 'daap_pass', '', 'DAAP backend password', 100, 'string', 'system', 'backend'), " .
            "(120, 'upnp_backend', '0', 'Use UPnP backend', 100, 'boolean', 'system', 'backend'), " .
            "(121, 'allow_video', '0', 'Allow Video Features', 75, 'integer', 'options', 'feature'), " .
            "(122, 'album_release_type', '1', 'Album - Group per release type', 25, 'boolean', 'interface', 'library'), " .
            "(123, 'ajax_load', '1', 'Ajax page load', 25, 'boolean', 'interface', null), " .
            "(124, 'direct_play_limit', '0', 'Limit direct play to maximum media count', 25, 'integer', 'interface', 'player'), " .
            "(125, 'home_moment_albums', '1', 'Show Albums of the Moment', 25, 'integer', 'interface', 'home'), " .
            "(126, 'home_moment_videos', '0', 'Show Videos of the Moment', 25, 'integer', 'interface', 'home'), " .
            "(127, 'home_recently_played', '1', 'Show Recently Played', 25, 'integer', 'interface', 'home'), " .
            "(128, 'home_now_playing', '1', 'Show Now Playing', 25, 'integer', 'interface', 'home'), " .
            "(129, 'custom_logo', '', 'Custom URL - Logo', 25, 'string', 'interface', 'custom'), " .
            "(130, 'album_release_type_sort', 'album,ep,live,single', 'Album - Group per release type sort', 25, 'string', 'interface', 'library'), " .
            "(131, 'browser_notify', '1', 'Web Player browser notifications', 25, 'integer', 'interface', 'notification'), " .
            "(132, 'browser_notify_timeout', '10', 'Web Player browser notifications timeout (seconds)', 25, 'integer', 'interface', 'notification'), " .
            "(133, 'geolocation', '0', 'Allow Geolocation', 25, 'integer', 'options', 'feature'), " .
            "(134, 'webplayer_aurora', '1', 'Authorize JavaScript decoder (Aurora.js) in Web Player', 25, 'boolean', 'streaming', 'player'), " .
            "(135, 'upload_allow_remove', '1', 'Allow users to remove uploaded songs', 100, 'boolean', 'system', 'upload'), " .
            "(136, 'custom_login_logo', '', 'Custom URL - Login page logo', 75, 'string', 'interface', 'custom'), " .
            "(137, 'custom_favicon', '', 'Custom URL - Favicon', 75, 'string', 'interface', 'custom'), " .
            "(138, 'custom_text_footer', '', 'Custom text footer', 75, 'string', 'interface', 'custom'), " .
            "(139, 'webdav_backend', '0', 'Use WebDAV backend', 100, 'boolean', 'system', 'backend'), " .
            "(140, 'notify_email', '0', 'Allow E-mail notifications', 25, 'boolean', 'options', null), " .
            "(141, 'theme_color', 'dark', 'Theme color', 0, 'special', 'interface', 'theme'), " .
            "(142, 'disabled_custom_metadata_fields', '', 'Custom metadata - Disable these fields', 100, 'string', 'system', 'metadata'), " .
            "(143, 'disabled_custom_metadata_fields_input', '', 'Custom metadata - Define field list', 100, 'string', 'system', 'metadata'), " .
            "(144, 'podcast_keep', '0', '# latest episodes to keep', 100, 'integer', 'system', 'podcast'), " .
            "(145, 'podcast_new_download', '0', '# episodes to download when new episodes are available', 100, 'integer', 'system', 'podcast'), " .
            "(146, 'libitem_contextmenu', '1', 'Library item context menu', 0, 'boolean', 'interface', 'library'), " .
            "(147, 'upload_catalog_pattern', '0', 'Rename uploaded file according to catalog pattern', 100, 'boolean', 'system', 'upload'), " .
            "(148, 'catalog_check_duplicate', '0', 'Check library item at import time and disable duplicates', 100, 'boolean', 'system', 'catalog'), " .
            "(149, 'browse_filter', '0', 'Show filter box on browse', 25, 'boolean', 'interface', 'browse'), " .
            "(150, 'sidebar_light', '0', 'Light sidebar by default', 25, 'boolean', 'interface', 'theme'), " .
            "(151, 'custom_blankalbum', '', 'Custom blank album default image', 75, 'string', 'interface', 'custom'), " .
            "(152, 'custom_blankmovie', '', 'Custom blank video default image', 75, 'string', 'interface', 'custom'), " .
            "(153, 'libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)', 75, 'string', 'interface', 'browse'), " .
            "(154, 'show_skipped_times', '0', 'Show # skipped', 25, 'boolean', 'interface', 'browse'), " .
            "(155, 'custom_datetime', '', 'Custom datetime', 25, 'string', 'interface', 'custom'), " .
            "(156, 'cron_cache', '0', 'Cache computed SQL data (eg. media hits stats) using a cron', 100, 'boolean', 'system', 'catalog'), " .
            "(157, 'unique_playlist', '0', 'Only add unique items to playlists', 25, 'boolean', 'playlist', NULL), " .
            "(158, 'of_the_moment', '6', 'Set the amount of items Album/Video of the Moment will display', 25, 'integer', 'interface', 'home'), " .
            "(159, 'custom_login_background', '', 'Custom URL - Login page background', 75, 'string', 'interface', 'custom'), " .
            "(160, 'show_license', '1', 'Show License', 25, 'boolean', 'interface', 'browse'), " .
            "(161, 'use_original_year', '0', 'Browse by Original Year for albums (falls back to Year)', 25, 'boolean', 'interface', 'browse'), " .
            "(162, 'hide_single_artist', '0', 'Hide the Song Artist column for Albums with one Artist', 25, 'boolean', 'interface', 'browse'), " .
            "(163, 'hide_genres', '0', 'Hide the Genre column in browse table rows', 25, 'boolean', 'interface', 'browse'), " .
            "(164, 'subsonic_always_download', '0', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', 25, 'boolean', 'options', 'subsonic'), " .
            "(165, 'api_enable_3', '1', 'Allow Ampache API3 responses', 25, 'boolean', 'options', 'ampache'), " .
            "(166, 'api_enable_4', '1', 'Allow Ampache API3 responses', 25, 'boolean', 'options', 'ampache'), " .
            "(167, 'api_enable_5', '1', 'Allow Ampache API3 responses', 25, 'boolean', 'options', 'ampache'), " .
            "(168, 'api_force_version', '0', 'Force a specific API response no matter what version you send', 25, 'special', 'options', 'ampache'), " .
            "(169, 'show_playlist_username', '1', 'Show playlist owner username in titles', 25, 'boolean', 'interface', 'browse'), " .
            "(170, 'api_hidden_playlists', '', 'Hide playlists in Subsonic and API clients that start with this string', 25, 'string', 'options', null), " .
            "(171, 'api_hide_dupe_searches', '0', 'Hide smartlists that match playlist names in Subsonic and API clients', 25, 'boolean', 'options', NULL), " .
            "(172, 'show_album_artist', '1', 'Show \'Album Artists\' link in the main sidebar', 25, 'boolean', 'interface', 'theme'), " .
            "(173, 'show_artist', '0', 'Show \'Artists\' link in the main sidebar', 25, 'boolean', 'interface', 'theme'), " .
            "(175, 'demo_use_search', '0', 'Democratic - Use smartlists for base playlist', 100, 'boolean', 'system', NULL);";
        Dba::write($sql);
    } // set_defaults

    /**
     * translate_db
     * Make sure the default prefs are readable by the users
     */
    public static function translate_db()
    {
        $sql        = "UPDATE `preference` SET `preference`.`description` = ? WHERE `preference`.`name` = ? AND `preference`.`description` != ?;";
        $pref_array = array(
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
            'cron_cache' => T_('Cache computed SQL data (eg. media hits stats) using a cron'),
            'custom_blankalbum' => T_('Custom blank album default image'),
            'custom_blankmovie' => T_('Custom blank video default image'),
            'custom_datetime' => T_('Custom datetime'),
            'custom_favicon' => T_('Custom URL - Favicon'),
            'custom_login_background' => T_('Custom URL - Login page background'),
            'custom_login_logo' => T_('Custom URL - Login page logo'),
            'custom_logo' => T_('Custom URL - Logo'),
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
            'flattr_user_id' => T_('Flattr User ID'),
            'flickr_api_key' => T_('Flickr API key'),
            'force_http_play' => T_('Force HTTP playback regardless of port'),
            'ftl_max_items' => T_('Friends timeline max items'),
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
            'httpq_active' => T_('HTTPQ Active Instance'),
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
            'personalfav_display' => T_('Personal favorites on the homepage'),
            'personalfav_playlist' => T_('Favorite Playlists'),
            'personalfav_smartlist' => T_('Favorite Smartlists'),
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
            'share_expire' => T_('Share links default expiration days (0=never)'),
            'share' => T_('Allow Share'),
            'shouthome_max_items' => T_('Shoutbox on homepage max items'),
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
            'sidebar_light' => T_('Light sidebar by default'),
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
            'tvdb_api_key' => T_('TVDb API key'),
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
            'yourls_use_idn' => T_('YOURLS use IDN')
        );
        foreach ($pref_array as $key => $value) {
            Dba::write($sql, array($value, $key, $value));
        }
    } // translate_db

    /**
     * load_from_session
     * This loads the preferences from the session rather then creating a connection to the database
     * @param int $uid
     * @return bool
     */
    public static function load_from_session($uid = -1)
    {
        if (!isset($_SESSION)) {
            return false;
        }
        if (array_key_exists('userdata', $_SESSION) && array_key_exists('preferences', $_SESSION['userdata']) && is_array($_SESSION['userdata']['preferences']) && $_SESSION['userdata']['uid'] == $uid) {
            AmpConfig::set_by_array($_SESSION['userdata']['preferences'], true);

            return true;
        }

        return false;
    } // load_from_session

    /**
     * clear_from_session
     * This clears the users preferences, this is done whenever modifications are made to the preferences
     * or the admin resets something
     */
    public static function clear_from_session()
    {
        if (isset($_SESSION) && array_key_exists('userdata', $_SESSION) && array_key_exists('preferences', $_SESSION['userdata'])) {
            unset($_SESSION['userdata']['preferences']);
        }
    } // clear_from_session

    /**
     * is_boolean
     * This returns true / false if the preference in question is a boolean preference
     * This is currently only used by the debug view, could be used other places.. wouldn't be a half
     * bad idea
     * @param $key
     * @return bool
     */
    public static function is_boolean($key)
    {
        $boolean_array = array(
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
            'httpq_active',
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
            'sidebar_light',
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
            'upload_catalog',
            'upload_catalog_pattern',
            'upload_script',
            'upload_subdir',
            'upload_user_artist',
            'upnp_active',
            'upnp_backend',
            'use_auth',
            'use_now_playing_embedded',
            'use_original_year',
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
            'write_id3',
            'write_id3_art',
            'write_tags',
            'xml_rpc'
        );

        if (in_array($key, $boolean_array)) {
            return true;
        }

        return false;
    } // is_boolean

    /**
     * init
     * This grabs the preferences and then loads them into conf it should be run on page load
     * to initialize the needed variables
     * @return bool
     */
    public static function init()
    {
        $user    = Core::get_global('user');
        $user_id = $user->id ?? -1;

        // First go ahead and try to load it from the preferences
        if (self::load_from_session($user_id)) {
            return true;
        }

        /* Get Global Preferences */
        $sql        = "SELECT `preference`.`name`, `user_preference`.`value`, `syspref`.`value` AS `system_value` FROM `preference` LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`catagory`='system' LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user` = ? AND `preference`.`catagory`!='system'";
        $db_results = Dba::read($sql, array($user_id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $value          = $row['system_value'] ?? $row['value'];
            $name           = $row['name'];
            $results[$name] = $value;
        } // end while sys prefs

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
        $themecfg                  = get_theme($results['theme_name']);
        $results['theme_css_base'] = $themecfg['base'];

        if (array_key_exists('theme_color', $results) && strlen((string)$results['theme_color']) > 0) {
            // In case the color was removed
            if (!Core::is_readable(__DIR__ . '/../../../public/themes/' . $results['theme_name'] . '/templates/' . $results['theme_color'] . '.css')) {
                unset($results['theme_color']);
            }
        } else {
            unset($results['theme_color']);
        }
        if (!isset($results['theme_color'])) {
            $results['theme_color'] = strtolower((string)$themecfg['colors'][0]);
        }

        AmpConfig::set_by_array($results, true);
        $_SESSION['userdata']['preferences'] = $results;
        $_SESSION['userdata']['uid']         = $user_id;

        return true;
    } // init
}
