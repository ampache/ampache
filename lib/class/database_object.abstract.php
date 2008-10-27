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
 * database_object
 * This is a general object that is extended by all of the basic
 * database based objects in ampache. It attempts to do some standard 
 * caching for all of the objects to cut down on the database calls
 */
abstract class database_object { 

	private static $object_cache = array(); 

	// Statistics for debugging
	public static $cache_hit = 0; 

	/**
	 * get_info
	 * retrieves the info from the database and puts it in the cache
	 * 
	 * @param string $id
	 * @param string $table_name
	 * @return array
	 */
	public function get_info($id,$table_name='') { 

		$table_name = $table_name ? Dba::escape($table_name) : Dba::escape(strtolower(get_class($this)));

		if (self::is_cached($table_name,$id)) { 
			return self::get_from_cache($table_name,$id); 
		} 

		$sql = "SELECT * FROM `$table_name` WHERE `id`='$id'"; 
		$db_results = Dba::query($sql); 

		if (!$db_results) { return array(); } 

		$row = Dba::fetch_assoc($db_results); 

		self::add_to_cache($table_name,$id,$row); 

		return $row; 	

	} // get_info

	/**
	 * is_cached
	 * this checks the cache to see if the specified object is there
	 */
	public static function is_cached($index,$id) { 
		
		return isset(self::$object_cache[$index][$id]); 

	} // is_cached

	/**
	 * get_from_cache
	 * This attempts to retrive the specified object from the cache we've got here
	 * 
	 * @param string $index
	 * @param string $id
	 * @return array
	 */
	public static function get_from_cache($index,$id) { 

		// Check if the object is set
		if (isset(self::$object_cache) 
			&& isset(self::$object_cache[$index])
			&& isset(self::$object_cache[$index][$id])
			) { 
			
			self::$cache_hit++; 		
			return self::$object_cache[$index][$id]; 
		} 

		return false; 

	} // get_from_cache

	/**
	 * add_to_cache
	 * This adds the specified object to the specified index in the cache
	 *
	 * @param string $index
	 * @param string $id
	 * @param array $data
	 * @return boolean
	 */
	public static function add_to_cache($index,$id,$data) { 
		$hasbeenset = false;
		
		// Set the data if it is set
		if (isset($data)) {
			self::$object_cache[$index][$id] = $data;
			$hasbeenset = true;
		}
		
		return $hasbeenset; 

	} // add_to_cache

} // end database_object
