<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

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

require_once 'lib/init.php';

show_header(); 


/* Switch on the action passed in */
switch ($_REQUEST['action']) { 
	case 'add_dyn_song':
		/* Check Rights */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
		
		$playlist->add_dyn_song();
		$_SESSION['data']['playlist_id']        = $playlist->id;
		show_playlist($playlist);
	break;
	case 'create_playlist':
		/* Check rights */
		if (!$GLOBALS['user']->has_access(25)) { 
			access_denied();
			break;
		} 
		
		$playlist_name	= scrub_in($_REQUEST['playlist_name']);
		$playlist_type	= scrub_in($_REQUEST['type']);

		$playlist->create($playlist_name,$playlist_type);	
		$_SESSION['data']['playlist_id']        = $playlist->id;
		show_confirmation(_('Playlist Created'),$playlist_name . ' (' . $playlist_type . ') ' . _(' has been created'),'playlist.php');
	break;
	case 'remove_song':
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
		$playlist = new Playlist($_REQUEST['playlist_id']); 
		$playlist->format(); 
		require_once Config::get('prefix') . '/templates/show_playlist.inc.php'; 
	break;
	case 'show_import_playlist':
		show_import_playlist();
	break;
	case 'import_playlist':
		/* first we rename the file to it's original name before importing.
		Otherwise the playlist name will have the $_FILES['filename']['tmp_name'] which doesn't look right... */
		$dir = dirname($_FILES['filename']['tmp_name']) . "/";
		$filename = $dir . basename($_FILES['filename']['name']);
		move_uploaded_file($_FILES['filename']['tmp_name'], $filename );

		$catalog = new Catalog();
		$catalog->import_m3u($filename);

		$url	= conf('web_path') . '/playlist.php';
		$title = _('Playlist Imported');
		$body  = basename($_FILES['filename']['name']);
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
	case 'prune_empty':
		/* Make sure they have permission */
		if (!$GLOBALS['user']->has_access(100)) { 
			access_denied(); 
			break;
		}

		prune_empty_playlists(); 
		$url = conf('web_path') . '/playlist.php';
		$title = _('Empty Playlists Deleted'); 
		$body  = '';
		show_confirmation($title,$body,$url);
	break;
	case 'normalize_tracks':
		$playlist = new Playlist($_REQUEST['playlist_id']); 

		/* Make sure they have permission */
		if (!$playlist->has_access()) { 
			access_denied();
			break;
		}
		
		/* Normalize the tracks */
		$playlist->normalize_tracks();

	break;
	default:
		require_once Config::get('prefix') . '/templates/show_playlist.inc.php'; 
	break;
} // switch on the action

show_footer(); 
?>
