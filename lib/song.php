<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved

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
 * Song Library
 * This is for functions that don't make sense in the class because we aren't looking
 * at a specific song... these should be general function that return arrays of songs
 * and the like
 */

/**
 * get_recently_played
 * This function returns the last X songs that have been played
 * It uses the 'popular' threshold to determine how many to pull
 */
function get_recently_played($user_id='') { 

	if ($user_id) { 
		$user_limit = " AND object_count.user='" . Dba::escape($user_id) . "'"; 
	} 

	$sql = "SELECT object_count.object_id, object_count.user, object_count.object_type, object_count.date " . 
        	"FROM object_count " .
		"WHERE object_type='song'$user_limit " . 
        	"ORDER by object_count.date DESC " . 
		"LIMIT " . Config::get('popular_threshold'); 
	$db_results = Dba::query($sql); 

	$results = array(); 

	while ($r = Dba::fetch_assoc($db_results)) { 
		$results[] = $r; 	
	}

	return $results;

} // get_recently_played


/**
 * get_song_id_from_file
 * This function takes a filename and returns it's best guess for a song id
 * It is used by some of the localplay methods to go from filename to ampache
 * song record for items that are manualy entered into the clients
 */
function get_song_id_from_file($filename) { 

	$filename = Dba::escape($filename);

	$sql = "SELECT `id` FROM `song` WHERE `file` LIKE '%$filename'";
	$db_results = Dba::query($sql);

	$results = Dba::fetch_assoc($db_results);

	return $results['id'];

} // get_song_id_from_file

?>
