<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/**
 * Browse Class
 * This handles all of the sql/filtering
 * on the data before it's thrown out to the templates
 * it also handles pulling back the object_ids and then
 * calling the correct template for the object we are displaying
 */
class Browse { 

	
	/**
	 * constructor
	 * This should never be called
	 */
	private __construct() { 

		// Rien a faire

	} // __construct


	/**
	 * set_filter
	 * This saves the filter data we pass it into the session
	 * This is done here so that it's easy to modify where the data
	 * is saved should I change my mind in the future. It also makes
	 * a single point for whitelist tweaks etc
	 */
	public static function set_filter($key,$value) { 

		switch ($key) { 
                        case 'show_art':
                        case 'min_count':
                        case 'unplayed':
                        case 'rated':
                                $key = $_REQUEST['key'];
                                $_SESSION['browse']['filter'][$key] = make_bool($_REQUEST['value']);
                        break;
                        default
                                // Rien a faire
                        break;
                } // end switch
	
	} // set_filter

	/**
 	 * set_type
	 * This sets the type of object that we want to browse by
	 * we do this here so we only have to maintain a single whitelist
	 * and if I want to change the location I only have to do it here
	 */
	public static function set_type($type) { 

		switch($type) { 
			case 'song':
			case 'album':
			case 'artist':
			case 'genre':
				$_SESSION['browse']['type'] = $type;
			break;
			default:
				// Rien a faire
			break;
		} // end type whitelist

	} // set_type

} // browse
