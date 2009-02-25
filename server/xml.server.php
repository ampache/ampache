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
	debug_event('Access Control','Error Attempted to use XML API with Access Control turned off','3'); 
	echo xmlData::error('501','Access Control not Enabled');
	exit; 
}  


/** 
 * Verify the existance of the Session they passed in we do allow them to
 * login via this interface so we do have an exception for action=login
 */
if (!vauth::session_exists('api', $_REQUEST['auth']) AND $_REQUEST['action'] != 'handshake' AND $_REQUEST['action'] != 'ping') {
        debug_event('Access Denied','Invalid Session attempt to API [' . $_REQUEST['action'] . ']','3');
        ob_end_clean();
        echo xmlData::error('401','Session Expired');
        exit();
}

// If the session exists then let's try to pull some data from it to see if we're still allowed to do this
$session = vauth::get_session_data($_REQUEST['auth']);
$username = ($_REQUEST['action'] == 'handshake' || $_REQUEST['action'] == 'ping') ? $_REQUEST['user'] : $session['username'];


if (!Access::check_network('init-api',$username,'5')) { 
        debug_event('Access Denied','Unathorized access attempt to API [' . $_SERVER['REMOTE_ADDR'] . ']', '3');
        ob_end_clean(); 
        echo xmlData::error('403','ACL Error');
        exit(); 
}


if ($_REQUEST['action'] != 'handshake' AND $_REQUEST['action'] != 'ping') { 
        vauth::session_extend($_REQUEST['auth']); 
        $GLOBALS['user'] = User::get_from_username($session['username']);
} 

switch ($_REQUEST['action']) { 
	case 'handshake': 

		// Send the data we were sent to the API class so it can be chewed on 
		$token = Api::handshake($_REQUEST['timestamp'],$_REQUEST['auth'],$_SERVER['REMOTE_ADDR'],$_REQUEST['user'],$_REQUEST['version']); 
		
		if (!$token) { 
			ob_end_clean(); 
			echo xmlData::error('401',_('Error Invalid Handshake - ') . Error::get('api')); 
		} 
		else { 
			ob_end_clean(); 
			echo xmlData::keyed_array($token); 
		} 

	break; 
	case 'ping': 
		
		$xmldata = array('version'=>Api::$version); 

		// Check and see if we should extend the api sessions (done if valid sess is passed)
		if (vauth::session_exists('api', $_REQUEST['auth'])) { 
			vauth::session_extend($_REQUEST['auth']); 
			$xmldata = array_merge(array('session_expire'=>date("r",time()+Config::get('session_length')-60)),$xmldata);
		} 

		debug_event('API','Ping Received from ' . $_SERVER['REMOTE_ADDR'] . ' :: ' . $_REQUEST['auth'],'5'); 

		ob_end_clean(); 
		echo xmlData::keyed_array($xmldata); 

	break; 
	case 'artists': 
		Browse::reset_filters(); 
		Browse::set_type('artist'); 
		Browse::set_sort('name','ASC'); 
	
		Api::set_filter('alpha_match',$_REQUEST['filter']); 

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
		
		Api::set_filter('alpha_match',$_REQUEST['filter']); 
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
	case 'tags':
		Browse::reset_filters(); 
		Browse::set_type('genre'); 
		Browse::set_sort('name','ASC'); 

		Api::set_filter('alpha_match',$_REQUEST['filter']); 
		$genres = Browse::get_objects(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 

		ob_end_clean(); 
		echo xmlData::genres($genres); 
	break; 
	case 'tag':
		$uid = scrub_in($_REQUEST['filter']); 
		ob_end_clean();
		echo xmlData::genres(array($uid)); 
	break; 
	case 'tag_artists':
		$genre = new Genre($_REQUEST['filter']); 
		$artists = $genre->get_artists(); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 
		echo xmlData::artists($artists); 	
	break; 
	case 'tag_albums':
		$genre = new Genre($_REQUEST['filter']); 
		$albums = $genre->get_albums(); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 
		echo xmlData::albums($albums); 
	break;
	case 'tag_songs': 
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

		Api::set_filter('alpha_match',$_REQUEST['filter']); 
		Api::set_filter('add',$_REQUEST['add']); 
		
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
	case 'url_to_song': 
		$url = scrub_in($_REQUEST['url']); 

		$song_id = Song::parse_song_url($url); 
		ob_end_clean(); 
		echo xmlData::songs(array($song_id)); 
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
	case 'playlist': 
		$uid = scrub_in($_REQUEST['filter']); 

		ob_end_clean(); 
		echo xmlData::playlists(array($uid)); 
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
	case 'localplay': 
		// Load their localplay instance
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$localplay->connect(); 

		switch ($_REQUEST['command']) { 
			case 'next': 
			case 'prev':
			case 'play': 
			case 'stop': 
				$result_status = $localplay->$command(); 
				$xml_array = array('localplay'=>array('command'=>array($command=>make_bool($result_status))));
				echo xmlData::build_from_array($xml_array); 
			break; 
			default:
				// They are doing it wrong
				echo xmlData::error('405',_('Invalid Request'));
			break;
		} // end switch on command

	break; 
	default:
                ob_end_clean();
                echo xmlData::error('405',_('Invalid Request'));
	break;
} // end switch action
?>
