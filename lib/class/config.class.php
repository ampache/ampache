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


/* config class
 * used to store static arrays of
 * config values, can read from ini files
 * 
 * has static methods, this uses the global config
 * creating a 'Config' object will allow for local
 * config overides and/or local configs (for like dba)
 * The class should be a static var in the other classes 
 */
class Config {

	// These are the settings for this specific class
	private $_local	= array(); 

	// These are the global settings they go where it goes
	private static $_global = array(); 

	/**
	 * constructor
	 * This is what is called when the class is loaded
	 */
	public function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * get
	 * This checks to see if this is an instance or procedure
	 * call, procedure == global, instance == local
	 */
	public static function get($name) {

		return self::$_global[$name];

	} // get

	/**
	 * get_all
	 * This returns all of the current config variables as an array
	 */
	public static function get_all() { 

		return self::$_global; 

	} // get_all
	
	/**
	 * set
	 * This checks to see if this is an instance or procedure calls
	 * and then sets the correct variable based on that
	 */
	public static function set($name, $value, $clobber = 0) {

		if (isset(self::$_global[$name]) && !$clobber) { 
			Error::add('Config Global',"Trying to clobber'$name' without setting clobber"); 
			return;
		}
		else { 
			self::$_global[$name] = $value; 
		} 

	} // set

	/**
	 * set_by_array
	 * This is the same as the set function except it takes an array as input
	 */
	public static function set_by_array($array, $clobber = 0) {
		
		foreach ($array as $name => $value) { 
			self::set($name,$value,$clobber); 
		} 

	} // set_by_array
	
} // end Config class
?>
