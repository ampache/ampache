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
 * Core
 * This is really just a namespace class, it's full of static functions
 * would be replaced by a namespace library once that exists in php
 */
class Core {

	/**
	 * constructor
	 * This doesn't do anything
	 */
	private function __construct() { 

		return false; 

	} // construction

	/**
	 * form_register
	 * This registers a form with a SID, inserts it into the session variables
	 * and then returns a string for use in the HTML form
	 */
	public static function form_register($name,$type='post') { 

		// Make ourselves a nice little sid
		$sid =  md5(uniqid(rand(), true));

		// Register it
		$_SESSION['forms'][$name] = array('sid'=>$sid,'expire'=>time() + Config::get('session_length')); 

		switch ($type) { 
			default: 
			case 'post': 
				$string = '<input type="hidden" name="form_validation" value="' . $sid . '" />'; 
			break; 
			case 'get': 
				$string = $sid; 
			break; 
		} // end switch on type 

		return $string; 

	} // form_register

	/**
	 * form_verify
	 * This takes a form name and then compares it with the posted sid, if they don't match
	 * then it returns false and doesn't let the person continue
	 */
	public static function form_verify($name,$method='post') { 

		switch ($method) { 
			case 'post': 
				$source = $_POST['form_validation']; 
			break; 
			case 'get': 
				$source = $_GET['form_validation'];
			break; 
			case 'cookie': 
				$source = $_COOKIE['form_validation']; 
			break; 
			case 'request': 
				$source = $_REQUEST['form_validation']; 
			break; 
		} 

		if ($source == $_SESSION['forms'][$name]['sid'] AND $_SESSION['forms'][$name]['expire'] > time()) { 
			unset($_SESSION['forms'][$name]); 
			return true; 
		} 

		unset($_SESSION['forms'][$name]); 
		return false; 

	} // form_verify

	/**
 	* image_dimensions
	* This returns the dimensions of the passed song of the passed type
	* returns an empty array if PHP-GD is not currently installed, returns
	* false on error
	*/ 
	public static function image_dimensions($image_data) { 

		if (!function_exists('ImageCreateFromString')) { return false; } 

		$image = ImageCreateFromString($image_data); 

		if (!$image) { return false; } 

		$width = imagesx($image); 
		$height = imagesy($image); 

		if (!$width || !$height) { return false; } 

		return array('width'=>$width,'height'=>$height); 

	} // image_dimensions

} // Core
?>
