<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/*!
	@header Update Class
	@discussion this class handles updating from one version of 
	maintain to the next. Versions are a 6 digit number
	220000
	^
	Major Revision
	
	220000
	 ^
	 Minor Revision

	The last 4 digits are a build number...
	If Minor can't go over 9 Major can go as high as we want
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

		$sql = "SELECT * FROM update_info WHERE key='$this->key'";
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
			
			$version = '310000';		
			
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
	 * maintain at all
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
	
		$version[] = array('version' => '333004','description' => $update_string); 

		$update_string = '- Moved back to ID for user tracking internally.<br />' . 
				'- Added date to user_vote to allow sorting by vote time.<br />' . 
				'- Added Random Method and Object Count Preferences.<br />' . 
				'- Removed some unused tables/fields.<br />' . 
				'- Added Label, Catalog # and Language to Extended Song Data Table<br />';

		$version[] = array('version' => '340001','description' => $update_string);

		$update_string = '- Added Offset Limit to Preferences and removed from user table';

		$version[] = array('version' => '340002','description' => $update_string); 

		$update_string = '- Moved Art from the Album table into album_data to improve performance.<br />' . 
				'- Made some minor changes to song table to reduce size of each row.<br />' . 
				'- Moved song_ext_data to song_data to match album_data pattern.<br />' . 
				'- Added Playlist Method and Rate Limit Preferences.<br />';

		$version[] = array('version' => '340003','description' => $update_string); 

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
		$db_results = Dba::query($sql);
                
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
					call_user_func(array('Update',$update_function)); 
				}

			} 
		
		} // end foreach version

	} // run_update

	/**
	 * set_version
	 * This updates the 'update_info' which is used by the updater
	 * and plugins
	 */
	private function set_version($key,$value) {

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

			$sql = "UPDATE `access_list` SET `user`='$user_id' WERE `user`='$username'"; 
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
		

		// Fix the preferences for everyone 
		$sql = "SELECT `id` FROM `user`";
		$db_results = Dba::query($sql); 

		User::fix_preferences('-1'); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			User::fix_preferences($r['id']); 
		} 

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
			"`art` BLOB NULL , " . 
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

		$sql = "RENAME TABLE `ampache`.`song_ext_data`  TO `ampache`.`song_data`"; 
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

               /* Add the rate_limit preference */
                $sql = "INSERT INTO `preferences` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('rate_limit','8192','Rate Limit','100','integer','streaming')";
                $db_results = Dba::query($sql);

               /* Add the playlist_method preference and remove it from the user table */
                $sql = "INSERT INTO `preferences` (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('playlist_method','50','Playlist Method','5','string','streaming')";
                $db_results = Dba::query($sql);

                $sql = "SELECT `id` FROM `user`";
                $db_results = Dba::query($sql);

                User::fix_preferences('-1'); 

                while ($r = Dba::fetch_assoc($db_results)) { 
                        User::fix_preferences($r['id']);
                }

		self::set_version('db_version','340003'); 

	} // update_340003

} // end update class
?>
