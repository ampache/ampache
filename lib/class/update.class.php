<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
     */
    public static function get_version()
    {
        $version = "";
        /* Make sure that update_info exits */
        $sql = "SHOW TABLES LIKE 'update_info'";
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
            $sql = "SELECT * FROM `update_info` WHERE `key`='db_version'";
            $db_results = Dba::read($sql);
            $results = Dba::fetch_assoc($db_results);
            $version = $results['value'];
        }

        return $version;

    } // get_version

    /**
     * format_version
     *
     * Make the version number pretty.
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
     */
    public static function populate_version()
    {
        /* Define the array */
        $version = array();

        $update_string = '- Moved back to ID for user tracking internally.<br />' .
                '- Added date to user_vote to allow sorting by vote time.<br />' .
                '- Added Random Method and Object Count Preferences.<br />' .
                '- Removed some unused tables/fields.<br />' .
                '- Added Label, Catalog # and Language to Extended Song Data Table.';
        $version[] = array('version' => '340001','description' => $update_string);

        $update_string = '- Added Offset Limit to Preferences and removed from user table.';
        $version[] = array('version' => '340002','description' => $update_string);

        $update_string = '- Moved Art from the Album table into album_data to improve performance.<br />' .
                '- Made some minor changes to song table to reduce size of each row.<br />' .
                '- Moved song_ext_data to song_data to match album_data pattern.<br />' .
                '- Added Playlist Method and Rate Limit Preferences.<br />' .
                '- Renamed preferences and ratings to preference and rating to fit table pattern.<br />' .
                '- Fixed rating table, renamed user_rating to rating and switched 00 for -1.<br />';
        $version[] = array('version' => '340003','description' => $update_string);

        $update_string = '- Alter the Session.id to be VARCHAR(64) to account for all potential configs.<br />' .
                '- Added new user_shout table for Sticky objects / shoutbox.<br />' .
                '- Added new playlist preferences, and new preference catagory of playlist.<br />' .
                '- Tweaked Now Playing Table.<br />';
        $version[] = array('version' => '340004','description' => $update_string);

        $update_string = '- Altered Ratings table so the fields make more sense.<br />' .
                '- Moved Random Method to Playlist catagory.<br />' .
                '- Added Transcode Method to Streaming.<br />';
        $version[] = array('version' => '340005','description' => $update_string);

        $update_string = '- Remove Random Method config option, ended up being useless.<br />' .
                '- Check and change album_data.art to a MEDIUMBLOB if needed.<br />';
        $version[] = array('version' => '340006','description' => $update_string);

        $update_string = '- Altered the session table, making value a LONGTEXT.<br />';
        $version[] = array('version' => '340007','description' => $update_string);

        $update_string = '- Modified Playlist_Data table to account for multiple object types.<br />' .
                '- Verified previous updates, adjusting as needed.<br />' .
                '- Dropped Allow Downsampling pref, configured in cfg file.<br />' .
                '- Renamed Downsample Rate --> Transcode Rate to reflect new terminiology.<br />';
        $version[] = array('version' => '340008','description' => $update_string);

        $update_string = '- Added disk to Album table.<br />' .
                '- Added artist_data for artist images and bios.<br />' .
                '- Added DNS to access list to allow for dns based ACLs.<br />';
        $version[] = array('version' => '340009','description' => $update_string);

        $update_string = '- Removed Playlist Add preference.<br />' .
                '- Moved Localplay* preferences to options.<br />' .
                '- Tweaked Default Playlist Method.<br />' .
                '- Change wording on Localplay preferences.<br />';
        $version[] = array('version' => '340010','description'=>$update_string);

        $update_string = '- Added Democratic Table for new democratic play features.<br />' .
                '- Added Add Path to Catalog to improve add speeds on large catalogs.<br />';
        $version[] = array('version' => '340012','description'=>$update_string);

        $update_string = '- Removed Unused Preferences.<br />' .
                '- Changed Localplay Config to Localplay Access.<br />' .
                '- Changed all XML-RPC acls to RPC to reflect inclusion of new API.<br />';
        $version[] = array('version' => '340013','description'=>$update_string);

        $update_string = '- Removed API Session table, been a nice run....<br />' .
                '- Alterted Session table to handle API sessions correctly.<br />';
        $version[] = array('version' => '340014','description'=>$update_string);

        $update_string = '- Alter Playlist Date Field to fix issues with some MySQL configurations.<br />' .
                '- Alter Rating type to correct AVG issue on searching.<br />';
        $version[] = array('version' => '340015','description'=>$update_string);

        $update_string = '- Alter the Democratic Playlist table, adding base_playlist.<br />' .
                '- Alter tmp_playlist to account for Democratic changes.<br />' .
                '- Cleared Existing Democratic playlists due to changes.<br />';
        $version[] = array('version' => '340016','description'=>$update_string);

        $update_string = '- Fix Tables for new Democratic Play methodology.<br />';
        $version[] = array('version' => '340017','description'=>$update_string);

        $update_string = '- Modify the Tag tables so that they actually work.<br />' .
                '- Alter the Prefix fields to allow for more prefixs.<br />';
        $version[] = array('version' => '350001','description'=>$update_string);

        $update_string = '- Remove Genre Field from song table.<br />' .
                '- Add user_catalog table for tracking user<-->catalog mappings.<br />' .
                '- Add tmp_browse to handle caching rather then session table.<br />';
        $version[] = array('version' => '350002','description'=>$update_string);

        $update_string = '- Modify Tag tables.<br />' .
                '- Remove useless config preferences.<br />';
        $version[] = array('version'=> '350003','description'=>$update_string);

        $update_string = '- Modify ACL table to enable IPv6 ACL support<br />' .
                '- Modify Session Tables to store IPv6 addresses if provided<br />' .
                '- Modify IP History table to store IPv6 addresses and User Agent<br />';
        $version[] = array('version'=>'350004','description'=>$update_string);

        $update_string = "- Add table for Video files<br />";
        $version[] = array('version'=>'350005','description'=>$update_string);

        $update_string = "- Add data for Lyrics<br />";
        $version[] = array('version'=>'350006','description'=>$update_string);

        $update_string = '- Remove unused fields from catalog, playlist, playlist_data<br />' .
                '- Add tables for dynamic playlists<br />' .
                '- Add last_clean to catalog table<br />' .
                '- Add track to tmp_playlist_data<br />' .
                '- Increase Thumbnail blob size<br />';
        $version[] = array('version'=>'350007','description'=>$update_string);

        $update_string = '- Modify Now Playing table to handle Videos<br />' .
                '- Modify tmp_browse to make it easier to prune<br />' .
                '- Add missing indexes to the _data tables<br />' .
                '- Drop unused song.hash<br />' .
                '- Add addition_time and update_time to video table<br />';
        $version[] = array('version'=>'350008','description'=>$update_string);

        $update_string = '- Add MBID (MusicBrainz ID) fields<br />' .
                '- Remove useless preferences<br />';
        $version[] = array('version'=>'360001','description'=>$update_string);

        $update_string = '- Add Bandwidth and Feature preferences to simplify how interface is presented<br />' .
                '- Change Tables to FULLTEXT() for improved searching<br />' .
                '- Increase Filename lengths to 4096<br />' .
                '- Remove useless "KEY" reference from ACL and Catalog tables<br />' .
                '- Add new Remote User / Remote Password fields to Catalog<br />';
        $version[] = array('version'=>'360002','description'=>$update_string);

        $update_string = '- Add image table to store images.<br />' .
                '- Drop album_data and artist_data.<br />';
        $version[] = array('version'=>'360003','description'=>$update_string);

        $update_string = '- Add uniqueness constraint to ratings.<br />';
        $version[] = array('version' => '360004','description' => $update_string);

        $update_string = '- Modify tmp_browse to allow caching of multiple browses per session.<br />';
        $version[] = array('version' => '360005','description' => $update_string);

        $update_string = '- Add table for dynamic playlists.<br />';
        $version[] = array('version' => '360006','description' => $update_string);

        $update_string = '- Verify remote_username and remote_password were added correctly to catalog table.<br />';
        $version[] = array('version' => '360008','description' => $update_string);

        $update_string = '- Allow long sessionids in tmp_playlist table.<br />';
        $version[] = array('version' => '360009', 'description' => $update_string);

        $update_string = '- Allow compound MBIDs in the artist table.<br />';
        $version[] = array('version' => '360010', 'description' => $update_string);

        $update_string = '- Add table to store stream session playlist.<br />';
        $version[] = array('version' => '360011', 'description' => $update_string);

        $update_string = '- Drop enum for the type field in session.<br />';
        $version[] = array('version' => '360012', 'description' => $update_string);

        $update_string = '- Update stream_playlist table to address performance issues.<br />';
        $version[] = array('version' => '360013', 'description' => $update_string);

        $update_string = '- Increase the length of sessionids again.<br />';
        $version[] = array('version' => '360014', 'description' => $update_string);

        $update_string = '- Add iframes parameter to preferences.<br />';
        $version[] = array('version' => '360015', 'description' => $update_string);

        $update_string = '- Optionally filter Now Playing to return only the last song per user.<br />';
        $version[] = array('version' => '360016', 'description' => $update_string);

        $update_string = '- Add user flags on objects.<br />';
        $version[] = array('version' => '360017', 'description' => $update_string);

        $update_string = '- Add album default sort value to preferences.<br />';
        $version[] = array('version' => '360018', 'description' => $update_string);

        $update_string = '- Add option to show number of times a song was played.<br />';
        $version[] = array('version' => '360019', 'description' => $update_string);

        $update_string = '- Catalog types are plugins now.<br />';
        $version[] = array('version' => '360020', 'description' => $update_string);

        $update_string = '- Add insertion date on Now Playing and option to show the current song in page title for Web player.<br />';
        $version[] = array('version' => '360021', 'description' => $update_string);

        $update_string = '- Remove unused live_stream fields and add codec field.<br />';
        $version[] = array('version' => '360022', 'description' => $update_string);

        $update_string = '- Enable/Disable SubSonic and Plex backend.<br />';
        $version[] = array('version' => '360023', 'description' => $update_string);

        $update_string = '- Drop flagged table.<br />';
        $version[] = array('version' => '360024', 'description' => $update_string);

        $update_string = '- Add options to enable HTML5 / Flash on web players.<br />';
        $version[] = array('version' => '360025', 'description' => $update_string);

        $update_string = '- Added agent to `object_count` table.<br />';
        $version[] = array('version' => '360026','description' => $update_string);

        $update_string = '- Add option to allow/disallow to show personnal information to other users (now playing and recently played).<br />';
        $version[] = array('version' => '360027','description' => $update_string);

        $update_string = '- Personnal information: allow/disallow to show in now playing.<br />'.
                '- Personnal information: allow/disallow to show in recently played.<br />'.
                '- Personnal information: allow/disallow to show time and/or agent in recently played.<br />';
        $version[] = array('version' => '360028','description' => $update_string);

        $update_string = '- Add new table to store wanted releases.<br />';
        $version[] = array('version' => '360029','description' => $update_string);

        $update_string = '- New table to store song previews.<br />';
        $version[] = array('version' => '360030','description' => $update_string);

        $update_string = '- Add option to fix header/sidebars position on compatible themes.<br />';
        $version[] = array('version' => '360031','description' => $update_string);

        $update_string = '- Add check update automatically option.<br />';
        $version[] = array('version' => '360032','description' => $update_string);

        $update_string = '- Add song waveform as song data.<br />';
        $version[] = array('version' => '360033','description' => $update_string);

        $update_string = '- Add settings for confirmation when closing window and auto-pause between tabs.<br />';
        $version[] = array('version' => '360034','description' => $update_string);

        $update_string = '- Add beautiful stream url setting.<br />';
        $version[] = array('version' => '360035','description' => $update_string);

        $update_string = '- Remove unused parameters.<br />';
        $version[] = array('version' => '360036','description' => $update_string);

        $update_string = '- Add sharing features.<br />';
        $version[] = array('version' => '360037','description' => $update_string);

        $update_string = '- Add missing albums browse on missing artists.<br />';
        $version[] = array('version' => '360038','description' => $update_string);

        $update_string = '- Add website field on users.<br />';
        $version[] = array('version' => '360039','description' => $update_string);

        $update_string = '- Add channels.<br />';
        $version[] = array('version' => '360041','description' => $update_string);

        $update_string = '- Add broadcasts and player control.<br />';
        $version[] = array('version' => '360042','description' => $update_string);

        $update_string = '- Add slideshow on currently played artist preference.<br />';
        $version[] = array('version' => '360043','description' => $update_string);

        $update_string = '- Add artist description/recommendation external service data cache.<br />';
        $version[] = array('version' => '360044','description' => $update_string);

        $update_string = '- Set user field on playlists as optional.<br />';
        $version[] = array('version' => '360045','description' => $update_string);

        $update_string = '- Add broadcast web player by default preference.<br />';
        $version[] = array('version' => '360046','description' => $update_string);

        $update_string = '- Add apikey field on users.<br />';
        $version[] = array('version' => '360047','description' => $update_string);

        $update_string = '- Add concerts options.<br />';
        $version[] = array('version' => '360048','description' => $update_string);

        $update_string = '- Add album group multiple disks setting.<br />';
        $version[] = array('version' => '360049','description' => $update_string);

        $update_string = '- Add top menu setting.<br />';
        $version[] = array('version' => '360050','description' => $update_string);

        $update_string = '- Copy default .htaccess configurations.<br />';
        $version[] = array('version' => '360051','description' => $update_string);

        $update_string = '- Drop unused dynamic_playlist tables and add session id to votes.<br />';
        $version[] = array('version' => '370001','description' => $update_string);

        $update_string = '- Add tag persistent merge reference.<br />';
        $version[] = array('version' => '370002','description' => $update_string);

        $update_string = '- Add show/hide donate button preference.<br />';
        $version[] = array('version' => '370003','description' => $update_string);

        $update_string = '- Add license information and user\'s artist association.<br />';
        $version[] = array('version' => '370004','description' => $update_string);

        $update_string = '- Add new column album_artist into table song.<br />';
        $version[] = array('version' => '370005','description' => $update_string);

        $update_string = '- Add random and limit options to smart playlists.<br />';
        $version[] = array('version' => '370006','description' => $update_string);

        $update_string = '- Add DAAP backend preference.<br />';
        $version[] = array('version' => '370007','description' => $update_string);

        $update_string = '- Add UPnP backend preference.<br />';
        $version[] = array('version' => '370008','description' => $update_string);

        $update_string = '- Enhance video support with TVShows and Movies.<br />';
        $version[] = array('version' => '370009','description' => $update_string);

        $update_string = '- Add MusicBrainz Album Release Group identifier.<br />';
        $version[] = array('version' => '370010','description' => $update_string);

        $update_string = '- Add Prefix to TVShows and Movies.<br />';
        $version[] = array('version' => '370011','description' => $update_string);

        $update_string = '- Add metadata information to albums / songs / videos.<br />';
        $version[] = array('version' => '370012','description' => $update_string);

        $update_string = '- Replace iframe with ajax page load.<br />';
        $version[] = array('version' => '370013','description' => $update_string);

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

        if (!defined('CLI')) { echo "<ul>\n"; }

        foreach (self::$versions as $update) {

            if ($update['version'] > $current_version) {
                $update_needed = true;
                if (!defined('CLI')) { echo '<li><b>'; }
                echo 'Version: ', self::format_version($update['version']);
                if (defined('CLI')) {
                    echo "\n", str_replace('<br />', "\n", $update['description']), "\n";
                } else {
                    echo '</b><br />', $update['description'], "<br /></li>\n";
                }
            } // if newer

        } // foreach versions

        if (!defined('CLI')) { echo "</ul>\n"; }

        if (!$update_needed) {
            if (!defined('CLI')) { echo '<p align="center">'; }
            echo T_('No updates needed.');
            if (!defined('CLI')) {
                echo ' [<a href="', AmpConfig::get('web_path'), '">', T_('Return to main page'), '</a>]</p>';
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
            echo "<p align=\"center\">Database version too old, please upgrade to <a href=\"http://ampache.org/downloads/ampache-3.3.3.5.tar.gz\">Ampache-3.3.3.5</a> first</p>";
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
                if (in_array($update_function,$methods)) {
                    $success = call_user_func(array('Update',$update_function));

                    // If the update fails drop out
                    if ($success) {
                        self::set_version('db_version', $version['version']);
                    } else {
                        Error::display('update');
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
     */
    private static function set_version($key, $value)
    {
        $sql = "UPDATE update_info SET value='$value' WHERE `key`='$key'";
        Dba::write($sql);
    }

    /**
     * update_340003
     * This update moves the album art out of the album table
     * and puts it in an album_data table. It also makes some
     * minor changes to the song table in an attempt to reduce
     * the size of each row
     */
    public static function update_340003()
    {
        $retval = true;
        $sql = "ALTER TABLE `song` CHANGE `mode` `mode` ENUM( 'abr', 'vbr', 'cbr' ) NULL DEFAULT 'cbr'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` CHANGE `time` `time` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` CHANGE `rate` `rate` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` CHANGE `bitrate` `bitrate` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` CHANGE `track` `track` SMALLINT( 5 ) UNSIGNED NULL DEFAULT NULL ";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user` CHANGE `disabled` `disabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `album_data` (" .
            "`album_id` INT( 11 ) UNSIGNED NOT NULL , " .
            "`art` MEDIUMBLOB NULL , " .
            "`art_mime` VARCHAR( 64 ) NULL , " .
            "`thumb` BLOB NULL , " .
            "`thumb_mime` VARCHAR( 64 ) NULL , " .
            "UNIQUE ( `album_id` )" .
            ") ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        /* Foreach the Albums and move the data into the new album_data table */
        $sql = "SELECT * FROM album";
        $db_results = Dba::write($sql);

        while ($data = Dba::fetch_assoc($db_results)) {
            $id = $data['id'];
            $art = Dba::escape($data['art']);
            $art_mime = Dba::escape($data['art_mime']);
            $sql = "INSERT INTO `album_data` (`album_id`,`art`,`art_mime`)" .
                " VALUES ('$id','$art','$art_mime')";
            $retval = Dba::write($sql) ? $retval : false;
        } // end while

        $sql = "RENAME TABLE `song_ext_data`  TO `song_data`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "RENAME TABLE `preferences` TO `preference`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "RENAME TABLE `ratings` TO `rating`";
        $retval = Dba::write($sql) ? $retval : false;

        // Go ahead and drop the art/thumb stuff
        $sql = "ALTER TABLE `album`  DROP `art`,  DROP `art_mime`,  DROP `thumb`,  DROP `thumb_mime`";
        $retval = Dba::write($sql) ? $retval : false;

        // We need to fix the user_vote table
        $sql = "ALTER TABLE `user_vote` CHANGE `user` `user` INT( 11 ) UNSIGNED NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        // Remove offset limit from the user
        $sql = "ALTER TABLE `user` DROP `offset_limit`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `rating` CHANGE `user_rating` `rating` ENUM( '-1', '0', '1', '2', '3', '4', '5' ) NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        /* Add the rate_limit preference */
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('rate_limit','8192','Rate Limit','100','integer','streaming')";
        $retval = Dba::write($sql) ? $retval : false;

        /* Add the playlist_method preference and remove it from the user table */
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('playlist_method','normal','Playlist Method','5','string','streaming')";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `update_info` ADD UNIQUE (`key`)";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    } // update_340003

    /**
      * update_340004
     * Update the session.id to varchar(64) to handle
     * newer configs
     */
    public static function update_340004()
    {
        $retval = true;
        /* Alter the session.id so that it's 64 */
        $sql = "ALTER TABLE `session` CHANGE `id` `id` VARCHAR( 64 ) NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        /* Add Playlist Related Preferences */
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('playlist_add','append','Add Behavior','5','string','playlist')";
        $retval = Dba::write($sql) ? $retval : false;

        // Switch the existing preferences over to this new catagory
        $sql = "UPDATE `preference` SET `catagory`='playlist' WHERE `name`='playlist_method' " .
            " OR `name`='playlist_type'";
        $retval = Dba::write($sql) ? $retval : false;

        // Change the default value for playlist_method
        $sql = "UPDATE `preference` SET `value`='normal' WHERE `name`='playlist_method'";
        $retval = Dba::write($sql) ? $retval : false;

        // Add in the shoutbox
        $sql = "CREATE TABLE `user_shout` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`user` INT( 11 ) NOT NULL , " .
            "`text` TEXT NOT NULL , " .
            "`date` INT( 11 ) UNSIGNED NOT NULL , " .
            "`sticky` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0', " .
            "`object_id` INT( 11 ) UNSIGNED NOT NULL , " .
            "`object_type` VARCHAR( 32 ) NOT NULL " .
            ") ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user_shout` ADD INDEX ( `sticky` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user_shout` ADD INDEX ( `date` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user_shout` ADD INDEX ( `user` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `now_playing` CHANGE `start_time` `expire` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "OPTIMIZE TABLE `album`";
        Dba::write($sql);

        return $retval;
    } // update_340004

    /**
     * update_340005
     * This update fixes the preferences types
      */
    public static function update_340005()
    {
        $retval = true;

        $sql = "UPDATE `preference` SET `catagory`='playlist' WHERE `name`='random_method'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('transcode','default','Transcoding','25','string','streaming')";
        $retval = Dba::write($sql) ? $retval : false;

        /* We need to check for playlist_method here because I fubar'd an earlier update */
        $sql = "SELECT * FROM `preference` WHERE `name`='playlist_method'";
        $db_results = Dba::read($sql);
        if (!Dba::num_rows($db_results)) {
            /* Add the playlist_method preference and remove it from the user table */
            $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                "VALUES ('playlist_method','default','Playlist Method','5','string','playlist')";
            $retval = Dba::write($sql) ? $retval : false;
        }

        // Add in the object_type to the tmpplaylist data table so that we can have non-songs in there
        $sql = "ALTER TABLE `tmp_playlist_data` ADD `object_type` VARCHAR( 32 ) NULL AFTER `tmp_playlist`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    } // update_340005

    /**
     * update_340006
     * This just updates the size of the album_data table
     * and removes the random_method config option
     */
    public static function update_340006()
    {
        // No matter what remove that random method preference
        Dba::write("DELETE FROM `preference` WHERE `name`='random_method'");
        return true;
    }

    /**
     * update_340007
     * This update converts the session.value to a longtext
     * and adds a session_stream table
     */
    public static function update_340007()
    {
        $retval = true;
        // Tweak the session table to handle larger session vars for my page-a-nation hotness
        $sql = "ALTER TABLE `session` CHANGE `value` `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `now_playing` CHANGE `id` `id` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        // Now longer needed because of the new hotness
        $sql = "ALTER TABLE `now_playing` DROP `session`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    } // update_340007

    /**
     * update_340008
     * This modifies the playlist table to handle the different types of objects that it needs to be able to
     * store, and tweaks how dynamic playlist stuff works
     */
    public static function update_340008()
    {
        $retval = true;
        $sql = "ALTER TABLE `playlist_data` CHANGE `song` `object_id` INT( 11 ) UNSIGNED NULL DEFAULT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `playlist_data` CHANGE `dyn_song` `dynamic_song` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `playlist_data` ADD `object_type` VARCHAR( 32 ) NOT NULL DEFAULT 'song' AFTER `object_id`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `playlist` ADD `genre` INT( 11 ) UNSIGNED NOT NULL AFTER `type`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `preference` WHERE `name`='allow_downsample_playback'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `preference` SET `description`='Transcode Bitrate' WHERE `name`='sample_rate'";
        $retval = Dba::write($sql) ? $retval : false;

        // Check for old tables and drop if found, seems like there was a glitch
        // that caused them not to get dropped.. *shrug*
        $sql = "DROP TABLE IF EXISTS `preferences`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DROP TABLE IF EXISTS `song_ext_data`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DROP TABLE IF EXISTS `ratings`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_340009
     * This modifies the song table to handle pos fields
     */
    public static function update_340009()
    {
        $retval = true;
        $sql = "ALTER TABLE `album` ADD `disk` smallint(5) UNSIGNED DEFAULT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `album` ADD INDEX (`disk`)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `access_list` ADD `dns` VARCHAR( 255 ) NOT NULL AFTER `end`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `artist_data` (" .
            "`artist_id` INT( 11 ) UNSIGNED NOT NULL ," .
            "`art` MEDIUMBLOB NOT NULL ," .
            "`art_mime` VARCHAR( 32 ) NOT NULL ," .
            "`thumb` BLOB NOT NULL ," .
            "`thumb_mime` VARCHAR( 32 ) NOT NULL ," .
            "`bio` TEXT NOT NULL , " .
            "UNIQUE (`artist_id`) ) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_340010
     * Bunch of minor tweaks to the preference table
     */
    public static function update_340010()
    {
        $retval = true;
        $sql = "UPDATE `preference` SET `catagory`='options' WHERE `name` LIKE 'localplay_%'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `preference` WHERE `name`='playlist_add'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `preference` SET `catagory`='plugins' WHERE (`name` LIKE 'mystrands_%' OR `name` LIKE 'lastfm_%') AND `catagory`='options'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `preference` SET `value`='default' WHERE `name`='playlist_method'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `preference` SET `description`='Localplay Config' WHERE `name`='localplay_level'";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_340012
     * This update adds in the democratic stuff, checks for some potentially screwed up indexes
     * and removes the timestamp from the playlist, and adds the field to the catalog for the upload dir
     */
    public static function update_340012()
    {
        $retval = true;
        $sql = "ALTER TABLE `catalog` ADD `add_path` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `path`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `democratic` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," .
            "`name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ," .
            "`cooldown` TINYINT( 4 ) UNSIGNED NULL ," .
            "`level` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '25'," .
            "`user` INT( 11 ) NOT NULL ," .
            "`primary` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `democratic` ADD INDEX (`primary`)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `democratic` ADD INDEX (`level`)";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_340013
     *
     * This update removes a whole bunch of preferences that are no longer
     * being used in any way, and changes the ACL XML-RPC to just RPC
     */
    public static function update_340013()
    {
        $sql = "DELETE FROM `preference` WHERE `name`='localplay_mpd_hostname' OR `name`='localplay_mpd_port' " .
            "OR `name`='direct_link' OR `name`='localplay_mpd_password' OR `name`='catalog_echo_count'";
        Dba::write($sql);

        $sql = "UPDATE `preference` SET `description`='Localplay Access' WHERE `name`='localplay_level'";
        Dba::write($sql);

        $sql = "UPDATE `access_list` SET `type`='rpc' WHERE `type`='xml-rpc'";
        Dba::write($sql);

        // We're not manipulating the structure, so we'll pretend it always works
        return true;
    }

    /**
     * update_340014
     *
     * This update drops the session_api table that I added just two updates ago
     * it's been nice while it lasted but it's time to pack your stuff and GTFO
     * at the same time it updates the core session table to handle the
     * additional stuff we're going to ask it to do.
     */
    public static function update_340014()
    {
        $retval = true;
        $sql = "DROP TABLE IF EXISTS `session_api`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `session` CHANGE `type` `type` ENUM ('mysql','ldap','http','api','xml-rpc') NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `session` ADD `agent` VARCHAR ( 255 ) NOT NULL AFTER `type`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `session` ADD INDEX (`type`)";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_340015
     *
     * This update tweaks the playlist table responding to complaints from usres
     * who say it doesn't work, unreproduceable. This also adds an index to the
     * album art table to try to make the random album art faster
     */
    public static function update_340015()
    {
        $retval = true;
        $sql = "ALTER TABLE `playlist` DROP `date`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `playlist` ADD `date` INT ( 11 ) UNSIGNED NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        // Pull all of the rating information
        $sql = "SELECT `id`,`rating` FROM `rating`";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
        }

        $sql = "ALTER TABLE `rating` DROP `rating`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `rating` ADD `rating` TINYINT ( 4 ) NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        foreach ($results as $row) {
            $rating = Dba::escape($row['rating']);
            $id    = Dba::escape($row['id']);
            $sql = "UPDATE `rating` SET `rating`='$rating' WHERE `id`='$id'";
            Dba::write($sql);
        }

        return $retval;
    }

    /**
     * update_340016
     *
     * This adds in the base_playlist to the democratic table... should have
     * done this in the previous one but I screwed up... sigh.
     */
    public static function update_340016()
    {
        $sql = "ALTER TABLE `democratic` ADD `base_playlist` INT ( 11 ) UNSIGNED NOT NULL AFTER `name`";
        return Dba::write($sql);
    }

    /**
     * update_340017
     *
     * This finalizes the democratic table.
     * And fixes the charset crap.
     */
    public static function update_340017()
    {
        $retval = true;

        $sql = "ALTER TABLE `tmp_playlist` DROP `base_playlist`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `tmp_playlist` WHERE `session`='-1'";
        Dba::write($sql);

        $sql = "TRUNCATE `democratic`";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_350001
     *
     * This updates modifies the tag tables per codeunde1load's specs from his
     * tag patch.
     *
     * It also adjusts the prefix fields so that we can use more prefixes,
     */
    public static function update_350001()
    {
        $retval = true;
        $sql = "ALTER TABLE `tag_map` ADD `tag_id` INT ( 11 ) UNSIGNED NOT NULL AFTER `id`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "RENAME TABLE `tags`  TO `tag`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `tag` CHANGE `map_id` `id` INT ( 11 ) UNSIGNED NOT NULL auto_increment";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `album` CHANGE `prefix` `prefix` VARCHAR ( 32 ) NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `artist` CHANGE `prefix` `prefix` VARCHAR ( 32 ) NULL";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_350002
     *
     * This update adds in the browse_cache table that we use to hold people's
     * cached browse results. Rather then try to store everything in the session
     * we split them out into one serialized array per row, per person. A little
     * slow this way when browsing, but faster and more flexible when not.
     */
    public static function update_350002()
    {
        $retval = true;

        $sql = "ALTER TABLE `song` DROP `genre`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `user_catalog` (`user` INT( 11 ) UNSIGNED NOT NULL ,`catalog` INT( 11 ) UNSIGNED NOT NULL ,`level` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '5', " .
            "INDEX ( `user` )) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user_catalog` ADD INDEX ( `catalog` )";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_350003
     *
     * This update tweakes the tag tables a little bit more, we're going to
     * simplify things for the first little bit and then  if it all works out
     * we will worry about making it complex again. One thing at a time people...
     */
    public static function update_350003()
    {
        $retval = true;

        $sql = "ALTER TABLE `tag` DROP `order`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `tag` ADD UNIQUE ( `name` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `tag` CHANGE `name` `name` VARCHAR( 255 )";
        $retval = Dba::write($sql) ? $retval : false;

        // Make sure that they don't have any of the mystrands crap left
        $sql = "DELETE FROM `preference` WHERE `name`='mystrands_user' OR `name`='mystrands_pass'";
        Dba::write($sql);

        return $retval;
    } // update_350003

    /**
     * update_350004
     *
     * This update makes some changes to the ACL table so that it can support
     * IPv6 entries as well as some other feature enhancements.
     */
    public static function update_350004()
    {
        $retval = true;

        $sql = "ALTER TABLE `session` CHANGE `ip` `ip` VARBINARY( 255 ) NULL";
        $retval = Dba::write($sql) ? $retval : false;

        // Pull all of the IP history, this could take a while
        $sql = "SELECT * FROM `ip_history`";
        $db_results = Dba::read($sql);

        $ip_history = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $row['ip'] = long2ip($row['ip']);
            $ip_history[] = $row;
        }

        // Clear the table before we make the changes
        $sql = "TRUNCATE `ip_history`";
        Dba::write($sql);

        $sql = "ALTER TABLE `ip_history` CHANGE `ip` `ip` VARBINARY( 255 ) NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `ip_history` ADD `agent` VARCHAR ( 255 ) NULL AFTER `date`";
        $retval = Dba::write($sql) ? $retval : false;

        // Reinsert the old rows
        foreach ($ip_history as $row) {
            $ip = Dba::escape(inet_pton($row['ip']));
            $sql = "INSERT INTO `ip_history` (`user`,`ip`,`date`,`agent`) " .
                "VALUES ('" . $row['user'] . "','" . $ip . "','" . $row['date'] . "',NULL)";
            Dba::write($sql);
        }

        // First pull all of their current ACL's
        $sql = "SELECT * FROM `access_list`";
        $db_results = Dba::read($sql);

        $acl_information = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $row['start'] = long2ip($row['start']);
            $row['end'] = long2ip($row['end']);
            $acl_information[] = $row;
        }

        $sql = "TRUNCATE `access_list`";
        Dba::write($sql);

        // Make the changes to the database
        $sql = "ALTER TABLE `access_list` CHANGE `start` `start` VARBINARY( 255 ) NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `access_list` CHANGE `end` `end` VARBINARY( 255 ) NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `access_list` DROP `dns`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `access_list` ADD `enabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `key`";
        $retval = Dba::write($sql) ? $retval : false;

        // If we had nothing in there before add some base ALLOW ALL stuff as
        // we're going to start defaulting Access Control on.
        if (!count($acl_information)) {
            $v6_start = Dba::escape(inet_pton('::'));
            $v6_end = Dba::escape(inet_pton('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'));
            $v4_start = Dba::escape(inet_pton('0.0.0.0'));
            $v4_end = Dba::escape(inet_pton('255.255.255.255'));
            $sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " .
                "VALUES ('DEFAULTv4','75','$v4_start','$v4_end',NULL,'-1','interface','1')";
            Dba::write($sql);
            $sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " .
                "VALUES ('DEFAULTv4','75','$v4_start','$v4_end',NULL,'-1','stream','1')";
            Dba::write($sql);
            $sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " .
                "VALUES ('DEFAULTv6','75','$v6_start','$v6_end',NULL,'-1','interface','1')";
            Dba::write($sql);
            $sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " .
                "VALUES ('DEFAULTv6','75','$v6_start','$v6_end',NULL,'-1','stream','1')";
            Dba::write($sql);
        } // Adding default information

        foreach ($acl_information as $row) {
            $row['start'] = Dba::escape(inet_pton($row['start']));
            $row['end'] = Dba::escape(inet_pton($row['end']));
            $row['key'] = Dba::escape($row['key']);
            $sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " .
                "VALUES ('" . Dba::escape($row['name']) . "','" . intval($row['level']) .
                "','" . $row['start'] . "','" . $row['end'] . "','" . $row['key'] . "','" . intval($row['user']) . "','" .
                $row['type'] . "','1')";
            Dba::write($sql);
        } // end foreach of existing rows

        return $retval;
    }

    /**
     * update_350005
     *
     * This update adds the video table... *gasp* no you didn't <head shake>
     */
    public static function update_350005()
    {
        $retval = true;

        $sql = " CREATE TABLE `video` (" .
            "`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," .
            "`file` VARCHAR( 255 ) NOT NULL , " .
            "`catalog` INT( 11 ) UNSIGNED NOT NULL ," .
            "`title` VARCHAR( 255 ) NOT NULL ," .
            "`video_codec` VARCHAR( 255 ) NOT NULL ," .
            "`audio_codec` VARCHAR( 255 ) NOT NULL ," .
            "`resolution_x` MEDIUMINT UNSIGNED NOT NULL ," .
            "`resolution_y` MEDIUMINT UNSIGNED NOT NULL ," .
            "`time` INT( 11 ) UNSIGNED NOT NULL ," .
            "`size` BIGINT UNSIGNED NOT NULL," .
            "`mime` VARCHAR( 255 ) NOT NULL," .
            "`enabled` TINYINT( 1) NOT NULL DEFAULT '1'" .
            ") ENGINE = MYISAM ";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `access_list` ADD INDEX ( `enabled` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD INDEX ( `file` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD INDEX ( `enabled` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD INDEX ( `title` )";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_350006
     *
     * This update inserts the Lyrics pref table...
     */
    public static function update_350006()
    {
        $sql = "INSERT INTO `preference` VALUES (69,'show_lyrics','0','Show Lyrics',0,'boolean','interface')";
        Dba::write($sql);

        $sql = "INSERT INTO `user_preference` VALUES (1,69,'0')";
        Dba::write($sql);

        return true;
    }

    /**
     * update_350007
     *
     * This update adds in the random rules tables. Also increase the size of the
     * blobs on the album and artist data and add track to tmp_playlist_data
     */
    public static function update_350007()
    {
        $retval = true;

        // We need to clear the thumbs as they will need to be re-generated
        $sql = "UPDATE `album_data` SET `thumb`=NULL,`thumb_mime`=NULL";
        Dba::write($sql);

        $sql = "UPDATE `artist_data` SET `thumb`=NULL,`thumb_mime`=NULL";
        Dba::write($sql);

        // Remove dead column
        $sql = "ALTER TABLE `playlist_data` DROP `dynamic_song`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `playlist` DROP `genre`";
        $retval = Dba::write($sql) ? $retval : false;

        // Add track item to tmp_playlist_data so we can order this stuff manually
        $sql = "ALTER TABLE `tmp_playlist_data` ADD `track` INT ( 11 ) UNSIGNED NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DROP TABLE `genre`";
        $retval = Dba::write($sql) ? $retval : false;

        // Clean up the catalog and add last_clean to it
        $sql = "ALTER TABLE `catalog` ADD `last_clean` INT ( 11 ) UNSIGNED NULL AFTER `last_update`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `catalog` DROP `add_path`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `dynamic_playlist` (" .
            "`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," .
            "`name` VARCHAR( 255 ) NOT NULL ," .
            "`user` INT( 11 ) NOT NULL ," .
            "`date` INT( 11 ) UNSIGNED NOT NULL ," .
            "`type` VARCHAR( 128 ) NOT NULL" .
            ") ENGINE = MYISAM ";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `dynamic_playlist_data` (" .
            "`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," .
            "`dynamic_id` INT( 11 ) UNSIGNED NOT NULL ," .
            "`field` VARCHAR( 255 ) NOT NULL ," .
            "`internal_operator` VARCHAR( 64 ) NOT NULL ," .
            "`external_operator` VARCHAR( 64 ) NOT NULL ," .
            "`value` VARCHAR( 255 ) NOT NULL" .
            ") ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_350008
     *
     * Change song_id references to be object so they are a little more general.
     * Add type to the now playing table so that we can handle different playing
     * information.
     */
    public static function update_350008()
    {
        $retval = true;
        $sql = "ALTER TABLE `now_playing` CHANGE `song_id` `object_id` INT( 11 ) UNSIGNED NOT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `now_playing` ADD `object_type` VARCHAR ( 255 ) NOT NULL AFTER `object_id`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `now_playing` ADD INDEX ( `expire` )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD `addition_time` INT( 11 ) UNSIGNED NOT NULL AFTER `mime`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD `update_time` INT( 11 ) UNSIGNED NULL AFTER `addition_time`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD INDEX (`addition_time`)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD INDEX (`update_time`)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` DROP `hash`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360001
     *
     * This adds the MB UUIDs to the different tables as well as some additional
     * cleanup.
     */
    public static function update_360001()
    {
        $retval = true;

        $sql = "ALTER TABLE `album` ADD `mbid` CHAR ( 36 ) AFTER `prefix`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `artist` ADD `mbid` CHAR ( 36 ) AFTER `prefix`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` ADD `mbid` CHAR ( 36 ) AFTER `track`";
        $retval = Dba::write($sql) ? $retval : false;

        // Remove any RIO related information from the database as the plugin has been removed
        $sql = "DELETE FROM `update_info` WHERE `key` LIKE 'Plugin_Ri%'";
        Dba::write($sql);

        $sql = "DELETE FROM `preference` WHERE `name` LIKE 'rio_%'";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_360002
     *
     * This update makes changes to the cataloging to accomodate the new method
     * for syncing between Ampache instances.
     */
    public static function update_360002()
    {
        $retval = true;
        // Drop the key from catalog and ACL
        $sql = "ALTER TABLE `catalog` DROP `key`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `access_list` DROP `key`";
        $retval = Dba::write($sql) ? $retval : false;

        // Add in Username / Password for catalog - to be used for remote catalogs
        $sql = "ALTER TABLE `catalog` ADD `remote_username` VARCHAR ( 255 ) AFTER `catalog_type`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `catalog` ADD `remote_password` VARCHAR ( 255 ) AFTER `remote_username`";
        $retval = Dba::write($sql) ? $retval : false;

        // Adjust the Filename field in song, make it gi-normous. If someone has
        // anything close to this file length, they seriously need to reconsider
        // what they are doing.
        $sql = "ALTER TABLE `song` CHANGE `file` `file` VARCHAR ( 4096 )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` CHANGE `file` `file` VARCHAR ( 4096 )";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `live_stream` CHANGE `url` `url` VARCHAR ( 4096 )";
        $retval = Dba::write($sql) ? $retval : false;

        // Index the Artist, Album, and Song tables for fulltext searches.
        $sql = "ALTER TABLE `artist` ADD FULLTEXT(`name`)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `album` ADD FULLTEXT(`name`)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` ADD FULLTEXT(`title`)";
        $retval = Dba::write($sql) ? $retval : false;

        // Now add in the min_object_count preference and the random_method
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('bandwidth','50','Bandwidth','5','integer','interface')";
        Dba::write($sql);

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('features','50','Features','5','integer','interface')";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_360003
     *
     * This update moves the image data to its own table.
     */
    public static function update_360003()
    {
        $sql = "CREATE TABLE `image` (" .
            "`id` int(11) unsigned NOT NULL auto_increment," .
            "`image` mediumblob NOT NULL," .
            "`mime` varchar(64) NOT NULL," .
            "`size` varchar(64) NOT NULL," .
            "`object_type` varchar(64) NOT NULL," .
            "`object_id` int(11) unsigned NOT NULL," .
            "PRIMARY KEY  (`id`)," .
            "KEY `object_type` (`object_type`)," .
            "KEY `object_id` (`object_id`)" .
            ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $retval = Dba::write($sql);

        foreach (array('album', 'artist') as $type) {
            $sql = "SELECT `" . $type . "_id` AS `object_id`, " .
                "`art`, `art_mime` FROM `" . $type .
                "_data` WHERE `art` IS NOT NULL";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT INTO `image` " .
                    "(`image`, `mime`, `size`, " .
                    "`object_type`, `object_id`) " .
                    "VALUES('" . Dba::escape($row['art']) .
                    "', '" . $row['art_mime'] .
                    "', 'original', '" . $type . "', '" .
                    $row['object_id'] . "')";
                Dba::write($sql);
            }
            $sql = "DROP TABLE `" . $type . "_data`";
            $retval = Dba::write($sql) ? $retval : false;
        }

        return $retval;
    }

    /**
     * update_360004
     *
     * This update creates an index on the rating table.
     */
    public static function update_360004()
    {
        $sql = "CREATE UNIQUE INDEX `unique_rating` ON `rating` (`user`, `object_type`, `object_id`)";
        return Dba::write($sql);
    }

    /**
     * update_360005
     *
     * This changes the tmp_browse table around.
     */
    public static function update_360005()
    {
        $retval = true;

        $sql = "DROP TABLE IF EXISTS `tmp_browse`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `tmp_browse` (" .
        "`id` int(13) NOT NULL auto_increment," .
        "`sid` varchar(128) character set utf8 NOT NULL default ''," .
        "`data` longtext NOT NULL," .
        "`object_data` longtext," .
        "PRIMARY KEY  (`sid`,`id`)" .
        ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360006
     *
     * This adds the table for newsearch/dynamic playlists
     */
    public static function update_360006()
    {
        $sql = "CREATE TABLE `search` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `user` int(11) NOT NULL,
        `type` enum('private','public') CHARACTER SET utf8 DEFAULT NULL,
        `rules` mediumtext NOT NULL,
        `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
        `logic_operator` varchar(3) CHARACTER SET utf8 DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8";
        return Dba::write($sql);
    }

    /**
     * update_360008
     *
     * Fix bug that caused the remote_username/password fields to not be created.
     * FIXME: Huh?
     */
    public static function update_360008()
    {
        $retval = true;
        $remote_username = false;
        $remote_password = false;

        $sql = "DESCRIBE `catalog`";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'remote_username') {
                $remote_username = true;
            }
            if ($row['Field'] == 'remote_password') {
                $remote_password = true;
            }
        } // end while

        if (!$remote_username) {
            // Add in Username / Password for catalog - to be used for remote catalogs
            $sql = "ALTER TABLE `catalog` ADD `remote_username` VARCHAR ( 255 ) AFTER `catalog_type`";
            $retval = Dba::write($sql) ? $retval : false;
        }
        if (!$remote_password) {
            $sql = "ALTER TABLE `catalog` ADD `remote_password` VARCHAR ( 255 ) AFTER `remote_username`";
            $retval = Dba::write($sql) ? $retval : false;
        }

        return $retval;
    }

    /**
     * update_360009
     *
     * The main session table was already updated to use varchar(64) for the ID,
     * tmp_playlist needs the same change
     */
    public static function update_360009()
    {
        $sql = "ALTER TABLE `tmp_playlist` CHANGE `session` `session` VARCHAR(64)";
        return Dba::write($sql);
    }

    /**
     * update_360010
     *
     * MBz NGS means collaborations have more than one MBID (the ones
     * belonging to the underlying artists).  We need a bigger column.
     */
    public static function update_360010()
    {
        $sql = 'ALTER TABLE `artist` CHANGE `mbid` `mbid` VARCHAR(1369)';
        return Dba::write($sql);
    }

    /**
     * update_360011
     *
     * We need a place to store actual playlist data for downloadable
     * playlist files.
     */
    public static function update_360011()
    {
        $sql = 'CREATE TABLE `stream_playlist` (' .
            '`id` int(11) unsigned NOT NULL AUTO_INCREMENT,' .
            '`sid` varchar(64) NOT NULL,' .
            '`url` text NOT NULL,' .
            '`info_url` text DEFAULT NULL,' .
            '`image_url` text DEFAULT NULL,' .
            '`title` varchar(255) DEFAULT NULL,' .
            '`author` varchar(255) DEFAULT NULL,' .
            '`album` varchar(255) DEFAULT NULL,' .
            '`type` varchar(255) DEFAULT NULL,' .
            '`time` smallint(5) DEFAULT NULL,' .
            'PRIMARY KEY (`id`), KEY `sid` (`sid`))';
        return Dba::write($sql);
    }

    /**
     * update_360012
     *
     * Drop the enum on session.type
     */
    public static function update_360012()
    {
        return Dba::write('ALTER TABLE `session` CHANGE `type` `type` VARCHAR(16) DEFAULT NULL');
    }

    /**
     * update_360013
     *
     * MyISAM works better out of the box for the stream_playlist table
     */
    public static function update_360013()
    {
        return Dba::write('ALTER TABLE `stream_playlist` ENGINE=MyISAM');
    }

    /**
     * update_360014
     *
     * PHP session IDs are an ever-growing beast.
     */
    public static function update_360014()
    {
        $retval = true;

        $retval = Dba::write('ALTER TABLE `stream_playlist` CHANGE `sid` `sid` VARCHAR(256)') ? $retval : false;
        $retval = Dba::write('ALTER TABLE `tmp_playlist` CHANGE `session` `session` VARCHAR(256)') ? $retval : false;
        $retval = Dba::write('ALTER TABLE `session` CHANGE `id` `id` VARCHAR(256) NOT NULL') ? $retval : false;

        return $retval;
    }

    /**
     * update_360015
     *
     * This inserts the Iframes preference...
     */
    public static function update_360015()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('iframes','1','Iframes',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /*
     * update_360016
     *
     * Add Now Playing filtered per user preference option
     */
    public static function update_360016()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('now_playing_per_user','1','Now playing filtered per user',50,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360017
     *
     * New table to store user flags.
     */
    public static function update_360017()
    {
        $sql = "CREATE TABLE `user_flag` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`user` int(11) NOT NULL," .
            "`object_id` int(11) unsigned NOT NULL," .
            "`object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL," .
            "`date` int(11) unsigned NOT NULL DEFAULT '0'," .
            "PRIMARY KEY (`id`)," .
            "UNIQUE KEY `unique_userflag` (`user`,`object_type`,`object_id`)," .
            "KEY `object_id` (`object_id`)) ENGINE = MYISAM";
        return Dba::write($sql);
    }

    /**
     * update_360018
     *
     * Add Album default sort preference...
     */
    public static function update_360018()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('album_sort','0','Album Default Sort',25,'string','interface')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360019
     *
     * Add Show number of times a song was played preference
     */
    public static function update_360019()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('show_played_times','0','Show # played',25,'string','interface')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360020
     *
     * Catalog types are plugins now
     */
    public static function update_360020()
    {
        $retval = true;

        $sql = "SELECT `id`, `catalog_type`, `path`, `remote_username`, `remote_password` FROM `catalog`";
        $db_results = Dba::read($sql);

        $c = Catalog::create_catalog_type('local');
        $c->install();
        $c = Catalog::create_catalog_type('remote');
        $c->install();

        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['catalog_type'] == 'local') {
                $sql = "INSERT INTO `catalog_local` (`path`, `catalog_id`) VALUES (?, ?)";
                $retval = Dba::write($sql, array($results['path'], $results['id'])) ? $retval : false;
            } elseif ($results['catalog_type'] == 'remote') {
                $sql = "INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)";
                $retval = Dba::write($sql, array($results['path'], $results['remote_username'], $results['remote_password'], $results['id'])) ? $retval : false;
            }
        }

        $sql = "ALTER TABLE `catalog` DROP `path`, DROP `remote_username`, DROP `remote_password`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `catalog` MODIFY COLUMN `catalog_type` varchar(128)";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `artist` SET `mbid` = null WHERE `mbid` = ''";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `album` SET `mbid` = null WHERE `mbid` = ''";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "UPDATE `song` SET `mbid` = null WHERE `mbid` = ''";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360021
     *
     * Add insertion date on Now Playing and option to show the current song in page title for Web player
     */
    public static function update_360021()
    {
        $retval = true;

        $sql = "ALTER TABLE `now_playing` ADD `insertion` INT (11) AFTER `expire`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('song_page_title','1','Show current song in Web player page title',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360022
     *
     * Remove unused live_stream fields and add codec field
     */
    public static function update_360022()
    {
        $retval = true;

        $sql = "ALTER TABLE `live_stream` ADD `codec` VARCHAR(32) NULL AFTER `catalog`, DROP `frequency`, DROP `call_sign`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `stream_playlist` ADD `codec` VARCHAR(32) NULL AFTER `time`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360023
     *
     * Enable/Disable SubSonic and Plex backend
     */
    public static function update_360023()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('subsonic_backend','1','Use SubSonic backend',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval: false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('plex_backend','0','Use Plex backend',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360024
     *
     * Drop unused flagged table
     */
    public static function update_360024()
    {
        $sql = "DROP TABLE IF EXISTS `flagged`";
        return Dba::write($sql);
    }

    /**
     * update_360025
     *
     * Add options to enable HTML5 / Flash on web players
     */
    public static function update_360025()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('webplayer_flash','1','Authorize Flash Web Player(s)',25,'boolean','streaming')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('webplayer_html5','1','Authorize HTML5 Web Player(s)',25,'boolean','streaming')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360026
     *
     * Add agent field in `object_count` table
     */
    public static function update_360026()
    {
        $sql = "ALTER TABLE `object_count` ADD `agent` VARCHAR(255) NULL AFTER `user`";
        return Dba::write($sql);
    }

    /**
     * update_360027
     *
     * Personal information: allow/disallow to show my personal information into now playing and recently played lists.
     */
    public static function update_360027()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('allow_personal_info','1','Allow to show my personal info to other users (now playing, recently played)',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;

        $id = Dba::insert_id();

        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360028
     *
     * Personal information: allow/disallow to show in now playing.
     * Personal information: allow/disallow to show in recently played.
     * Personal information: allow/disallow to show time and/or agent in recently played.
     */
    public static function update_360028()
    {
        $retval = true;

        // Update previous update preference
        $sql = "UPDATE `preference` SET `name`='allow_personal_info_now', `description`='Personal information visibility - Now playing' WHERE `name`='allow_personal_info'";
        $retval = Dba::write($sql) ? $retval : false;

        // Insert new recently played preference
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('allow_personal_info_recent','1','Personal information visibility - Recently played',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        // Insert streaming time preference
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('allow_personal_info_time','1','Personal information visibility - Recently played - Allow to show streaming date/time',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        // Insert streaming agent preference
        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('allow_personal_info_agent','1','Personal information visibility - Recently played - Allow to show streaming agent',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360029
     *
     * New table to store wanted releases
     */
    public static function update_360029()
    {
        $sql = "CREATE TABLE `wanted` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`user` int(11) NOT NULL," .
            "`artist` int(11) NOT NULL," .
            "`mbid` varchar(36) CHARACTER SET utf8 NULL," .
            "`name` varchar(255) CHARACTER SET utf8 NOT NULL," .
            "`year` int(4) NULL," .
            "`date` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`accepted` tinyint(1) NOT NULL DEFAULT '0'," .
            "PRIMARY KEY (`id`)," .
            "UNIQUE KEY `unique_wanted` (`user`, `artist`,`mbid`)) ENGINE = MYISAM";

        return Dba::write($sql);
    }

    /**
     * update_360030
     *
     * New table to store song previews
     */
    public static function update_360030()
    {
        $sql = "CREATE TABLE `song_preview` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`session` varchar(256) CHARACTER SET utf8 NOT NULL," .
            "`artist` int(11) NOT NULL," .
            "`title` varchar(255) CHARACTER SET utf8 NOT NULL," .
            "`album_mbid` varchar(36) CHARACTER SET utf8 NULL," .
            "`mbid` varchar(36) CHARACTER SET utf8 NULL," .
            "`disk` int(11) NULL," .
            "`track` int(11) NULL," .
            "`file` varchar(255) CHARACTER SET utf8 NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";

        return Dba::write($sql);
    }

    /**
     * update_360031
     *
     * Add option to fix header/sidebars position on compatible themes
     */
    public static function update_360031()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('ui_fixed','0','Fix header/sidebars position on compatible themes',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360032
     *
     * Add check update automatically option
     */
    public static function update_360032()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('autoupdate','1','Check for Ampache updates automatically',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        Preference::insert('autoupdate_lastcheck','AutoUpdate last check time','','25','string','internal');
        Preference::insert('autoupdate_lastversion','AutoUpdate last version from last check','','25','string','internal');
        Preference::insert('autoupdate_lastversion_new','AutoUpdate last version from last check is newer','','25','boolean','internal');

        return $retval;
    }

    /**
     * update_360033
     *
     * Add song waveform as song data
     */
    public static function update_360033()
    {
        $retval = true;

        $sql = "ALTER TABLE `song_data` ADD `waveform` MEDIUMBLOB NULL AFTER `language`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user_shout` ADD `data` VARCHAR(256) NULL AFTER `object_type`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360034
     *
     * Add settings for confirmation when closing window and auto-pause between tabs
     */
    public static function update_360034()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('webplayer_confirmclose','0','Confirmation when closing current playing window',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('webplayer_pausetabs','1','Auto-pause betweens tabs',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360035
     *
     * Add beautiful stream url setting
     */
    public static function update_360035()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('stream_beautiful_url','0','Use beautiful stream url',25,'boolean','streaming')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360036
     *
     * Remove some unused parameters
     */
    public static function update_360036()
    {
        $retval = true;

        $sql = "DELETE FROM `preference` WHERE `name` LIKE 'ellipse_threshold_%'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `preference` WHERE `name` = 'min_object_count'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `preference` WHERE `name` = 'bandwidth'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `preference` WHERE `name` = 'features'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "DELETE FROM `preference` WHERE `name` = 'tags_userlist'";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360037
     *
     * Add sharing features
     */
    public static function update_360037()
    {
        $retval = true;

        $sql = "CREATE TABLE `share` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`user` int(11) unsigned NOT NULL," .
            "`object_type` varchar(32) NOT NULL," .
            "`object_id` int(11) unsigned NOT NULL," .
            "`allow_stream` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`allow_download` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`expire_days` int(4) unsigned NOT NULL DEFAULT '0'," .
            "`max_counter` int(4) unsigned NOT NULL DEFAULT '0'," .
            "`secret` varchar(20) CHARACTER SET utf8 NULL," .
            "`counter` int(4) unsigned NOT NULL DEFAULT '0'," .
            "`creation_date` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`lastvisit_date` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`public_url` varchar(255) CHARACTER SET utf8 NULL," .
            "`description` varchar(255) CHARACTER SET utf8 NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('share','0','Allow Share',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('share_expire','7','Share links default expiration days (0=never)',25,'integer','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'7')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360038
     *
     * Add missing albums browse on missing artists
     */
    public static function update_360038()
    {
        $retval = true;

        $sql = "ALTER TABLE `wanted` ADD `artist_mbid` varchar(1369) CHARACTER SET utf8 NULL AFTER `artist`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `wanted` MODIFY `artist` int(11) NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song_preview` ADD `artist_mbid` varchar(1369) CHARACTER SET utf8 NULL AFTER `artist`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song_preview` MODIFY `artist` int(11) NULL";
        $retval= Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360039
     *
     * Add website field on users
     */
    public static function update_360039()
    {
        $sql = "ALTER TABLE `user` ADD `website` varchar(255) CHARACTER SET utf8 NULL AFTER `email`";
        return Dba::write($sql);
    }

    /**
     * update_360040 skipped.
     */

    /**
     * update_360041
     *
     * Add channels
     */
    public static function update_360041()
    {
        $sql = "CREATE TABLE `channel` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`name` varchar(64) CHARACTER SET utf8 NULL," .
            "`description` varchar(256) CHARACTER SET utf8 NULL," .
            "`url` varchar(256) CHARACTER SET utf8 NULL," .
            "`interface` varchar(64) CHARACTER SET utf8 NULL," .
            "`port` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`fixed_endpoint` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`object_type` varchar(32) NOT NULL," .
            "`object_id` int(11) unsigned NOT NULL," .
            "`is_private` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`random` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`loop` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`admin_password` varchar(20) CHARACTER SET utf8 NULL," .
            "`start_date` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`max_listeners` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`peak_listeners` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`listeners` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`connections` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`stream_type` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT 'mp3'," .
            "`bitrate` int(11) unsigned NOT NULL DEFAULT '128'," .
            "`pid` int(11) unsigned NOT NULL DEFAULT '0'," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        return Dba::write($sql);
    }

    /**
     * update_360042
     *
     * Add broadcasts and player control
     */
    public static function update_360042()
    {
        $retval = true;

        $sql = "CREATE TABLE `broadcast` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`user` int(11) unsigned NOT NULL," .
            "`name` varchar(64) CHARACTER SET utf8 NULL," .
            "`description` varchar(256) CHARACTER SET utf8 NULL," .
            "`is_private` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`song` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`started` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
            "`listeners` int(11) unsigned NOT NULL DEFAULT '0'," .
            "`key` varchar(32) CHARACTER SET utf8 NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval= Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `player_control` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`user` int(11) unsigned NOT NULL," .
            "`cmd` varchar(32) CHARACTER SET utf8 NOT NULL," .
            "`value` varchar(256) CHARACTER SET utf8 NULL," .
            "`object_type` varchar(32) NOT NULL," .
            "`object_id` int(11) unsigned NOT NULL," .
            "`send_date` int(11) unsigned NOT NULL DEFAULT '0'," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360043
     *
     * Add slideshow on currently played artist preference
     */
    public static function update_360043()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('slideshow_time','0','Artist slideshow inactivity time',25,'integer','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360044
     *
     * Add artist description/recommendation external service data cache
     */
    public static function update_360044()
    {
        $retval = true;

        $sql = "ALTER TABLE `artist` ADD `summary` TEXT CHARACTER SET utf8 NULL," .
            "ADD `placeformed` varchar(64) NULL," .
            "ADD `yearformed` int(4) NULL," .
            "ADD `last_update` int(11) unsigned NOT NULL DEFAULT '0'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `recommendation` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`object_type` varchar(32) NOT NULL," .
            "`object_id` int(11) unsigned NOT NULL," .
            "`last_update` int(11) unsigned NOT NULL DEFAULT '0'," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `recommendation_item` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`recommendation` int(11) unsigned NOT NULL," .
            "`recommendation_id` int(11) unsigned NULL," .
            "`name` varchar(256) NULL," .
            "`rel` varchar(256) NULL," .
            "`mbid` varchar(1369) NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_360045
     *
     * Set user field on playlists as optional
     */
    public static function update_360045()
    {
        $sql = "ALTER TABLE `playlist` MODIFY `user` int(11) NULL";
        return Dba::write($sql);
    }

    /**
     * update_360046
     *
     * Add broadcast web player by default preference
     */
    public static function update_360046()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('broadcast_by_default','0','Broadcast web player by default',25,'boolean','streaming')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360047
     *
     * Add apikey field on users
     */
    public static function update_360047()
    {
        $sql = "ALTER TABLE `user` ADD `apikey` varchar(255) CHARACTER SET utf8 NULL AFTER `website`";
        return Dba::write($sql);
    }

    /**
     * update_360048
     *
     * Add concerts options
     */
    public static function update_360048()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('concerts_limit_future','0','Limit number of future events',25,'integer','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('concerts_limit_past','0','Limit number of past events',25,'integer','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360049
     *
     * Add album group multiple disks setting
     */
    public static function update_360049()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('album_group','0','Album - Group multiple disks',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360050
     *
     * Add top menu setting
     */
    public static function update_360050()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('topmenu','0','Top menu',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_360051
     *
     * Copy default .htaccess configurations
     */
    public static function update_360051()
    {
        require_once AmpConfig::get('prefix') . '/lib/install.lib.php';

        if (!install_check_server_apache()) {
            debug_event('update', 'Not using Apache, update 360051 skipped.', '5');
            return true;
        }

        $htaccess_play_file = AmpConfig::get('prefix') . '/play/.htaccess';
        $htaccess_rest_file = AmpConfig::get('prefix') . '/rest/.htaccess';

        $ret = true;
        if (!is_readable($htaccess_play_file)) {
            $created = false;
            if (check_htaccess_play_writable()) {
                if (!install_rewrite_rules($htaccess_play_file, AmpConfig::get('raw_web_path'), false)) {
                    Error::add('general', T_('File copy error.'));
                } else {
                    $created = true;
                }
            }

            if (!$created) {
                Error::add('general', T_('Cannot copy default .htaccess file.') . ' Please copy <b>' . $htaccess_play_file . '.dist</b> to <b>' . $htaccess_play_file . '</b>.');
                $ret = false;
            }
        }

        if (!is_readable($htaccess_rest_file)) {
            $created = false;
            if (check_htaccess_rest_writable()) {
                if (!install_rewrite_rules($htaccess_rest_file, AmpConfig::get('raw_web_path'), false)) {
                    Error::add('general', T_('File copy error.'));
                } else {
                    $created = true;
                }
            }

            if (!$created) {
                Error::add('general', T_('Cannot copy default .htaccess file.') . ' Please copy <b>' . $htaccess_rest_file . '.dist</b> to <b>' . $htaccess_rest_file . '</b>.');
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * update_370001
     *
     * Drop unused dynamic_playlist tables and add session id to votes
     */
    public static function update_370001()
    {
        $retval = true;

        $sql = "DROP TABLE dynamic_playlist";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "DROP TABLE dynamic_playlist_data";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `user_vote` ADD `sid` varchar(256) CHARACTER SET utf8 NULL AFTER `date`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('demo_clear_sessions','0','Clear democratic votes of expired user sessions',25,'boolean','playlist')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_370002
     *
     * Add tag persistent merge reference
     */
    public static function update_370002()
    {
        $sql = "ALTER TABLE `tag` ADD `merged_to` int(11) NULL AFTER `name`";
        return Dba::write($sql);
    }

    /**
     * update_370003
     *
     * Add show/hide donate button preference
     */
    public static function update_370003()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('show_donate','1','Show donate button in footer',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_370004
     *
     * Add license information and user's artist association
     */
    public static function update_370004()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('upload_catalog','-1','Uploads catalog destination',25,'integer','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'-1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('allow_upload','0','Allow users to upload media',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('upload_subdir','1','Upload: create a subdirectory per user (recommended)',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('upload_user_artist','0','Upload: consider the user sender as the track\'s artist',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('upload_script','','Upload: run the following script after upload (current directory = upload target directory)',25,'string','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('upload_allow_edit','1','Upload: allow users to edit uploaded songs',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "ALTER TABLE `artist` ADD `user` int(11) NULL AFTER `last_update`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `license` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`name` varchar(80) NOT NULL," .
            "`description` varchar(256) NULL," .
            "`external_link` varchar(256) NOT NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY', 'https://creativecommons.org/licenses/by/3.0/')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY NC', 'https://creativecommons.org/licenses/by-nc/3.0/')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY NC ND', 'https://creativecommons.org/licenses/by-nc-nd/3.0/')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY NC SA', 'https://creativecommons.org/licenses/by-nc-sa/3.0/')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY ND', 'https://creativecommons.org/licenses/by-nd/3.0/')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY SA', 'https://creativecommons.org/licenses/by-sa/3.0/')";
        $retval= Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Licence Art Libre', 'http://artlibre.org/licence/lal/')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Yellow OpenMusic', 'http://openmusic.linuxtag.org/yellow.html')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Green OpenMusic', 'http://openmusic.linuxtag.org/green.html')";
        $retval= Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Gnu GPL Art', 'http://gnuart.org/english/gnugpl.html')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('WTFPL', 'https://en.wikipedia.org/wiki/WTFPL')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('FMPL', 'http://www.fmpl.org/fmpl.html')";
        $retval = Dba::write($sql) ? $retval : false;
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('C Reaction', 'http://morne.free.fr/Necktar7/creaction.htm')";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` ADD `user_upload` int(11) NULL AFTER `addition_time`, ADD `license` int(11) NULL AFTER `user_upload`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_370005
     *
     * Add new column album_artist into table album
     *
     */
    public static function update_370005()
    {
        $sql = "ALTER TABLE `song` ADD `album_artist` int(11) unsigned DEFAULT NULL AFTER `artist`";
        return Dba::write($sql);
    }

    /**
     * update_370006
     *
     * Add random and limit options to smart playlists
     *
     */
    public static function update_370006()
    {
        $sql = "ALTER TABLE `search` ADD `random` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `logic_operator`, ADD `limit` int(11) unsigned NOT NULL DEFAULT '0' AFTER `random`";
        return Dba::write($sql);
    }

    /**
     * update_370007
     *
     * Add DAAP backend preference
     */
    public static function update_370007()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('daap_backend','0','Use DAAP backend',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('daap_pass','','DAAP backend password',25,'string','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "CREATE TABLE `daap_session` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`creationdate` int(11) unsigned NOT NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_370008
     *
     * Add UPnP backend preference
     *
     */
    public static function update_370008()
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('upnp_backend','0','Use UPnP backend',25,'boolean','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'0')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_370009
     *
     * Enhance video support with TVShows and Movies
     */
    public static function update_370009()
    {
        $retval = true;

        $sql = "ALTER TABLE `video` ADD `release_date` int(11) unsigned NULL AFTER `enabled`, " .
            "ADD `played` tinyint(1) unsigned DEFAULT '1' NOT NULL AFTER `enabled`";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `tvshow` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`name` varchar(80) NOT NULL," .
            "`summary` varchar(256) NULL," .
            "`year` int(11) unsigned NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `tvshow_season` (" .
            "`id` int(11) unsigned NOT NULL AUTO_INCREMENT," .
            "`season_number` int(11) unsigned NOT NULL," .
            "`tvshow` int(11) unsigned NOT NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `tvshow_episode` (" .
            "`id` int(11) unsigned NOT NULL," .
            "`original_name` varchar(80) NULL," .
            "`season` int(11) unsigned NOT NULL," .
            "`episode_number` int(11) unsigned NOT NULL," .
            "`summary` varchar(256) NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `movie` (" .
            "`id` int(11) unsigned NOT NULL," .
            "`original_name` varchar(80) NULL," .
            "`summary` varchar(256) NULL," .
            "`year` int(11) unsigned NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval= Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `personal_video` (" .
            "`id` int(11) unsigned NOT NULL," .
            "`location` varchar(256) NULL," .
            "`summary` varchar(256) NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "CREATE TABLE `clip` (" .
            "`id` int(11) unsigned NOT NULL," .
            "`artist` int(11) NULL," .
            "`song` int(11) NULL," .
            "PRIMARY KEY (`id`)) ENGINE = MYISAM";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('allow_video','1','Allow video features',25,'integer','system')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        $sql = "ALTER TABLE `image` ADD `kind` VARCHAR( 32 ) NULL DEFAULT 'default' AFTER `object_id`";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_370010
     *
     * Add MusicBrainz Album Release Group identifier
     */
    public static function update_370010()
    {
        $sql = "ALTER TABLE `album` ADD `mbid_group` varchar(36) CHARACTER SET utf8 NULL";
        return Dba::write($sql);
    }

    /**
     * update_370011
     *
     * Add Prefix to TVShows and Movies
     */
    public static function update_370011()
    {
        $retval = true;

        $sql = "ALTER TABLE `tvshow` ADD `prefix` varchar(32) CHARACTER SET utf8 NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `movie` ADD `prefix` varchar(32) CHARACTER SET utf8 NULL";
        $retval = Dba::write($sql) ? $retval : false;

        return $retval;
    }

    /**
     * update_370012
     *
     * Add metadata information to albums / songs / videos
     */
    public static function update_370012()
    {
        $retval = true;

        $sql = "ALTER TABLE `album` ADD `release_type` varchar(32) CHARACTER SET utf8 NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `song` ADD `composer` varchar(256) CHARACTER SET utf8 NULL, ADD `channels` MEDIUMINT NULL";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "ALTER TABLE `video` ADD `channels` MEDIUMINT NULL, ADD `bitrate` MEDIUMINT(8) NULL, ADD `video_bitrate` MEDIUMINT(8) NULL, ADD `display_x` MEDIUMINT(8) NULL, ADD `display_y` MEDIUMINT(8) NULL, ADD `frame_rate` FLOAT NULL, ADD `mode` ENUM( 'abr', 'vbr', 'cbr' ) NULL DEFAULT 'cbr'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('album_release_type','1','Album - Group per release type',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }

    /**
     * update_370013
     *
     * Replace iframe with ajax page load
     */
    public static function update_370013()
    {
        $retval = true;

        $sql = "DELETE FROM `preference` WHERE `name` = 'iframes'";
        $retval = Dba::write($sql) ? $retval : false;

        $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
            "VALUES ('ajax_load','1','Ajax page load',25,'boolean','interface')";
        $retval = Dba::write($sql) ? $retval : false;
        $id = Dba::insert_id();
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,'1')";
        $retval = Dba::write($sql, array($id)) ? $retval : false;

        return $retval;
    }
}
