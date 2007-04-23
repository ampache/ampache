<?php
/**
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
