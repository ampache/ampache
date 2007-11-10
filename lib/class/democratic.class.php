<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
 * Democratic
 * This class handles democratic play, which is a fancy
 * name for voting based playback. This uses the tmp playlist
 * heavily
 */
class Democratic {

	/**
 	 * Constructor
	 * This doesn't do anything currently
	 */
	public function __construct() { 

		return true;

	} // Constructor

	/**
	 * get_playlists
	 * This returns all of the current valid 'Democratic' Playlists
	 * that have been created.
	 */
	public static function get_playlists() { 



	} // get_playlists

	/**
	 * get_current_playlist
	 * This returns the curren users current playlist, or if specified
	 * this current playlist of the user
	 */
	public static function get_current_playlist($user_id='') { 

		// If not passed user global
		$user_id = $user_id ? $user_id : $GLOBALS['user']->id; 


		$object = new tmpPlaylist($playlist_id); 

		return $object; 

	} // get_playlist

} // Democratic class
?>
