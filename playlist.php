<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

/*

 Playlist mojo for adding, viewing, deleting, etc.

*/

require_once("modules/init.php");

// Get user object for later

if (isset($_REQUEST['action'])) {
	$action = scrub_in($_REQUEST['action']);
}

$type = scrub_in($_REQUEST['type']);

if (isset($_REQUEST['results'])) {
	$results = scrub_in($_REQUEST['results']);
}
else {
	$results = array();
}

if (isset($_REQUEST['artist_id'])) {
	$artist_id = scrub_in($_REQUEST['artist_id']);
}

if (isset($_REQUEST['playlist_name'])) {
	$playlist_name = scrub_in($_REQUEST['playlist_name']);
}

if (isset($_REQUEST['new_playlist_name'])) {
	$new_playlist_name = scrub_in($_REQUEST['new_playlist_name']);
}

if (isset($_REQUEST['playlist_id'])) {
	$playlist_id = scrub_in($_REQUEST['playlist_id']);
}

if (isset($_REQUEST['confirm'])) {
	$confirm = scrub_in($_REQUEST['confirm']);
}

if (isset($_REQUEST['song'])) {
	$song_ids = scrub_in($_REQUEST['song']);
}

/* Prepare the Variables */
$playlist = new Playlist(scrub_in($_REQUEST['playlist_id']));

/* First Switch */
// Have to handle this here, since we use this file
//   for playback of the "Play Selected Stuff" and display (for now)
switch ($action) { 
	case _("Flag Selected"):
	        require_once(conf('prefix').'/lib/flag.php');
	        $flags = scrub_in($_REQUEST['song']);
	        set_flag_value($flags, 'badid3','');
	        header("Location:" . conf('web_path')."/admin/flags.php" );
	break;
	case _("Edit Selected"):
	        require_once(conf('prefix').'/lib/flag.php');
	        $flags = scrub_in($_REQUEST['song']);
	        set_flag_value($flags, 'badid3','');
	        $count = add_to_edit_queue($flags);
	        session_write_close();
	        header( 'Location: '.conf('web_path').'/admin/flags.php?action='.urlencode($action) );
	        exit();
	break;
	default:
	break;
} // end first action switch

show_template('header');

show_menu_items('Playlists'); 

show_playlist_menu(); 


$playlist = new Playlist($playlist_id);

if ( isset($playlist_id) && ($playlist_id != 0) && $_REQUEST['action'] != 'delete_playlist' ) {
	// Get the playlist and check access
	$pluser = new User($playlist->user);

	if (! isset($playlist->id)) {
		show_playlist_access_error($playlist_id, $pluser->username);
	}

	echo "<div style=\"width:50%;\" class=\"text-box\">\n";
	echo "<span class=\"header2\">$playlist->name</span><br />";
	echo "&nbsp;&nbsp;&nbsp;" . _("owned by") . " $pluser->fullname ($pluser->username)<br />";
	echo "<ul>";
	if ($pluser->username == $user->username || $user->access === 'admin') {
		echo "<li><a href=\"" . conf('web_path') . "/playlist.php?action=edit&amp;playlist_id=$playlist->id\">" . _("Edit Playlist") . "</a></li>\n";
	}
	if (count($playlist->get_songs()) > 0) {
		echo "<li><a href=\"" . conf('web_path') . "/song.php?action=m3u&amp;playlist_id=$playlist->id\">" . _("Play Full Playlist") . "</a></li>\n";
		echo "<li><a href=\"" . conf('web_path') . "/song.php?action=random&amp;playlist_id=$playlist->id\">" . _("Play Random") . "</a></li>\n";
	}
	echo "</ul>";
	echo "</div>";
}


switch($action) {
	// Add to a playlist
	case 'Add to':
	case 'add_to':
		if ($playlist_id == 0) {
			// Creating a new playlist
			$playlist_name = _("New Playlist") . " - " . date("m/j/y, g:i a");
			$playlist->create_playlist($playlist_name, $user->username, 'private');
		}

		if ($type === 'album') {
			if ($song_ids = get_songs_from_type($type, $song_ids, $artist_id)) {
				$playlist->add_songs($song_ids);
			}
		}
		else {
			if (isset($song_ids) && is_array($song_ids)) {
				$playlist->add_songs($song_ids);
			}
		}
		show_playlist($playlist->id);
		break;

	case 'Create':
		$playlist->create_playlist($playlist_name, $user->username, $type);
		show_playlists();
		break;

	case 'delete_playlist':
		if ($_REQUEST['confirm'] === 'Yes') {
		
			$playlist->playlist($_REQUEST['playlist_id']);
			$playlist->delete();
			show_confirmation("Playlist Deleted","The $playlist->name Playlist has been deleted","playlist.php");
		}
		elseif ($_REQUEST['confirm'] === 'No') {
			show_songs($playlist->get_songs(), $_REQUEST['playlist_id']);
		}
		else {
			show_confirm_action("Are you sure you want to delete '$playlist->name' playlist?",
				"playlist.php",
				"action=delete_playlist&amp;playlist_id=$playlist_id");
		}
		break;

	case 'edit':
	case 'Edit':
		show_playlist_edit($playlist);
		break;

	case 'new':
		show_playlist_create();
		break;

	case 'remove_song':
	case 'Remove Selected Tracks':
		$playlist->remove_songs($song_ids);
		show_songs($playlist->get_songs(), $playlist_id);
		break;

	case 'Update':
		$playlist->update_type($type);
		$playlist->update_name($new_playlist_name);
		echo _("Playlist updated.");
		break;

	case 'Update Selected':
		pl_update_selected();
		break;
	case 'import_playlist':
		$filename = scrub_in($_REQUEST['filename']);
		$catalog = new Catalog();
		if ($catalog->import_m3u($filename)) { 
			show_confirmation($_REQUEST['playlist_type'] . " Imported",$filename . " was imported as a playlist","playlist.php");	
		} // it worked
		else { 
			show_confirmation("Import Failure",$filename . " failed to import correctly, this can be because the file wasn't found or no songs were matched","playlist.php");
		} // it didnt' work
		break;
	case 'view_list':
	case 'view':
        case 'View':
		show_playlist($playlist->id);
		break;
	case 'show_import_playlist':
		$playlist->show_import(); 
		break;
	case 'set_track_numbers':
	case 'Set Track Numbers':
		$song_ids = scrub_in($_REQUEST['song']);
		foreach ($song_ids as $song_id) {
			$track = scrub_in($_REQUEST['tr_' . $song_id]);
			$changes[] = array('song_id' => $song_id, 'track' => $track);
		}

		$playlist->update_track_numbers($changes);
		show_playlist($playlist->id);
		break;

	default:
		show_playlists();

} //switch($action)

echo "<br /><br />";
show_page_footer ('Playlists', '',$user->prefs['display_menu']);

/* Function definitions for this file */

/*************************/
function pl_update_selected() {

	$username = scrub_in($_SESSION['userdata']['id']);
	if ($user->has_access(100)) {
		// we have to update the current numbers for the artist these were
		//   for and who they will become
		$artists_to_update = array();
		$artists_to_update[] = $artist;

		while ( list($index, $s) = each($song) ) {
			$info = get_song_info($s);
			$artists_to_update[] = $info->artist;

			if ( $update_artist ) {
				$info->artist = $artist;
			}

			if ( $update_album ) {
				$info->album = $album;
			}

			if ( $update_genre ) {
				$info->genre = $genre;
			}

			// now just update the song in the db and you're good to go
			update_song($info->id, $info->title, 
					$info->artist, $info->album, $info->genre);

			// let's update the local file (if we can)
			if ( is_writable($info->file) ) {
				$id3 = new id3( $info->file );
				$id3->artists = get_artist_name($info->artist);
				$id3->album   = get_album_name($info->album);
				$genre_info   = get_genre($info->genre);
				$id3->genre   = $genre_info->name;
				$id3->genreno = $genre_info->id;
				$id3->write();
			}
		}

		$artists_to_update = array_unique($artists_to_update);

		foreach ($artists_to_update as $art) {
			update_artist_info($art);
		}

		header("Location:" . $HTTP_REFERER );
	}//admin access
	else {
		header("Location:" . conf('web_path') . "/index.php?access=denied" );
	}
} //function pl_update_selected
?>
