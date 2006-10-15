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
/**
 * TempPlaylist Class
 * This class handles the temporary playlists in ampache, it handles the
 * tmp_playlist and tmp_playlist_data tables, and sneaks out at night to 
 * visit user_vote from time to time
 */
class tmpPlaylist { 

	/* Variables from the Datbase */
	var $id;
	var $session;
	var $type;
	var $object_type;
	var $base_playlist;

	/* Generated Elements */
	var $items = array(); 


	/**
	 * Constructor 
	 * This takes a playlist_id as an optional argument and gathers the information
	 * if not playlist_id is passed returns false (or if it isn't found 
	 */
	function tmpPlaylist($playlist_id = 0) { 

		if (!$playlist_id) { return false; }
		
		$this->id 	= intval($playlist_id);
		$info 		= $this->_get_info();

		/* If we get something back */
		if (count($info)) { 
			$this->session		= $info['session'];
			$this->type		= $info['type'];
			$this->object_type 	= $info['object_type'];
			$this->base_playlist	= $info['base_playlist'];
		} 

		return true;

	} // tmpPlaylist

	/** 
	 * _get_info
	 * This is an internal (private) function that gathers the information for this object from the 
	 * playlist_id that was passed in. 
	 */
	function _get_info() { 

		$sql = "SELECT * FROM tmp_playlist WHERE id='" . sql_escape($this->id) . "'";	
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_assoc($db_results);

		return $results;

	} // _get_info

} // class tmpPlaylist
