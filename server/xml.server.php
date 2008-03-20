<?php
/*

 Copyright (c) 2001 - 2008 Ampache.org
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
 * This is accessed remotly to allow outside scripts access to ampache information 
 * as such it needs to verify the session id that is passed 
 */

define('NO_SESSION','1');
require_once '../lib/init.php';

// If it's not a handshake then we can allow it to take up lots of time
if ($_REQUEST['action'] != 'handshake') { 
	set_time_limit(0); 
} 

/* Set the correct headers */
header("Content-type: text/xml; charset=" . Config::get('site_charset'));
header("Content-Disposition: attachment; filename=information.xml");

// If we don't even have access control on then we can't use this!
if (!Config::get('access_control')) { 
	ob_end_clean(); 
	echo xmlData::error('501','Access Control not Enabled');
	exit; 
}  

/** 
 * Verify the existance of the Session they passed in we do allow them to
 * login via this interface so we do have an exception for action=login
 */
if (!Access::check_network('init-api',$_SERVER['REMOTE_ADDR'],$_REQUEST['user'],'5')) { 
	debug_event('Access Denied','Unathorized access attempt to API [' . $_SERVER['REMOTE_ADDR'] . ']', '5');
	ob_end_clean(); 
        echo xmlData::error('403','ACL Error');
	exit(); 
}

if ((!vauth::session_exists('api', $_REQUEST['auth']) AND $_REQUEST['action'] != 'handshake')) { 
	debug_event('Access Denied','Invalid Session attempt to API [' . $_REQUEST['action'] . ']','5'); 
	ob_end_clean(); 
	echo xmlData::error('401','Session Expired');
	exit(); 
}

// If we make it past the check and we're not a hand-shaking then we should extend the session
if ($_REQUEST['action'] != 'handshake') { 
	vauth::session_extend($_REQUEST['auth']); 
	$session = vauth::get_session_data($_REQUEST['auth']);
	$GLOBALS['user'] = User::get_from_username($session['username']);
} 

switch ($_REQUEST['action']) { 
	case 'handshake': 
		// Send the data we were sent to the API class so it can be chewed on 
		$token = Api::handshake($_REQUEST['timestamp'],$_REQUEST['auth'],$_SERVER['REMOTE_ADDR'],$_REQUEST['user']); 
		
		if (!$token) { 
			ob_end_clean(); 
			echo xmlData::error('401','Error Invalid Handshake, attempt logged'); 
		} 
		else { 
			ob_end_clean(); 
			echo xmlData::keyed_array($token); 
		} 

	break; 
	case 'artists': 
		Browse::reset_filters(); 
		Browse::set_type('artist'); 
		Browse::set_sort('name','ASC'); 
	
		if ($_REQUEST['filter']) { 
			Browse::set_filter('alpha_match',$_REQUEST['filter']); 
		} 

		// Set the offset
		xmlData::set_offset($_REQUEST['offset']); 
		xmlData::set_limit($_REQUEST['limit']); 

		$artists = Browse::get_objects(); 
		// echo out the resulting xml document
		ob_end_clean(); 
		echo xmlData::artists($artists);
	break; 
	case 'artist': 
		$uid = scrub_in($_REQUEST['filter']); 
		echo xmlData::artists(array($uid)); 
	break; 
	case 'artist_albums': 
		$artist = new Artist($_REQUEST['filter']); 

		$albums = $artist->get_albums(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 
		ob_end_clean(); 
		echo xmlData::albums($albums); 
	break; 
	case 'artist_songs': 
		$artist = new Artist($_REQUEST['filter']); 
		$songs = $artist->get_songs(); 

		// Set the offset
		xmlData::set_offset($_REQUEST['offset']); 
		xmlData::set_limit($_REQUEST['limit']); 
		ob_end_clean(); 
		echo xmlData::songs($songs); 
	break; 
	case 'albums': 
		Browse::reset_filters(); 
		Browse::set_type('album'); 
		Browse::set_sort('name','ASC'); 
		
		if ($_REQUEST['filter']) { 
			Browse::set_filter('alpha_match',$_REQUEST['filter']); 
		} 
		$albums = Browse::get_objects(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 
		ob_end_clean(); 
		echo xmlData::albums($albums); 
	break; 
	case 'album': 
		$uid = scrub_in($_REQUEST['filter']); 
		echo xmlData::albums(array($uid)); 
	break; 
	case 'album_songs': 
		$album = new Album($_REQUEST['filter']); 
		$songs = $album->get_songs(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 

		ob_end_clean(); 
		echo xmlData::songs($songs); 
	break; 
	case 'genres': 
		Browse::reset_filters(); 
		Browse::set_type('genre'); 
		Browse::set_sort('name','ASC'); 
		
		if ($_REQUEST['filter']) { 
			Browse::set_filter('alpha_match',$_REQUEST['filter']); 
		} 
		$genres = Browse::get_objects(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 

		ob_end_clean(); 
		echo xmlData::genres($genres); 
	break; 
	case 'genre_artists': 
		$genre = new Genre($_REQUEST['filter']); 
		$artists = $genre->get_artists(); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 
		echo xmlData::artists($artists); 	
	break; 
	case 'genre_albums': 
		$genre = new Genre($_REQUEST['filter']); 
		$albums = $genre->get_albums(); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 
		echo xmlData::albums($albums); 
	break;
	case 'genre_songs': 
		$genre = new Genre($_REQUEST['filter']); 
		$songs = $genre->get_songs(); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 	
		echo xmlData::songs($songs); 
	break; 
	case 'songs': 
		Browse::reset_filters(); 
		Browse::set_type('song'); 
		Browse::set_sort('title','ASC'); 
		
		if ($_REQUEST['filter']) { 
			Browse::set_filter('alpha_match',$_REQUEST['filter']); 
		} 
		$songs = Browse::get_objects(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 

		ob_end_clean(); 
		echo xmlData::songs($songs); 
	break; 
	case 'song': 
		$uid = scrub_in($_REQUEST['filter']); 

		ob_end_clean(); 
		echo xmlData::songs(array($uid)); 
	break; 
	case 'playlists': 
		Browse::reset_filters(); 
		Browse::set_type('playlist'); 
		Browse::set_sort('name','ASC'); 

		if ($_REQUEST['filter']) { 
			Browse::set_filter('alpha_match',$_REQUEST['filter']); 
		} 

		$playlist_ids = Browse::get_objects(); 

		xmlData::set_offset($_REQUEST['offset']); 
		xmlData::set_limit($_REQUEST['limit']); 

		ob_end_clean(); 
		echo xmlData::playlists($playlist_ids);
	break; 
	case 'playlist_songs': 
		$playlist = new Playlist($_REQUEST['filter']); 
		$items = $playlist->get_items(); 

		foreach ($items as $object) { 
			if ($object['type'] == 'song') { 
				$songs[] = $object['object_id']; 
			} 
		} // end foreach

		xmlData::set_offset($_REQUEST['offset']); 
		xmlData::set_limit($_REQUEST['limit']); 
		ob_end_clean(); 
		echo xmlData::songs($songs); 
	break; 
	case 'search_songs': 
		$array['s_all'] = $_REQUEST['filter']; 
		$results = run_search($array);
		ob_end_clean(); 

		xmlData::set_offset($_REQUEST['offset']); 
		xmlData::set_limit($_REQUEST['limit']); 

		echo xmlData::songs($results); 
	break; 
	default:
                ob_end_clean();
                echo xmlData::error('405','Invalid Request');
	break;
} // end switch action
?>
