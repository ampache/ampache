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

/* Because this is accessed via Ajax we are going to allow the session_id 
 * as part of the get request
 */

// Set that this is an ajax include
define('AJAX_INCLUDE','1'); 

require_once '../lib/init.php';

/* Set the correct headers */
header("Content-type: text/xml; charset=" . Config::get('site_charset'));
header("Content-Disposition: attachment; filename=ajax.xml");
header("Expires: Tuesday, 27 Mar 1984 05:00:00 GMT"); 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache"); 

switch ($_REQUEST['page']) { 
	case 'flag': 
		require_once Config::get('prefix') . '/server/flag.ajax.php'; 
		exit; 
	break;
	case 'stats': 
		require_once Config::get('prefix') . '/server/stats.ajax.php'; 
		exit; 
	break;
	case 'browse': 
		require_once Config::get('prefix') . '/server/browse.ajax.php'; 
		exit; 
	break;
	case 'random': 
		require_once Config::get('prefix') . '/server/random.ajax.php'; 
		exit; 
	break;
	case 'playlist': 
		require_once Config::get('prefix') . '/server/playlist.ajax.php'; 
		exit; 
	break;
	case 'localplay': 
		require_once Config::get('prefix') . '/server/localplay.ajax.php'; 
		exit; 
	break;
	case 'tag':
		require_once Config::get('prefix') . '/server/tag.ajax.php';
		exit; 
	break;
	case 'stream': 
		require_once Config::get('prefix') . '/server/stream.ajax.php';
		exit; 
	break;
	case 'song': 
		require_once Config::get('prefix') . '/server/song.ajax.php'; 
		exit; 
	break; 
	case 'democratic': 
		require_once Config::get('prefix') . '/server/democratic.ajax.php'; 
		exit; 
	break;
	case 'index': 
		require_once Config::get('prefix') . '/server/index.ajax.php'; 
		exit; 
	break; 
	default: 
		// A taste of compatibility
	break;
} // end switch on page 

switch ($_REQUEST['action']) { 
	case 'refresh_rightbar': 
		$results['rightbar'] = ajax_include('rightbar.inc.php'); 
	break;
	/* Controls the editing of objects */
	case 'show_edit_object': 
		
		// Set the default required level
		$level = '50'; 

		switch ($_GET['type']) { 
			case 'album': 
				$key = 'album_' . $_GET['id']; 
				$album = new Album($_GET['id']); 
				$album->format(); 
			break;
			case 'artist': 
				$key = 'artist_' . $_GET['id']; 
				$artist = new Artist($_GET['id']); 
				$artist->format(); 
			break;
			case 'song': 
				$key = 'song_' . $_GET['id']; 
				$song = new Song($_GET['id']); 
				$song->format(); 
			break;
			case 'live_stream': 
				$key = 'live_stream_' . $_GET['id']; 
				$radio = new Radio($_GET['id']); 
				$radio->format(); 
			break;
			case 'playlist': 
				$key = 'playlist_row_' . $_GET['id']; 
				$playlist = new Playlist($_GET['id']); 
				$playlist->format(); 
				// If the current user is the owner, only user is required
				if ($playlist->user == $GLOBALS['user']->id) { 
					$level = '25'; 
				} 
			break;
			default: 
				$key = 'rfc3514'; 
				echo xml_from_array(array($key=>'0x1')); 
				exit; 
			break;
		} // end switch on type 

		// Make sure they got them rights
		if (!Access::check('interface',$level)) { 
			$results['rfc3514'] = '0x1'; 
			break; 
		} 

		ob_start(); 
		require Config::get('prefix') . '/templates/show_edit_' . $_GET['type'] . '_row.inc.php'; 
		$results[$key] = ob_get_contents(); 
		ob_end_clean(); 
	break; 
	case 'edit_object': 

		$level = '50'; 
		
		if ($_POST['type'] == 'playlist') { 
			$playlist = new Playlist($_POST['id']); 
			if ($GLOBALS['user']->id == $playlist->user) { 
				$level = '25'; 
			} 
		} 

		// Make sure we've got them rights
		if (!Access::check('interface',$level) || Config::get('demo_mode')) { 
			$results['rfc3514'] = '0x1'; 
			break; 
		} 

		switch ($_POST['type']) { 
			case 'album': 
				$key = 'album_' . $_POST['id']; 
				$album = new Album($_POST['id']); 
				$songs = $album->get_songs(); 
				$new_id = $album->update($_POST); 
				if ($new_id != $_POST['id']) { 
					$album = new Album($new_id); 
					foreach ($songs as $song_id) { 
						Flag::add($song_id,'song','retag','Inline Album Update'); 
					} 
				} 
				$album->format(); 
			break;
			case 'artist': 
				$key = 'artist_' . $_POST['id']; 
				$artist = new Artist($_POST['id']); 
				$songs = $artist->get_songs(); 
				$new_id = $artist->update($_POST); 
				if ($new_id != $_POST['id']) { 
					$artist = new Artist($new_id); 
					foreach ($songs as $song_id) { 
						Flag::add($song_id,'song','retag','Inline Artist Update'); 
					} 
				} 
				$artist->format(); 
			break;
			case 'song': 
				$key = 'song_' . $_POST['id']; 
				$song = new Song($_POST['id']);
				Flag::add($song->id,'song','retag','Inline Single Song Update'); 
				$song->update($_POST); 
				$song->format(); 
			break;
			case 'playlist': 
				$key = 'playlist_row_' . $_POST['id']; 
				$playlist->update($_POST); 
				$playlist->format(); 
				$count = $playlist->get_song_count();
			break;
			case 'live_stream': 
				$key = 'live_stream_' . $_POST['id']; 
				Radio::update($_POST); 
				$radio = new Radio($_POST['id']); 
				$radio->format(); 
			break;
			default: 
				$key = 'rfc3514';
				echo xml_from_array(array($key=>'0x1')); 
				exit; 
			break;
		} // end switch on type

		ob_start(); 
		require Config::get('prefix') . '/templates/show_' . $_POST['type'] . '_row.inc.php'; 
		$results[$key] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	case 'current_playlist':
		switch ($_REQUEST['type']) { 
			case 'delete':
				$GLOBALS['user']->playlist->delete_track($_REQUEST['id']);
			break;
		} // end switch

		$results['rightbar'] = ajax_include('rightbar.inc.php');
	break;
	// Handle the users basketcases... 
	case 'basket': 
		switch ($_REQUEST['type']) { 
			case 'album': 
			case 'artist': 
			case 'tag': 
				$object = new $_REQUEST['type']($_REQUEST['id']); 
				$songs = $object->get_songs(); 
				foreach ($songs as $song_id) { 
					$GLOBALS['user']->playlist->add_object($song_id,'song'); 
				} // end foreach
			break;
			case 'browse_set':
				Browse::set_type($_REQUEST['object_type']); 
				$objects = Browse::get_saved(); 
				foreach ($objects as $object_id) { 
					$GLOBALS['user']->playlist->add_object($object_id,'song'); 
				} 
			break; 
			case 'album_random': 
			case 'artist_random': 
			case 'tag_random':
				$data = explode('_',$_REQUEST['type']); 
				$type = $data['0'];
				$object = new $type($_REQUEST['id']); 
				$songs = $object->get_random_songs(); 
				foreach ($songs as $song_id) { 
					$GLOBALS['user']->playlist->add_object($song_id,'song'); 
				} 
			break; 
			case 'playlist': 
				$playlist = new Playlist($_REQUEST['id']); 
				$items = $playlist->get_items(); 
				foreach ($items as $item) { 
					$GLOBALS['user']->playlist->add_object($item['object_id'],$item['type']); 
				} 
			break;
			case 'playlist_random': 
				$playlist = new Playlist($_REQUEST['id']); 
				$items = $playlist->get_random_items(); 
				foreach ($items as $item) { 
					$GLOBALS['user']->playlist->add_object($item['object_id'],$item['type']); 
				} 
			break;
			case 'clear_all': 
				$GLOBALS['user']->playlist->clear(); 
			break;
			case 'live_stream': 
				$object = new Radio($_REQUEST['id']); 
				// Confirm its a valid ID
				if ($object->name) { 
					$GLOBALS['user']->playlist->add_object($object->id,'radio'); 
				} 
			break;
			case 'dynamic': 
				$random_id = Random::get_type_id($_REQUEST['random_type']); 
				$GLOBALS['user']->playlist->add_object($random_id,'random'); 
			break;
			case 'video': 
				$GLOBALS['user']->playlist->add_object($_REQUEST['id'],'video'); 
			break; 
			default: 
			case 'song': 
				$GLOBALS['user']->playlist->add_object($_REQUEST['id'],'song'); 
			break;
		} // end switch
		
		$results['rightbar'] = ajax_include('rightbar.inc.php'); 
	break;
	/* Setting ratings */
	case 'set_rating':
		ob_start(); 
		$rating = new Rating($_GET['object_id'],$_GET['rating_type']);
		$rating->set_rating($_GET['rating']);
                // We have to clear the cache now :(
                Rating::remove_from_cache('rating_' . $_GET['rating_type'] . '_all',$_GET['object_id']);
                Rating::remove_from_cache('rating_' . $_GET['rating_type'] . '_user',$_GET['object_id']);
		Rating::show($_GET['object_id'],$_GET['rating_type']); 
		$key = "rating_" . $_GET['object_id'] . "_" . $_GET['rating_type'];
		$results[$key] = ob_get_contents();
		ob_end_clean();
	break;
	// Used to change filter/settings on browse
	case 'browse':
		if ($_REQUEST['key'] && $_REQUEST['value']) {
			// Set any new filters we've just added
			Browse::set_filter($_REQUEST['key'],$_REQUEST['value']); 
		} 
		if ($_REQUEST['sort']) { 
			// Set the new sort value
			Browse::set_sort($_REQUEST['sort']); 
		} 

		// Refresh the browse div with our new filter options
		$object_ids = Browse::get_objects(); 

		ob_start(); 
		Browse::show_objects($object_ids, true); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	default:
		$results['rfc3514'] = '0x1';
	break;
} // end switch action

// Go ahead and do the echo
echo xml_from_array($results); 

?>
