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

/**
 * Preference Class
 *
 * This handles all of the preference stuff for Ampache
 *
 */
class Preference extends database_object
{
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
        'autoupdate',
        'autoupdate_lastcheck',
        'autoupdate_lastversion',
        'autoupdate_lastversion_new',
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
        'custom_login_backgound',
        'custom_login_logo',
        'custom_logo',
        'custom_text_footer',
        'daap_backend',
        'daap_pass',
        'demo_clear_sessions',
        'direct_play_limit',
        'disabled_custom_metadata_fields',
        'disabled_custom_metadata_fields_input',
        'download',
        'force_http_play',
        'geolocation',
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
        'playlist_method',
        'playlist_type',
        'play_type',
        'podcast_keep',
        'podcast_new_download',
        'popular_threshold',
        'rate_limit',
        'share',
        'share_expire',
        'show_donate',
        'show_lyrics',
        'show_played_times',
        'show_skipped_times',
        'sidebar_light',
        'site_title',
        'slideshow_time',
        'song_page_title',
        'stats_threshold',
        'stream_beautiful_url',
        'subsonic_backend',
        'theme_color',
        'theme_name',
        'topmenu',
        'transcode',
        'transcode_bitrate',
        'ui_fixed',
        'unique_playlist',
        'upload_allow_edit',
        'upload_allow_remove',
        'upload_catalog',
        'upload_catalog_pattern',
        'upload_script',
        'upload_subdir',
        'upload_user_artist',
        'upnp_backend',
        'webdav_backend',
        'webplayer_aurora',
        'webplayer_confirmclose',
        'webplayer_flash',
        'webplayer_html5',
        'webplayer_pausetabs'
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
     * @param integer $user_id
     * @param string $pref_name
     * @return integer|string
     */
    public static function get_by_user($user_id, $pref_name)
    {
        //debug_event(self::class, 'Getting preference {'.$pref_name.'} for user identifier {'.$user_id.'}...', 5);
        $user_id   = (int) Dba::escape($user_id);
        $pref_name = Dba::escape($pref_name);
        $pref_id   = self::id_from_name($pref_name);

        if (parent::is_cached('get_by_user', $user_id)) {
            return (int) (parent::get_from_cache('get_by_user', $user_id))[0];
        }

        $sql        = "SELECT `value` FROM `user_preference` WHERE `preference`='$pref_id' AND `user`='$user_id'";
        $db_results = Dba::read($sql);
        if (Dba::num_rows($db_results) < 1) {
            $sql        = "SELECT `value` FROM `user_preference` WHERE `preference`='$pref_id' AND `user`='-1'";
            $db_results = Dba::read($sql);
        }
        $data = Dba::fetch_assoc($db_results);

        parent::add_to_cache('get_by_user', $user_id, $data);

        return $data['value'];
    } // get_by_user


    /**
     * update
     * This updates a single preference from the given name or id
     * @param string $preference
     * @param integer $user_id
     * @param array|string $value
     * @param boolean $applytoall
     * @param boolean $applytodefault
     * @return boolean
     */
    public static function update($preference, $user_id, $value, $applytoall = false, $applytodefault = false)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $pref_id = self::id_from_name($preference);
            $name    = $preference;
        } else {
            $pref_id = $preference;
            $name    = self::name_from_id($preference);
        }
        if ($applytoall && Access::check('interface', 100)) {
            $user_check = "";
        } else {
            $user_check = " AND `user`='$user_id'";
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if ($applytodefault && Access::check('interface', 100)) {
            $sql = "UPDATE `preference` SET `value`='$value' WHERE `id`='$pref_id'";
            Dba::write($sql);
        }

        $value = Dba::escape($value);

        if (self::has_access($name)) {
            $user_id = (int) Dba::escape($user_id);
            $sql     = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$pref_id'$user_check";
            Dba::write($sql);
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
     * @return boolean
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
     * @param integer $preference_id
     * @param string $value
     * @return boolean
     */
    public static function update_all($preference_id, $value)
    {
        $preference_id = (string) Dba::escape($preference_id);
        $value         = (string) Dba::escape($value);

        $sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$preference_id'";
        Dba::write($sql);

        parent::clear_cache();

        return true;
    } // update_all

    /**
     * exists
     * This just checks to see if a preference currently exists
     * @param string $preference
     * @return integer
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
     * @return boolean
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
     * @return array|integer
     */
    public static function id_from_name($name)
    {
        $name = Dba::escape($name);

        if (parent::is_cached('id_from_name', $name)) {
            return (int) (parent::get_from_cache('id_from_name', $name))[0];
        }

        $sql        = "SELECT `id` FROM `preference` WHERE `name`='$name'";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);

        parent::add_to_cache('id_from_name', $name, $row);

        return (int) $row['id'];
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
        $sql        = "SELECT `preference`.`catagory` FROM `preference` GROUP BY `catagory` ORDER BY `catagory`";
        $db_results = Dba::read($sql);

        $results = array();

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
     * @param integer $user_id
     * @return array
     */
    public static function get_all($user_id)
    {
        $user_id    = Dba::escape($user_id);
        $user_limit = ($user_id != -1) ? "AND `preference`.`catagory` != 'system'" : "";

        $sql = "SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`," .
            " `preference`.`type`, `preference`.`catagory`, `preference`.`subcatagory`, `user_preference`.`value`" .
            " FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` " .
            " WHERE `user_preference`.`user` = ? AND `preference`.`catagory` != 'internal' $user_limit " .
            " ORDER BY `preference`.`subcatagory`, `preference`.`description`";

        $db_results = Dba::read($sql, array($user_id));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array('id' => $row['id'], 'name' => $row['name'], 'level' => $row['level'], 'description' => $row['description'],
                'value' => $row['value'], 'type' => $row['type'], 'category' => $row['catagory'], 'subcategory' => $row['subcatagory']);
        }

        return $results;
    } // get_all

    /**
     * get
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @param string $pref_name
     * @param integer $user_id
     * @return array
     */
    public static function get($pref_name, $user_id)
    {
        $user_id    = Dba::escape($user_id);
        $user_limit = ($user_id != -1) ? "AND `preference`.`catagory` != 'system'" : "";

        $sql = "SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`," .
            " `preference`.`type`, `preference`.`catagory`, `preference`.`subcatagory`, `user_preference`.`value`" .
            " FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` " .
            " WHERE `preference`.`name` = ? AND `user_preference`.`user`= ? AND `preference`.`catagory` != 'internal' $user_limit " .
            " ORDER BY `preference`.`subcatagory`, `preference`.`description`";

        $db_results = Dba::read($sql, array($pref_name, $user_id));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array('id' => $row['id'], 'name' => $row['name'], 'level' => $row['level'], 'description' => $row['description'],
                'value' => $row['value'], 'type' => $row['type'], 'category' => $row['catagory'], 'subcategory' => $row['subcatagory']);
        }

        return $results;
    } // get

    /**
     * insert
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     * @param string $name
     * @param string $description
     * @param string|integer $default
     * @param integer $level
     * @param string $type
     * @param string $category
     * @param string $subcategory
     * @return boolean
     */
    public static function insert($name, $description, $default, $level, $type, $category, $subcategory = null)
    {
        if ($subcategory !== null) {
            $subcategory = strtolower((string) $subcategory);
        }
        $sql = "INSERT INTO `preference` (`name`, `description`, `value`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($name, $description, $default, (int) $level, $type, $category, $subcategory));

        if (!$db_results) {
            return false;
        }
        $pref_id    = Dba::insert_id();
        $params     = array($pref_id, $default);
        $sql        = "INSERT INTO `user_preference` VALUES (-1,?,?)";
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

        return true;
    } // insert

    /**
     * delete
     * This deletes the specified preference, a name or a ID can be passed
     * @param string|integer $preference
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
        $sql = "DELETE FROM `user_preference` USING `user_preference` LEFT JOIN `preference` ON `preference`.`id`=`user_preference`.`preference` " .
            "WHERE `preference`.`id` IS NULL";
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
            'auth_methods', 'getid3_tag_order', 'metadata_order',
            'metadata_order_video', 'art_order', 'registration_display_fields',
            'registration_mandatory_fields'
        );

        foreach ($arrays as $item) {
            $results[$item] = (trim((string) $results[$item])) ? explode(',', $results[$item]) : array();
        }

        foreach ($results as $key => $data) {
            if (!is_array($data)) {
                if (strcasecmp((string) $data, "true") == "0") {
                    $results[$key] = 1;
                }
                if (strcasecmp((string) $data, "false") == "0") {
                    $results[$key] = 0;
                }
            }
        }

        return $results;
    } // fix_preferences

    /**
     * set_defaults
     * Make sure the default prefs are set!
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
               "(84, 'show_played_times', '0', 'Show # played', 25, 'string', 'interface', 'library'), " .
               "(85, 'song_page_title', '1', 'Show current song in Web player page title', 25, 'boolean', 'interface', 'player'), " .
               "(86, 'subsonic_backend', '1', 'Use Subsonic backend', 100, 'boolean', 'system', 'backend'), " .
               "(88, 'webplayer_flash', '1', 'Authorize Flash Web Player', 25, 'boolean', 'streaming', 'player'), " .
               "(89, 'webplayer_html5', '1', 'Authorize HTML5 Web Player', 25, 'boolean', 'streaming', 'player'), " .
               "(90, 'allow_personal_info_now', '1', 'Share Now Playing information', 25, 'boolean', 'interface', 'privacy'), " .
               "(91, 'allow_personal_info_recent', '1', 'Share Recently Played information', 25, 'boolean', 'interface', 'privacy'), " .
               "(92, 'allow_personal_info_time', '1', 'Share Recently Played information - Allow access to streaming date/time', 25, 'boolean', 'interface', 'privacy'), " .
               "(93, 'allow_personal_info_agent', '1', 'Share Recently Played information - Allow access to streaming agent', 25, 'boolean', 'interface', 'privacy'), " .
               "(94, 'ui_fixed', '0', 'Fix header position on compatible themes', 25, 'boolean', 'interface', 'theme'), " .
               "(95, 'autoupdate', '1', 'Check for Ampache updates automatically', 25, 'boolean', 'system', 'update'), " .
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
               "(112, 'upload_catalog', '-1', 'Destination catalog', 75, 'integer', 'system', 'upload'), " .
               "(113, 'allow_upload', '0', 'Allow user uploads', 75, 'boolean', 'system', 'upload'), " .
               "(114, 'upload_subdir', '1', 'Create a subdirectory per user', 75, 'boolean', 'system', 'upload'), " .
               "(115, 'upload_user_artist', '0', 'Consider the user sender as the track''s artist', 75, 'boolean', 'system', 'upload'), " .
               "(116, 'upload_script', '', 'Post-upload script (current directory = upload target directory)', 75, 'string', 'system', 'upload'), " .
               "(117, 'upload_allow_edit', '1', 'Allow users to edit uploaded songs', 75, 'boolean', 'system', 'upload'), " .
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
               "(135, 'upload_allow_remove', '1', 'Allow users to remove uploaded songs', 75, 'boolean', 'system', 'upload'), " .
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
               "(148, 'catalog_check_duplicate', '0', 'Check library item at import time and don\'t import duplicates', 100, 'boolean', 'system', 'catalog'), " .
               "(149, 'browse_filter', '0', 'Show filter box on browse', 25, 'boolean', 'interface', 'library'), " .
               "(150, 'sidebar_light', '0', 'Light sidebar by default', 25, 'boolean', 'interface', 'theme'), " .
               "(151, 'custom_blankalbum', '', 'Custom blank album default image', 75, 'string', 'interface', 'custom'), " .
               "(152, 'custom_blankmovie', '', 'Custom blank video default image', 75, 'string', 'interface', 'custom'), " .
               "(153, 'libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)', 75, 'string', 'interface', 'library'), " .
               "(154, 'show_skipped_times', '0', 'Show # skipped', 25, 'boolean', 'interface', 'library'), " .
               "(155, 'custom_datetime', '', 'Custom datetime', 25, 'string', 'interface', 'custom'), " .
               "(156, 'cron_cache', '0', 'Cache computed SQL data (eg. media hits stats) using a cron', 25, 'boolean', 'system', 'catalog'), " .
               "(157, 'unique_playlist', '0', 'Only add unique items to playlists', 25, 'boolean', 'playlist', NULL);";
        Dba::write($sql);
    } // set_defaults

    /**
     * load_from_session
     * This loads the preferences from the session rather then creating a connection to the database
     * @param integer $uid
     * @return boolean
     */
    public static function load_from_session($uid = -1)
    {
        if (isset($_SESSION['userdata']['preferences']) && is_array($_SESSION['userdata']['preferences']) && $_SESSION['userdata']['uid'] == $uid) {
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
        unset($_SESSION['userdata']['preferences']);
    } // clear_from_session

    /**
     * is_boolean
     * This returns true / false if the preference in question is a boolean preference
     * This is currently only used by the debug view, could be used other places.. wouldn't be a half
     * bad idea
     * @param $key
     * @return boolean
     */
    public static function is_boolean($key)
    {
        $boolean_array = array('session_cookiesecure', 'require_session',
                    'access_control', 'require_localnet_session',
                    'downsample_remote', 'track_user_ip',
                    'xml_rpc', 'allow_zip_download', 'ratings',
                    'shoutbox', 'resize_images', 'show_played_times', 'show_skipped_times',
                    'show_album_art', 'allow_public_registration',
                    'captcha_public_reg', 'admin_notify_reg',
                    'use_rss', 'download', 'force_http_play', 'cookie_secure',
                    'allow_stream_playback', 'allow_democratic_playback',
                    'use_auth', 'allow_localplay_playback', 'debug', 'lock_songs',
                    'transcode_m4a', 'transcode_mp3', 'transcode_ogg', 'transcode_flac',
                    'httpq_active', 'show_lyrics');

        if (in_array($key, $boolean_array)) {
            return true;
        }

        return false;
    } // is_boolean

    /**
      * init
     * This grabs the preferences and then loads them into conf it should be run on page load
     * to initialize the needed variables
     * @return boolean
     */
    public static function init()
    {
        $user_id = Core::get_global('user')->id ? (int) (Core::get_global('user')->id) : -1;

        // First go ahead and try to load it from the preferences
        if (self::load_from_session($user_id)) {
            return true;
        }

        /* Get Global Preferences */
        $sql = "SELECT `preference`.`name`, `user_preference`.`value`, `syspref`.`value` AS `system_value` FROM `preference` " .
            "LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`catagory`='system' " .
            "LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user` = ? AND `preference`.`catagory`!='system'";
        $db_results = Dba::read($sql, array($user_id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $value          = $row['system_value'] ? $row['system_value'] : $row['value'];
            $name           = $row['name'];
            $results[$name] = $value;
        } // end while sys prefs

        /* Set the Theme mojo */
        if (strlen((string) $results['theme_name']) > 0) {
            // In case the theme was removed
            if (!Core::is_readable(AmpConfig::get('prefix') . '/themes/' . $results['theme_name'])) {
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

        if (strlen((string) $results['theme_color']) > 0) {
            // In case the color was removed
            if (!Core::is_readable(AmpConfig::get('prefix') . '/themes/' . $results['theme_name'] . '/templates/' . $results['theme_color'] . '.css')) {
                unset($results['theme_color']);
            }
        } else {
            unset($results['theme_color']);
        }
        if (!isset($results['theme_color'])) {
            $results['theme_color'] = strtolower((string) $themecfg['colors'][0]);
        }

        AmpConfig::set_by_array($results, true);
        $_SESSION['userdata']['preferences'] = $results;
        $_SESSION['userdata']['uid']         = $user_id;

        return true;
    } // init
} // end preference.class
