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

/*!
	@header Error handler requires error_results() function

*/
class Error {

	//Basic Componets
	var $error_state=0;

	/* Generated values */
	var $errors = array();

	/*!
		@function error
		@discussion this is the constructor for the error class
	*/
	function Error() { 

		return true;

	} //constructor

	/*!
		@function add_error
		@discussion adds an error to the static array stored in 
			error_results()
	*/
	function add_error($name,$description) { 

		$array = array($name=>$description);

		error_results($array,1);
		$this->error_state = 1;

		return true;
		
	} // add_error


	/*!
		@function has_error
		@discussion returns true if the name given has an error, 
			false if it doesn't
	*/
	function has_error($name) { 

		$results = error_results($name);

		if (!empty($results)) { 
			return true;
		}

		return false;

	} // has_error

	/*!
		@function print_error
		@discussion prints out the error for a name if it exists
	*/
	function print_error($name) { 

		if ($this->has_error($name)) { 
			echo "<div class=\"fatalerror\">" . error_results($name) . "</div>\n"; 
		}

	} // print_error

} //end error class
?>
