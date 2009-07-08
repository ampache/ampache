<?php
/*
 
 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 
*/ 

/**
 * preference Class
 * This handles all of the preference stuff for ampache it replaces
 * /lib/preference.lib.php
 */
class Preference { 

	/**
	 * __constructor
	 * This does nothing... amazing isn't it!
	 */
	private function __construct() { 

		// Rien a faire
	
	} // __construct

	/**
	 * update
	 * This updates a single preference from the given name or id
	 */
	public static function update($preference,$user_id,$value,$applytoall='') { 

		// First prepare
		if (!is_numeric($preference)) { 
			$id = self::id_from_name($preference); 
			$name = $preference; 
		} 
		else { 
			$name = self::name_from_id($preference); 
			$id = $preference; 
		} 
		if ($applytoall AND Access::check('interface','100')) { 
			$user_check = "";
		}
		else { 
			$user_check = " AND `user`='$user_id'";
		} 

		// Now do
		if (self::has_access($name)) { 
			$value 		= Dba::escape($value); 
			$user_id	= Dba::escape($user_id); 
			$sql = "UPDATE `user_preference` SET `value`='$value' " . 
				"WHERE `preference`='$id'$user_check"; 
			$db_results = Dba::query($sql); 
			Preference::clear_from_session();
			return true; 
		} 
		else { 
			debug_event('denied',$GLOBALS['user']->username . ' attempted to update ' . $name . ' but does not have sufficient permissions','3'); 
		}

		return false; 
	} // update

	/**
	 * update_level
	 * This takes a preference ID and updates the level required to update it (performed by an admin)
	 */
	public static function update_level($preference,$level) { 

                // First prepare
                if (!is_numeric($preference)) { 
                        $preference_id = self::id_from_name($preference);
                } 
                else { 
                        $preference_id = $preference;
                } 

		$preference_id 	= Dba::escape($preference_id);
		$level		= Dba::escape($level); 

		$sql = "UPDATE `preference` SET `level`='$level' WHERE `id`='$preference_id'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // update_level

	/**
	 * update_all
	 * This takes a preference id and a value and updates all users with the new info
	 */
	public static function update_all($preference_id,$value) { 

		$preference_id	= Dba::escape($preference_id);
		$value		= Dba::escape($value); 

		$sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$preference_id'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // update_all

	/**
	 * exists
	 * This just checks to see if a preference currently exists
	 */
	public static function exists($preference) { 

		// We assume it's the name
		$name = Dba::escape($preference); 
		$sql = "SELECT * FROM `preference` WHERE `name`='$name'"; 
		$db_results = Dba::query($sql); 

		return Dba::num_rows($db_results); 

	} // exists

	/**
	 * has_access
	 * This checks to see if the current user has access to modify this preference
	 * as defined by the preference name
	 */
	public static function has_access($preference) { 

		// Nothing for those demo thugs
		if (Config::get('demo_mode')) { return false; } 

		$preference = Dba::escape($preference); 

		$sql = "SELECT `level` FROM `preference` WHERE `name`='$preference'"; 
		$db_results = Dba::query($sql); 
		$data = Dba::fetch_assoc($db_results);

		if (Access::check('interface',$data['level'])) { 
			return true; 
		} 

		return false; 

	} // has_access

	/**
	 * id_from_name
	 * This takes a name and returns the id
	 */
	public static function id_from_name($name) { 

		$name = Dba::escape($name); 

		$sql = "SELECT `id` FROM `preference` WHERE `name`='$name'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		return $row['id']; 

	} // id_from_name

	/**
	 * name_from_id
	 * This returns the name from an id, it's the exact opposite
	 * of the function above it, amazing!
	 */
	public static function name_from_id($id) { 

		$id = Dba::escape($id); 

		$sql = "SELECT `name` FROM `preference` WHERE `id`='$id'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		return $row['name']; 

	} // name_from_id

	/**
 	 * get_catagories
	 * This returns an array of the names of the different possible sections
	 * it ignores the 'internal' catagory
	 */
	public static function get_catagories() { 

		$sql = "SELECT `preference`.`catagory` FROM `preference` GROUP BY `catagory` ORDER BY `catagory`"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			if ($row['catagory'] != 'internal') { 
				$results[] = $row['catagory']; 
			}
		} // end while

		return $results; 

	} // get_catagories

	/**
	 * get_all
	 * This returns a nice flat array of all of the possible preferences for the specified user
	 */
	public static function get_all($user_id) { 

		$user_id = Dba::escape($user_id); 

                if ($user_id != '-1') {
                        $user_limit = "AND `preference`.`catagory` != 'system'";
                }
		
		$sql = "SELECT `preference`.`name`,`preference`.`description`,`user_preference`.`value` FROM `preference` " . 
			" INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` " . 
			" WHERE `user_preference`.`user`='$user_id' AND `preference`.`catagory` != 'internal' $user_limit"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = array('name'=>$row['name'],'level'=>$row['level'],'description'=>$row['description'],'value'=>$row['value']);
		} 

		return $results; 

	} // get_all	

	/**
	 * insert
	 * This inserts a new preference into the preference table
	 * it does NOT sync up the users, that should be done independtly
	 */
	public static function insert($name,$description,$default,$level,$type,$catagory) { 

		// Clean em up 
		$name		= Dba::escape($name); 
		$description	= Dba::escape($description); 
		$default	= Dba::escape($default); 
		$level		= Dba::escape($level); 
		$type		= Dba::escape($type); 
		$catagory	= Dba::escape($catagory); 

		$sql = "INSERT INTO `preference` (`name`,`description`,`value`,`level`,`type`,`catagory`) " . 
			"VALUES ('$name','$description','$default','$level','$type','$catagory')"; 
		$db_results = Dba::query($sql); 

		if (!$db_results) { return false; } 

		return true; 

	} // insert

	/**
	 * delete
	 * This deletes the specified preference, a name or a ID can be passed
	 */
	public static function delete($preference) { 

               // First prepare
                if (!is_numeric($preference)) {
			$name = Dba::escape($preference); 
			$sql = "DELETE FROM `preference` WHERE `name`='$name'"; 
                }
                else {
			$id = Dba::escape($preference); 
			$sql = "DELETE FROM `preference` WHERE `id`='$id'"; 
                }

		$db_results = Dba::query($sql); 

		self::rebuild_preferences(); 

	} // delete

	/**
	 * rebuild_preferences
	 * This removes any garbage and then adds back in anything missing preferences wise
	 */
	public static function rebuild_preferences() { 

		// First remove garbage
		$sql = "DELETE FROM `user_preference` USING `user_preference` LEFT JOIN `preference` ON `preference`.`id`=`user_preference`.`preference` " . 
			"WHERE `preference`.`id` IS NULL"; 
		$db_results = Dba::query($sql); 

		// Now add anything that we are missing back in, except System
		$sql = "SELECT * FROM `preference` WHERE `type`!='system'"; 	

	} // rebuild_preferences

	/**
	 * fix_preferences
	 * This takes the preferences, explodes what needs to 
	 * become an array and boolean everythings
	 */
	public static function fix_preferences($results) {

	        $results['auth_methods']        = trim($results['auth_methods'])	? explode(",",$results['auth_methods']) : array(); 
	        $results['tag_order']           = trim($results['tag_order'])		? explode(",",$results['tag_order']) : array(); 
	        $results['album_art_order']     = trim($results['album_art_order'])	? explode(",",$results['album_art_order']) : array(); 
	        if (isset($results['amazin_base_urls']))
	        	$results['amazon_base_urls']    = trim($results['amazin_base_urls'])	? explode(",",$results['amazon_base_urls']) : array();
	        else 
				$results['amazon_base_urls']= array();
				
	        foreach ($results as $key=>$data) {
        		if (!is_array($data)) {
                	if (strcasecmp($data,"true") == "0") { $results[$key] = 1; }
                	if (strcasecmp($data,"false") == "0") { $results[$key] = 0; }
        		}
	        }

        	return $results;

	} // fix_preferences

	/**
	 * load_from_session
	 * This loads the preferences from the session rather then creating a connection to the database
	 */ 
	public static function load_from_session($uid=-1) { 
		
		if (is_array($_SESSION['userdata']['preferences']) AND $_SESSION['userdata']['uid'] == $uid) { 
			Config::set_by_array($_SESSION['userdata']['preferences'],1); 
			return true; 
		} 

		return false; 

	} // load_from_session

	/**
	 * clear_from_session
	 * This clears the users preferences, this is done whenever modifications are made to the preferences
	 * or the admin resets something
	 */
	public static function clear_from_session() { 

		unset($_SESSION['userdata']['preferences']); 

	} // clear_from_session

	/**
	 * is_boolean
	 * This returns true / false if the preference in question is a boolean preference
	 * This is currently only used by the debug view, could be used other places.. wouldn't be a half
	 * bad idea
	 */
	public static function is_boolean($key) { 

		$boolean_array = array('session_cookiesecure','require_session',
					'access_control','require_localnet_session',
					'downsample_remote','track_user_ip',
					'xml_rpc','allow_zip_download',
					'file_zip_download','ratings',
					'shoutbox','resize_images',
					'show_album_art','allow_public_registration',
					'captcha_public_reg','admin_notify_reg',
					'use_rss','download','force_http_play',
					'allow_stream_playback','allow_democratic_playback',
					'use_auth','allow_localplay_playback','debug','lock_songs'); 

		if (in_array($key,$boolean_array)) { 
			return true; 
		} 

		return false; 

	} // is_boolean

	/**
 	 * init
	 * This grabs the preferences and then loads them into conf it should be run on page load
	 * to initialize the needed variables
	 */
	public static function init() { 
		
		$user_id = $GLOBALS['user']->id ? Dba::escape($GLOBALS['user']->id) : '-1'; 

		// First go ahead and try to load it from the preferences
		if (self::load_from_session($user_id)) { 
			return true; 	
		} 

	        /* Get Global Preferences */
		$sql = "SELECT `preference`.`name`,`user_preference`.`value`,`syspref`.`value` AS `system_value` FROM `preference` " . 
			"LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`catagory`='system' " . 
			"LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user`='$user_id' AND `preference`.`catagory`!='system'"; 
	        $db_results = Dba::read($sql);

	        while ($row = Dba::fetch_assoc($db_results)) {
			$value = $row['system_value'] ? $row['system_value'] : $row['value']; 
	                $name = $row['name'];
	                $results[$name] = $value; 
	        } // end while sys prefs

	        /* Set the Theme mojo */
	        if (strlen($results['theme_name']) > 0) {
	                $results['theme_path'] = '/themes/' . $results['theme_name'];
	        }
	        // Default to the classic theme if we don't get anything from their
	        // preferenecs because we're going to want at least something otherwise
	        // the page is going to be really ugly
	        else {
	                $results['theme_path'] = '/themes/classic';
	        }

	        Config::set_by_array($results,1);
		$_SESSION['userdata']['preferences'] = $results; 
		$_SESSION['userdata']['uid'] = $user_id; 

	} // init


} // end Preference class
