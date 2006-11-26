<?php
/*

 Copyright (c) 2004 ampache.org
 All rights reserved.

 All of the main functions for Ampache.
 FIXME: Remove this file... shouldn't be used anymore

*/


/*********************************************************/
/* Functions for getting songs given artist, album or id */
/*********************************************************/
// TODO : albums should be always gruoped by
// id, like 'greatest hits' album is below, never by name. 
// Other catalog functions should take care of assigning all
// songs with same name album to the same album id. It should
// not be done here. 
// I'm commenting all this out to always sort by ID, to 
// see how bad it is. -Rubin
function get_songs_from_album ($album) {

	global $settings;
	$dbh = dbh();

	$songs = array();

	$query = "SELECT track, id as song FROM song" .
		" WHERE album = '$album'" .
		" ORDER BY track, title";

	$db_result = mysql_query($query, $dbh);

	while ( $r = mysql_fetch_array($db_result) ) {
		$songs[] = $r;
	}

	return $songs;
}


// Used by playlist functions when you have an array of something of type
//  and you want to extract the songs from it whether type is artists or albums
function get_songs_from_type ($type, $results, $artist_id = 0) {

	$dbh = dbh();

	$count = 0;
	$song = array();

	foreach ($results as $value) {

		// special case from the album view where we don't want every orphan
		if ($type == 'album' && ($value == 'orphans' || $artist_id != 0)) {
			$sql = "SELECT id FROM song WHERE $type = '$value' AND artist = '$artist_id'";
			$db_result = mysql_query($sql, $dbh);
		}
		else {
			$sql = "SELECT id FROM song WHERE $type = '$value'";
			$db_result = mysql_query($sql, $dbh);
		}

		while ( $r = mysql_fetch_row($db_result) ) {
			$song[$count] = $r[0];
			$count++;
		}
	}
	return $song;
}


function show_playlist_form () {

	print <<<ECHO
<table cellpadding="5" class="tabledata">
  <tr align="center" class="odd">
    <td>
      <input type="button" name="select_all" value="Select All" onclick="this.value=check_results()" />
    </td>
    <td> Playlist:</td>
    <td>
      <input name="action" class="button" type="submit" value="Add to" />
ECHO;
 
	show_playlist_dropdown();
    
	print <<<ECHO
      <input name="action" class="button" type="submit" value="View" />
      <input name="action" class="button" type="submit" value="Edit" />
    
    </td>
  </tr>
  <tr align="center" class="even">
    <td colspan="6">
      <input name="action" class="button" type="submit" value="Play Selected" />
    </td>
  </tr> 
</table>
ECHO;

}


function get_artist_info ($artist_id) {

	$dbh = dbh();

	$sql = "SELECT * FROM artist WHERE id = '$artist_id'";
	$db_result = mysql_query($sql, $dbh);
	if ($info = mysql_fetch_array($db_result)) {
		$sql = "SELECT COUNT(song.album) FROM song " .
			" WHERE song.artist = '$artist_id'" .
			" GROUP BY song.album";
		$db_result = mysql_query($sql, $dbh);

		$albums = 0;
		$songs = 0;
		while(list($song) = mysql_fetch_row($db_result)) {
			$songs += $song;
			$albums++;
		}
		$info['songs'] = $songs;
		$info['albums'] = $albums;
		//FIXME: Lame place to put this
		//if ($songs < conf('min_artist_songs') || $albums < conf('min_artist_albums')) { 
		//	return FALSE;
		//}
		return $info;
	}
	else {
		return FALSE;
	}
}


function get_album_name ($album, $dbh = 0) {

	$album = new Album($album);
	return $album->name;
} // get_album_name


function get_genre_info($genre_id) {

	global $settings;
	$dbh = dbh();

	$sql = "SELECT name FROM genre WHERE id = '$genre_id'";
	$db_result = mysql_query($sql, $dbh);

	// if its -1 then we're doing all songs
	if ( $genre_id < 0 ) {
		$sql = "SELECT count(*) FROM song";
	}
	else {
		$sql = "SELECT count(*) FROM song WHERE genre = '$genre_id'";
	}

	$genre_result = mysql_query($sql, $dbh);

	$genre_count = mysql_fetch_row($genre_result);

	$r = mysql_fetch_row($db_result);

        // Crude hack for non-standard genre types
	if ($genre_id == -1) {
		return array('All', $genre_count[0]);
	}
	elseif ($genre_id == 0) {
		return array('N/A', $genre_count[0]);
	}
	else {
		return array($r[0], $genre_count[0]);
	}
}


function get_genre($id) {

	global $settings;
	$dbh = dbh();

	$query = "SELECT * FROM genre WHERE id = '$id'";
	$db_result = mysql_query($query, $dbh);

	$r = mysql_fetch_object($db_result);
	return $r;
}


// Utility function to help move things along
function get_song_info ($song, $dbh = 0) {
	
	$song = new Song($song);
	return $song;
	
} // get_song_info


/*!
	@function show_albums
	@discussion show many albums, uses view class
*/
function show_albums ($albums,$view=0) {

	$dbh = libglue_param(libglue_param('dbh_name'));

	if (!$view) {
	        $view = new View($_SESSION['view_base_sql'], $_SESSION['script'], $total_items,$_SESSION['view_offset_limit']);
	}

	if ($albums) { 
		require (conf('prefix') . "/templates/show_albums.inc");
	}
	else {
		echo "<p><font color=\"red\">No Albums Found</font></p>";
	}

} // show_albums

function get_playlist_track_from_song ( $playlist_id, $song_id ) {

	$dbh = dbh();

	$sql = "SELECT track FROM playlist_data" .
		" WHERE playlist = '$playlist_id'" .
		" AND song = '$song_id'";
	$db_result = mysql_query($sql, $dbh);
	if ($r = mysql_fetch_array($db_result)) {
		return $r[0];
	}
	else {
		return FALSE;
	}
}

/**
 * show_playlist_dropdown
 * Hacking this for now... will fix tomorrow evening 
 * Hmm Vollmer Lies... it's been a lot longer then said tomorrow evening...
 */
function show_playlist_dropdown ($playlist_id=0,$no_new=false) {

	global $settings;
	$dbh = dbh();

	$userid = scrub_in($_SESSION['userdata']['username']);
	$sql = "SELECT * FROM playlist" .
		" WHERE user = '$userid'" .
		" AND name <> 'Temporary'" .
		" ORDER BY name";
	$db_result = @mysql_query($sql, $dbh);

	echo "<select name=\"playlist_id\">\n";
	if (!$no_new) { echo "<option value=\"0\"> -New Playlist- </option>\n"; }

	while ( $r = @mysql_fetch_object($db_result) ) {
		if ( $playlist_id == $r->id ) {
			echo "<option value=\"" . $r->id . "\" selected=\"selected\">" . $r->name . "</option>\n";
		}
		else {
			echo "<option value=\"" . $r->id . "\">" . $r->name . "</option>\n";
		}
	}
	echo "</select>\n";
}


// Used to show when we have an access error for a playlist
function show_playlist_access_error ($playlist, $username) {

	$plname = $playlist->name;
	$pluser = new User($playlist->user);
	$plowner = $pluser->username;

	print <<<ECHO
<p style="font: 12px bold;"> Playlist Access Error </p>
<p>$username doesn't have access to update the '$plname' playlist, it is owned by $plowner.</p>

ECHO;

}


// Used to show a form with confirm action button on it (for deleting playlists, users, etc)
/*!
	@function show_confirm_action
	@discussion shows a confirmation of an action, gives a YES/NO choice
*/
function show_confirm_action ($text, $script, $arg) {

	$web_path = conf('web_path');
	require (conf('prefix') . "/templates/show_confirm_action.inc.php");

} // show_confirm_action


function unhtmlentities ($string)  {

	$trans_tbl = get_html_translation_table (HTML_ENTITIES);
	$trans_tbl = array_flip ($trans_tbl);
	$ret = strtr ($string, $trans_tbl);
	return preg_replace('/&#(\d+);/me', "chr('\\1')",$ret);
}

?>
