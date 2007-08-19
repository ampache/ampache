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
Copyright (c) 2001 - 2007 Ampache.org
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


 * Error class
 * This is the baic error class, its better now that we can use php5
 * hello static functions and variables
 */
class Error { 

	public static $state = false; // set to one when an error occurs
	public static $errors = array(); // Errors array key'd array with errors that have occured

	/**
	 * __constructor
	 * This does nothing... amazing isn't it!
	 */
	private function __construct() { 

		// Rien a faire
	
	} // __construct

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
			return true;
		} 

		// They want us to clobber it
		if ($clobber) { 
			Error::$state = 1;
			Error::$errors[$name] = $message;
			return true; 
		} 

		// They want us to append the error, add a BR\n and then the message
		else { 
			Error::$state = 1;
			Error::$errors[$name] .= "<br />\n" . $message;
			return true; 
		} 


	} // add

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


} // Error
