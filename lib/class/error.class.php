<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANT ABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, 
 USA.

*/

/**
 * Error class
 * This is the baic error class, its better now that we can use php5
 * hello static functions and variables
 */
class Error { 

	private static $state = false; // set to one when an error occurs
	private static $errors = array(); // Errors array key'd array with errors that have occured

	/**
	 * __constructor
	 * This does nothing... amazing isn't it!
	 */
	private function __construct() { 

		// Rien a faire
	
	} // __construct

	/**
	 * __destruct
	 * This saves all of the errors that are left into the session
	 */
	public function __destruct() { 


		foreach (self::$errors as $key=>$error) { 
			$_SESSION['errors'][$key] = $error; 
		} 

	} // __destruct

	/**
	 * add
	 * This is a public static function it adds a new error message to the array 
	 * It can optionally clobber rather then adding to the error message
	 */
	public static function add($name,$message,$clobber=0) { 

		// Make sure its set first 
		if (!isset(Error::$errors[$name])) { 
			Error::$errors[$name] = $message; 
			Error::$state = 1;
			$_SESSION['errors'][$key] = $message; 
		} 
		// They want us to clobber it
		elseif ($clobber) { 
			Error::$state = 1;
			Error::$errors[$name] = $message;
			$_SESSION['errors'][$key] = $message; 
		} 
		// They want us to append the error, add a BR\n and then the message
		else { 
			Error::$state = 1;
			Error::$errors[$name] .= "<br />\n" . $message;
			$_SESSION['errors'][$key] .=  "<br />\n" . $message; 
		} 

	} // add

	/**	
	 * occurred
	 * This returns true / false if an error has occured anywhere
	 */
	public static function occurred() { 

		if (self::$state == '1') { return true; } 

		return false; 

	} // occurred

	/**
	 * get
	 * This returns an error by name
	 */
	public static function get($name) { 

		if (!isset(Error::$errors[$name])) { return ''; } 

		return Error::$errors[$name];

	} // get

	/**
	 * display
	 * This prints the error out with a standard Error class span
	 * Ben Goska: Renamed from print to display, print is reserved
	 */
	public static function display($name) { 

		// Be smart about this, if no error don't print
		if (!isset(Error::$errors[$name])) { return ''; } 

		echo '<span class="error">' . Error::$errors[$name] . '</span>';

	} // display

	/**
 	 * auto_init 
	 * This loads the errors from the session back into Ampache
	 */
	public static function auto_init() { 

		if (!is_array($_SESSION['errors'])) { return false; } 

		// Re-insert them 
		foreach ($_SESSION['errors'] as $key=>$error) { 
			self::add($key,$error);
		} 

	} // auto_init

} // Error
