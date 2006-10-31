<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * get_democratic_playlist
 * This retrives the tmpPlaylist->id based on our cheating
 * use of the -1 session value. We still pass a value just
 * incase we want to support multiple 'voting' queues later
 * in life
 */
function get_democratic_playlist($session_id) { 

	$session_id = sql_escape($session_id);

	$sql = "SELECT id FROM tmp_playlist WHERE session='$session_id'";
	$db_results = mysql_query($sql, dbh());

	$results = mysql_fetch_assoc($db_results);

	$tmp_playlist = new tmpPlaylist($results['id']);

	return $tmp_playlist;

} //get_democratic_playlist

?>
