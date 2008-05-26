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
	 * is_cached
	 * this checks the cache to see if the specified object is there
	 */
	public static function is_cached($index,$id) { 
		
		$is_cached = isset(self::$object_cache[$index][$id]); 

		return $is_cached; 

	} // is_cached

	/**
 	 * get_from_cache
	 * This attempts to retrive the specified object from the cache we've got here
	 */
	public static function get_from_cache($index,$id) { 

		if (isset(self::$object_cache[$index][$id])) { 
			self::$cache_hit++; 		
			return self::$object_cache[$index][$id]; 
		} 

		return false; 

	} // get_from_cache

	/**
	 * add_to_cache
	 * This adds the specified object to the specified index in the cache
	 */
	public static function add_to_cache($index,$id,$data) { 

		self::$object_cache[$index][$id] = $data;

		return true; 

	} // add_to_cache

} // end database_object
