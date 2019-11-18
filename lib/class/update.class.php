<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Update Class
 *
 * This class mainly handles schema updates for the database.
 * Versions are a monotonically increasing integer: First column(s) are the
 * major version, followed by a single column for the minor version and four
 * columns for the build number. 3.6 build 1 is 360000; 10.9 build 17 is
 * 1090017.
 */


class Update
{
    public $key;
    public $value;
    public static $versions; // array containing version information

    /*
     * Constructor
     *
     * This should never be called
     */
    private function __construct()
    {
        // static class
    }

    /**
     * get_version
     *
     * This checks to see what version you are currently running.
     * Because we may not have the update_info table we have to check
     * for its existence first.
     * @return string
     */
    public static function get_version()
    {
        $version = "";
        /* Make sure that update_info exits */
        $sql        = "SHOW TABLES LIKE 'update_info'";
        $db_results = Dba::read($sql);
        if (!Dba::dbh()) {
            header("Location: test.php");
        }

        // If no table
        if (!Dba::num_rows($db_results)) {
            // They can't upgrade, they are too old
            header("Location: test.php");
        } // if table isn't found

        else {
            // If we've found the update_info table, let's get the version from it
            $sql        = "SELECT * FROM `update_info` WHERE `key`='db_version'";
            $db_results = Dba::read($sql);
            $results    = Dba::fetch_assoc($db_results);
            $version    = $results['value'];
        }

        return $version;
    } // get_version

    /**
     * format_version
     *
     * Make the version number pretty.
     * @return string
     */
    public static function format_version($data)
    {
        $new_version =
            substr($data, 0, strlen($data) - 5) . '.' .
            substr($data, strlen($data) - 5, 1) . ' Build:' .
            substr($data, strlen($data) - 4, strlen($data));

        return $new_version;
    }

    /**
     * need_update
     *
     * Checks to see if we need to update ampache at all.
     * @return boolean
     */
    public static function need_update()
    {
        $current_version = self::get_version();

        if (!is_array(self::$versions)) {
            self::$versions = self::populate_version();
        }

        // Iterate through the versions and see if we need to apply any updates
        foreach (self::$versions as $update) {
            if ($update['version'] > $current_version) {
                return true;
            }
        }

        return false;
    }

    /**
     * populate_version
     * just sets an array the current differences
     * that require an update
     * @return array
     */
    public static function populate_version()
    {
        /* Define the array */
        $version = array();

        $update_string = "- Add manual update flag on artist.<br />";
        $version[]     = array('version' => '380005', 'description' => $update_string);

        $update_string = "- Add library item context menu option.<br />";
        $version[]     = array('version' => '380006', 'description' => $update_string);

        $update_string = "- Add upload rename pattern and ignore duplicate options.<br />";
        $version[]     = array('version' => '380007', 'description' => $update_string);

        $update_string = "- Add browse filter and light sidebar options.<br />";
        $version[]     = array('version' => '380008', 'description' => $update_string);

        $update_string = "- Add update date to playlist.<br />";
        $version[]     = array('version' => '380009', 'description' => $update_string);

        $update_string = "- Add custom blank album/video default image and alphabet browsing options.<br />";
        $version[]     = array('version' => '380010', 'description' => $update_string);

        $update_string = "- Fix username max size to be the same one across all tables.<br />";
        $version[]     = array('version' => '380011', 'description' => $update_string);

        $update_string = "- Fix change in <a href='https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee'>this commit</a>, that left the userbase with an inconsistent database, if users updated or installed Ampache before 28 Apr 2015<br />";
        $version[]     = array('version' => '380012', 'description' => $update_string);

        $update_string = "* Enable better podcast defaults<br />" .
                         "* Increase copyright column size to fix issue #1861<br />" .
                         "* Add name_track, name_artist, name_album to user_activity<br />" .
                         "* Add mbid_track, mbid_artist, mbid_album to user_activity<br />" .
                         "* Insert some decent SmartLists for a better default experience<br />" .
                         "* Delete plex preferences from the server<br />";
        $version[]     = array('version' => '400000', 'description' => $update_string);

        $update_string = "* Update preferences for older users to match current subcategory items<br />" .
                         "  (~3.6 introduced subcategories but didn't include updates for existing users.<br />" .
                         "  This is a cosmetic update and does not affect any operation)<br />";
        $version[]     = array('version' => '400001', 'description' => $update_string);

        $update_string = "**IMPORTANT UPDATE NOTES**<br /><br />" .
                         "This is part of a major update to how Ampache handles Albums, " .
                         "Artists and data migration during tag updates.<br /><br />" .
                         " * Update album disk support to allow 1 instead of 0 by default.<br />" .
                         " * Add barcode catalog_number and original_year to albums.<br />" .
                         " * Drop catalog_number from song_data and use album instead.<br />";
        $version[]     = array('version' => '400002', 'description' => $update_string);

        $update_string = "* Make sure preference names are updated to current strings<br />";
        $version[]     = array('version' => '400003', 'description' => $update_string);

        $update_string = "* Delete upload_user_artist database settings<br />";
        $version[]     = array('version' => '400004', 'description' => $update_string);

        return $version;
    }

    /**
     * display_update
     * This displays a list of the needed
     * updates to the database. This will actually
     * echo out the list...
     */
    public static function display_update()
    {
        $current_version = self::get_version();
        if (!is_array(self::$versions)) {
            self::$versions = self::populate_version();
        }
        $update_needed = false;

        if (!defined('CLI')) {
            echo "<ul>\n";
        }

        foreach (self::$versions as $update) {
            if ($update['version'] > $current_version) {
                $update_needed = true;
                if (!defined('CLI')) {
                    echo '<li><b>';
                }
                echo T_('Version') . ': ', self::format_version($update['version']);
                if (defined('CLI')) {
                    echo "\n", str_replace('<br />', "\n", $update['description']), "\n";
                } else {
                    echo '</b><br />', $update['description'], "<br /></li>\n";
                }
            } // if newer
        } // foreach versions

        if (!defined('CLI')) {
            echo "</ul>\n";
        }

        if (!$update_needed) {
            if (!defined('CLI')) {
                echo '<p class="database-update">';
            }
            echo T_('No Update Needed');
            if (!defined('CLI')) {
                echo ' [<a href="', AmpConfig::get('web_path'), '/">', T_('Return to main page'), '</a>]</p>';
            } else {
                echo "\n";
            }
        }
    } // display_update

    /**
     * run_update
     * This function actually updates the db.
     * it goes through versions and finds the ones
     * that need to be run. Checking to make sure
     * the function exists first.
     */
    public static function run_update()
    {
        /* Nuke All Active session before we start the mojo */
        $sql = "TRUNCATE session";
        Dba::write($sql);

        // Prevent the script from timing out, which could be bad
        set_time_limit(0);

        $current_version = self::get_version();

        // Run a check to make sure that they don't try to upgrade from a version that
        // won't work.
        if ($current_version < '340002') {
            echo '<p class="database-update">Database version too old, please upgrade to <a href="http://ampache.org/downloads/ampache-3.3.3.5.tar.gz">Ampache-3.3.3.5</a> first</p>';

            return false;
        }


        $methods = get_class_methods('Update');

        if (!is_array((self::$versions))) {
            self::$versions = self::populate_version();
        }

        foreach (self::$versions as $version) {
            // If it's newer than our current version let's see if a function
            // exists and run the bugger.
            if ($version['version'] > $current_version) {
                $update_function = "update_" . $version['version'];
                if (in_array($update_function, $methods)) {
                    $success = call_user_func(array('Update', $update_function));

                    // If the update fails drop out
                    if ($success) {
                        self::set_version('db_version', $version['version']);
                    } else {
                        AmpError::display('update');

                        return false;
                    }
                }
            }
        } // end foreach version

        // Once we've run all of the updates let's re-sync the character set as
        // the user can change this between updates and cause mis-matches on any
        // new tables.
        Dba::reset_db_charset();

        // Let's also clean up the preferences unconditionally
        User::rebuild_all_preferences();
    } // run_update

    /**
     * set_version
     *
     * This updates the 'update_info' which is used by the updater
     * and plugins
     * @param string $key
     */
    private static function set_version($key, $value)
    {
        $sql = "UPDATE update_info SET value='$value' WHERE `key`='$key'";
        Dba::write($sql);
    }

    /**
     * update_380005
     *
     * Add manual update flag on artist
     */
    public static function update_380005()
    {
        $retval = true;

        $sql    = "ALTER TABLE `artist` ADD COLUMN `manual_update` SMALLINT( 1 ) DEFAULT '0'";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_380006
     *
     * Add library item context menu option
     */
    public static function update_380006()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('libitem_contextmenu', '1', 'Library item context menu',0, 'boolean', 'interface', 'library')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '1')";
        $retval &= Dba::write($sql, array($row_id));

        return $retval;
    }

    /**
     * update_380007
     *
     * Add upload rename pattern and ignore duplicate options
     */
    public static function update_380007()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('upload_catalog_pattern', '0', 'Rename uploaded file according to catalog pattern',100, 'boolean', 'system', 'upload')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '0')";
        $retval &= Dba::write($sql, array($row_id));

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('catalog_check_duplicate', '0', 'Check library item at import time and don\'t import duplicates',100, 'boolean', 'system', 'catalog')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '0')";
        $retval &= Dba::write($sql, array($row_id));

        return $retval;
    }

    /**
     * update_380008
     *
     * Add browse filter and light sidebar options
     */
    public static function update_380008()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('browse_filter', '1', 'Show filter box on browse',25, 'boolean', 'interface', 'library')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '1')";
        $retval &= Dba::write($sql, array($row_id));

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('sidebar_light', '0', 'Light sidebar by default',25, 'boolean', 'interface', 'theme')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '0')";
        $retval &= Dba::write($sql, array($row_id));

        return $retval;
    }

    /**
     * update_380009
     *
     * Add update date to playlist
     */
    public static function update_380009()
    {
        $retval = true;

        $sql    = "ALTER TABLE `playlist` ADD COLUMN `last_update` int(11) unsigned NOT NULL DEFAULT '0'";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_380010
     *
     * Add custom blank album/video default image and alphabet browsing options
     */
    public static function update_380010()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('custom_blankalbum', '', 'Custom blank album default image',75, 'string', 'interface', 'custom')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '')";
        $retval &= Dba::write($sql, array($row_id));

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('custom_blankmovie', '', 'Custom blank video default image',75, 'string', 'interface', 'custom')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '')";
        $retval &= Dba::write($sql, array($row_id));

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) " .
            "VALUES ('libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)',75, 'string', 'interface', 'library')";
        $retval &= Dba::write($sql);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1,?, '')";
        $retval &= Dba::write($sql, array($row_id));

        return $retval;
    }

    /**
     * update_380011
     *
     * Fix username max size to be the same one across all tables.
     */
    public static function update_380011()
    {
        $retval = true;

        $sql = "ALTER TABLE session MODIFY username VARCHAR(255)";
        $retval &= Dba::write($sql);

        $sql = "ALTER TABLE session_remember MODIFY username VARCHAR(255)";
        $retval &= Dba::write($sql);

        $sql = "ALTER TABLE user MODIFY username VARCHAR(255)";
        $retval &= Dba::write($sql);

        $sql = "ALTER TABLE user MODIFY fullname VARCHAR(255)";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_380012
     *
     * Fix change in https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
     * That left the userbase with an inconsistent database.
     * For more information, please look at update_360035.
     */
    public static function update_380012()
    {
        $retval = true;

        $sql = "UPDATE `preference` SET `description`='Enable url rewriting' WHERE `preference`.`name`='stream_beautiful_url'";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_400000
     *
     * Increase copyright column size to fix issue #1861
     * Add name_track, name_artist, name_album to user_activity
     * Add mbid_track, mbid_artist, mbid_album to user_activity
     * Insert some decent SmartLists for a better default experience
     * Delete the following plex preferences from the server
     *   plex_backend
     *   myplex_username
     *   myplex_authtoken
     *   myplex_published
     *   plex_uniqid
     *   plex_servername
     *   plex_public_address
     *   plex_public_port
     *   plex_local_auth
     *   plex_match_email
     * Add preference for master/develop branch selection
     */
    public static function update_400000()
    {
        $retval = true;

        $sql = "ALTER TABLE `podcast` MODIFY `copyright` VARCHAR(255)";
        $retval &= Dba::write($sql);

        $sql = "ALTER TABLE `user_activity` " .
                "ADD COLUMN `name_track` VARCHAR(255) NULL DEFAULT NULL," .
                "ADD COLUMN `name_artist` VARCHAR(255) NULL DEFAULT NULL," .
                "ADD COLUMN `name_album` VARCHAR(255) NULL DEFAULT NULL;";
        $retval &= Dba::write($sql);

        $sql = "ALTER TABLE `user_activity` " .
                "ADD COLUMN `mbid_track` VARCHAR(255) NULL DEFAULT NULL," .
                "ADD COLUMN `mbid_artist` VARCHAR(255) NULL DEFAULT NULL," .
                "ADD COLUMN `mbid_album` VARCHAR(255) NULL DEFAULT NULL;";
        $retval &= Dba::write($sql);

        $sql = "INSERT IGNORE INTO `search` (`user`, `type`, `rules`, `name`, `logic_operator`, `random`, `limit`) VALUES " .
                "(-1, 'public', '[[\"artistrating\",\"equal\",\"5\",null]]', 'Artist 5*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"artistrating\",\"equal\",\"4\",null]]', 'Artist 4*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"artistrating\",\"equal\",\"3\",null]]', 'Artist 3*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"artistrating\",\"equal\",\"2\",null]]', 'Artist 2*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"artistrating\",\"equal\",\"1\",null]]', 'Artist 1*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"albumrating\",\"equal\",\"5\",null]]', 'Album 5*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"albumrating\",\"equal\",\"4\",null]]', 'Album 4*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"albumrating\",\"equal\",\"3\",null]]', 'Album 3*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"albumrating\",\"equal\",\"2\",null]]', 'Album 2*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"albumrating\",\"equal\",\"1\",null]]', 'Album 1*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"myrating\",\"equal\",\"5\",null]]', 'Song 5*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"myrating\",\"equal\",\"4\",null]]', 'Song 4*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"myrating\",\"equal\",\"3\",null]]', 'Song 3*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"myrating\",\"equal\",\"2\",null]]', 'Song 2*', 'AND', 0, 0), " .
                "(-1, 'public', '[[\"myrating\",\"equal\",\"1\",null]]', 'Song 1*', 'AND', 0, 0);";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_backend');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'myplex_username');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'myplex_authtoken');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'myplex_published');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_uniqid');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_servername');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_public_address');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_public_port');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_local_auth');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'plex_match_email');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `preference` " .
               "WHERE `preference`.`name` IN " .
               "('plex_backend', 'myplex_username', " .
               "'myplex_authtoken', 'myplex_published', 'plex_uniqid', " .
               "'plex_servername', 'plex_public_address', " .
               "'plex_public_port ', 'plex_local_auth', 'plex_match_email');";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_400001
     *
     * Make sure people on older databases have the same preference categories
     */
    public static function update_400001()
    {
        $retval = true;
        $sql    = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'library' " .
               "WHERE `preference`.`name` in ('album_sort', 'show_played_times', 'album_group', 'album_release_type', 'album_release_type_sort', 'libitem_contextmenu', 'browse_filter', 'libitem_browse_alpha') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'backend' " .
               "WHERE `preference`.`name` in ('subsonic_backend', 'daap_backend', 'daap_pass', 'upnp_backend', 'webdav_backend') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'catalog' " .
               "WHERE `preference`.`name` = 'catalog_check_duplicate' AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'custom' " .
               "WHERE `preference`.`name` in ('site_title', 'custom_logo', 'custom_login_logo', 'custom_favicon', 'custom_text_footer', 'custom_blankalbum', 'custom_blankmovie') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'feature' " .
               "WHERE `preference`.`name` in ('download', 'allow_stream_playback', 'allow_democratic_playback', 'share', 'allow_video', 'geolocation') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'home' " .
               "WHERE `preference`.`name` in ('now_playing_per_user', 'home_moment_albums', 'home_moment_videos', 'home_recently_played', 'home_now_playing') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'httpq' " .
               "WHERE `preference`.`name` = 'httpq_active' AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'lastfm' " .
               "WHERE `preference`.`name` in ('lastfm_grant_link', 'lastfm_challenge') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'localplay' " .
               "WHERE `preference`.`name` in ('localplay_controller', 'localplay_level', 'allow_localplay_playback') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'metadata' " .
               "WHERE `preference`.`name` in ('disabled_custom_metadata_fields', 'disabled_custom_metadata_fields_input') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'mpd' " .
               "WHERE `preference`.`name` = 'mpd_active' AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'notification' " .
               "WHERE `preference`.`name` in ('browser_notify', 'browser_notify_timeout') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'player' " .
               "WHERE `preference`.`name` in ('show_lyrics', 'song_page_title', 'webplayer_flash', 'webplayer_html5', 'webplayer_confirmclose', 'webplayer_pausetabs', 'slideshow_time', 'broadcast_by_default', 'direct_play_limit', 'webplayer_aurora') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'podcast' " .
               "WHERE `preference`.`name` in ('podcast_keep', 'podcast_new_download') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'privacy' " .
               "WHERE `preference`.`name` in ('allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_personal_info_agent') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'query' " .
               "WHERE `preference`.`name` in ('popular_threshold', 'offset_limit', 'stats_threshold', 'concerts_limit_future', 'concerts_limit_past') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'share' " .
               "WHERE `preference`.`name` = 'share_expire' AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'shoutcast' " .
               "WHERE `preference`.`name` = 'shoutcast_active' AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'theme' " .
               "WHERE `preference`.`name` in ('theme_name', 'ui_fixed', 'topmenu', 'theme_color', 'sidebar_light') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'transcoding' " .
               "WHERE `preference`.`name` in ('transcode_bitrate', 'rate_limit', 'transcode') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'update' " .
               "WHERE `preference`.`name` in ('autoupdate', 'autoupdate_lastcheck', 'autoupdate_lastversion', 'autoupdate_lastversion_new') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`subcatagory` = 'upload' " .
               "WHERE `preference`.`name` in ('upload_catalog', 'allow_upload', 'upload_subdir', 'upload_user_artist', 'upload_script', 'upload_allow_edit', 'upload_allow_remove', 'upload_catalog_pattern') AND " .
               "`preference`.`subcatagory` IS NULL;";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_400002
     *
     * Update disk to allow 1 instead of making it 0 by default
     * Add barcode catalog_number and original_year
     * Drop catalog_number from song_data
     */
    public static function update_400002()
    {
        $retval = true;
        $sql    = "UPDATE `album` SET `album`.`disk` = 1 " .
                  "WHERE `album`.`disk` = 0;";
        $retval &= Dba::write($sql);

        $sql = "ALTER TABLE `album` ADD `original_year` INT(4) NULL," .
               "ADD `barcode` VARCHAR(64) NULL," .
               "ADD `catalog_number` VARCHAR(64) NULL;";
        $retval &= Dba::write($sql);

        $sql    = "ALTER TABLE `song_data`  DROP `catalog_number`";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_400003
     *
     * Make sure preference names are updated to current strings
     */
    public static function update_400003()
    {
        $retval = true;
        $sql    = "UPDATE `preference` " .
                  "SET `preference`.`description` = 'Force HTTP playback regardless of port' " .
                  "WHERE `preference`.`name` = 'force_http_play' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Playback Type' " .
               "WHERE `preference`.`name` = 'play_type' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'httpQ Active Instance' " .
               "WHERE `preference`.`name` = 'httpq_active' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Now Playing filtered per user' " .
               "WHERE `preference`.`name` = 'now_playing_per_user' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Use Subsonic backend' " .
               "WHERE `preference`.`name` = 'subsonic_backend' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Share Now Playing information' " .
               "WHERE `preference`.`name` = 'allow_personal_info_now' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Share Recently Played information' " .
               "WHERE `preference`.`name` = 'allow_personal_info_recent' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming date/time' " .
               "WHERE `preference`.`name` = 'allow_personal_info_time' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming agent' " .
               "WHERE `preference`.`name` = 'allow_personal_info_agent' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Enable URL Rewriting' " .
               "WHERE `preference`.`name` = 'stream_beautiful_url' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Destination catalog' " .
               "WHERE `preference`.`name` = 'upload_catalog' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow user uploads' " .
               "WHERE `preference`.`name` = 'allow_upload' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Create a subdirectory per user' " .
               "WHERE `preference`.`name` = 'upload_subdir' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Consider the user sender as the track''s artist' " .
               "WHERE `preference`.`name` = 'upload_user_artist' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Post-upload script (current directory = upload target directory)' " .
               "WHERE `preference`.`name` = 'upload_script' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow users to edit uploaded songs' " .
               "WHERE `preference`.`name` = 'upload_allow_edit' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow users to remove uploaded songs' " .
               "WHERE `preference`.`name` = 'upload_allow_remove' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Show Albums of the Moment' " .
               "WHERE `preference`.`name` = 'home_moment_albums' ";
        $retval &= Dba::write($sql);

        $sql    = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Show Videos of the Moment' " .
               "WHERE `preference`.`name` = 'home_moment_videos' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Custom URL - Logo' " .
               "WHERE `preference`.`name` = 'custom_logo' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Custom URL - Login page logo' " .
               "WHERE `preference`.`name` = 'custom_login_logo' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Custom URL - Favicon' " .
               "WHERE `preference`.`name` = 'custom_favicon' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Album - Default sort' " .
               "WHERE `preference`.`name` = 'album_sort' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow Geolocation' " .
               "WHERE `preference`.`name` = 'Geolocation' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow Video Features' " .
               "WHERE `preference`.`name` = 'allow_video' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Democratic - Clear votes for expired user sessions' " .
               "WHERE `preference`.`name` = 'demo_clear_sessions' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow Transcoding' " .
               "WHERE `preference`.`name` = 'transcoding' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Authorize Flash Web Player' " .
               "WHERE `preference`.`name` = 'webplayer_flash' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Authorize HTML5 Web Player' " .
               "WHERE `preference`.`name` = 'webplayer_html5' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Web Player browser notifications' " .
               "WHERE `preference`.`name` = 'browser_notify' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Web Player browser notifications timeout (seconds)' " .
               "WHERE `preference`.`name` = 'browser_notify_timeout' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Authorize JavaScript decoder (Aurora.js) in Web Player' " .
               "WHERE `preference`.`name` = 'webplayer_aurora' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Show Now Playing' " .
               "WHERE `preference`.`name` = 'home_now_playing' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Show Recently Played' " .
               "WHERE `preference`.`name` = 'home_recently_played' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = '# latest episodes to keep' " .
               "WHERE `preference`.`name` = 'podcast_keep' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = '# episodes to download when new episodes are available' " .
               "WHERE `preference`.`name` = 'podcast_new_download' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow Transcoding' " .
               "WHERE `preference`.`name` = 'transcode' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Allow E-mail notifications' " .
               "WHERE `preference`.`name` = 'notify_email' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Custom metadata - Disable these fields' " .
               "WHERE `preference`.`name` = 'disabled_custom_metadata_fields' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Custom metadata - Define field list' " .
               "WHERE `preference`.`name` = 'disabled_custom_metadata_fields_input' ";
        $retval &= Dba::write($sql);

        $sql = "UPDATE `preference` " .
               "SET `preference`.`description` = 'Auto-pause between tabs' " .
               "WHERE `preference`.`name` = 'webplayer_pausetabs' ";
        $retval &= Dba::write($sql);

        return $retval;
    }

    /**
     * update_400004
     *
     * delete upload_user_artist database settings
     */
    public static function update_400004()
    {
        $retval = true;

        $sql = "DELETE FROM `user_preference` " .
               "WHERE `user_preference`.`preference` IN  " .
               "(SELECT `preference`.`id` FROM `preference`  " .
               "WHERE `preference`.`name` = 'upload_user_artist');";
        $retval &= Dba::write($sql);

        $sql = "DELETE FROM `preference` " .
               "WHERE `preference`.`name` = 'upload_user_artist';";
        $retval &= Dba::write($sql);

        return $retval;
    }
}
