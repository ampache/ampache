<?php
/*

 Copyright (c) 2001 - 2008 ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
require_once 'lib/init.php';

/* If we are running a demo, quick while you still can! */
if (Config::get('demo_mode') || !$GLOBALS['user']->has_access('25')) {

	access_denied();
	exit;
}

$song_ids = array();
$web_path = Config::get('web_path');

/**
 * action switch 
 */
switch ($_REQUEST['action']) { 
	case 'basket': 
		// Pull in our items (multiple types) 
		$objects = $GLOBALS['user']->playlist->get_items(); 

		//Recurse through the objects 
		foreach ($objects as $object_data) { 
			// Switch on the type of object we've got in here
			switch ($object_data['1']) { 
				case 'radio': 
					$radio = new Radio($object_data['0']); 
					$urls[] = $radio->url;
					$song_ids[] = '-1'; 
				break;
				case 'song': 
					$song_ids[] = $object_data['0'];
				break;
				default: 
					$random_url = Random::play_url($object_data['1']); 
					// If there's something to actually add
					if ($random_url) { 
						$urls[] = $random_url; 
						$song_ids[] = '-1'; 
					} 
				break;
			} // end switch on type
		} // end foreach

		// Check to see if 'clear' was passed if it was then we need to reset the basket
		if ( ($_REQUEST['playlist_method'] == 'clear' || Config::get('playlist_method') == 'clear') AND Config::get('play_method') != 'xspf_player') { 
			$GLOBALS['user']->playlist->clear(); 
		} 

	break;
	/* This is run if we need to gather info based on a tmp playlist */
	case 'tmp_playlist':
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmpplaylist_id']);
		$song_ids = $tmp_playlist->get_items();
	break;
	case 'play_favorite':
		$data = $GLOBALS['user']->get_favorites($_REQUEST['type']); 
		$song_ids = array(); 
		switch ($_REQUEST['type']) { 
			case 'artist':
			case 'album':
				foreach ($data as $value) { 
					$songs = $value->get_songs(); 
					$song_ids = array_merge($song_ids,$songs); 
				} 
			break;
			case 'song':
				foreach ($data as $value) { 
					$song_ids[] = $value->id; 
				} 
			break;
		} // end switch on type
	break;
	case 'single_song':
		$song_ids[] = scrub_in($_REQUEST['song_id']);
	break;
	case 'your_popular_songs':
		$song_ids = get_popular_songs($_REQUEST['limit'], 'your', $GLOBALS['user']->id);
	break;
	case 'popular_songs':
		$song_ids = get_popular_songs($_REQUEST['limit'], 'global');
	break;
	case 'genre':
		$genre = new Genre($_REQUEST['genre']);
		$song_ids = $genre->get_songs();
	break;
	case 'artist':
		$artist = new Artist($_REQUEST['artist_id']);
		$song_ids = $artist->get_songs();
	break;
	case 'artist_random':
		$artist = new Artist($_REQUEST['artist_id']);
		$artist->get_count();
		$song_ids = $artist->get_random_songs();
	break;
	case 'album_random':
		$album = new Album($_REQUEST['album_id']);
		$song_ids = $album->get_random_songs();
	break;
	case 'album':
		$album = new Album($_REQUEST['album_id']);
		$song_ids = $album->get_songs();
	break;
	case 'random_genre':
		$genre 		= new Genre($_REQUEST['genre']);
		$song_ids 	= $genre->get_random_songs();
	break;
	case 'playlist':
		$playlist	= new Playlist($_REQUEST['playlist_id']);
		$song_ids	= $playlist->get_songs($_REQUEST['song']);
	break;
	case 'playlist_random':
		$playlist	= new Playlist($_REQUEST['playlist_id']);
		$song_ids	= $playlist->get_random_songs();
	break;
	case 'random':
		if($_REQUEST['genre'][0] != '-1') {
			$matchlist['genre'] = $_REQUEST['genre'];
		}
		if($_REQUEST['catalog'] != '-1') {
			$matchlist['catalog'] = $_REQUEST['catalog'];
		}
		/* Setup the options array */
		$options = array('limit' => $_REQUEST['random'], 'random_type' => $_REQUEST['random_type'],'size_limit'=>$_REQUEST['size_limit']);
		$song_ids = get_random_songs($options, $matchlist);
	break;
	case 'democratic': 
		$democratic = new Democratic($_REQUEST['democratic_id']); 
		$urls[] = $democratic->get_url(); 
		$song_ids = array('0'); 
	break;
	case 'download': 
		$song_ids[] = $_REQUEST['song_id']; 
	default:
	break;
} // end action switch


/* Now that we've gathered the song information we decide what 
 * we should do with it, this is a sensitive time for the song id's
 * they don't know where they want to go.. let's help them out
 */
switch ($_REQUEST['method']) { 
	case 'download':
		// Run the access check and exit if they are not allowed to download
		if (!Access::check_function('batch_download')) { access_denied(); exit; } 

		// Format the zip file
		$name = "AmpacheZip-" . date("m-d-Y",time());
		$song_files = get_song_files($song_ids);
		set_memory_limit($song_files[1]+32);
		send_zip($name,$song_files[0]);
	break;
	case 'stream':
	default:
		// See if we need a special streamtype
		switch ($_REQUEST['action']) { 
			case 'download': 
				$stream_type = 'download'; 
			break;
			case 'democratic': 
				// Don't let them loop it
				if (Config::get('play_type') == 'democratic') { 
					Config::set('play_type','stream','1'); 
				}
			default:
				if (Config::get('play_type') == 'stream') { 
					$stream_type = Config::get('playlist_type');
				} 
				else { 
					$stream_type = Config::get('play_type'); 
				} 
			break;
		} 

		/* Start the Stream */
		$stream = new Stream($stream_type,$song_ids);
		if (is_array($urls)) { 
			foreach ($urls as $url) { 
				$stream->manual_url_add($url); 
			} 
		} 
		$stream->start();
	break;
} // end method switch 
?>
