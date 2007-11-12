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
 * name for voting based playback. This extends the tmpplaylist
 */
class Democratic extends tmpPlaylist {

	/**
	 * get_playlists
	 * This returns all of the current valid 'Democratic' Playlists
	 * that have been created.
	 */
	public static function get_playlists() { 

		// Pull all tmp playlsits with a session of < 0 (as those are fake) 
		// This is kind of hackish, should really think about tweaking the db
		// and doing this right. 
		$sql = "SELECT `id` FROM `tmp_playlist` WHERE `session`< '0'"; 
		$db_results = Dba::query($sql);  

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 

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
