<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

	var $key;
	var $value;
	var $versions; // array containing version information

	/*!
		@function Update
		@discussion Constructor, pulls out information about
			the desired key
	*/
	function Update ( $key=0 ) {

		if ($key) {
			$info = $this->get_info();
			$this->key = $key;
			$this->value = $info->value;
			$this->versions = $this->populate_version();
		}

	} // constructor

	/*!
		@function get_info
		@discussion gets the information for the zone
	*/
	function get_info() {
		global $conf;

		$sql = "SELECT * FROM update_info WHERE key='$this->key'";
		$db_results = mysql_query($sql, dbh());

		return mysql_fetch_object($db_results);		

	} //get_info

	/*!
		@function get_version
		@discussion this checks to see what version you are currently running
			because we may not have the update_info table we have to check 
			for it's existance first. 
	*/
	function get_version() {


		/* Make sure that update_info exits */
		$sql = "SHOW TABLES LIKE 'update_info'";
		$db_results = mysql_query($sql, dbh());
		// If no table
		if (!mysql_num_rows($db_results)) {
			
			$version = '310000';		
			
		} // if table isn't found

		else {
			// If we've found the update_info table, let's get the version from it
			$sql = "SELECT * FROM update_info WHERE `key`='db_version'";
			$db_results = mysql_query($sql, dbh());
			$results = mysql_fetch_object($db_results);
			$version = $results->value;
		} 

		return $version;

	} // get_version

	/*!
		@function format_version
		@discussion make the version number pretty
	*/
	function format_version($data) {

		$new_version = substr($data,0,strlen($data) - 5) . "." . substr($data,strlen($data)-5,1) . " Build:" . 
				substr($data,strlen($data)-4,strlen($data));

		return $new_version;

	} // format_version

	/*!
		@function need_update
		@discussion checks to see if we need to update 
			maintain at all
	*/
	function need_update() {

		$current_version = $this->get_version();
		
		if (!is_array($this->versions)) {
			$this->versions = $this->populate_version();
		}
		
		/* 
		   Go through the versions we have and see if
		   we need to apply any updates
		*/
		foreach ($this->versions as $update) {
			if ($update['version'] > $current_version) {
				return true;
			}

		} // end foreach version

		return false;

	} // need_update

	/*!
		@function populate_version
		@discussion just sets an array the current differences
			that require an update
	*/
	function populate_version() {

		/* Define the array */
		$version = array();
	
                /* Version 3.2 Build 0001 */
                $update_string = "- Add update_info table to the database<br />" .
                                 "- Add Now Playing Table<br />" .
                                 "- Add album art columns to album table<br />" . 
				 "- Compleatly Changed Preferences table<br />" . 
				 "- Added Upload table<br />";
                $version[] = array('version' => '320001', 'description' => $update_string);

		$update_string = "- Add back in catalog_type for XML-RPC Mojo<br />" . 
				 "- Add level to access list to allow for play/download/xml-rpc share permissions<br />" .
				 "- Changed access_list table to allow start-end (so we can set full ip ranges)<br />" . 
				 "- Add default_play to preferences to allow quicktime/localplay/stream<br />" .
				 "- Switched Artist ID from 10 --> 11 to match other tables<br />";
		$version[] = array('version' => '320002', 'description' => $update_string);

		$update_string = "- Added a last_seen field user table to track users<br />" .
				 "- Made preferences table key/value based<br />";

		$version[] = array('version' => '320003', 'description' => $update_string);

		$update_string = "- Added play_type to preferences table<br />" .
				 "- Removed multicast,downsample,localplay from preferences table<br />" .
				 "- Dropped old config table which was no longer needed<br />";

		$version[] = array('version' => '320004', 'description' => $update_string);

		$update_string = "- Added type to preferences to allow for site/user preferences<br />";

		$version[] = array('version' => '330000', 'description' => $update_string);

		$update_string = "- Added Year to album table<br />" . 
				 "- Increased length of password field in User table<br />";

		$version[] = array('version' => '330001', 'description' => $update_string);

		$update_string = "- Changed user.access to varchar from enum for more flexibility<br />" .
				 "- Added catalog.private for future catalog access control<br />" . 
				 "- Added user_catalog table for future catalog access control<br />";
		
		
		$version[] = array('version' => '330002', 'description' => $update_string);

		$update_string = "- Added user_preferences table to once and for all fix preferences.<br />" . 
				 "- Moved Contents of preferences into new table, and modifies old preferences table.<br />";

		$version[] = array('version' => '330003', 'description' => $update_string);

		$update_string = "- Changed song comment from varchar255 in order to handle comments longer than 255 chr.<br />" . 
				 "- Added Language and Playlist Type as a per user preference.<br />" . 
				 "- Added Level to Catalog_User table for future use.<br />" . 
				 "- Added gather_types to Catalog table for future use.<br />";
				

		$version[] = array('version' => '330004', 'description' => $update_string);
		
		$update_string = "- Added Theme config option.<br />";

		$version[] = array('version' => '331000', 'description' => $update_string);

		$update_string = "- Added Elipse Threshold Preferences.<br />";

		$version[] = array('version' => '331001', 'description' => $update_string);	

		$update_string = "- Added Show bottom menu option.<br />";
		$version[] = array('version' => '331002', 'description' => $update_string);	

		$update_string = "- Cleaned up user management.<br />";
		
		$version[] = array('version' => '331003', 'description' => $update_string);
		$update_string = "- Added Genre and Catalog to the stats tracking enum.<br />" . 
				 "- Added CondPL preference for the new MPD playlist.<br />";

		$version[] = array('version' => '332001', 'description' => $update_string);

		$update_string = "- Removed every Instance of User->ID *Note* This update clears Now Playing.<br />" .
				 "- Added field allowing for Dynamic Playlists.<br />" . 
				 "- Added required table/fields for security related IP Tracking.<br />";

		$version[] = array('version' => '332002', 'description' => $update_string);
	
		$update_string = "- Fixed Upload system, previous uploaded files are broken by this update.<br />" . 
				 " &nbsp;If quarantine is turned on use /bin/quarantine_migration.php.inc to move<br />" . 
				 " &nbsp;them into place<br />" .
				 "- Added New Fields to ACL table to allow for improved access control and XML-RPC security.<br />" . 
				 "- Added New Field to Now Playing to Account for WMP10 and other over-zelous buffering apps.<br />";
	
		$version[] = array('version' => '332003', 'description' => $update_string);

		$update_string = "- Added ID to playlist_data so that duplicate songs on the same playlist can actually work.<br />" . 
				 "- Re-worked Preferences, again :'(, hopefully making them better.<br />" . 
				 "- Added rating table for SoundOfEmotions Rating system.<br />";

		$version[] = array('version' => '332004', 'description' => $update_string);

		$update_string = "- Verify Previous Update, I dropped the ball and allowed a nightly to be built with an invalid Update function " . 
				 "this update simply verifies that the previous database upgrade worked correctly and corrects it if it didn't. I appologize " . 
				 "for the mistake and will do my best to make sure it never happens again. - Karl Vollmer<br />";

		$version[] = array('version' => '332005','description' => $update_string);


		$update_string = '- Adds Create Date to User table to track registration and user creation time.<br />';

		$version[] = array('version' => '332006','description' => $update_string);

		$update_string = '- Alters the Dynamic Song field to be TEXT instead of Varchar (varchar was not long enough).<br />';
		
		$version[] = array('version' => '332007','description' => $update_string);

		$update_string = '- Drop All 3 Flagging Tables and recreate the Flagged table, this will remove all previous flagging records.<br />' .
				 '&nbsp;&nbsp;&nbsp; the code has changed enough to make it useless to migrate old data.<br />';

		$version[] = array('version' => '332008','description' => $update_string);

		$update_string = "- Add missing date and approval fields to Flagged table, can't belive I forgot these.<br />";

		$version[] = array('version' => '332009','description' => $update_string);

		$update_string = '- Reconfigure preferences to account for the new Localplay API, this also removes some preferences' . 
				' from the web interface, see /test.php for any setting you may be missing';

		$version[] = array('version' => '332010','description' => $update_string);

		return $version;

	} // populate_version

	/*!
		@function display_update
		@discussion This displays a list of the needed
			updates to the database. This will actually
			echo out the list...
	*/
	function display_update() {

		$current_version = $this->get_version();
		if (!is_array($this->versions)) {
			$this->versions = $this->populate_version();
		} 

		echo "<ul>\n";

		foreach ($this->versions as $version) {
		
			if ($version['version'] > $current_version) {
				$updated = true;
				echo "<li><b>Version: " . $this->format_version($version['version']) . "</b><br />";
				echo $version['description'] . "<br /></li>\n"; 
			} // if newer
		
		} // foreach versions

		echo "</ul>\n";

		if (!$updated) { echo "<p align=\"center\">No Updates Needed [<a href=\"" . conf('web_path') . "\">Return]</a></p>"; }
	} // display_update

	/*!
		@function run_update
		@discussion This function actually updates the db.
			it goes through versions and finds the ones 
			that need to be run. Checking to make sure
			the function exists first.
	*/
	function run_update() {
	
		/* Nuke All Active session before we start the mojo */
		$sql = "DELETE * FROM session";
		$db_results = mysql_query($sql, dbh());

	
		$methods = array();
		
		$current_version = $this->get_version();
		
		$methods = get_class_methods('Update');
		
		if (!is_array($this->versions)) { 
			$this->versions = $this->populate_version();
		}

		foreach ($this->versions as $version) { 


			// If it's newer than our current version
			// let's see if a function exists and run the 
			// bugger
			if ($version['version'] > $current_version) { 
				$update_function = "update_" . $version['version'];
				if (in_array($update_function,$methods)) {
					$this->{$update_function}();
				}

			} 
		
		} // end foreach version

	} // run_update

	/*!
		@function set_version
		@discussion sets a new version takes
			a key and value
	*/
	function set_version($key,$value) {

		$sql = "UPDATE update_info SET value='$value' WHERE `key`='$key'";
		$db_results = mysql_query($sql, dbh());		

	} //set_version

        /*!
                @function update_320001
                @discussion Migration function for 3.2 Build 0001
        */
        function update_320001() {

                // Add the update_info table to the database
                $sql = "CREATE TABLE `update_info` (`key` VARCHAR( 128 ) NOT NULL ,`value` VARCHAR( 255 ) NOT NULL ,INDEX ( `key` ) )";
                $db_results = mysql_query($sql, dbh());

		// Insert the first version info
		$sql = "INSERT INTO update_info (`key`,`value`) VALUES ('db_version','320001')";
		$db_results = mysql_query($sql, dbh());

                // Add now_playing table to database
                $sql = "CREATE TABLE now_playing (" .
                        "id int(11) unsigned NOT NULL auto_increment, " .
                        "song_id int(11) unsigned NOT NULL default '0', " .
                        "user_id int(11) unsigned default NULL, " .
                        "start_time int(11) unsigned NOT NULL default '0', " .
                        "PRIMARY KEY (id) " .
                        ") TYPE=MyISAM";
                $db_results = mysql_query($sql, dbh());

		// Add the upload table to the database
		$sql = "CREATE TABLE upload ( id int(11) unsigned NOT NULL auto_increment, `user` int(11) unsigned NOT NULL," .
			"`file` varchar(255) NOT NULL , `comment` varchar(255) NOT NULL , action enum('add','quarantine','delete') NOT NULL default 'quarantine', " .
			"addition_time int(11) unsigned default '0', PRIMARY KEY  (id), KEY action (`action`), KEY user (`user`) )";
		$db_results = mysql_query($sql, dbh());
		
		/* 
		  Ok we need to compleatly tweak the preferences table 
		  first things first, nuke the damn thing so we can 
		  setup our new mojo
		*/
		$sql = "DROP TABLE `preferences`";
		$db_results = mysql_query($sql, dbh());

		$sql = "CREATE TABLE `preferences` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , `user` INT( 11 ) UNSIGNED NOT NULL ," .
			"`download` ENUM( 'true', 'false' ) DEFAULT 'false' NOT NULL , `upload` ENUM( 'disabled', 'html', 'gui' ) DEFAULT 'disabled' NOT NULL ," .
			"`downsample` ENUM( 'true', 'false' ) DEFAULT 'false' NOT NULL , `local_play` ENUM( 'true', 'false' ) DEFAULT 'false' NOT NULL ," .
			"`multicast` ENUM( 'true', 'false' ) DEFAULT 'false' NOT NULL , `quarantine` ENUM( 'true', 'false' ) DEFAULT 'true' NOT NULL ," .
			"`popular_threshold` INT( 11 ) UNSIGNED DEFAULT '10' NOT NULL , `font` VARCHAR( 255 ) DEFAULT 'Verdana, Helvetica, sans-serif' NOT NULL ," .
			"`bg_color1` VARCHAR( 32 ) DEFAULT '#ffffff' NOT NULL , `bg_color2` VARCHAR( 32 ) DEFAULT '#000000' NOT NULL , `base_color1` VARCHAR( 32 ) DEFAULT '#bbbbbb' NOT NULL , " .
			"`base_color2` VARCHAR( 32 ) DEFAULT '#dddddd' NOT NULL , `font_color1` VARCHAR( 32 ) DEFAULT '#222222' NOT NULL , " .
			"`font_color2` VARCHAR( 32 ) DEFAULT '#000000' NOT NULL , `font_color3` VARCHAR( 32 ) DEFAULT '#ffffff' NOT NULL , " .
			"`row_color1` VARCHAR( 32 ) DEFAULT '#cccccc' NOT NULL , `row_color2` VARCHAR( 32 ) DEFAULT '#bbbbbb' NOT NULL , " .
			"`row_color3` VARCHAR( 32 ) DEFAULT '#dddddd' NOT NULL , `error_color` VARCHAR( 32 ) DEFAULT '#990033' NOT NULL , " .
			"`font_size` INT( 11 ) UNSIGNED DEFAULT '10' NOT NULL , `upload_dir` VARCHAR( 255 ) NOT NULL , " .
			"`sample_rate` INT( 11 ) UNSIGNED DEFAULT '32' NOT NULL , PRIMARY KEY ( `id` ), KEY user (`user`) )";
		$db_results = mysql_query($sql, dbh());

		$sql = "INSERT INTO preferences (`user`,`font_size`) VALUES ('0','12')";
		$db_results = mysql_query($sql, dbh());

		// Now we need to give everyone some preferences
		$sql = "SELECT * FROM user";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$users[] = $r;
		}

		foreach ($users as $user) { 
			$sql = "INSERT INTO preferences (`user`) VALUES ('$user->id')";
			$db_results = mysql_query($sql, dbh());
		}

                // Add album art columns to album table
                $sql = "ALTER TABLE album ADD art MEDIUMBLOB, ADD art_mime VARCHAR(128)";
                $db_result = mysql_query($sql, dbh());

        } // update_320001

	/*!
		@function update_320002
		@discussion update to alpha 2
	*/
	function update_320002() {

		/* Add catalog_type back in for XML-RPC */
		$sql = "ALTER TABLE `catalog` ADD `catalog_type` ENUM( 'local', 'remote' ) DEFAULT 'local' NOT NULL AFTER `path`";
		$db_results = mysql_query($sql, dbh());

		/* Add default_play to pick between stream/localplay/quicktime */
		$sql = "ALTER TABLE `preferences` ADD `default_play` VARCHAR( 128 ) DEFAULT 'stream' NOT NULL AFTER `popular_threshold`";
		$db_results = mysql_query($sql, dbh());

		/* Should be INT(11) Why not eah? */
		$sql = "ALTER TABLE `artist` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT";
		$db_results = mysql_query($sql, dbh());

		/* Add level to access_list so we can limit playback/download/xml-rpc share */
		$sql = "ALTER TABLE `access_list` ADD `level` SMALLINT( 3 ) UNSIGNED DEFAULT '5' NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Shouldn't be zero fill... not needed */
		$sql = "ALTER TABLE `user` CHANGE `offset_limit` `offset_limit` INT( 5 ) UNSIGNED DEFAULT '00050' NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Let's knock it up a notch 11.. BAM */
		$sql = "ALTER TABLE `user` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT";
		$db_results = mysql_query($sql, dbh());

		/* Change IP --> Start */
		$sql = "ALTER TABLE `access_list` CHANGE `ip` `start` INT( 11 ) UNSIGNED NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Add End */
		$sql = "ALTER TABLE `access_list` ADD `end` INT( 11 ) UNSIGNED NOT NULL AFTER `start`";
		$db_results = mysql_query($sql, dbh());

		/* Update Version */
		$this->set_version('db_version', '320002');

	} // update_320002


	/*!
		@function update_320003
		@discussion updates to the alpha 3 of 3.2
	*/
	function update_320003() { 

		/* Add last_seen to user table */
		$sql = "ALTER TABLE `user` ADD `last_seen` INT( 11 ) UNSIGNED NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* 
		   Load the preferences table into an array 
		   so we can migrate it to the new format
		*/
		$sql = "SELECT * FROM preferences";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_object($db_results)) { 
			$results[$r->user]['download'] 		= $r->download;
			$results[$r->user]['upload']		= $r->upload;
			$results[$r->user]['downsample']	= $r->downsample;
			$results[$r->user]['local_play']	= $r->local_play;
			$results[$r->user]['multicast']		= $r->multicast;
			$results[$r->user]['quarantine']	= $r->quarantine;
			$results[$r->user]['popular_threshold'] = $r->popular_threshold;
			$results[$r->user]['default_play']	= $r->default_play;
			$results[$r->user]['font']		= $r->font;
			$results[$r->user]['bg_color1']		= $r->bg_color1;
			$results[$r->user]['bg_color2']		= $r->bg_color2;
			$results[$r->user]['base_color1']	= $r->base_color1;
			$results[$r->user]['base_color2']	= $r->base_color2;
			$results[$r->user]['font_color1']	= $r->font_color1;
			$results[$r->user]['font_color2']	= $r->font_color2;
			$results[$r->user]['font_color3']	= $r->font_color3;
			$results[$r->user]['row_color1']	= $r->row_color1;
			$results[$r->user]['row_color2']	= $r->row_color2;
			$results[$r->user]['row_color3']	= $r->row_color3;
			$results[$r->user]['error_color']	= $r->error_color;
			$results[$r->user]['font_size']		= $r->font_size;
			$results[$r->user]['upload_dir']	= $r->upload_dir;
			$results[$r->user]['sample_rate']	= $r->sample_rate;

		} // while preferences

		/* Drop the preferences table so we can start over */
		$sql = "DROP TABLE `preferences`";
		$db_results = mysql_query($sql, dbh()) or die('Query failed: ' . mysql_error());

		/* Create the new preferences table */
		$sql = "CREATE TABLE `preferences` (`key` VARCHAR( 255 ) NOT NULL , `value` VARCHAR( 255 ) NOT NULL , `user` INT( 11 ) UNSIGNED NOT NULL)";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `preferences` ADD INDEX ( `key` )";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `preferences` ADD INDEX ( `user` )";
		$db_results = mysql_query($sql, dbh());


		$user = new User();

		/* Populate the mofo! */
		foreach ($results as $key => $data) {

			$user->add_preference('download',$results[$key]['download'],$key);
			$user->add_preference('upload',$results[$key]['upload'], $key);
			$user->add_preference('downsample',$results[$key]['downsample'], $key);
			$user->add_preference('local_play', $results[$key]['local_play'], $key);
			$user->add_preference('multicast', $results[$key]['multicast'], $key);
			$user->add_preference('quarantine', $results[$key]['quarantine'], $key);
			$user->add_preference('popular_threshold',$results[$key]['popular_threshold'], $key);
			$user->add_preference('font', $results[$key]['font'], $key);
			$user->add_preference('bg_color1',$results[$key]['bg_color1'], $key);
			$user->add_preference('bg_color2',$results[$key]['bg_color2'], $key);
			$user->add_preference('base_color1',$results[$key]['base_color1'], $key);
			$user->add_preference('base_color2',$results[$key]['base_color2'], $key);
			$user->add_preference('font_color1',$results[$key]['font_color1'], $key);
			$user->add_preference('font_color2',$results[$key]['font_color2'], $key);
			$user->add_preference('font_color3',$results[$key]['font_color3'], $key);
			$user->add_preference('row_color1',$results[$key]['row_color1'], $key);
			$user->add_preference('row_color2',$results[$key]['row_color2'], $key);
			$user->add_preference('row_color3',$results[$key]['row_color3'], $key);
			$user->add_preference('error_color', $results[$key]['error_color'], $key);
			$user->add_preference('font_size', $results[$key]['font_size'], $key);
			$user->add_preference('upload_dir', $results[$key]['upload_dir'], $key);
			$user->add_preference('sample_rate', $results[$key]['sample_rate'], $key);

		} // foreach preferences 

		/* Update Version */
		$this->set_version('db_version', '320003');

	 } // update_320003

	 /*!
	 	@function update_320004
		@discussion updates to the 320004 
			version of the db
	*/
	function update_320004() { 

		$results = array();

		$sql = "SELECT * FROM preferences WHERE `key`='local_play' AND `value`='true'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[$r->user] = 'local_play';
		}

		$sql = "SELECT * FROM preferences WHERE `key`='downsample' AND `value`='true'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[$r->user] = 'downsample';
		}

		$sql = "SELECT * FROM preferences WHERE `key`='multicast' AND `value`='true'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[$r->user] = 'multicast';
		}

		$sql = "SELECT DISTINCT(user) FROM preferences";
		$db_results = mysql_query($sql, dbh());
		
		while ($r = mysql_fetch_object($db_results)) { 
			if (!isset($results[$r->user])) { 
				$results[$r->user] = 'normal';
			}
		}

		foreach ($results as $key => $value) { 
			$sql = "INSERT INTO preferences (`key`,`value`,`user`) VALUES ('play_type','$value','$key')";
			$db_results = mysql_query($sql, dbh());
		}

		$sql = "DELETE FROM preferences WHERE `key`='downsample'";
		$db_results = mysql_query($sql, dbh());
		
		$sql = "DELETE FROM preferences WHERE `key`='local_play'";
		$db_results = mysql_query($sql, dbh());
		
		$sql = "DELETE FROM preferences WHERE `key`='multicast'";
		$db_results = mysql_query($sql, dbh());

		$sql = "DROP TABLE `config`";
		$db_results = mysql_query($sql, dbh());

		/* Update Version */
		$this->set_version('db_version', '320004');

	} // update_320004

	/*!
		@function update_330000
		@discussion updates to 3.3 Build 0
	*/
	function update_330000() { 

		/* Add Type to preferences */
		$sql = "ALTER TABLE `preferences` ADD `type` VARCHAR( 128 ) NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Set Type on current preferences */
		$sql = "UPDATE `preferences` SET type='user'";
		$db_results = mysql_query($sql, dbh());

		/* Add New Preferences */
		$new_prefs[] = array('key' => 'local_length', 'value' => libglue_param('local_length'));
		$new_prefs[] = array('key' => 'site_title', 'value' => conf('site_title'));
		$new_prefs[] = array('key' => 'access_control', 'value' => conf('access_control'));
		$new_prefs[] = array('key' => 'xml_rpc', 'value' => conf('xml_rpc'));
		$new_prefs[] = array('key' => 'lock_songs', 'value' => conf('lock_songs'));
		$new_prefs[] = array('key' => 'force_http_play', 'value' => conf('force_http_play'));
		$new_prefs[] = array('key' => 'http_port', 'value' => conf('http_port'));
		$new_prefs[] = array('key' => 'do_mp3_md5', 'value' => conf('do_mp3_md5'));
		$new_prefs[] = array('key' => 'catalog_echo_count', 'value' => conf('catalog_echo_count'));
		$new_prefs[] = array('key' => 'no_symlinks', 'value' => conf('no_symlinks'));
		$new_prefs[] = array('key' => 'album_cache_limit', 'value' => conf('album_cache_limit'));
		$new_prefs[] = array('key' => 'artist_cache_limit', 'value' => conf('artist_cache_limit'));
		$new_prefs[] = array('key' => 'memory_limit', 'value' => conf('memory_limit'));
		$new_prefs[] = array('key' => 'refresh_limit', 'value' => conf('refresh_interval'));
		
		foreach ($new_prefs as $pref) { 
			$sql = "INSERT INTO `preferences` (`key`,`value`,`type`) VALUES ('".$pref['key']."','".$pref['value']."','system')";
			$db_results = mysql_query($sql, dbh());
		}
		

		/* Update Version */
		$this->set_version('db_version','330000');


	} // update_330000


	/*!
		@function update_330001
		@discussion adds year to album and tweaks
			the password field in session
	*/
	function update_330001() { 
		
		/* Add Year to Album Table */
		$sql = "ALTER TABLE `album` ADD `year` INT( 4 ) UNSIGNED NOT NULL AFTER `prefix`";
		$db_results = mysql_query($sql, dbh());

		/* Alter Password Field */
		$sql = "ALTER TABLE `user` CHANGE `password` `password` VARCHAR( 64 ) NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Update Version */
		$this->set_version('db_version', '330001');

	} // update_330001

	/*!
		@function update_330002
		@discussion changes user.access from enum to a 
			varchr field
	*/
	function update_330002() { 

		/* Alter user table */
		$sql = "ALTER TABLE `user` CHANGE `access` `access` VARCHAR( 64 ) NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Add private option to catalog */
		$sql = "ALTER TABLE `catalog` ADD `private` INT( 1 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `enabled`";
		$db_results = mysql_query($sql, dbh());

		/* Add new user_catalog table */
		$sql = "CREATE TABLE `user_catalog` ( `user` INT( 11 ) UNSIGNED NOT NULL , `catalog` INT( 11 ) UNSIGNED NOT NULL )";
		$db_results = mysql_query($sql, dbh());

		/* Update Version */
		$this->set_version('db_version', '330002');

	} // update_330002

	/*!
		@function update_330003
		@discussion adds user_preference and modifies the 
			existing preferences table
	*/
	function update_330003() { 

		/* Add new user_preference table */
		$sql = "CREATE TABLE `user_preference` ( `user` INT( 11 ) UNSIGNED NOT NULL , `preference` INT( 11 ) UNSIGNED NOT NULL, `value` VARCHAR( 255 ) NOT NULL )";
		$db_results = mysql_query($sql, dbh()); 

		/* Add indexes */ 
		$sql = "ALTER TABLE `user_preference` ADD INDEX ( `user` )";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `user_preference` ADD INDEX ( `preference` )";
		$db_results = mysql_query($sql, dbh());

		/* Pull and store all preference information */
		$sql = "SELECT * FROM preferences";
		$db_results = mysql_query($sql, dbh());
		
		$results = array();
		
		while ($r = mysql_fetch_object($db_results)) { 
			$results[] = $r;
		}


		/* Re-combobulate preferences table */
        
			/* Drop the preferences table so we can start over */
			$sql = "DROP TABLE `preferences`";
			$db_results = mysql_query($sql, dbh()) or die('Query failed: ' . mysql_error());

			/* Insert new preference table */
			$sql = "CREATE TABLE `preferences` ( `id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR ( 128 ) NOT NULL, `value` VARCHAR ( 255 ) NOT NULL," . 
				" `description` VARCHAR ( 255 ) NOT NULL, `level` INT ( 11 ) UNSIGNED NOT NULL DEFAULT '100', `type` VARCHAR ( 128 ) NOT NULL, `locked` SMALLINT ( 1 ) NOT NULL Default '1'" . 
				", PRIMARY KEY ( `id` ) )"; 
			$db_results = mysql_query($sql, dbh()) or die("Query failed: " . mysql_error());

			/* Create Array of Preferences */
			$new_prefs = array();

			$new_prefs[] = array('name' => 'download', 'value' => '0', 'description' => 'Allow Downloads', 'level' => '100', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'upload', 'value' => '0', 'description' => 'Allow Uploads', 'level' => '100', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'quarantine', 'value' => '1', 'description' => 'Quarantine All Uploads', 'level' => '100', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'popular_threshold', 'value' => '10', 'description' => 'Popular Threshold', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'font', 'value' => 'Verdana, Helvetica, sans-serif', 'description' => 'Interface Font', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'bg_color1', 'value' => '#ffffff', 'description' => 'Background Color 1', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'bg_color2', 'value' => '#000000', 'description' => 'Background Color 2', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'base_color1', 'value' => '#bbbbbb', 'description' => 'Base Color 1', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'base_color2', 'value' => '#dddddd', 'description' => 'Base Color 2', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'font_color1', 'value' => '#222222', 'description' => 'Font Color 1', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'font_color2', 'value' => '#000000', 'description' => 'Font Color 2', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'font_color3', 'value' => '#ffffff', 'description' => 'Font Color 3', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'row_color1', 'value' => '#cccccc', 'description' => 'Row Color 1', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'row_color2', 'value' => '#bbbbbb', 'description' => 'Row Color 2', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'row_color3', 'value' => '#dddddd', 'description' => 'Row Color 3', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'error_color', 'value' => '#990033', 'description' => 'Error Color', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'font_size', 'value' => '10', 'description' => 'Font Size', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'upload_dir', 'value' => '', 'description' => 'Upload Directory', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'sample_rate', 'value' => '32', 'description' => 'Downsample Bitrate', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'refresh_limit', 'value' => '0', 'description' => 'Refresh Rate for Homepage', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'local_length', 'value' => '900', 'description' => 'Session Expire in Seconds', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'site_title', 'value' => 'For The Love of Music', 'description' => 'Website Title', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'lock_songs', 'value' => '0', 'description' => 'Lock Songs', 'level' => '100', 'locked' => '1', 'type' => 'system');
			$new_prefs[] = array('name' => 'force_http_play', 'value' => '1', 'description' => 'Forces Http play regardless of port', 'level' => '100', 'locked' => '1', 'type' => 'system');
			$new_prefs[] = array('name' => 'http_port', 'value' => '80', 'description' => 'Non-Standard Http Port', 'level' => '100', 'locked' => '1', 'type' => 'system');
			$new_prefs[] = array('name' => 'catalog_echo_count', 'value' => '100', 'description' => 'Catalog Echo Interval', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'no_symlinks', 'value' => '0', 'description' => 'Don\'t Follow Symlinks', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'album_cache_limit', 'value' => '25', 'description' => 'Album Cache Limit', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'artist_cache_limit', 'value' => '50', 'description' => 'Artist Cache Limit', 'level' => '100', 'locked' => '0', 'type' => 'system');
			$new_prefs[] = array('name' => 'play_type', 'value' => 'stream', 'description' => 'Type of Playback', 'level' => '25', 'locked' => '0', 'type' => 'user');
			$new_prefs[] = array('name' => 'direct_link', 'value' => '1', 'description' => 'Allow Direct Links', 'level' => '100', 'locked' => '0', 'type' => 'user');
			
			foreach ($new_prefs as $prefs) { 

				$sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`locked`,`type`) VALUES ('" . $prefs['name'] . "','" . $prefs['value'] ."','". $prefs['description'] ."','" . $prefs['level'] ."','". $prefs['locked'] ."','" . $prefs['type'] . "')";
				$db_results = mysql_query($sql, dbh());
	
			} // foreach prefs


		/* Re-insert Data into preferences table */

		$user = new User();
		$users = array();

		foreach ($results as $old_pref) { 
			// This makes sure that true/false yes no get turned into 0/1
			$temp_array = fix_preferences(array('old' => $old_pref->value));
			$old_pref->value = $temp_array['old'];
			$user->add_preference($old_pref->key,$old_pref->value,$old_pref->user);
			$users[$old_pref->user] = 1;
		} // end foreach old preferences

		/* Fix missing preferences */
		foreach ($users as $userid => $data) { 
			$user->old_fix_preferences($userid);
		} // end foreach user

		/* Update Version */
                $this->set_version('db_version', '330003');

	} // update_330003

	/*! 
		@function update_330004
		@discussion changes comment from varchar to text
			and also adds a few preferences options and
			adds the per db art functions
	*/
	function update_330004() { 

		/* Change comment field in song */
		$sql = "ALTER TABLE `song` CHANGE `comment` `comment` TEXT NOT NULL";
		$db_results = mysql_query($sql, dbh());

		/* Add Extra Preferences */
		$sql = "INSERT INTO `preferences` ( `id` , `name` , `value` , `description` , `level` , `type` , `locked` ) VALUES ('', 'lang', 'en_US', 'Language', '100', 'user', '0')";
		$db_results = mysql_query($sql, dbh());

		$sql = "INSERT INTO `preferences` ( `id` , `name` , `value` , `description` , `level` , `type` , `locked` ) VALUES ('', 'playlist_type','m3u','Playlist Type','100','user','0')";
		$db_results = mysql_query($sql, dbh());

		/* Add Gathertype to Catalog for future use */
		$sql = "ALTER TABLE `catalog` ADD `gather_types` VARCHAR( 255 ) NOT NULL AFTER `sort_pattern`";
		$db_results = mysql_query($sql, dbh());

		/* Add level to user_catalog for future use */
		$sql = "ALTER TABLE `user_catalog` ADD `level` SMALLINT( 3 ) DEFAULT '25' NOT NULL AFTER `catalog`";
		$db_results = mysql_query($sql, dbh());

		/* Fix existing preferences */
		$sql = "SELECT id FROM user";
		$db_results = mysql_query($sql, dbh());

		$user = new User(0);

		while ($results = mysql_fetch_array($db_results)) { 
			$user->old_fix_preferences($results[0]);
		}

                /* Update Version */
                $this->set_version('db_version', '330004');
				
	} // update_330004

	/*!
		@function update_331000
		@discussion this updates is for 3.3.1 it adds 
			the theme preference.
	*/
	function update_331000() { 


		/* Add new preference */
		$sql = "INSERT INTO `preferences` (`id`,`name`,`value`,`description`,`level`,`type`,`locked`) VALUES ('','theme_name','classic','Theme','0','user','0')";
		$db_results = mysql_query($sql, dbh());

		/* Fix existing preferecnes */
		$sql = "SELECT DISTINCT(user) FROM user_preference";
		$db_results = mysql_query($sql, dbh());

		$user = new User(0);
		
		while ($results = mysql_fetch_array($db_results)) { 
			$user->old_fix_preferences($results[0]);
		}

		/* Update Version */
		$this->set_version('db_version','331000');

	} // update_331000

	/*!
		@function update_331001
		@discussion this adds a few more user preferences
	*/
	function update_331001() { 

		/* Add new preference */
		$sql = "INSERT INTO `preferences` (`id`,`name`,`value`,`description`,`level`,`type`,`locked`) VALUES ('','ellipse_threshold_album','27','Album Ellipse Threshold','0','user','0')";
		$db_results = mysql_query($sql, dbh());

		$sql = "INSERT INTO `preferences` (`id`,`name`,`value`,`description`,`level`,`type`,`locked`) VALUES ('','ellipse_threshold_artist','27','Artist Ellipse Threshold','0','user','0')";
		$db_results = mysql_query($sql, dbh());
		
		$sql = "INSERT INTO `preferences` (`id`,`name`,`value`,`description`,`level`,`type`,`locked`) VALUES ('','ellipse_threshold_title','27','Title Ellipse Threshold','0','user','0')";
		$db_results = mysql_query($sql, dbh());
		
                /* Fix existing preferecnes */
                $sql = "SELECT DISTINCT(user) FROM user_preference";
                $db_results = mysql_query($sql, dbh());
                
                $user = new User(0);
                
                while ($results = mysql_fetch_array($db_results)) {
                        $user->old_fix_preferences($results[0]);
                }       
                
                /* Update Version */
                $this->set_version('db_version','331001');	

	} // update_331001


	function update_331002() { 

		/* Add new preference */
		$sql = "INSERT INTO `preferences` (`id`,`name`,`value`,`description`,`level`,`type`,`locked`) VALUES ('','display_menu','1','Show Bottom Menu','0','user','0')";
		$db_results = mysql_query($sql, dbh());
               /* Fix existing preferecnes */
                $sql = "SELECT DISTINCT(user) FROM user_preference";
                $db_results = mysql_query($sql, dbh());
                
                $user = new User(0);
                
                while ($results = mysql_fetch_array($db_results)) {
                        $user->old_fix_preferences($results[0]);
                }       
                
                /* Update Version */
                $this->set_version('db_version','331002');	

	} // update_331002

	function update_331003() {

		/* Add `disabled` column to user table */
		$sql = "ALTER TABLE `user` ADD `disabled` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `access`";
		$db_results = mysql_query($sql, dbh());

		/* Set `disabled` to '1' to all users that have an access level of 'disabled',
		 * then change their access level to 'user' because an access level of 'disabled'
		 * is now going to cause problems.
		 */
		 $sql = "UPDATE `user` SET `disabled`='1',`access`='user' WHERE `access`='disabled'";
		 $db_results = mysql_query($sql, dbh());

		 $this->set_version('db_version','331003');

	} //update 331003

	function update_332001() { 

		$sql = "ALTER TABLE `object_count` CHANGE `object_type` `object_type` ENUM( 'album', 'artist', 'song', 'playlist', 'genre', 'catalog' ) NOT NULL DEFAULT 'song'";
		$db_results = mysql_query($sql, dbh());
		
		$sql = "ALTER TABLE `session` CHANGE `type` `type` ENUM( 'sso', 'mysql', 'ldap', 'http' ) NOT NULL DEFAULT 'mysql'";
		$db_results = mysql_query($sql, dbh());
		
                /* Add new preference */
                $sql = "INSERT INTO `preferences` (`id`,`name`,`value`,`description`,`level`,`type`,`locked`) " . 
				"VALUES ('','condPL','1','Condense Localplay Playlist','0','user','0')";

                $db_results = mysql_query($sql, dbh());
		
		$this->set_version('db_version','332001');

	} // update_332001

	function update_332002() { 

		$sql = "CREATE TABLE `ip_history` (`username` VARCHAR(128), `ip` INT(11) UNSIGNED NOT NULL DEFAULT '0', " . 
			"`connections` INT(11) UNSIGNED NOT NULL DEFAULT '1', `date` INT(11) UNSIGNED NOT NULL DEFAULT '0')";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `ip_history` ADD INDEX ( `username` )";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `ip_history` ADD INDEX ( `date` )";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `session` ADD `ip` INT( 11 ) UNSIGNED AFTER `value`";
		$db_results = mysql_query($sql, dbh());

                $sql = "ALTER TABLE `object_count` CHANGE `object_type` `object_type` ENUM( 'album', 'artist', 'song', 'playlist', 'genre', 'catalog' ) NOT NULL DEFAULT 'song'";
                $db_results = mysql_query($sql, dbh());

                $sql = "ALTER TABLE `session` CHANGE `type` `type` ENUM( 'sso', 'mysql', 'ldap', 'http' ) NOT NULL DEFAULT 'mysql'";
                $db_results = mysql_query($sql, dbh());

	
		/* We're gonna need a user->id => user->username mapping a few times let's get it! */
		$sql = "SELECT id,username FROM user";
		$db_results = mysql_query($sql, dbh());

		$username_id_map = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$id = $r['id'];
			$username_id_map[$id] = $r['username'];
		}
	
		/* It's time for some serious DB Clean Up. Nuke this stuff from Orbit! */
		$sql = "ALTER TABLE `catalog` DROP `private`";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `catalog` CHANGE `enabled` `enabled` TINYINT ( 1 ) UNSIGNED NOT NULL DEFAULT '1'";
		$db_results = mysql_query($sql, dbh());

		/* 
		 * Fix up the Flagged tables to match the current database 
		 */
		
		/* We need to pull the current id's */
		$sql = "SELECT id,user FROM flagged";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r;
		}	
		
		$sql = "ALTER TABLE `flagged` CHANGE `user` `user` VARCHAR( 128 ) NOT NULL";
		$db_results = mysql_query($sql, dbh());

		foreach ($results as $flag_users) { 
			// Reference the correct element
			$username = $username_id_map[$flag_users['user']];
			$sql = "UPDATE flagged SET user='$username' WHERE id='" . $flag_users['id'] . "'";
			$db_results = mysql_query($sql, dbh());
		} // foreach flag_users

		$sql = "ALTER TABLE `flagged` CHANGE `date` `date` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `flagged_song` CHANGE `song` `song` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `flagged_song` CHANGE `genre` `genre` INT( 11 ) UNSIGNED NULL DEFAULT NULL";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `flagged_song` CHANGE `played` `played` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `flagged_song` CHANGE `enabled` `enabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1'";
		$db_results = mysql_query($sql, dbh());
	
		/* We need to do some migration for this */
		$sql = "SELECT id,access FROM flagged_types";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r;
		} // end while results

		$sql = "ALTER TABLE `flagged_types` CHANGE `access` `access` SMALLINT( 3 ) UNSIGNED NOT NULL DEFAULT '25'";
		$db_results = mysql_query($sql, dbh());

		foreach ($results as $flag_types) { 
			if ($flag_types['access'] == 'user') { 
				$access = '25';
			}
			else { 
				$access = '100';
			}
			$sql = "UPDATE flagged_types SET access='$access' WHERE id='" . $flag_types['id'] . "'";
			$db_results = mysql_query($sql, dbh());
		} // end foreach
		
		/*
		 * I'm lazy, blast now playing then fix the table 
		 */
		$sql = "DELETE FROM now_playing";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `now_playing` CHANGE `user_id` `user` VARCHAR( 128 ) NULL";
		$db_results = mysql_query($sql, dbh());

		/*
		 * Now to Fix the Playlists
		 */
		
		// First gather all the information we need
		$sql = "SELECT id,owner FROM playlist";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r;
		}

		$sql = "ALTER TABLE `playlist` CHANGE `owner` `user` VARCHAR( 128 ) NOT NULL";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `playlist` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT";
		$db_results = mysql_query($sql, dbh());

		// Re-populate!
		foreach ($results as $data) { 
			$username = $username_id_map[$data['owner']];
			$sql = "UPDATE playlist SET user='$username' WHERE id='" . $data['id'] . "'";
			$db_results = mysql_query($sql, dbh());
		} // end foreach playlist

		/* Add a dyn_song varchar to the playlist table for future use */
		$sql = "ALTER TABLE `playlist_data` ADD `dyn_song` VARCHAR( 255 ) AFTER `song`";
		$db_results = mysql_query($sql, dbh());

		/*
		 * Time to fix the song table 
		 */

		// First pull in a full mapping for played and status
		$sql = "SELECT id,played,status FROM song";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r;
		}

		$sql = "ALTER TABLE `song` CHANGE `played` `played` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `song` CHANGE `status` `enabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1'";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `song` CHANGE `genre` `genre` INT( 11 ) UNSIGNED NULL DEFAULT NULL";
		$db_results = mysql_query($sql, dbh());

		// Now put everything back
		foreach ($results as $data) { 
			$played 	= '0';
			$enabled 	= '1';
			if ($data['played'] == 'true') { 
				$played = '1';
			}
			if ($data['status'] == 'disabled') {
				$enabled = '0';
			}
			$sql = "UPDATE song SET played='$played', enabled='$enabled' WHERE id='" . $data['id'] . "'";
			$db_results = mysql_query($sql, dbh());
		} // foreach

		/* 
		 * Again with the playing with the preferences :(
		 */
		
		// Pull the User/Preference Map
		$sql = "SELECT * FROM user_preference";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r;
		}

		$sql = "ALTER TABLE `user_preference` CHANGE `user` `user` VARCHAR( 128 ) NOT NULL";
		$db_results = mysql_query($sql, dbh());

		// Dump It!!
		foreach ($results as $data) { 
			$id = $data['user'];
			$username = $username_id_map[$id];
			if ($data['user'] == '0') { $username = '-1'; }
			$sql = "UPDATE user_preference SET user='$username' WHERE user='$id' AND preference='" . $data['preference'] . "'";
			$db_results = mysql_query($sql, dbh());
		} // foreach

		/*
		 * All of that for this....
		 */
		$sql = "ALTER TABLE `user` DROP `id`";
		$db_results = mysql_query($sql, dbh());

		/* Fix existing preferecnes */
                $sql = "SELECT DISTINCT(user) FROM user_preference";
                $db_results = mysql_query($sql, dbh());

                $user = new User(0);

                while ($results = mysql_fetch_array($db_results)) {
                        $user->fix_preferences($results[0]);
                }
		

		$this->set_version('db_version', '332002');

	} // update_332002
	
	function update_332003() { 
	
		/* Modify the Upload table to take into account the new code */
 		$sql = "ALTER TABLE `upload` CHANGE `user` `user` VARCHAR( 128 ) NOT NULL";	
		$db_results = mysql_query($sql, dbh());

		/* Drop the Comment Field (we dont' use it!) */
		$sql = "ALTER TABLE `upload` DROP `comment`";
		$db_results = mysql_query($sql, dbh());

		/* Tweak the Enum */
		$sql = "ALTER TABLE `upload` CHANGE `action` `action` ENUM( 'add', 'delete', 'quarantine' ) NOT NULL DEFAULT 'add'";
		$db_results = mysql_query($sql, dbh());

		/* Add Session to the Now Playing so we can deal with them damn'd WMP10 people */
		$sql = "ALTER TABLE `now_playing` ADD `session` VARCHAR( 64 )";
		$db_results = mysql_query($sql, dbh());

		/* Add in the extra Access Control fields */
		$sql = "ALTER TABLE `access_list` ADD `user` VARCHAR( 128 ) ," .
			"ADD `key` VARCHAR( 255 )";
		$db_results = mysql_query($sql, dbh());

		$sql = "INSERT INTO `preferences` ( `id` , `name` , `value` , `description` , `level` , `type` , `locked` )" . 
			"VALUES ('', 'quarantine_dir', '', 'Quarantine Directory', '100', 'system', '0')";
		$db_results = mysql_query($sql, dbh());

		/* Since this is a system value we only need to rebuild the -1 users preferences */
		$user = new User();
		$user->fix_preferences(-1);

		$this->set_version('db_version','332003');

	} // update_332003

	/*!
		@function update_332004
		@discussion adds a id to the playlist_data field because of a problem
			with updating the same song on the same playlist being basicly
			impossible...Adds rating table and general clean up 
	*/
	function update_332004() { 

		$sql = "ALTER TABLE `playlist_data` ADD `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
		$db_results = mysql_query($sql, dbh());

		/* Create the ratings table */
		$sql = " CREATE TABLE `ratings` (`id` int(11) unsigned NOT NULL auto_increment," . 
			" `user` varchar(128) NOT NULL default ''," . 
			" `object_type` enum('artist','album','song') NOT NULL default 'artist'," . 
			" `object_id` int(11) unsigned NOT NULL default '0'," . 
			" `user_rating` enum('00','0','1','2','3','4','5') NOT NULL default '0'," . 
			" PRIMARY KEY (`id`))";
		$db_results = mysql_query($sql, dbh());	

		/* Add an index for the object ID */
		$sql = "ALTER TABLE `ratings` ADD INDEX ( `object_id` ) ";
		$db_results = mysql_query($sql, dbh());

		/**
		 * Update the Type designation on the preference table 
		 * possible types are 
		 * system, theme, interface, options, streaming
		 * users get everything but site, admins get the whole kit and kabodle
		 */

		/* Set the Theme preferences */
		$sql = "UPDATE preferences SET type='theme' WHERE name='font' OR name='bg_color1' OR name='bg_color2' " . 
				" OR name='base_color1' OR name='base_color2' OR name='font_color1' OR name='font_color2' " . 
				" OR name='font_color3' OR name='row_color1' OR name='row_color2' OR name='row_color3' " . 
				" OR name='error_color' OR name='theme_name' OR name='font' OR name='font_size'";
		$db_results = mysql_query($sql, dbh());

		/* Set the Interface preferences */
		$sql = "UPDATE preferences SET type='interface' WHERE name='refresh_limit' OR name='lang' OR name='condPL' " . 
				" OR name='popular_threshold' OR name='ellipse_threshold_album' OR name='ellipse_threshold_artist' " . 
				" OR name='ellipse_threshold_title'";
		$db_results = mysql_query($sql, dbh());

		/* Set the Options Preferences */
		$sql = "UPDATE preferences SET type='options' WHERE name='download' OR name='upload' OR name='quarantine' " . 
				" OR name='direct_link' OR name='upload_dir'";
		$db_results = mysql_query($sql, dbh()); 

		/* Set the Streaming Preferences */
		$sql = "UPDATE preferences SET type='streaming' WHERE name='sample_rate' OR name='play_type' " . 
				" OR name='playlist_type'";
		$db_results = mysql_query($sql, dbh());

		/* Get the ID */
		$sql = "SELECT id FROM preferences WHERE name='display_menu'";
		$db_results = mysql_query($sql, dbh());

		$result = mysql_fetch_assoc($db_results);

		/* Kill the bottom menu preference as its no longer needed */
		$sql = "DELETE FROM preferences WHERE name='display_menu'";
		$db_results = mysql_query($sql, dbh());

		/* Kill the user pref references */
		$sql = "DELETE FROM user_preference WHERE preference='" . $result['id'] . "'";
		$db_results = mysql_query($sql, dbh());

		$this->set_version('db_version','332004');

	} // update_332004
	
	/**
	 * update_332005
	 * I tottaly messed up the 332004 update so I've gotta go back and verify what
	 * happened and didn't happen and then fix that which didn't happen 
	 * Doublecheck the playlist_data entry
	 * Check the ratings table to make sure it's correct
	 */
	function update_332005() { 

		/* Check Playlist_Data */
		$sql = "DESCRIBE playlist_data";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_assoc($db_results)) { 
			$key = $r['Field'];
			$results[$key] = $r['Key'];
		}

		/* If $results['id'] != PRI then we're screwed and we need to try again */
		if ($results['id'] != 'PRI') { 
			/* Try again!!!! */
			$sql = "ALTER TABLE `playlist_data` ADD `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
			$db_results = mysql_query($sql, dbh());
		}

		/* Next verify the setup of the ratings table */
		$sql = "DESCRIBE ratings";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		/* it fails horribly? */
		if (!$db_results) { 
			/* Try to create the table again */
			$sql =  "CREATE TABLE `ratings` (`id` int(11) unsigned NOT NULL auto_increment," .
	                        " `user` varchar(128) NOT NULL default ''," .
	                        " `object_type` enum('artist','album','song') NOT NULL default 'artist'," .
	                        " `object_id` int(11) unsigned NOT NULL default '0'," .
	                        " `user_rating` enum('00','0','1','2','3','4','5') NOT NULL default '0'," .
	                        " PRIMARY KEY (`id`))";
			$db_results = mysql_query($sql, dbh());
		} 
		else { 
			/* Go through the friggin results */
			while ($r = mysql_fetch_assoc($db_results)) { 
				$key = $r['Field'];
				$results[$key] = $r['Type'];
			}
			if ($results['rating']) { 
				$sql = "ALTER TABLE `ratings` CHANGE `rating` `user_rating` ENUM( '00', '0', '1', '2', '3', '4', '5' ) NOT NULL DEFAULT '0'";
				$db_results = mysql_query($sql, dbh());
			}

		} // end else

		/* One more thing I pooched */
		$sql = "ALTER TABLE `playlist_data` CHANGE `song` `song` INT( 11 ) UNSIGNED NULL DEFAULT NULL";
		$db_results = mysql_query($sql, dbh());

		$this->set_version('db_version','332005');

	} // update_332005

	/**
	 * update_332006
	 * Hmm 2006 perfect for the new year.. anyway this just adds the create_date on the account
	 * so that you know when they were registered/created 
	 */
	function update_332006() { 

		$sql = "ALTER TABLE `user` ADD `create_date` INT ( 11 ) UNSIGNED NULL DEFAULT NULL";
		$db_results = mysql_query($sql, dbh());
		
		$sql = "ALTER TABLE `user` ADD `validation` VARCHAR ( 128 )";
		$db_results = mysql_query($sql, dbh());
		
		$this->set_version('db_version','332006');

	} // update_332006

	/**
	 * update_332007
	 * Arg... I'm tried of writting these updates
	 * If I would only get it right the first time I wouldn't have to do this
	 */
	function update_332007() { 

		$sql = "ALTER TABLE `playlist_data` CHANGE `dyn_song` `dyn_song` TEXT NULL DEFAULT NULL";
		$db_results = mysql_query($sql, dbh());

		$this->set_version('db_version','332007');

	} // update_332007

	/**
	 * update_332008
	 * Re-combobulating Flaging Mojo
	 * Nuf Said...
	 */
	function update_332008() { 

		/* First drop the existing tables */
		$sql = "DROP TABLE flagged_song";
		$db_results = mysql_query($sql, dbh());

		$sql = "DROP TABLE flagged_types";
		$db_results = mysql_query($sql, dbh());

		$sql = "DROP TABLE flagged";
		$db_results = mysql_query($sql, dbh());

		/* Add in the spiffy new Flagged table */
		$sql = "CREATE TABLE `flagged` (`id` int(11) unsigned NOT NULL auto_increment," .
                                " `object_id` int(11) unsigned NOT NULL default '0'," .
                                " `object_type` enum('artist','album','song') NOT NULL default 'song'," .
                                " `user` varchar(128) NOT NULL default ''," .
                                " `flag` enum('delete','retag','reencode','other') NOT NULL default 'other'," .
				" `comment` varchar(255) NOT NULL default''," .
                                " PRIMARY KEY (`id`))";
		$db_results = mysql_query($sql, dbh());

		$this->set_version('db_version','332008');

	} // update_332008

	/**
	 * update_332009
	 * Wonderfull another update, this one adds the missing date and approved fields to flagged
	 * which I can't belive I forgot, and adds id back to the user table because warreng said so
	 */
	function update_332009() { 

		/* Add the missing fields */
		$sql = "ALTER TABLE `flagged` ADD `date` INT( 11 ) UNSIGNED NOT NULL AFTER `flag` ," . 
			"ADD `approved` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `date`";
		$db_results = mysql_query($sql, dbh());

		$sql = "ALTER TABLE `flagged` ADD INDEX ( `date` , `approved` )";
		$db_results = mysql_query($sql, dbh());

		/* Add the ID back to the user table because warreng said so */
		$sql = "ALTER TABLE `user` ADD `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
		$db_results = mysql_query($sql, dbh());

		$this->set_version('db_version','332009');

	} // update_332009

	/**
	 * update_332010
	 * This update changes the preferences table yet again... :(
	 */
	function update_332010() { 

		/* Drop the Locked option */
		$sql = "ALTER TABLE `preferences` DROP `locked`";
		$db_results = mysql_query($sql, dbh());

		/* Add the New catagory field */
		$sql = "ALTER TABLE `preferences` ADD `catagory` VARCHAR( 128 ) NOT NULL AFTER `type`";
		$db_results = mysql_query($sql, dbh());

		/* Grab all of the Types and populate it into the catagory */
		$sql = "SELECT id,type FROM preferences";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_assoc($db_results)) { 
			$key = $r['id'];
			$results[$key] = $r['type'];
		}

		foreach ($results as $key=>$catagory) { 

			$sql = "UPDATE preferences SET catagory='" . $catagory . "' WHERE id='$key'";
			$db_results = mysql_query($sql, dbh());

		} // foreach preferences
		
		/* Drop the Refresh Limit Option */
		$sql = "DELETE FROM preferences WHERE name='refresh_limit'";
		$db_results = mysql_query($sql, dbh());

		/* Drop the Local Length */
		$sql = "DELETE FROM preferences WHERE name='local_length'";
		$db_results = mysql_query($sql, dbh());

		/* Drop The cache limits */
		$sql = "DELETE FROM preferences WHERE name='album_cache_limit'";
		$db_results = mysql_query($sql, dbh());

		$sql = "DELETE FROM preferences WHERE name='artist_cache_limit'";
		$db_results = mysql_query($sql, dbh());

		$sql = "DELETE FROM preferences WHERE name='condPL'";
		$db_results = mysql_query($sql, dbh());

		/* Insert the new Localplay Level */
		$sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			" VALUES ('localplay_level','0','Localplay Access Level','100','special','streaming')";
		$db_results = mysql_query($sql, dbh());

		/* Inser the new Localplay Controller */
		$sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
			" VALUES ('localplay_controller','0','Localplay Type','100','special','streaming')";
		$db_results = mysql_query($sql, dbh());

		/* Set the Types for everything */
		$types['download']		= 'boolean';
		$types['upload']		= 'boolean';
		$types['quarantine']		= 'boolean';
		$types['popular_threshold']	= 'integer';
		$types['font']			= 'string';
		$types['bg_color1']		= 'string';
		$types['bg_color2']		= 'string';
		$types['base_color1']		= 'string';
		$types['base_color2']		= 'string';
		$types['font_color1']		= 'string';
		$types['font_color2']		= 'string';
		$types['font_color3']		= 'string';
		$types['row_color1']		= 'string';
		$types['row_color2']		= 'string';
		$types['row_color3']		= 'string';
		$types['error_color']		= 'string';
		$types['font_size']		= 'integer';
		$types['upload_dir']		= 'string';
		$types['sample_rate']		= 'string';
		$types['site_title']		= 'string';
		$types['lock_songs']		= 'boolean';
		$types['force_http_play']	= 'boolean';
		$types['http_port']		= 'integer';
		$types['catalog_echo_count']	= 'integer';
		$types['play_type']		= 'special';
		$types['direct_link']		= 'boolean';
		$types['lang']			= 'special';
		$types['playlist_type']		= 'special';
		$types['theme_name']		= 'special';
		$types['ellipse_threshold_album'] 	= 'integer';
		$types['ellipse_threshold_artist']	= 'integer';
		$types['ellipse_threshold_title']	= 'integer';
		$types['quarantine_dir']		= 'string';
		$types['localplay_level']		= 'special';
		$types['localplay_controller']		= 'special';

		/* Now we need to insert this crap */
		foreach ($types as $key=>$type) { 

			$sql = "UPDATE preferences SET type='$type' WHERE name='$key'";
			$db_results = mysql_query($sql, dbh());

		} // foreach types

		/* Fix every users preferences */
		$sql = "SELECT * FROM user";
		$db_results = mysql_query($sql, dbh());

		$user = new User();
		$user->fix_preferences('-1');

		while ($r = mysql_fetch_assoc($db_results)) { 
			$user->fix_preferences($r['username']);
		} // while results

		$this->set_version('db_version','332010');

	} // update_332010

} // end update class
?>
