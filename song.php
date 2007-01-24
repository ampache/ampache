<?php
/*

 Copyright (c) 2001 - 2006 ampache.org
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
//FIXME: This should be renamed to stream.php as it makes a little more sense
//FIXME: considering what this file does
require('lib/init.php');

/* If we are running a demo, quick while you still can! */
if (conf('demo_mode') || !$user->has_access('25')) {
	access_denied();
}

$song_ids = array();
$web_path = conf('web_path');

/* We need an action and a method */
$action = scrub_in($_REQUEST['action']);
$method = scrub_in($_REQUEST['method']);


switch ($action) { 
	case 'play_selected':
		$type = scrub_in($_REQUEST['type']);
		if ($type == 'album') { 
			$song_ids = get_songs_from_type($type, $_POST['song'], $_REQUEST['artist_id']);
		} 
		elseif ($_REQUEST['playlist_id']) { 
			$playlist = new Playlist($_REQUEST['playlist_id']);
			$song_ids = $playlist->get_songs($_REQUEST['song']);
		}
		else { 
			$song_ids = $_POST['song'];
		}
		// Make sure they actually passed soemthing
		if (!count($song_ids)) { header("Location:" . return_referer()); exit; } 
	break;
	/* This is run if we need to gather info based on a tmp playlist */
	case 'tmp_playlist':
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmpplaylist_id']);
		$song_ids = $tmp_playlist->get_items();
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
		$song_ids = $artist->get_song_ids();
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
		$song_ids = $album->get_song_ids();
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
	default:
	break;
} // end action switch


/* Now that we've gathered the song information we decide what 
 * we should do with it, this is a sensitive time for the song id's
 * they don't know where they want to go.. let's help them out
 */
switch ($method) { 
	case 'download':
		/* Make sure they are allowed to download */
		if (!batch_ok()) { break; } 
		$name = "AmpacheZip-" . date("m-d-Y",time());
		$song_files = get_song_files($song_ids);
		set_memory_limit($song_files[1]+32);
		send_zip($name,$song_files[0]);
	break;
	case 'stream':
	default:
		$stream_type = conf('playlist_type');

		/* For non-stream/downsample methos we need to so something else */
		switch ($GLOBALS['user']->prefs['play_type']) { 
			case 'stream':
			case 'downsample':
				// Rien a faire
			break;
			default:
				$stream_type = $GLOBALS['user']->prefs['play_type'];
			break;
		}

		/* Start the Stream */
		$stream = new Stream($stream_type,$song_ids);
		$stream->start();
	break;
} // end method switch 
?>
