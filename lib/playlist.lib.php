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

	show_playlist_menu();
	
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
 * show_playlist
 * This function takes a playlist object and calls show_songs after
 * runing get_items()
 */
function show_playlist($playlist) {
        
	/* Create the Playlist */
        $song_ids = $playlist->get_items();


	show_playlist_menu();

        if (count($song_ids) > 0) {
                show_songs($song_ids, $playlist);
        }
        else {
                echo "<div class=\"text-box\">" . _("No songs in this playlist.") . "</div>\n";
        }

} // show_playlist

/**
 * show_playlist_menu
 * This shows a little pretty box that contains the playlist 'functions'
 */
function show_playlist_menu() {

	require (conf('prefix') . '/templates/show_playlist_box.inc.php');

} // show_playlist_menu

/**
 * show_playlist_edit
 * This function shows the edit form for a playlist, nothing special here
 */
function show_playlist_edit($playlist_id) { 

	$playlist = new Playlist($playlist_id);
	/* Chuck em out if they don't have the rights */
	if (!$playlist->has_access()) { access_denied(); return false; }

	require_once (conf('prefix') . '/templates/show_playlist_edit.inc.php');

} // show_playlist_edit

/**
 * get_playlists
 * This function takes private,adminprivate or public and returns an array of playlist objects
 * that match, it checks permission
 */
function get_playlists($type) { 

	switch ($type) { 
		case 'private':
			$sql = "SELECT id FROM playlist WHERE user='" . sql_escape($GLOBALS['user']->id) . "'" . 
				" AND type='private' ORDER BY name";
		break;
		case 'adminprivate':
			if (!$GLOBALS['user']->has_access(100)) { return false; }
			$sql = "SELECT id FROM playlist WHERE user!='" . sql_escape($GLOBALS['user']->id) . "'" . 
				" AND type='private' ORDER BY name";
		break;
		default:
		case 'public':
			$sql = "SELECT id FROM playlist WHERE type='public' ORDER BY name";
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

/**
 * prune_empty_playlists
 * This function goes through and deletes any playlists which have
 * no songs in them. This can only be done by a full admin
 */
function prune_empty_playlists() { 


	$sql = "SELECT playlist.id FROM playlist LEFT JOIN playlist_data ON playlist.id=playlist_data.playlist " . 
		"WHERE playlist_data.id IS NULL";
	$db_results = mysql_query($sql, dbh());

	$results = array();

	while ($r = mysql_fetch_assoc($db_results)) { 
		$results[] = $r['id'];
	}

	/* Delete the Playlists */
	foreach ($results as $playlist_id) { 
		$playlist = new Playlist($playlist_id);
		$playlist->delete();
	}
	
	return true;

} // prune_empty_playlists

/**
 * show_playlist_select
 * This shows the playlist select box, it takes a playlist ID and a type as an optional
 * param, the type is 'normal','democratic','dynamic' these are hacks and make baby vollmer cry
 * but I'm too lazy to fix them right now
 */
function show_playlist_select($playlist_id=0,$type='') { 

	/* If democratic show everything, else normal */
	if ($type == 'democratic') { 
		$where_sql = '1=1';
	}
	else { 
		$where_sql = " `user` = '" . sql_escape($GLOBALS['user']->id) . "'";
	}

	$sql = "SELECT id,name FROM playlist " . 
		"WHERE " . $where_sql . " ORDER BY name";
	$db_results = mysql_query($sql,dbh());

	echo "<select name=\"playlist_id\">\n";
	
	/* The first value changes depending on our type */
	if ($type == 'democratic') { 
		echo "\t<option value=\"0\">" . _('All') . "</option>\n";
	}
	elseif ($type != 'dynamic') { 
		echo "\t<option value=\"0\"> -" . _('New Playlist') . "- </option>\n"; 
	}  

	while ($r = mysql_fetch_assoc($db_results)) { 
		$select_txt = '';
		if ($playlist_id == $r['id']) { $select_txt = ' selected="selected" '; } 

		echo "\t<option value=\"" . scrub_out($r['id']) . "\"$select_txt>" . scrub_out($r['name']) . "</option>\n";

	} // end while

	echo '</select>';

} // show_playlist_select

?>
