<?php
/*
 
 Copyright (c) 2001 - 2007 Ampache.org
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
	public static function update($preference,$user_id,$value) { 

		// First prepare
		if (!is_numeric($preference)) { 
			$id = self::id_from_name($preference); 
			$name = $preference; 
		} 
		else { 
			$name = self::name_from_id($preference); 
			$id = $preference; 
		} 

		// Now do
		if (self::has_access($name)) { 
			$value 		= Dba::escape($value); 
			$user_id	= Dba::escape($user_id); 
			$sql = "UPDATE `user_preference` SET `value`='$value' " . 
				"WHERE `preference`='$id' AND `user`='$user_id'"; 
			$db_results = Dba::query($sql); 
			return true; 
		} 
		else { 
			debug_event('denied',$GLOBALS['user']->username . ' attempted to update ' . $name . ' but does not have sufficient permissions','3'); 
		}

		return false; 
	} // update

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

		if ($GLOBALS['user']->has_access($data['level'])) { 
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

		$sql = "INSERT INTO `preference` (`name`,`description`,`value`,`level`,`catagory`) " . 
			"VALUES ('$name','$description','$default','$level','$catagory')"; 
		$db_results = Dba::query($sql); 

		if (!$db_results) { return false; } 

		return true; 

	} // insert

} // end Preference class
