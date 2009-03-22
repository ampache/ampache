<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/**
 * Update Class
 * this class handles updating from one version of 
 * ampache to the next. Versions are a 6 digit number
 *  220000
 *  ^
 *  Major Revision
 *	
 *  220000
 *   ^
 *  Minor Revision
 *
 * The last 4 digits are a build number...
 * If Minor can't go over 9 Major can go as high as we want
 */
class Update {

	public $key;
	public $value;
	public static $versions; // array containing version information

	/**
	 * Update
	 * Constructor, pulls out information about the desired key
	 */
	function Update ( $key=0 ) {

		if (!$key) { return false; } 

		$this->key = intval($key);
		$info = $this->_get_info();
		$this->value = $info['value'];
		$this->versions = $this->populate_version();

	} // constructor

	/**
	 * _get_info
	 * gets the information for the zone
	 */
	private function _get_info() {

		$sql = "SELECT * FROM `update_info` WHERE `key`='$this->key'";
		$db_results = Dba::query($sql);

		return Dba::fetch_assoc($db_results);		

	} // _get_info

	/**
	 * get_version
	 * this checks to see what version you are currently running
	 * because we may not have the update_info table we have to check 
	 * for it's existance first. 
	 */
	public static function get_version() {

		/* Make sure that update_info exits */
		$sql = "SHOW TABLES LIKE 'update_info'";
		$db_results = Dba::query($sql);
		if (!is_resource(Dba::dbh())) { header("Location: test.php"); } 

		// If no table
		if (!Dba::num_rows($db_results)) {
			// They can't upgrade, they are too old
			header("Location: test.php");
			
		} // if table isn't found

		else {
			// If we've found the update_info table, let's get the version from it
			$sql = "SELECT * FROM `update_info` WHERE `key`='db_version'";
			$db_results = Dba::query($sql);
			$results = Dba::fetch_assoc($db_results);
			$version = $results['value'];
		} 

		return $version;

	} // get_version

	/**
	 * format_version
	 * make the version number pretty
	 */
	public static function format_version($data) {

		$new_version = substr($data,0,strlen($data) - 5) . "." . substr($data,strlen($data)-5,1) . " Build:" . 
				substr($data,strlen($data)-4,strlen($data));

		return $new_version;

	} // format_version

	/**
	 * need_update
	 * checks to see if we need to update 
	 * ampache at all
	 */
	public static function need_update() {

		$current_version = self::get_version();
		
		if (!is_array(self::$versions)) {
			self::$versions = self::populate_version();
		}
		
		/* 
		   Go through the versions we have and see if
		   we need to apply any updates
		*/
		foreach (self::$versions as $update) {
			if ($update['version'] > $current_version) {
				return true;
			}

		} // end foreach version

		return false;

	} // need_update

	/**
	 * plugins_installed
	 * This function checks to make sure that there are no plugins
	 * installed before allowing you to run the update. this is
	 * to protect the integrity of the database
	 */
	public static function plugins_installed() { 

		/* Pull all version info */
		$sql = "SELECT * FROM `update_info`";
		$db_results = Dba::query($sql);

		while ($results = Dba::fetch_assoc($db_results)) { 

			/* We have only one allowed string */
			if ($results['key'] != 'db_version') { 
				return false; 
			}

		} // while update_info results
	
		return true; 

	} // plugins_installed

	/**
	 * populate_version
	 * just sets an array the current differences
	 * that require an update
	 */
	public static function populate_version() {

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

		$update_string = '- Added new session_stream table for sessions tied directly to stream instances.<br />' . 
				'- Altered the session table, making value a LONGTEXT.<br />'; 

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

		$update_string =  '- Added api session table, will eventually recombine with others.<br />'; 

		$version[] = array('version' => '340011','description'=>$update_string); 

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

		$update_string = '- Attempt to detect and correct charset issues between filesystem and database.<br />'; 

		$version[] = array('version' => '340018','description'=>$update_string); 

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


		return $version;

	} // populate_version

	/**
	 * display_update
	 * This displays a list of the needed
	 * updates to the database. This will actually
	 * echo out the list...
	 */
	public static function display_update() {

		$current_version = self::get_version();
		if (!is_array(self::$versions)) {
			self::$versions = self::populate_version();
		} 

		echo "<ul>\n";

		foreach (self::$versions as $version) {
		
			if ($version['version'] > $current_version) {
				$updated = true;
				echo "<li><b>Version: " . self::format_version($version['version']) . "</b><br />";
				echo $version['description'] . "<br /></li>\n"; 
			} // if newer
		
		} // foreach versions

		echo "</ul>\n";

		if (!$updated) { echo "<p align=\"center\">No Updates Needed [<a href=\"" . Config::get('web_path') . "\">Return]</a></p>"; }
	} // display_update

	/**
	 * run_update
	 * This function actually updates the db.
	 * it goes through versions and finds the ones 
	 * that need to be run. Checking to make sure
	 * the function exists first.
	 */
	public static function run_update() {
	
		/* Nuke All Active session before we start the mojo */
		$sql = "TRUNCATE session";
		$db_results = Dba::write($sql);
                
		// Prevent the script from timing out, which could be bad
		set_time_limit(0);

		/* Verify that there are no plugins installed 
		//FIXME: provide a link to remove all plugins, otherwise this could turn into a catch 22
		if (!$self::plugins_installed()) { 
			$GLOBALS['error']->add_error('general',_('Plugins detected, please remove all Plugins and try again'));
			return false; 
		} */

		$methods = array();
		
		$current_version = self::get_version();

		// Run a check to make sure that they don't try to upgrade from a version that
		// won't work. 
		if ($current_version < '340001') { 
			echo "<p align=\"center\">Database version too old, please upgrade to <a href=\"http://ampache.org/downloads/ampache-3.3.3.5.tar.gz\">Ampache-3.3.3.5</a> first</p>";			
			return false; 
		} 
			
		
		$methods = get_class_methods('Update');
		
		if (!is_array((self::$versions))) { 
			self::$versions = self::populate_version();
		}

		foreach (self::$versions as $version) { 

			// If it's newer than our current version
			// let's see if a function exists and run the 
			// bugger
			if ($version['version'] > $current_version) { 
				$update_function = "update_" . $version['version'];
				if (in_array($update_function,$methods)) {
					$success = call_user_func(array('Update',$update_function)); 

					// If the update fails drop out
					if (!$success) { 
						Error::display('update'); 
						return false; 
					} 
				}

			} 
		
		} // end foreach version

		// Once we've run all of the updates let's re-sync the character set as the user
		// can change this between updates and cause mis-matches on any new tables
		Dba::reset_db_charset(); 

	} // run_update

	/**
	 * set_version
	 * This updates the 'update_info' which is used by the updater
	 * and plugins
	 */
	private static function set_version($key,$value) {

		$sql = "UPDATE update_info SET value='$value' WHERE `key`='$key'";
		$db_results = Dba::query($sql);		

	} //set_version

	/**
 	 * update_340001
	 * This update moves back to the ID for user UID and 
	 * adds date to the user_vote so that it can be sorted
	 * correctly
	 */
	private function update_340001() { 


		// Build the User -> ID map using the username as the key
		$sql = "SELECT `id`,`username` FROM `user`"; 
		$db_results = Dba::query($sql);

		$user_array = array(); 

		while ($r = mysql_fetch_assoc($db_results)) { 
			$username = $r['username'];
			$user_array[$username] = Dba::escape($r['id']); 
		} // end while

		// Alter the user table so that you can't have an ID beyond the 
		// range of the other tables which have to allow for -1
		$sql = "ALTER TABLE `user` CHANGE `id` `id` INT ( 11 ) NOT NULL AUTO_INCREMENT";
		$db_results = Dba::query($sql); 

		// Now pull the access list users, alter table and then re-insert
		$sql = "SELECT DISTINCT(`user`) FROM `access_list`"; 
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			// Build the new SQL
			$username	= $r['user'];
			$user_id	= $user_array[$username]; 
			$username	= Dba::escape($username); 

			$sql = "UPDATE `access_list` SET `user`='$user_id' WHERE `user`='$username'"; 
			$update_results = Dba::query($sql); 

		} // end while access_list

		// Alter the table
		$sql = "ALTER TABLE `access_list` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql);

		// Now pull flagged users, update and alter
		$sql = "SELECT DISTINCT(`user`) FROM `flagged`";
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$username	= $r['user']; 
			$user_id	= $user_array[$username];
			$username	= Dba::escape($username); 

			$sql = "UPDATE `flagged` SET `user`='$user_id' WHERE `user`='$username'";
			$update_results = Dba::query($sql); 

		} // end while 

		// Alter the table
		$sql = "ALTER TABLE `flagged` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql); 


		// Now fix up the ip history
		$sql = "SELECT DISTINCT(`user`) FROM `ip_history`";
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$username 	= $r['user'];
			$user_id	= $user_array[$username];
			$username	= Dba::escape($username); 

			$sql = "UPDATE `ip_history` SET `user`='$user_id' WHERE `user`='$username'";
			$update_results = Dba::query($sql); 

		} // end while

		// Alter the table
		$sql = "ALTER TABLE `ip_history` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql); 

		// Now fix now playing
		$sql = "SELECT DISTINCT(`user`) FROM `now_playing`";
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$username	= $r['user'];
			$user_id	= $user_array[$username];
			$username	= Dba::escape($username); 

			$sql = "UPDATE `now_playing` SET `user`='$user_id' WHERE `user`='$username'";
			$update_results = Dba::query($sql); 

		} // end while

		// Alter the table
		$sql = "ALTER TABLE `now_playing` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql); 

		// Now fix the playlist table
		$sql = "SELECT DISTINCT(`user`) FROM `playlist`";
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$username	= $r['user'];
			$user_id	= $user_array[$username];
			$username	= Dba::escape($username); 

			$sql = "UPDATE `playlist` SET `user`='$user_id' WHERE `user`='$username'";
			$update_results = Dba::query($sql); 

		} // end while

		// Alter the table
		$sql = "ALTER TABLE `playlist` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql); 

		// Drop unused table
		$sql = "DROP TABLE `playlist_permission`";
		$db_results = Dba::query($sql); 

		// Now fix the ratings table
		$sql = "SELECT DISTINCT(`user`) FROM `ratings`";
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$username	= $r['user'];
			$user_id	= $user_array[$username];
			$username	= Dba::escape($username); 

			$sql = "UPDATE `ratings` SET `user`='$user_id' WHERE `user`='$username'";
			$update_results = Dba::query($sql); 

		} // end while

		$sql = "ALTER TABLE `ratings` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql); 
		
		// Now work on the tag_map 
		$sql = "ALTER TABLE `tag_map` CHANGE `user_id` `user` INT ( 11 ) NOT NULL"; 
		$db_results = Dba::query($sql); 

		// Now fix user preferences
		$sql = "SELECT DISTINCT(`user`) FROM `user_preference`";
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$username	 = $r['user'];
			$user_id	 = $user_array[$username];
			$username	 = Dba::escape($username); 

			$sql = "UPDATE `user_preference` SET `user`='$user_id' WHERE `user`='$username'"; 
			$update_results = Dba::query($sql); 

		} // end while

		// Alter the table
		$sql = "ALTER TABLE `user_preference` CHANGE `user` `user` INT ( 11 ) NOT NULL";
		$db_results = Dba::query($sql); 

		// Add a date to the user_vote
		$sql = "ALTER TABLE `user_vote` ADD `date` INT( 11 ) UNSIGNED NOT NULL";
		$db_results = Dba::query($sql); 

		// Add the index for said field
		$sql = "ALTER TABLE `user_vote` ADD INDEX(`date`)";
		$db_results = Dba::query($sql); 

		// Add the thumb fields to album
		$sql = "ALTER TABLE `album` ADD `thumb` TINYBLOB NULL ,ADD `thumb_mime` VARCHAR( 128 ) NULL";
		$db_results = Dba::query($sql); 

		// Now add in the min_object_count preference and the random_method
		$sql = "INSERT INTO `preferences` (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES('min_object_count','1','Min Element Count','5','integer','interface')";
		$db_results = Dba::query($sql); 

		$sql = "INSERT INTO `preferences` (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES('random_method','default','Random Method','5','string','interface')"; 
		$db_results = Dba::query($sql); 

		// Delete old preference
		$sql = "DELETE FROM `preferences` WHERE `name`='min_album_size'"; 
		$db_results = Dba::query($sql); 

		// Make Hash a non-required field and smaller
		$sql = "ALTER TABLE `song` CHANGE `hash` `hash` VARCHAR ( 64 ) NULL";
		$db_results = Dba::query($sql); 

		// Make user access an int, nothing else
		$sql = "UPDATE `user` SET `access`='100' WHERE `access`='admin'";
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `user` SET `access`='25' WHERE `access`='user'";
		$db_results = Dba::query($sql); 
		
		$sql = "UPDATE `user` SET `access`='5' WHERE `access`='guest'";
		$db_results = Dba::query($sql); 	

		// Alter the table
		$sql = "ALTER TABLE `user` CHANGE `access` `access` TINYINT ( 4 ) UNSIGNED NOT NULL";
		$db_results = Dba::query($sql); 

		// Add in Label and Catalog # and language
		$sql = "ALTER TABLE `song_ext_data` ADD `label` VARCHAR ( 128 ) NULL, ADD `catalog_number` VARCHAR ( 128 ) NULL, ADD `language` VARCHAR ( 128 ) NULL";
		$db_results = Dba::query($sql); 

                /* Fix every users preferences */
                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql); 
         
                User::fix_preferences('-1');
        
                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                } // while results

		self::set_version('db_version','340001');

		return true; 

	} //update_340001

	/**
 	 * update_340002
	 * This update tweaks the preferences a little more and make sure that the 
	 * min_object_count has a rational value
	 */
	private function update_340002() { 

		/* Add the offset_limit preference and remove it from the user table */
		$sql = "INSERT INTO `preferences` (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES ('offset_limit','50','Offset Limit','5','integer','interface')";
		$db_results = Dba::query($sql); 
		
		self::set_version('db_version','340002'); 

		return true; 

	} // update_340002

	/**
	 * update_340003
	 * This update moves the album art out of the album table
	 * and puts it in an album_data table. It also makes some
	 * minor changes to the song table in an attempt to reduce
	 * the size of each row
	 */
	public static function update_340003() { 

		$sql = "ALTER TABLE `song` CHANGE `mode` `mode` ENUM( 'abr', 'vbr', 'cbr' ) NULL DEFAULT 'cbr'";
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `song` CHANGE `time` `time` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `song` CHANGE `rate` `rate` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `song` CHANGE `bitrate` `bitrate` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `song` CHANGE `track` `track` SMALLINT( 5 ) UNSIGNED NULL DEFAULT NULL "; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `user` CHANGE `disabled` `disabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = Dba::query($sql); 

		$sql = "CREATE TABLE `album_data` (" . 
			"`album_id` INT( 11 ) UNSIGNED NOT NULL , " . 
			"`art` MEDIUMBLOB NULL , " . 
			"`art_mime` VARCHAR( 64 ) NULL , " . 
			"`thumb` BLOB NULL , " . 
			"`thumb_mime` VARCHAR( 64 ) NULL , " . 
			"UNIQUE ( `album_id` )" . 
			") ENGINE = MYISAM";
		$db_results = Dba::query($sql); 

		/* Foreach the Albums and move the data into the new album_data table */
		$sql = "SELECT * FROM album"; 
		$db_results = Dba::query($sql); 

		while ($data = Dba::fetch_assoc($db_results)) { 
			$id = $data['id'];
			$art = Dba::escape($data['art']); 
			$art_mime = Dba::escape($data['art_mime']); 
			$thumb = Dba::escape($data['thumb']); 
			$thumb_mime = Dba::escape($data['thumb_mime']); 
			$sql = "INSERT INTO `album_data` (`album_id`,`art`,`art_mime`,`thumb`,`thumb_mime`)" . 
				" VALUES ('$id','$art','$art_mime','$thumb','$thumb_mime')"; 
			$insert_results = Dba::query($sql); 
		} // end while

		$sql = "RENAME TABLE `song_ext_data`  TO `song_data`"; 
		$db_results = Dba::query($sql); 

		$sql = "RENAME TABLE `preferences` TO `preference`"; 
		$db_results = Dba::query($sql); 

		$sql = "RENAME TABLE `ratings` TO `rating`"; 
		$db_results = Dba::query($sql); 

		// Go ahead and drop the art/thumb stuff
		$sql = "ALTER TABLE `album`  DROP `art`,  DROP `art_mime`,  DROP `thumb`,  DROP `thumb_mime`"; 
		$db_results = Dba::query($sql); 

		// We need to fix the user_vote table
		$sql = "ALTER TABLE `user_vote` CHANGE `user` `user` INT( 11 ) UNSIGNED NOT NULL"; 
		$db_results = Dba::query($sql); 

		// Remove offset limit from the user
		$sql = "ALTER TABLE `user` DROP `offset_limit`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `rating` CHANGE `user_rating` `rating` ENUM( '-1', '0', '1', '2', '3', '4', '5' ) NOT NULL DEFAULT '0'"; 
		$db_results = Dba::query($sql);

                /* Add the rate_limit preference */
                $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('rate_limit','8192','Rate Limit','100','integer','streaming')";
                $db_results = Dba::query($sql);

                /* Add the playlist_method preference and remove it from the user table */
                $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('playlist_method','normal','Playlist Method','5','string','streaming')";
                $db_results = Dba::query($sql);

		$sql = "ALTER TABLE `update_info` ADD UNIQUE (`key`)"; 
		$db_results = Dba::query($sql); 

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1'); 

                while ($r = Dba::fetch_assoc($db_results)) { 
                        User::fix_preferences($r['id']);
                }

		self::set_version('db_version','340003'); 

		return true; 

	} // update_340003

	/**
 	 * update_340004
	 * Update the session.id to varchar(64) to handle 
	 * newer configs
	 */
	public static function update_340004() { 

                /* Alter the session.id so that it's 64 */
                $sql = "ALTER TABLE `session` CHANGE `id` `id` VARCHAR( 64 ) NOT NULL";
		$db_results = Dba::query($sql); 

		/* Add Playlist Related Preferences */
		$sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES ('playlist_add','append','Add Behavior','5','string','playlist')"; 
		$db_results = Dba::query($sql); 

		// Switch the existing preferences over to this new catagory
		$sql = "UPDATE `preference` SET `catagory`='playlist' WHERE `name`='playlist_method' " . 
			" OR `name`='playlist_type'"; 
		$db_results = Dba::query($sql); 
	
		// Change the default value for playlist_method
		$sql = "UPDATE `preference` SET `value`='normal' WHERE `name`='playlist_method'"; 
		$db_results = Dba::query($sql); 
		
		// Add in the shoutbox
		$sql = "CREATE TABLE `user_shout` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " . 
			"`user` INT( 11 ) NOT NULL , " . 
			"`text` TEXT NOT NULL , " . 
			"`date` INT( 11 ) UNSIGNED NOT NULL , " . 
			"`sticky` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0', " . 
			"`object_id` INT( 11 ) UNSIGNED NOT NULL , " . 
			"`object_type` VARCHAR( 32 ) NOT NULL " . 
			") ENGINE = MYISAM"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `user_shout` ADD INDEX ( `sticky` )"; 
		$db_results = Dba::query($sql); 	

		$sql = "ALTER TABLE `user_shout` ADD INDEX ( `date` )"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `user_shout` ADD INDEX ( `user` )"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `now_playing` CHANGE `start_time` `expire` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'"; 
		$db_results = Dba::query($sql); 

		$sql = "OPTIMIZE TABLE `album`"; 
		$db_results = Dba::query($sql); 

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                }

		// Update our database version now that we are all done
		self::set_version('db_version','340004'); 

		return true; 

	} // update_340004	

	/**
	 * update_340005
	 * This update fixes the preferences types 
 	 */
	public static function update_340005() { 

		// Turn user_rating into a tinyint and call it score
		$sql = "ALTER TABLE `rating` CHANGE `user_rating` `score` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `preference` SET `catagory`='playlist' WHERE `name`='random_method'"; 
		$db_results = Dba::query($sql); 

		$sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES ('transcode','default','Transcoding','25','string','streaming')"; 
		$db_results = Dba::query($sql);

		/* We need to check for playlist_method here because I fubar'd an earlier update */
		$sql = "SELECT * FROM `preference` WHERE `name`='playlist_method'"; 
		$db_results = Dba::query($sql); 
		if (!Dba::num_rows($db_results)) { 
	                /* Add the playlist_method preference and remove it from the user table */
	                $sql = "INSERT INTO `preference` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
	                        "VALUES ('playlist_method','default','Playlist Method','5','string','playlist')";
	                $db_results = Dba::query($sql);
		} 

		// Add in the object_type to the tmpplaylist data table so that we can have non-songs in there
		$sql = "ALTER TABLE `tmp_playlist_data` ADD `object_type` VARCHAR( 32 ) NULL AFTER `tmp_playlist`";
		$db_results = Dba::query($sql); 

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                }

		self::set_version('db_version','340005'); 	

		return true; 

	} // update_340005

	/**
	 * update_340006
	 * This just updates the size of the album_data table 
	 * and removes the random_method config option
	 */
	public static function update_340006() { 

		$sql = "DESCRIBE `album_data`"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			if ($row['Field'] == 'art' AND $row['Type'] == 'blob') { 
				$blob_needed = true; 
			} 
		} // end while 
		if ($blob_needed) { 
			$sql = "ALTER TABLE `album_data` CHANGE `art` `art` MEDIUMBLOB NULL DEFAULT NULL";
			$db_results = Dba::query($sql); 
		} 

		// No matter what remove that random method preference
		$sql = "DELETE FROM `preference` WHERE `name`='random_method'";
		$db_results = Dba::query($sql); 

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                }

                self::set_version('db_version','340006');

                return true;


	} // update_340006

	/**
	 * update_340007
	 * This update converts the session.value to a longtext
	 * and adds a session_stream table
	 */
	public static function update_340007() { 

		// Tweak the session table to handle larger session vars for my page-a-nation hotness
		$sql = "ALTER TABLE `session` CHANGE `value` `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"; 
		$db_results = Dba::query($sql); 

		// Create the new stream table where we will store stream SIDs
		$sql = "CREATE TABLE `session_stream` ( " . 
			"`id` VARCHAR( 64 ) NOT NULL , " . 
			"`user` INT( 11 ) UNSIGNED NOT NULL , " . 
			"`agent` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL , " . 
			"`expire` INT( 11 ) UNSIGNED NOT NULL , " . 
			"`ip` INT( 11 ) UNSIGNED NULL , " . 
			"PRIMARY KEY ( `id` ) " . 
			") ENGINE = MYISAM"; 
		$db_results = Dba::query($sql); 

		// Change the now playing to use stream session ids for its ID
		$sql = "ALTER TABLE `now_playing` CHANGE `id` `id` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"; 
		$db_results = Dba::query($sql); 

		// Now longer needed because of the new hotness
		$sql = "ALTER TABLE `now_playing` DROP `session`"; 
		$db_results = Dba::query($sql); 

		self::set_version('db_version','340007'); 

		return true; 

	} // update_340007

	/**
	 * update_340008
	 * This modifies the playlist table to handle the different types of objects that it needs to be able to
	 * store, and tweaks how dynamic playlist stuff works
	 */
	public static function update_340008() { 

		$sql = "ALTER TABLE `playlist_data` CHANGE `song` `object_id` INT( 11 ) UNSIGNED NULL DEFAULT NULL"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `playlist_data` CHANGE `dyn_song` `dynamic_song` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `playlist_data` ADD `object_type` VARCHAR( 32 ) NOT NULL DEFAULT 'song' AFTER `object_id`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `playlist` ADD `genre` INT( 11 ) UNSIGNED NOT NULL AFTER `type`"; 
		$db_results = Dba::query($sql); 

		$sql = "DELETE FROM `preference` WHERE `name`='allow_downsample_playback'"; 
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `preference` SET `description`='Transcode Bitrate' WHERE `name`='sample_rate'"; 
		$db_results = Dba::query($sql); 

		// Check for old tables and drop if found, seems like there was a glitch that caused them
		// not to get droped.. *shrug*
		$sql = "DROP TABLE IF EXISTS `preferences`"; 
		$db_results = Dba::query($sql); 

		$sql = "DROP TABLE IF EXISTS `song_ext_data`"; 
		$db_results = Dba::query($sql); 

		$sql = "DROP TABLE IF EXISTS `ratings`"; 
		$db_results = Dba::query($sql); 

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql); 

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']); 
                }

		self::set_version('db_version','340008'); 

		return true; 

	} // update_340008

	/**
	 * update_340009
	 * This modifies the song table to handle pos fields
	 */
	public static function update_340009() { 

		$sql = "ALTER TABLE `album` ADD `disk` smallint(5) UNSIGNED DEFAULT NULL";
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `album` ADD INDEX (`disk`)";
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `access_list` ADD `dns` VARCHAR( 255 ) NOT NULL AFTER `end`"; 
		$db_results = Dba::query($sql); 

		$sql = "CREATE TABLE `artist_data` (" . 
			"`artist_id` INT( 11 ) UNSIGNED NOT NULL ," . 
			"`art` MEDIUMBLOB NOT NULL ," . 
			"`art_mime` VARCHAR( 32 ) NOT NULL ," . 
			"`thumb` BLOB NOT NULL ," . 
			"`thumb_mime` VARCHAR( 32 ) NOT NULL ," . 
			"`bio` TEXT NOT NULL , " . 
			"UNIQUE (`artist_id`) ) ENGINE = MYISAM";
		$db_results = Dba::query($sql); 

		self::set_version('db_version','340009'); 

		return true; 

	} // update_340009

	/**
	 * update_340010
	 * Bunch of minor tweaks to the preference table
	 */
	public static function update_340010() { 

		$sql = "UPDATE `preference` SET `catagory`='options' WHERE `name` LIKE 'localplay_%'"; 
		$db_results = Dba::query($sql); 

		$sql = "DELETE FROM `preference` WHERE `name`='playlist_add'"; 
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `preference` SET `catagory`='plugins' WHERE (`name` LIKE 'mystrands_%' OR `name` LIKE 'lastfm_%') AND `catagory`='options'"; 
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `preference` SET `value`='default' WHERE `name`='playlist_method'"; 
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `preference` SET `description`='Localplay Config' WHERE `name`='localplay_level'"; 
		$db_results = Dba::query($sql); 

                /* Fix every users preferences */
                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql); 

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                } // while results

		self::set_version('db_version','340010'); 

		return true; 

	} // update_340010

	/**
	 * update_340011
	 * This updates the democratic play stuff so that can handle a little more complext mojo
	 * It also adds yet another table to the db to handle the sessions for API access. Eventually
	 * should combine all of the session tables, but I'll do that later
	 */
	public static function update_340011() { 

		// First add the new table for the new session stuff
                $sql = "CREATE TABLE `session_api` ( " .
                        "`id` VARCHAR( 64 ) NOT NULL , " .
                        "`user` INT( 11 ) UNSIGNED NOT NULL , " .
                        "`agent` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL , " .
			"`level` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0', " . 
                        "`expire` INT( 11 ) UNSIGNED NOT NULL , " .
                        "`ip` INT( 11 ) UNSIGNED NULL , " .
                        "PRIMARY KEY ( `id` ) " .
                        ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
                $db_results = Dba::query($sql);

		self::set_version('db_version','340011'); 		

		return true; 

	} // 340011

	/**
	 * update_340012
	 * This update adds in the democratic stuff, checks for some potentially screwed up indexes
	 * and removes the timestamp from the playlist, and adds the field to the catalog for the upload dir
	 */
	public static function update_340012() { 

		$sql = "ALTER TABLE `catalog` ADD `add_path` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `path`"; 
		$db_results = Dba::query($sql); 

		$sql = "CREATE TABLE `democratic` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," . 
			"`name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ," . 
			"`cooldown` TINYINT( 4 ) UNSIGNED NULL ," . 
			"`level` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '25'," . 
			"`user` INT( 11 ) NOT NULL ," . 
			"`primary` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'" . 
			") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `democratic` ADD INDEX (`primary`)"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `democratic` ADD INDEX (`level`)"; 
		$db_results = Dba::query($sql);

		self::set_version('db_version','340012'); 

		return true; 

	} // update_340012

	/**
 	 * update_340013
	 * This update removes a whole bunch of preferences that are no longer
	 * being used in any way, and changes the ACL XML-RPC to just RPC
	 */
	public static function update_340013() { 

		$sql = "DELETE FROM `preference` WHERE `name`='localplay_mpd_hostname' OR `name`='localplay_mpd_port' " . 
			"OR `name`='direct_link' OR `name`='localplay_mpd_password' OR `name`='catalog_echo_count'"; 
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `preference` SET `description`='Localplay Access' WHERE `name`='localplay_level'"; 
		$db_results = Dba::query($sql); 

		$sql = "UPDATE `access_list` SET `type`='rpc' WHERE `type`='xml-rpc'"; 
		$db_results = Dba::query($sql); 

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                } // while we're fixing the useres stuff
		
		self::set_version('db_version','340013');	

		return true; 

	} // update_340013

	/**
	 * update_340014
	 * This update drops the session_api table that I added just two updates ago
	 * it's been nice while it lasted but it's time to pack your stuff and GTFO
	 * at the same time it updates the core session table to handle the additional
	 * stuff we're going to ask it to do. 
	 */
	public static function update_340014() { 

		$sql = "DROP TABLE `session_api`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `session` CHANGE `type` `type` ENUM ('mysql','ldap','http','api','xml-rpc') NOT NULL"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `session` ADD `agent` VARCHAR ( 255 ) NOT NULL AFTER `type`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `session` ADD INDEX (`type`)"; 
		$db_results = Dba::query($sql); 

		self::set_version('db_version','340014'); 

		return true; 

	} // update_340014

	/**
	 * update_340015
	 * This update tweaks the playlist table responding to complaints from usres
	 * who say it doesn't work, unreproduceable. This also adds an index to the 
	 * album art table to try to make the random album art faster
	 */	
	public static function update_340015() { 

		$sql = "ALTER TABLE `playlist` DROP `date`"; 
		$db_results = Dba::query($sql); 	

		$sql = "ALTER TABLE `playlist` ADD `date` INT ( 11 ) UNSIGNED NOT NULL"; 
		$db_results = Dba::query($sql); 

		// Pull all of the rating information
		$sql = "SELECT `id`,`rating` FROM `rating`"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row; 
		} 

		$sql = "ALTER TABLE `rating` DROP `rating`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `rating` ADD `rating` TINYINT ( 4 ) NOT NULL"; 
		$db_results = Dba::query($sql); 

		foreach ($results as $row) { 
			$rating = Dba::escape($row['rating']);
			$id	= Dba::escape($row['id']); 
			$sql = "UPDATE `rating` SET `rating`='$rating' WHERE `id`='$id'"; 
			$db_results = Dba::query($sql); 
		} 

		self::set_version('db_version','340015'); 

		return true; 
		
	} // update_340015

	/** 
	 * update_340016
	 * This adds in the base_playlist to the democratic table... should have
 	 * done this in the previous one but I screwed up... sigh
	 */
	public static function update_340016() { 

		$sql = "ALTER TABLE `democratic` ADD `base_playlist` INT ( 11 ) UNSIGNED NOT NULL"; 
		$db_results = Dba::query($sql); 

		self::set_version('db_version','340016'); 

		return true; 

	} // update_340016

	/**
	 * update_340017
	 * This finalizes the democratic table. 
	 * and fixes the charset crap
	 */
	public static function update_340017() { 

		$sql = "ALTER TABLE `democratic` ADD `base_playlist` INT( 11 ) UNSIGNED NOT NULL AFTER `name`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `tmp_playlist` DROP `base_playlist`"; 
		$db_results = Dba::query($sql); 

		$sql = "DELETE FROM `tmp_playlist` WHERE `session`='-1'"; 
		$db_results = Dba::query($sql); 

		$sql = "TRUNCATE `democratic`"; 
		$db_results = Dba::query($sql); 
		
		self::set_version('db_version','340017'); 

		return true; 

	} // update_340017

	/**
	 * update_340018
	 * This attempts to correct the charset on your database, it does some checking
	 * to make sure that if we do this it will actually will work. We will fail this update
	 * if it would cause problems
	 */
	public static function update_340018() { 

		// MySQL translate real charset names into fancy smancy MySQL land names
		switch (strtoupper(Config::get('site_charset'))) { 
			case 'CP1250': 
			case 'WINDOWS-1250': 
			case 'WINDOWS-1252': 
				$target_charset = 'cp1250'; 
				$target_collation = 'cp1250_general_ci'; 
			break; 
			case 'ISO-8859':  
			case 'ISO-8859-2': 
				$target_charset = 'latin2'; 
				$target_collation = 'latin2_general_ci'; 
			break; 
			case 'ISO-8859-1': 
				$target_charset = 'latin1'; 
				$target_charset = 'latin1_general_ci'; 
			break; 
			case 'EUC-KR': 
				$target_charset = 'euckr'; 
				$target_collation = 'euckr_korean_ci'; 
			break; 
			case 'CP932': 
				$target_charset = 'sjis'; 
				$target_collation = 'sjis_japanese_ci'; 
			break; 
			case 'KOI8-U': 
				$target_charset = 'koi8u'; 
				$target_collation = 'koi8u_general_ci'; 
			break; 
			case 'KOI8-R': 
				$target_charset = 'koi8r';
				$target_collation = 'koi8r_general_ci'; 
			break; 
			case 'ISO-8859': 
				$target_charset = 'latin2';
				$target_collation = 'latin2_general_ci'; 
			break; 
			default; 
			case 'UTF-8':
				$target_charset = 'utf8'; 
				$target_collation = 'utf8_unicode_ci'; 
			break; 
		} // end mysql charset translation

                // Alter the charset for the entire database
                $sql = "ALTER DATABASE `" . Config::get('database_name') . "` DEFAULT CHARACTER SET $target_charset COLLATE $target_collation"; 
                $db_results = Dba::query($sql); 
	
		$sql = "SHOW TABLES"; 
		$db_results = Dba::query($sql); 

		// Go through the tables!
		while ($row = Dba::fetch_row($db_results)) { 
			$sql = "DESCRIBE `" . $row['0'] . "`"; 
			$describe_results = Dba::query($sql); 

                        // Change the tables default charset and colliation
                        $sql = "ALTER TABLE `" . $row['0'] . "`  DEFAULT CHARACTER SET $target_charset COLLATE $target_collation"; 
                        $alter_table = Dba::query($sql); 

			// Itterate through the columns of the table
			while ($table = Dba::fetch_assoc($describe_results)) { 
				if (strstr($table['Type'],'varchar')  OR strstr($table['Type'],'enum') OR strstr($table['Table'],'text')) { 
					$sql = "ALTER TABLE `" . $row['0'] . "` MODIFY `" . $table['Field'] . "` " . $table['Type'] . " CHARACTER SET " . $target_charset;
					$charset_results = Dba::query($sql); 
					if (!$charset_results) { 
						debug_event('CHARSET','Unable to update the charset of ' . $table['Field'] . '.' . $table['Type'] . ' to ' . $target_charset,'3'); 
					} // if it fails
				} // if its a varchar 
			} // end columns

		} // end tables
		
		self::set_version('db_version','340018'); 

		return true; 

	} // update_340018

	/**
 	 * update_350001
	 * This updates modifies the tag tables per codeunde1load's specs from his tag patch
	 * it also adjusts the prefix fields so that we can use more prefixes
	 */
	public static function update_350001() { 

		$sql = "ALTER TABLE `tag_map` ADD `tag_id` INT ( 11 ) UNSIGNED NOT NULL AFTER `id`"; 
		$db_results = Dba::query($sql); 

		$sql = "RENAME TABLE `tags`  TO `tag`"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `tag` CHANGE `map_id` `id` INT ( 11 ) UNSIGNED NOT NULL auto_increment"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `album` CHANGE `prefix` `prefix` VARCHAR ( 32 ) NULL"; 
		$db_results = Dba::query($sql); 

		$sql = "ALTER TABLE `artist` CHANGE `prefix` `prefix` VARCHAR ( 32 ) NULL"; 
		$db_results = Dba::query($sql); 
		
		self::set_version('db_version','350001'); 

		return true; 

	} // update_350001

	/**
	 * update_350002
	 * This update adds in the browse_cache table that we use to hold peoples cached browse results
	 * rather then try to store everything in the session we split them out into one serilized array per
	 * row, per person. A little slow this way when browsing, but faster when now browsing and more flexible
	 */
	public static function update_350002() { 

		$sql = "CREATE TABLE `tmp_browse` (`sid` varchar(128) collate utf8_unicode_ci NOT NULL,`data` longtext collate utf8_unicode_ci NOT NULL," . 
			" UNIQUE KEY `sid` (`sid`)) ENGINE=MyISAM"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `tmp_browse` ADD INDEX ( `type` )"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `song` DROP `genre`"; 
		$db_results = Dba::write($sql); 

		$sql = "CREATE TABLE `user_catalog` (`user` INT( 11 ) UNSIGNED NOT NULL ,`catalog` INT( 11 ) UNSIGNED NOT NULL ,`level` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '5', " . 
			"INDEX ( `user` )) ENGINE = MYISAM";
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `user_catalog` ADD INDEX ( `catalog` )"; 
		$db_results = Dba::write($sql); 

		self::set_version('db_version','350002'); 

		return true; 

	} // update_350002

	/**
	 * update_350003
	 * This update tweakes the tag tables a little bit more, we're going to simplify things for the first little bit and then
	 * then if it all works out we will worry about making it complex again. One thing at a time people...
	 */
	public static function update_350003() { 

		$sql = "ALTER TABLE `tag` DROP `order`"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `tag` DROP INDEX `order`"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `tag` ADD UNIQUE ( `name` )"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `tag` CHANGE `name` `name` VARCHAR( 255 )"; 
		$db_results = Dba::write($sql); 

		// Make sure that they don't have any of the mystrands crap left
		$sql = "DELETE FROM `preference` WHERE `name`='mystrands_user' OR `name`='mystrands_pass'"; 
		$db_results = Dba::write($sql); 

		self::set_version('db_version','350003'); 

		return true; 

	} // update_350003

	/**
	 * update_350004
	 * This update makes some changes to the ACL table so that it can support IPv6 entries as well as some other feature 
	 * enhancements
	 */
	public static function update_350004() { 

		$sql = "ALTER TABLE `session` CHANGE `ip` `ip` VARBINARY( 255 ) NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `session_stream` CHANGE `ip` `ip` VARBINARY( 255 ) NULL"; 
		$db_results = Dba::write($sql); 

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
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `ip_history` CHANGE `ip` `ip` VARBINARY( 255 ) NULL"; 
		$db_results = Dba::write($sql); 
		
		$sql = "ALTER TABLE `ip_history` ADD `agent` VARCHAR ( 255 ) NULL AFTER `date`"; 
		$db_results = Dba::write($sql); 

		// Reinsert the old rows
		foreach ($ip_history as $row) { 
			$ip = Dba::escape(inet_pton($row['ip'])); 
			$sql = "INSERT INTO `ip_history` (`user`,`ip`,`date`,`agent`) " . 
				"VALUES ('" . $row['user'] . "','" . $ip . "','" . $row['date'] . "',NULL)"; 
			$db_results = Dba::write($sql); 
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
		$db_results = Dba::write($sql); 

		// Make the changes to the database
		$sql = "ALTER TABLE `access_list` CHANGE `start` `start` VARBINARY( 255 ) NOT NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `access_list` CHANGE `end` `end` VARBINARY( 255 ) NOT NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `access_list` DROP `dns`"; 	
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `access_list` ADD `enabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `key`"; 
		$db_results = Dba::write($sql); 

		// If we had nothing in there before add some base ALLOW ALL stuff as we're going
		// to start defaulting Access Control to On. 
		if (!count($acl_information)) { 
			$v6_start = Dba::escape(inet_pton('::')); 
			$v6_end = Dba::escape(inet_pton('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff')); 
			$v4_start = Dba::escape(inet_pton('0.0.0.0')); 
			$v4_end = Dba::escape(inet_pton('255.255.255.255')); 
			$sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " . 
				"VALUES ('DEFAULTv4','75','$v4_start','$v4_end',NULL,'-1','interface','1')"; 
			$db_results = Dba::write($sql); 
			$sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " . 
				"VALUES ('DEFAULTv4','75','$v4_start','$v4_end',NULL,'-1','stream','1')"; 
			$db_results = Dba::write($sql); 
			$sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " . 
				"VALUES ('DEFAULTv6','75','$v6_start','$v6_end',NULL,'-1','interface','1')"; 
			$db_results = Dba::write($sql); 
			$sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " . 
				"VALUES ('DEFAULTv6','75','$v6_start','$v6_end',NULL,'-1','stream','1')"; 
			$db_results = Dba::write($sql); 
		} // Adding default information

		foreach ($acl_information as $row) { 
			debug_event('Crap',print_r($row,1),1); 
			$row['start'] = Dba::escape(inet_pton($row['start'])); 
			$row['end'] = Dba::escape(inet_pton($row['end'])); 
			$row['key'] = Dba::escape($row['key']); 
			$sql = "INSERT INTO `access_list` (`name`,`level`,`start`,`end`,`key`,`user`,`type`,`enabled`) " . 
				"VALUES ('" . Dba::escape($row['name']) . "','" . intval($row['level']) . 
				"','" . $row['start'] . "','" . $row['end'] . "','" . $row['key'] . "','" . intval($row['user']) . "','" . 
				$row['type'] . "','1')"; 
			$db_results = Dba::write($sql); 
		} // end foreach of existing rows
		
		self::set_version('db_version','350004');

		return true; 

	} // update_350004

	/**
	 * update_350005
	 * This update adds the video table... *gasp* no you didn't <head shake>
	 */
	public static function update_350005() { 

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
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `access_list` ADD INDEX ( `enabled` )";
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `video` ADD INDEX ( `file` )"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `video` ADD INDEX ( `enabled` )"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `video` ADD INDEX ( `title` )"; 
		$db_results = Dba::write($sql); 

		self::set_version('db_version','350005');

		return true; 

	} // update_350005

	/**
	 * update_350006
	 * This update inserts the Lyrics pref table... 
	 */
	public static function update_350006() {

		$sql = "INSERT INTO `preference` VALUES (69,'show_lyrics','0','Show Lyrics',0,'boolean','interface')";
		$db_results = Dba::write($sql);

		$sql = "INSERT INTO `user_preference` VALUES (1,69,'0')";
		$db_results = Dba::write($sql);

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1');

                while ($r = Dba::fetch_assoc($db_results)) {
                        User::fix_preferences($r['id']);
                } // while we're fixing the useres stuff

		self::set_version('db_version','350006');

		return true;

	} // update_350006

	/**
	 * update_350007
	 * This update adds in the random rules tables, and also increases the size of the blobs
	 * on the album and artist data. Also add track to tmp_playlist_data 
	 */
	public static function update_350007() { 

		// We need to clear the thumbs as they will need to be re-generated
		$sql = "UPDATE `album_data` SET `thumb`=NULL,`thumb_mime`=NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "UPDATE `artist_data` SET `thumb`=NULL,`thumb_mime`=NULL"; 
		$db_results = Dba::write($sql); 

		// Change the db thumb sizes
		$sql = "ALTER TABLE `album_data` CHANGE `thumb` `thumb` MEDIUMBLOB NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `artist_data` CHANGE `thumb` `thumb` MEDIUMBLOB NULL"; 
		$db_results = Dba::write($sql); 

		// Remove dead column
		$sql = "ALTER TABLE `playlist_data` DROP `dynamic_song`"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `playlist` DROP `genre`"; 
		$db_results = Dba::write($sql); 

		// Add track item to tmp_playlist_data so we can order this stuff manually
		$sql = "ALTER TABLE `tmp_playlist_data` ADD `track` INT ( 11 ) UNSIGNED NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "DROP TABLE `genre`"; 
		$db_results = Dba::write($sql); 

		// Clean up the catalog and add last_clean to it
		$sql = "ALTER TABLE `catalog` ADD `last_clean` INT ( 11 ) UNSIGNED NULL AFTER `last_update`"; 
		$db_results = Dba::write($sql); 
	
		$sql = "ALTER TABLE `catalog` DROP `add_path`"; 
		$db_results = Dba::write($sql); 

		$sql = "CREATE TABLE `dynamic_playlist` (" . 
			"`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," . 
			"`name` VARCHAR( 255 ) NOT NULL ," . 
			"`user` INT( 11 ) NOT NULL ," . 
			"`date` INT( 11 ) UNSIGNED NOT NULL ," . 
			"`type` VARCHAR( 128 ) NOT NULL" . 
			") ENGINE = MYISAM ";
		$db_results = Dba::write($sql); 

		$sql = "CREATE TABLE `dynamic_playlist_data` (" . 
			"`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ," . 
			"`dynamic_id` INT( 11 ) UNSIGNED NOT NULL ," . 
			"`field` VARCHAR( 255 ) NOT NULL ," . 
			"`internal_operator` VARCHAR( 64 ) NOT NULL ," . 
			"`external_operator` VARCHAR( 64 ) NOT NULL ," . 
			"`value` VARCHAR( 255 ) NOT NULL" .
			") ENGINE = MYISAM"; 
		$db_results = Dba::write($sql); 

		self::set_version('db_version','350007'); 

		return true; 

	} // update_350007

	/**
	 * update_350008
	 * Change song_id references to be object so they are a little more general 
	 * add a type to now playing table so that we can handle different playing information
	 */
	public static function update_350008() { 

		$sql = "ALTER TABLE `now_playing` CHANGE `song_id` `object_id` INT( 11 ) UNSIGNED NOT NULL"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `now_playing` ADD `object_type` VARCHAR ( 255 ) NOT NULL AFTER `object_id`"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `now_playing` ADD INDEX ( `expire` )"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `video` ADD `addition_time` INT( 11 ) UNSIGNED NOT NULL AFTER `mime`"; 
		$db_results = Dba::write($sql); 
		
		$sql = "ALTER TABLE `video` ADD `update_time` INT( 11 ) UNSIGNED NULL AFTER `addition_time`"; 
		$db_results = Dba::write($sql); 	

		$sql = "ALTER TABLE `video` ADD INDEX (`addition_time`)"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `video` ADD INDEX (`update_time`)"; 
		$db_results = Dba::write($sql); 

                $sql = "ALTER TABLE `artist_data` ADD INDEX ( `art_mime` )";
                $db_results = Dba::write($sql);

		$sql = "ALTER TABLE `album_data` ADD INDEX ( `art_mime` )"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `tmp_browse` ADD `type` VARCHAR ( 255 ) NOT NULL AFTER `sid`"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `tmp_browse` ADD INDEX (`type)"; 
		$db_results = Dba::write($sql); 

		$sql = "ALTER TABLE `song` DROP `hash`"; 
		$db_results = Dba::write($sql); 

		self::set_version('db_version','350008'); 

	} // update_350008


} // end update class
?>
