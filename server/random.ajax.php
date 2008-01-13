<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
        case 'album':
                $album_id = Random::album();

		// If we don't get anything stop
		if (!$album_id) { $results['rfc3514'] = '0x1'; break; } 

                $album = new Album($album_id);
                $songs = $album->get_songs();
                foreach ($songs as $song_id) {
                        $GLOBALS['user']->playlist->add_object($song_id,'song');
                }
		$results['rightbar'] = ajax_include('rightbar.inc.php');
        break;
        case 'artist':
                $artist_id = Random::artist();

		// If we don't get anything stop
		if (!$artist_id) { $results['rfc3514'] = '0x1'; break; } 

                $artist = new Artist($artist_id);
                $songs = $artist->get_songs();
                foreach ($songs as $song_id) {
                        $GLOBALS['user']->playlist->add_object($song_id,'song');
                }
		$results['rightbar'] = ajax_include('rightbar.inc.php');
        break;
        case 'playlist':
                $playlist_id = Random::playlist();
		
		// If we don't get any results stop right here!
		if (!$playlist_id) { $results['rfc3514'] = '0x1'; break; } 

                $playlist = new Playlist($playlist_id);
                $items = $playlist->get_items();
                foreach ($items as $item) {
                        $GLOBALS['user']->playlist->add_object($item['object_id'],$item['type']);
                }
		$results['rightbar'] = ajax_include('rightbar.inc.php');
        break;
	case 'advanced_random': 
		$object_ids = Random::advanced($_POST); 

		// First add them to the active playlist
		foreach ($object_ids as $object_id) { 
			$GLOBALS['user']->playlist->add_object($object_id,'song'); 
		} 
		$results['rightbar'] = ajax_include('rightbar.inc.php'); 

		// Now setup the browse and show them below!
		Browse::set_type('song'); 
		Browse::save_objects($object_ids);
		ob_start(); 
		Browse::show_objects(); 
		$results['browse'] = ob_get_contents(); 
		ob_end_clean(); 
		
	break;
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
