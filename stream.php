<?php
/*

 Copyright (c) Ampache.org
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
if (Config::get('demo_mode') || !Access::check('interface','25')) {
	access_denied();
	exit;
}

$media_ids = array();
$web_path = Config::get('web_path');

/**
 * action switch 
 */
switch ($_REQUEST['action']) { 
	case 'basket': 
		// Pull in our items (multiple types) 
		$media_ids = $GLOBALS['user']->playlist->get_items(); 

		// Check to see if 'clear' was passed if it was then we need to reset the basket
		if ( ($_REQUEST['playlist_method'] == 'clear' || Config::get('playlist_method') == 'clear') AND Config::get('play_type') != 'xspf_player') { 
			$GLOBALS['user']->playlist->clear(); 
		} 

	break;
	/* This is run if we need to gather info based on a tmp playlist */
	case 'tmp_playlist':
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmpplaylist_id']);
		$media_ids = $tmp_playlist->get_items();
	break;
	case 'play_favorite':
		$data = $GLOBALS['user']->get_favorites($_REQUEST['type']); 
		$media_ids = array(); 
		switch ($_REQUEST['type']) { 
			case 'artist':
			case 'album':
				foreach ($data as $value) { 
					$songs = $value->get_songs(); 
					$media_ids = array_merge($media_ids,$songs); 
				} 
			break;
			case 'song':
				foreach ($data as $value) { 
					$media_ids[] = $value->id; 
				} 
			break;
		} // end switch on type
	break;
	case 'single_song':
		$media_ids[] = array('song',scrub_in($_REQUEST['song_id'])); 
	break;
	case 'your_popular_songs':
		$media_ids = get_popular_songs($_REQUEST['limit'], 'your', $GLOBALS['user']->id);
	break;
	case 'popular_songs':
		$media_ids = get_popular_songs($_REQUEST['limit'], 'global');
	break;
	case 'artist':
		$artist = new Artist($_REQUEST['artist_id']);
		$media_ids = $artist->get_songs();
	break;
	case 'artist_random':
		$artist = new Artist($_REQUEST['artist_id']);
		$artist->get_count();
		$media_ids = $artist->get_random_songs();
	break;
	case 'album_random':
		$album = new Album($_REQUEST['album_id']);
		$media_ids = $album->get_random_songs();
	break;
	case 'album':
		$album = new Album($_REQUEST['album_id']);
		$media_ids = $album->get_songs();
	break;
	case 'playlist':
		$playlist	= new Playlist($_REQUEST['playlist_id']);
		$media_ids	= $playlist->get_songs($_REQUEST['song']);
	break;
	case 'playlist_random':
		$playlist	= new Playlist($_REQUEST['playlist_id']);
		$media_ids	= $playlist->get_random_songs();
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
		$media_ids = get_random_songs($options, $matchlist);
	break;
	case 'democratic': 
		$democratic = new Democratic($_REQUEST['democratic_id']); 
		$urls = array($democratic->play_url()); 
	break;
	case 'download': 
		$media_ids[] = scrub_in($_REQUEST['song_id']); 
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
		$song_files = get_song_files($media_ids);
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
		$stream = new Stream($stream_type,$media_ids);
		$stream->add_urls($urls); 
		$stream->start(); 

} // end method switch 
?>
