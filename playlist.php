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
 * Playlist Document
 * This is the playlist document, it handles all things playlist.
 */

require_once("modules/init.php");


show_template('header');

/* Get the Vars we need for later cleaned up */
$action 	= strtolower(scrub_in($_REQUEST['action']));
$playlist	= new Playlist(scrub_in($_REQUEST['playlist_id']));

/* Switch on the action passed in */
switch ($action) { 
	case 'delete_playlist': 
		/* Make sure they have the rights */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
		/* Go for it! */
		$playlist->delete();
		show_confirmation(_('Playlist Deleted'),_('The Requested Playlist has been deleted'),'/playlist.php');
	break;
	case 'show_delete_playlist':
		/* Make sure they have the rights */
                if (!$playlist->has_access()) {
                        access_denied();
                        break;
                }
	
		/* Show Confirmation Question */
		$message = _('Are you sure you want to delete this playlist') . " " . $playlist->name . "?";
		show_confirm_action($message,'playlist.php','action=delete_playlist&amp;playlist_id=' . $playlist->id);
	break;
	case 'add_to':
	case 'add to':
		/* If we don't already have a playlist */
		if (!$playlist->id && $GLOBALS['user']->has_access(25)) { 
			$playlist_name = _('New Playlist') . " - " . date('m/j/y, g:i a');
			$id = $playlist->create($playlist_name, 'private');
			$playlist = new Playlist($id);
		}

		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}

		/* Must be admin or person who created this playlist */
		if ($GLOBALS['user']->username != $playlist->user && !$GLOBALS['user']->has_access(100)) { 
			access_denied();
		}
		if ($_REQUEST['type'] == 'album') { 
			$song_ids = get_songs_from_type($_REQUEST['type'],$_REQUEST['song'],$_REQUEST['artist_id']);
		}
		else { 	
			$song_ids = $_REQUEST['song'];
		}	

		/* Add the songs */
		$playlist->add_songs($song_ids);

		/* Show the Playlist */
		$_REQUEST['playlist_id'] = $playlist->id;
		show_playlist($playlist);
	break;	
	case 'add_dyn_song':
		/* Check Rights */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
		
		$playlist->add_dyn_song();
		show_playlist($playlist);
	break;
	case 'create_playlist':
	case 'create':
		/* Check rights */
		if (!$GLOBALS['user']->has_access(25)) { 
			access_denied();
			break;
		} 
		
		$playlist_name	= scrub_in($_REQUEST['playlist_name']);
		$playlist_type	= scrub_in($_REQUEST['type']);

		$playlist->create($playlist_name,$playlist_type);	
		show_confirmation(_('Playlist Created'),$playlist_name . ' (' . $playlist_type . ') ' . _(' has been created'),'playlist.php');
	break;
	case 'edit':
		show_playlist_edit($_REQUEST['playlist_id']);	
	break;
	case 'new':
		show_playlist_create();
	break;
	case 'remove_song':
	case _('Remote Selected Tracks'):
		/* Check em for rights */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
		$playlist->remove_songs($_REQUEST['song']);
		show_playlist($playlist);
	break;
	case 'update_playlist':
		/* Make sure they've got thems rights */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}

		$playlist->update_type($_REQUEST['type']);
		$playlist->update_name($_REQUEST['playlist_name']);
		$url 	= conf('web_path') . '/playlist.php?action=show_playlist&amp;playlist_id=' . $playlist->id;
		$title	= _('Playlist Updated');
		$body	= "$playlist->name " . _('has been updated and is now') . " $playlist->type";
		show_confirmation($title,$body,$url);
	break;
	case 'show_playlist':
		show_playlist($playlist);
	break;
	case 'show_import_playlist':
		show_import_playlist();
	break;
	case 'import_playlist':
		$catalog = new Catalog();
		$catalog->import_m3u(scrub_in($_REQUEST['filename']));

		$url	= conf('web_path') . '/playlist.php';
		$title = _('Playlist Imported');
		$body  = '';
		show_confirmation($title,$body,$url);
	break;
	case 'set_track_numbers':
		/* Make sure they have permission */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
                $song_ids = scrub_in($_REQUEST['song']);
                foreach ($song_ids as $song_id) {
                        $track = scrub_in($_REQUEST['tr_' . $song_id]);
                        $changes[] = array('song_id' => $song_id, 'track' => $track);
                }

                $playlist->update_track_numbers($changes);

                show_playlist($playlist);
        break;
	case 'normalize_tracks':
		/* Make sure they have permission */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
		
		/* Normalize the tracks */
		$playlist->normalize_tracks();

		/* Show our wonderful work */
		show_playlist($playlist);
	break;
	default:
		show_playlists();
	break;
} // switch on the action

show_footer(); 
?>
