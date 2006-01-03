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
 * Playlist Library
 * This file should contain the functions that don't fit inside the object, but
 * still are related to handling playlists
 */

/**
 * show_playlists
 * This shows all of the current playlists. Depending on your rights you may just
 * get to see Public + Yours Private or if you're an admin then you get to see
 * Public + Yours + Private 
 */
function show_playlists() { 

	/* Always show yours first */
	$playlists = get_playlists('private');
	$type = 'Private';
	require (conf('prefix') . '/templates/show_playlists.inc.php');

	/* Now for some Admin? */
	if ($GLOBALS['user']->has_access(100)) { 
		$playlists = get_playlists('adminprivate');
		$type = 'Admin';
		require (conf('prefix') . '/templates/show_playlists.inc.php');
	}

	/* Always Show Public */
	$playlists = get_playlists('public');
	$type = 'Public';
	require (conf('prefix') . '/templates/show_playlists.inc.php');

} // show_playlists

/**
 * get_playlists
 * This function takes private,adminprivate or public and returns an array of playlist objects
 * that match, it checks permission
 */
function get_playlists($type) { 

	switch ($type) { 
		case 'private':
			$sql = "SELECT id FROM playlist WHERE user='" . sql_escape($GLOBALS['user']->username) . "'" . 
				" AND type='private'";
		break;
		case 'adminprivate':
			if (!$GLOBALS['user']->has_access(100)) { return false; }
			$sql = "SELECT id FROM playlist WHERE user!='" . sql_escape($GLOBALS['user']->username) . "'" . 
				" AND type='private'";
		break;
		default:
		case 'public':
			$sql = "SELECT id FROM playlist WHERE type='public'";
		break;
	} // end switch

	$db_results = mysql_query($sql, dbh());

	$results = array();

	while ($r = mysql_fetch_assoc($db_results)) { 
		$playlist = new Playlist($r['id']);
		$results[] = $playlist;
	}

	return $results;

} // get_playlists

?>
