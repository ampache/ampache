<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * Sub-Ajax page, requires AJAX_INCLUDE as one
 */
if (AJAX_INCLUDE != '1') { exit; } 

switch ($_REQUEST['action']) { 
	case 'delete_track': 
		// Create the object and remove the track
		$playlist = new Playlist($_REQUEST['playlist_id']); 
		$playlist->format(); 
		if ($playlist->has_access()) {
			$playlist->delete_track($_REQUEST['track_id']); 
		} 

		$object_ids = $playlist->get_items(); 
		ob_start(); 
	        Browse::set_type('playlist_song');
	        Browse::add_supplemental_object('playlist',$playlist->id);
	        Browse::save_objects($object_ids);
	        Browse::show_objects($object_ids);
		$results['browse_content'] = ob_get_clean(); 
	break;
	case 'edit_track': 
		$playlist = new Playlist($_REQUEST['playlist_id']); 
		if (!$playlist->has_access()) { 
			$results['rfc3514'] = '0x1'; 
			break; 
		} 

		// They've got access, show the edit page
		$track = $playlist->get_track($_REQUEST['track_id']); 
		$song = new Song($track['object_id']); 
		$song->format(); 
		require_once Config::get('prefix') . '/templates/show_edit_playlist_song_row.inc.php';	
		$results['track_' . $track['id']] = ob_get_clean(); 
	break; 	
	case 'save_track': 
		$playlist = new Playlist($_REQUEST['playlist_id']); 
		if (!$playlist->has_access()) { 
			$results['rfc3514'] = '0x1'; 
			break; 
		} 
		$playlist->format(); 

		// They've got access, save this guy and re-display row
		$playlist->update_track_number($_GET['track_id'],$_POST['track']); 
		$track = $playlist->get_track($_GET['track_id']); 
		$song = new Song($track['object_id']); 
		$song->format(); 
		$playlist_track = $track['track']; 
		require Config::get('prefix') . '/templates/show_playlist_song_row.inc.php';
		$results['track_' . $track['id']] = ob_get_clean(); 
	break;	
	case 'create':
		if (!Access::check('interface','25')) { 
			debug_event('DENIED','Error:' . $GLOBALS['user']->username . ' does not have user access, unable to create playlist','1'); 
			break; 
		} 

		// Pull the current active playlist items
		$objects = $GLOBALS['user']->playlist->get_items(); 

		$name = $GLOBALS['user']->username . ' - ' . date("d/m/Y H:i:s",time()); 
	
		// generate the new playlist
		$playlist_id = Playlist::create($name,'public'); 
		if (!$playlist_id) { break; } 
		$playlist = new Playlist($playlist_id); 

		// Itterate through and add them to our new playlist
		foreach ($objects as $object_data) { 
			// For now only allow songs on here, we'll change this later
			$type = array_shift($object_data);
			if ($type == 'song') { 
				$songs[] = array_shift($object_data);  
			} 
		} // object_data
	
		// Add our new songs
		$playlist->add_songs($songs); 
		$playlist->format(); 
		$object_ids = $playlist->get_items(); 
		ob_start(); 
		require_once Config::get('prefix') . '/templates/show_playlist.inc.php'; 
		$results['content'] = ob_get_clean(); 
	break;
	case 'append': 
		// Pull the current active playlist items
		$objects = $GLOBALS['user']->playlist->get_items(); 

		// Create the playlist object
		$playlist = new Playlist($_REQUEST['playlist_id']); 

		// We need to make sure that they have access	
		if (!$playlist->has_access()) { 
			break; 
		} 

		$songs = array(); 

		// Itterate through and add them to our new playlist
		foreach ($objects as $element) { 
			$type = array_shift($element); 
			switch ($type) { 
				case 'song': 
					$songs[] = array_shift($element); 
				break; 
			} // end switch 	
		} // foreach

		// Override normal include procedure 
		Ajax::set_include_override(true); 

		// Add our new songs
		$playlist->add_songs($songs); 
		$playlist->format(); 
		$object_ids = $playlist->get_items(); 
		ob_start(); 
		require_once Config::get('prefix') . '/templates/show_playlist.inc.php'; 
		$results['content'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
