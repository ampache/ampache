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

class Localplay {

	/* Base Variables */




	/* Built Variables */
	var $_function_map = array(); 
	var $_template;
	var $_preferences = array();
	var $_player; 


	/**
	 * Constructor
	 * This must be called with a localplay type, it then loads the config
	 * file for the specified type and attempts to load in the function
	 * map, the preferences and the template
	 */
	function Localplay($type) { 


		$this->type = $type;

		$this->_get_info();


	} // Localplay


	/**
	 * _get_info
	 * This functions takes the type and attempts to get all the 
	 * information needed to load it. Will log errors if there are
	 * any failures, fatal errors will actually return something to the
	 * gui
	 */
	function _get_info() { 

		$this->_load_player();


	} // _get_info


	/**
	 * _load_player
	 * This function attempts to load the player class that localplay
	 * Will interface with in order to make all this magical stuf work
	 * all LocalPlay modules should be located in /modules/<name>/<name>.class.php
	 */
	function _load_player() { 

		$filename = conf('prefix') . '/modules/localplay/' . $this->type . '.controller.php';
		$include = require_once ($filename);
		
		if (!$include) { 
			/* Throw Error Here */

		} // include
		else { 
			$class_name = $this->type;
			$this->_player = new $class_name();
		}
		
	} // _load_player

	/**
	 * has_function
	 * This is used to check the function map and see if the current
	 * player type supports the indicated function. 
	 */
	function has_function($function_name) { 




	} // has_function


	/**
	 * _map_functions
	 * This takes the results from the loaded from the target player
	 * and maps them to the defined functions that Ampache currently 
	 * supports, this is broken into require and optional componets
	 * Failure of required componets will cause log entry and gui
	 * warning. The value of the elements in the $data array should
	 * be function names that are called on the action in question
	 */
	function _map_functions($data) { 

		/* Required Functions */
		$this->_function_map['add']	= $data['add'];
		$this->_function_map['delete']	= $data['delete'];
		$this->_function_map['play']	= $data['play'];
		$this->_function_map['stop']	= $data['stop'];

		/* Recommended Functions */
		$this->_function_map['next']		= $data['next'];
		$this->_function_map['prev']		= $data['prev'];
		$this->_function_map['get_playlist']	= $data['get_playlist'];
		$this->_function_map['get_playing']	= $data['get_playing'];

		/* Optional Functions */
		$this->_function_map['volume_set']	= $data['volume_set'];
		$this->_function_map['volume_up']	= $data['volume_up'];
		$this->_function_map['volume_down']	= $data['volume_down'];

	} // _map_functions

	/**
	 * add
	 * This function takes an array of song_ids and then passes the full URL
	 * to the player, this is a required function. 
	 */
	function add($songs) { 


		/* Call the Function Specified in the Function Map */


	} // add


} //end localplay class
?>
