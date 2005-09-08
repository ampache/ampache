<?php
/*

 Copyright (c) 2004 ampache.org
 All rights reserved.

 All of the main functions for Ampache.
 FIXME: Remove this file... shouldn't be used anymore

*/

/*
 * show_artist_pulldown()
 *
 * Helper functions for album and artist functions
 *
 */

function show_artist_pulldown ($artist) {

	global $settings;
	$dbh = dbh();

	$query = "SELECT id,name FROM artist ORDER BY name";
	$db_result = mysql_query($query, $dbh);
	echo "\n<select name=\"artist\">\n";

	while ( $r = mysql_fetch_row($db_result) ) {
		// $r[0] = id, $r[1] = name
		if ( $artist == $r[0] ) {
			echo "<option value=\"$r[0]\" selected=\"selected\">". htmlspecialchars($r[1]) ."</option>\n";
		}
		else {
			echo "<option value=\"$r[0]\">". htmlspecialchars($r[1]) ."</option>\n";
		}
	}

	echo "  </select>";
} // show_artist_pulldown()


/*
 * show_album_pulldown()
 *
 */

function show_album_pulldown ($album) {

	global $settings;
	$dbh = dbh();

	$sql = "SELECT id,name FROM album ORDER BY name";
	$db_result = mysql_query($sql, $dbh);

	echo "\n<select name=\"album\">\n";

	while ( $r = mysql_fetch_row($db_result) ) {
		// $r[0] = id, $r[1] = name
		if ( $album == $r[0] ) {
			echo "\t  <option value=\"${r[0]}\" selected=\"selected\">".htmlspecialchars($r[1])."</option>\n";
		}
		else {
			echo "\t  <option value=\"${r[0]}\">".htmlspecialchars($r[1])."</option>\n";
		}
	}//while

	echo "\n</select>\n";
} // show_album_pulldown()


/*
 * show_flagged_popup($reason);
 * 
 * Shows a listing of the flagged_types for when people want to mark
 *   a song as being broken in some way.
 */

function show_flagged_popup($reason,$label='value', $name='flagged_type', $other='') {

	global $settings;
	$dbh = dbh();

	$access = $_SESSION['userdata']['access'];

	$query = "SELECT type,value FROM flagged_types";
	if ($access !== 'admin') {
		$query .= " WHERE access = '$access'";
	}
	$db_result = mysql_query($query, $dbh);

	echo "\n<select name=\"$name\" $other>\n";

	while ( $r = mysql_fetch_array($db_result) ) {
		// $r[0] = id, $r[1] = type 
		if ( $reason === $r['type'] ) {
			echo "\t<option value=\"".$r['type']."\" selected=\"selected\">".htmlspecialchars($r[$label])."</option>\n";
		}
		else {
			echo "\t<option value=\"".$r['type']."\">".htmlspecialchars($r[$label])."</option>\n";
		}
	}

	echo "\n</select>\n";
} // show_flagged_popup()


/*
 * show_genre_pulldown()
 *
 * Set complete=1 if you want the entire genre list
 *
 */

function show_genre_pulldown ($genre, $complete, $lines= "'10' multiple='multiple'") {
	
	$dbh = dbh();

	// find the genres we have in use
	if ( $complete ) {
		$sql = "SELECT id FROM genre ORDER BY name";
	}
	else {
		$sql = "SELECT DISTINCT song.genre FROM genre, song" .
			" WHERE song.genre = genre.id" .
			" ORDER BY genre.name";
	}

	$db_result = mysql_query($sql, $dbh);

        echo "<select name=\"genre[]\" size=".$lines.">\n";

	if ( ! $complete ) {
		$genre_info = get_genre_info( -1 );
		if ( $genre == -1 ) {
			echo "  <option value=\"-1\" selected=\"selected\">${genre_info[0]} - (${genre_info[1]})</option>\n";
		}
		else {
			echo "  <option value=\"-1\">${genre_info[0]} - (${genre_info[1]})</option>\n";
		}
	}

	while ( $r = mysql_fetch_row($db_result) ) {
		// $r[0] = genre id
		list($genre_name, $genre_count) = get_genre_info($r[0]);
		$genre_name = htmlspecialchars($genre_name);

		if ( $genre == $r[0] ) {
			echo "  <option value=\"${r[0]}\" selected=\"selected\">$genre_name - ($genre_count)</option>\n";
		}
		else {
			echo "  <option value=\"${r[0]}\">$genre_name - ($genre_count)</option>\n";
		}
	}
	echo "  </select>\n";

} // show_genre_pulldown()

/*
 * show_catalog_pulldown()
 *
 * Set complete=1 if you want the entire catalog list (including disabled)
 *
 */

function show_catalog_pulldown ($catalog, $complete) {
	global $settings;
	// find the genres we have in use
        $sql = "SELECT id,name FROM catalog ORDER BY name";

	$db_result = mysql_query($sql, dbh());

	echo "\n<select name=\"catalog\">\n";

	echo "  <option value=\"-1\" selected=\"selected\">All</option>\n";

	while ( $r = mysql_fetch_row($db_result) ) 
	{
		// $r[0] = genre id
		$catalog_name = htmlspecialchars($r[1]);

		if ( $catalog == $r[0] ) 
		{
			echo "  <option value=\"${r[0]}\" selected=\"selected\">$catalog_name</option>\n";
		}
		else
		{
			echo "  <option value=\"${r[0]}\">$catalog_name</option>\n";
		}
	}
	echo "\n</select>\n";
} // show_catalog_pulldown()


/*
 * update_counter()
 *
 * update what song/album/artist has just been played
 *
 */

function update_counter ($type, $id, $dbh=0) {

	global $settings;
	if (!is_resource($dbh)) {
		$dbh = dbh();
	}

	// from hopson: these queries will be very useful for generating overall statistics:
	/*
	SELECT song.title,SUM(object_count.count) FROM song,object_count WHERE object_count.object_type = 'song' AND object_count.object_id = song.id GROUP BY song.id;

	SELECT album.name,SUM(object_count.count) FROM album,object_count WHERE object_count.object_type = 'album' AND object_count.object_id = album.id GROUP BY album.id;

	SELECT artist.name,SUM(object_count.count) FROM artist,object_count WHERE object_count.object_type = 'artist' AND object_count.object_id = artist.id GROUP BY artist.id;

	SELECT playlist.name,SUM(object_count.count) FROM playlist,object_count WHERE object_count.object_type = 'playlist' AND object_count.object_id = playlist.id GROUP BY playlist.id;
	*/

	if ( $type == 'song' ) {
		$sql = "UPDATE $type SET times_played = times_played + 1 WHERE id = '$id'";
	}
	else {
		$sql = "UPDATE $type SET times_played = times_played + 1 WHERE id = '$id'";
	}

	$db_result = mysql_query($sql, $dbh);
} // update_counter()



/*
 * delete_user_stats()
 *
 * just delete stats for specific users or all of them
 *
 */

function delete_user_stats ($user) {

	$dbh = dbh();

	if ( $user == 'all' ) {
		$sql = "DELETE FROM object_count";
	}
	else {
		$sql = "DELETE FROM object_count WHERE userid = '$user'";
	}
	$db_result = mysql_query($sql, $dbh);
} // delete_user_stats()


/*
 * insert_flagged_song()
 *
 */

function insert_flagged_song($song, $reason, $comment) {

	$user = $_SESSION['userdata']['id'];
	$time = time();
	$sql = "INSERT INTO flagged (user,song,type,comment,date)" .
		" VALUES ('$user','$song', '$reason', '$comment', '$time')";
	$db_result = mysql_query($sql, dbh());

} // insert_flagged_song()


/*
 * get_flagged();
 *
 * Get all of the songs from the flagged table.  These are songs that
 *  may or may not be broken.
 * Deprecated by hopson on 7/27
 */

function get_flagged() {

	$dbh = dbh();

	$sql = "SELECT flagged.id, user.username, type, song, date, comment" .
		" FROM flagged, user" .
		" WHERE flagged.user = user.username" .
		" ORDER BY date";
	$db_result = mysql_query($sql, $dbh);

	$arr = array();

	while ( $flag = mysql_fetch_object($db_result) ) {
		$arr[] = $flag;
	}

	return $arr;
} // get_flagged()


/*
 * get_flagged_type($type);
 *
 * Return the text associated with this type.
 */

function get_flagged_type($type) {

	$dbh = dbh();

	$sql = "SELECT value FROM flagged_types WHERE type = '$type'";
	echo $sql;
	$db_result = mysql_query($sql, $dbh);

	if ($flagged_type = mysql_fetch_object($db_result)) {
		return $flagged_type->value;
	}
	else {
		return FALSE;
	}
} // get_flagged_type()


/*
 * delete_flagged( $flag );
 *
 */

function delete_flagged($flag) {

        $dbh = dbh();

        $sql = "DELETE FROM flagged WHERE id = '$flag'";
        $db_result = mysql_query($sql, $dbh);
} // delete_flagged()


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


function get_song_ids_from_album ($album) {

	$dbh = dbh();

	$song_ids = array();

	$query = "SELECT id FROM song" .
		" WHERE album = '$album'" .
		" ORDER BY track, title";

	$db_result = mysql_query($query, $dbh);

	while ( $r = mysql_fetch_object($db_result) ) {
		$song_ids[] = $r->id;
	}

	return $song_ids;

}


function get_song_ids_from_artist ($artist) {

	global $settings;
	$dbh = dbh();

	$song_ids = array();
	$artist = sql_escape($artist);

	$query = "SELECT id FROM song" .
		" WHERE artist = '$artist'" .
		" ORDER BY album, track";

	$db_result = mysql_query($query, $dbh);

	while ( $r = mysql_fetch_object($db_result) ) {
		$song_ids[] = $r->id;
	}

	return $song_ids;
}


/*
 * get_song_ids_from_artist_and_album();
 *
 * Get all of the songs that are from this album and artist
 *
 */
function get_song_ids_from_artist_and_album ($artist, $album) {

	global $settings;
	$dbh = dbh();

	$sql = "SELECT id FROM song" .
		" WHERE artist = '$artist'" .
		" AND album = '$album'" .
		" ORDER BY track, title";
	$db_result = mysql_query($sql, $dbh);

	$song_ids = array();

	while ( $r = mysql_fetch_object($db_result) ) {
		$song_ids[] = $r->id;
	}

	return $song_ids;
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


/*********************************************************/
/* This is the main song display function.  I found tieing it to the playlist functions
     was really handy in getting added functionality at no cost.
/* Lets tie it to album too, so we can show art ;)       */
/*********************************************************/
/* One symbol, m(__)m */
function show_songs ($song_ids, $playlist_id=0, $album=0) {

	$dbh = dbh();

	// Get info about current user
	$user = new User($_SESSION['userdata']['username']);

	// Get info about playlist owner
	if (isset($playlist_id) && $playlist_id != 0) {
		$sql = "SELECT user FROM playlist WHERE id = '$playlist_id'";
		$db_result = mysql_query($sql, $dbh);
		if ($r = mysql_fetch_array($db_result)) {
			$pluser = get_user_byid($r[0]);
		}
	}

	$totaltime = 0;
	$totalsize = 0;

	require (conf('prefix') . "/templates/show_songs.inc");

	return true;

}// function show_songs



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


function get_artist_name ($artist, $dbh=0) {

	global $settings;
	if (!is_resource($dbh)) {
		$dbh = dbh();
	}

	$query = "SELECT name FROM artist WHERE id = '$artist'";
	$db_result = mysql_query($query, $dbh);

	if ($r = mysql_fetch_object($db_result)) {
		return $r->name;
	}
	else {
		return FALSE;
	}
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


function get_artist_from_album ($album_id) {

	global $settings;
	$dbh = dbh();

	$query = "SELECT DISTINCT artist.id, artist.name FROM artist,song" .
                 " WHERE song.album = '$album_id' AND song.artist = artist.id";
	$db_result = mysql_query($query, $dbh);
	$r = mysql_fetch_object($db_result);
	return $r;
}


function get_artist_name_from_song ($song_id) {

	$dbh = dbh();

	$sql = "SELECT artist.name AS name FROM artist, song" .
		" WHERE artist.id = song.artist" .
		" AND song.id = '$song_id'";
	$db_result = mysql_query($sql, $dbh);

	if ($r = mysql_fetch_object($db_result)) {
		return $r->name;
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


// Had to tweak this so it would show both public and private playlists
//  Defaults to showing both although you could pass type=private|adminprivate|public
//  to see only those
function show_playlists ($type = 'all') {

	$dbh = dbh();

	$user = $GLOBALS['user'];

	$web_path = conf('web_path');

	// mapping of types to pretty names
	$typemap = array( "public" => _("Public"),
			"private" => _("Your Private"),
			"adminprivate" => _("Other Private")
			);

	if ($type == 'all') {
		show_playlists('private');
		if ( $user->access === 'admin' ) {
			show_playlists('adminprivate');
		}
		show_playlists('public');
		return true;
	}
	elseif ($type == 'public') {
		$sql = "SELECT id,name,user,date ".
			" FROM playlist ".
			" WHERE type='public'".
			" ORDER BY name";
	}
	elseif ($type == 'private') {
		$sql = "SELECT id,name,user,date ".
			" FROM playlist ".
			" WHERE type='private'" .
			" AND user = '$user->username'" .
			" AND name <> 'Temporary'".
			" ORDER BY name";
	}
	elseif ($type == 'adminprivate') {
		if ( $user->access === 'admin' ) {
			$sql = "SELECT id,name,user,date ".
				" FROM playlist ".
				" WHERE type='private'" .
				" AND username != '$user->username'" .
				" AND name <> 'Temporary'".
				" ORDER BY name";
		}
		else {
			// No admin access
			$sql = 'SELECT 1+1';
		}
	}
	else {
 		echo "** Error ** Call to show_playlists with unknown type $type ".
 			 "in file ".$_SERVER['PHP_SELF']." ** <br />\n";
		$sql = 'SELECT 1+1';
	}

	$db_result = mysql_query($sql, $dbh);

	print <<<ECHO
<h3>$typemap[$type] Playlists</h3>

<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
  <tr class="table-header">
    <th>Playlist Name</th>
    <th># Songs</th>
    <th>Owner</th>
    <th colspan="6">Actions</th>
  </tr>

ECHO;

	flip_class(array('even','odd'));

	if ( mysql_num_rows($db_result) ) {
		while ( $r = mysql_fetch_array($db_result) ) {
			$plname = $r['name'];
			$plid = $r['id'];
			$pluser = new User($r['user']);
			$plfullname = $pluser->fullname;
			$plowner = $pluser->username;

			// find out how many songs in this playlist
			$count_query = "SELECT count(*) ".
					   " FROM playlist_data ".
					   " WHERE playlist = '$plid'";
			$count_result = mysql_query($count_query, $dbh);
			list($count) = mysql_fetch_row($count_result);
			$class = flip_class();
			echo "  <tr class=\"$class\">\n";
			echo "    <td><a href=\"$web_path/playlist.php?playlist_id=$plid&amp;action=view_list\">$plname</a></td>\n";
			echo "    <td>$count</td>\n";
			echo "    <td>$plfullname</td>\n"; 
			echo "    <td><a href=\"$web_path/playlist.php?playlist_id=$plid&amp;action=view_list\">" . _("View") . "</a></td>\n"; 

			if ($user->username == $pluser->username || $user->has_access(100)) {
				echo "    <td><a href=\"$web_path/playlist.php?playlist_id=$plid&amp;action=edit\">" . _("Edit") . "</a></td>\n";
				echo "    <td><a href=\"$web_path/playlist.php?playlist_id=$plid&amp;action=delete_playlist\">" . _("Delete") . "</a></td>\n";
			}
			else {
				echo "    <td>&nbsp;</td>\n";
				echo "    <td>&nbsp;</td>\n";
			}
			
			if ( $count[0] ) {
				echo "    <td><a href=\"$web_path/song.php?action=m3u&amp;playlist_id=$plid\">" . _("Play") . "</a> | " .
				     "<a href=\"$web_path/song.php?action=random&amp;playlist_id=$plid\">" . _("Random") . "</a></td>\n";				
			}
			else {
				echo "    <td>&nbsp;</td>\n";
			}                       
                        if( batch_ok() ) { 
                                echo"   <td><a href=\"$web_path/batch.php?action=pl&amp;id=$plid\">" . _("Download") . "</a></td>\n";
                        } else {
                                echo"   <td>&nbsp;</td>\n";
                        }                         

			echo "  </tr>\n";
		}
		echo "\n";
	} //if rows in result
	else { 
		echo "  <tr class=\"even\">\n";
	        echo "    <td colspan=\"7\">" . _("There are no playlists of this type") . "</td>\n"; 
		echo "  </tr>\n";
	}

	echo "</table>\n";
	echo "<br />\n";

}

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

//FIXME: Pull this and put it in a template
function show_playlist_create () {

	$web_path = conf('web_path');

	print <<<ECHO
<form name="songs" method="post" action="$web_path/playlist.php">
<table class="border"><tr class="table-header"><td colspan="2" align="center">
ECHO;

print _("Create a new playlist");
	print <<<ECHO
   </td>
  </tr>
  <tr class="even">
    <td align="left"> Name: </td>
    <td align="left"><input type="text" name="playlist_name" size="20" /></td>
  </tr>
  <tr class="odd">
    <td align="left"> Type: </td>
    <td align="left">
      <select name="type">
      <option value="private"> Private </option>
      <option value="public"> Public </option>
      </select>
    </td>
  </tr>
  <tr class="even">
    <td align="left"> &nbsp; </td>
    <td align="left">
      <input type="submit" name="action" value="Create" />
      <input type="reset" name="Reset" />
    </td>
  </tr>
</table>
</form>

ECHO;

}


function show_playlist_edit ( $playlist ) {

	$username = $_SESSION['userdata']['username'];
	if (check_playlist_access($playlist->id,$username) == false) {
		show_playlist_access_error($playlist, $username);
		return;
	}

	$plname = $playlist->name;
	$self = $_SERVER['PHP_SELF'];
	
	print <<<ECHO
<form name="songs" method="post" action="$self">
<input type="hidden" name="playlist_id" value="$playlist->id" />
<table class="border">
  <tr class="table-header">
	<td colspan="2">Editing Playlist</td>
  </tr>
  <tr>
    <td align="left"> Name: </td>
    <td align="left">
      <input type="text" name="new_playlist_name" value="$plname" size="20" />
    </td>
  </tr>
  <tr>
    <td align="left"> Type: </td>
    <td align="left">
      <select name="type">
ECHO;

	if ($playlist->type == 'public') {
		echo "<option value=\"public\" selected=\"selected\">Public</option>";
	}
	else {
		echo "<option value=\"public\">Public</option>";
	}
			
	if ($playlist->type == 'private') {
		echo "<option value=\"private\" selected=\"selected\">Private</option>";
	}
	else {
		echo "<option value=\"private\">Private</option>";
	}

	print <<<ECHO
      </select>
    </td>
  </tr>
  <tr>
    <td align="left"> &nbsp; </td>
    <td align="left">
      <input type="submit" name="action" value="Update" />
    </td>
  </tr>
</table>
</form>
ECHO;

}


// See if this user has access to work on this list
function check_playlist_access ($playlist_id, $username) {

	$dbh = dbh();

	$sql = "SELECT playlist.id FROM playlist, user" .
		" WHERE playlist.id = '$playlist_id'" .
		" AND playlist.user = user.username" .
		" AND user.username = '$username'";
	$db_result = mysql_query($sql, $dbh);

	if ( mysql_num_rows($db_result) == 1) {
		return TRUE;
	}
	else {
		if (!conf('use_auth')) { 
			return TRUE;
		}
		// check to see if this user is an admin
		if ($user = get_user($username)) {
			if ( $user->access == 'admin' ) {
				return TRUE;
			}
		}
	}

	// If we get here, access is denied
	return FALSE;

}


function show_playlist_dropdown ($playlist_id=0) {

	global $settings;
	$dbh = dbh();

	$userid = scrub_in($_SESSION['userdata']['username']);
	$sql = "SELECT * FROM playlist" .
		" WHERE user = '$userid'" .
		" AND name <> 'Temporary'" .
		" ORDER BY name";
	$db_result = @mysql_query($sql, $dbh);

	print <<<ECHO
<select name="playlist_id">
<option value="0"> -New Playlist- </option>

ECHO;

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
