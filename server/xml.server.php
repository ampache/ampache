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
		
		$xmldata = array('server'=>Config::get('version'),'version'=>Api::$version,'compatible'=>Api::$version); 

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
	
		$method = $_REQUEST['exact'] ? 'exact_match' : 'alpha_match'; 
		Api::set_filter($method,$_REQUEST['filter']); 
		Api::set_filter('add',$_REQUEST['add']); 
		Api::set_filter('update',$_REQUEST['update']); 

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
		$method = $_REQUEST['exact'] ? 'exact_match' : 'alpha_match'; 
		Api::set_filter($method,$_REQUEST['filter']); 
		Api::set_filter('add',$_REQUEST['add']); 
		Api::set_filter('update',$_REQUEST['update']); 

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
		Browse::set_type('tag'); 
		Browse::set_sort('name','ASC'); 

		$method = $_REQUEST['exact'] ? 'exact_match' : 'alpha_match'; 
		Api::set_filter($method,$_REQUEST['filter']); 
		$tags = Browse::get_objects(); 

                // Set the offset
                xmlData::set_offset($_REQUEST['offset']);
		xmlData::set_limit($_REQUEST['limit']); 

		ob_end_clean(); 
		echo xmlData::tags($tags); 
	break; 
	case 'tag':
		$uid = scrub_in($_REQUEST['filter']); 
		ob_end_clean();
		echo xmlData::tags(array($uid)); 
	break; 
	case 'tag_artists':
		$artists = Tag::get_tag_objects('artist',$_REQUEST['filter']); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 
		echo xmlData::artists($artists); 	
	break; 
	case 'tag_albums':
		$albums = Tag::get_tag_objects('album',$_REQUEST['filter']); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 
		echo xmlData::albums($albums); 
	break;
	case 'tag_songs': 
		$songs = Tag::get_tag_objects('song',$_REQUEST['filter']); 

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);

		ob_end_clean(); 	
		echo xmlData::songs($songs); 
	break; 
	case 'songs': 
		Browse::reset_filters(); 
		Browse::set_type('song'); 
		Browse::set_sort('title','ASC'); 

		$method = $_REQUEST['exact'] ? 'exact_match' : 'alpha_match';
		Api::set_filter($method,$_REQUEST['filter']); 
		Api::set_filter('add',$_REQUEST['add']); 
		Api::set_filter('update',$_REQUEST['update']); 
		
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
		// Don't scrub in we need to give her raw and juicy to the function
		$url = $_REQUEST['url']; 

		$song_id = Song::parse_song_url($url); 

		ob_end_clean(); 
		echo xmlData::songs(array($song_id)); 
	break; 
	case 'playlists': 
		Browse::reset_filters(); 
		Browse::set_type('playlist'); 
		Browse::set_sort('name','ASC'); 

		$method = $_REQUEST['exact'] ? 'exact_match' : 'alpha_match';
		Api::set_filter($method,$_REQUEST['filter']); 

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
		ob_end_clean(); 

		xmlData::set_offset($_REQUEST['offset']); 
		xmlData::set_limit($_REQUEST['limit']); 
	
		//WARNING!!! This is a horrible hack that has to be here because
		//Run search references these variables, ooh the huge manatee	
		unset($_REQUEST['limit'],$_REQUEST['offset']); 

		$results = run_search($array);

		echo xmlData::songs($results); 
	break; 
	case 'videos':
                Browse::reset_filters();
                Browse::set_type('video');
                Browse::set_sort('title','ASC');

                $method = $_REQUEST['exact'] ? 'exact_match' : 'alpha_match';
                Api::set_filter($method,$_REQUEST['filter']);
		
                $video_ids = Browse::get_objects();

                xmlData::set_offset($_REQUEST['offset']);
                xmlData::set_limit($_REQUEST['limit']);
		
		echo xmlData::videos($video_ids); 
	break; 
	case 'video': 
		$video_id = scrub_in($_REQUEST['filter']); 

		echo xmlData::videos(array($video_id)); 
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
				$result_status = $localplay->$_REQUEST['command'](); 
				$xml_array = array('localplay'=>array('command'=>array($_REQUEST['command']=>make_bool($result_status))));
				echo xmlData::keyed_array($xml_array); 
			break; 
			default:
				// They are doing it wrong
				echo xmlData::error('405',_('Invalid Request'));
			break;
		} // end switch on command

	break; 
	case 'democratic': 
		// Load up democratic information 
		$democratic = Democratic::get_current_playlist();
		$democratic->set_parent(); 
		
		switch ($_REQUEST['method']) { 
			case 'vote': 
				$type = 'song'; 
				$media = new $type($_REQUEST['oid']);
				if (!$media->id) { 
					echo xmlData::error('400',_('Media Object Invalid or Not Specified')); 
					break; 
				} 
				$democratic->vote(array(array('song',$media->id))); 

				// If everything was ok
				$xml_array = array('action'=>$_REQUEST['action'],'method'=>$_REQUEST['method'],'result'=>true); 	
				echo xmlData::keyed_array($xml_array); 
			break; 
			case 'devote': 
				$type = 'song'; 
				$media = new $type($_REQUEST['oid']); 
				if (!$media->id) { 
					echo xmlData::error('400',_('Media Object Invalid or Not Specified')); 
				} 

				$uid = $democratic->get_uid_from_object_id($media->id,$type);
				$democratic->remove_vote($uid); 
				
				// Everything was ok
				$xml_array = array('action'=>$_REQUEST['action'],'method'=>$_REQUEST['method'],'result'=>true); 
				echo xmlData::keyed_array($xml_array); 
			break; 
			case 'playlist': 
				$objects = $democratic->get_items(); 
				Song::build_cache($democratic->object_ids); 
				Democratic::build_vote_cache($democratic->vote_ids); 
				xmlData::democratic($objects); 
			break; 
			case 'play': 
				$url = $democratic->play_url(); 
				$xml_array = array('url'=>$url); 
				echo xmlData::keyed_array($xml_array); 
			break; 
			default: 
				echo xmlData::error('405',_('Invalid Request')); 
			break; 
		} // switch on method

		
	break; 
	default:
                ob_end_clean();
                echo xmlData::error('405',_('Invalid Request'));
	break;
} // end switch action
?>
