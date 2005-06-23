<?php
/*

 Copyright (c) 2001 - 2005 ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*!
	@header Song Document
	@discussion Actually play files from albums, artists or just given
	a bunch of id's.
	Special thanx goes to Mike Payson and Jon Disnard for the means
	to do this.
*/

require('modules/init.php');

/* If we are running a demo, quick while you still can! */
if (conf('demo_mode') || !$user->has_access('25')) {
	access_denied();
}


$web_path = conf('web_path');

$song_ids = array();
$web_path = conf('web_path');

$action = scrub_in($_REQUEST['action']);

switch ($action) { 
	case 'play_selected':
		$type = scrub_in($_REQUEST['type']);
		if ($type == 'album') { 
			$song_ids = get_songs_from_type($type, $_POST['song'], $_REQUEST['artist_id']);
		} 
		else { 
			$song_ids = $_POST['song'];
		}
		$_REQUEST['action'] = "m3u";
	break;
	default:
	break;

} // end action switch

if ($_REQUEST['album']) {
	$song_ids = get_song_ids_from_album( $_REQUEST['album'] );
}
elseif ( $_REQUEST['playlist_id'] AND $action != 'play_selected') {
	$playlist = new Playlist($_REQUEST['playlist_id']);
	if ($_REQUEST['action'] == "random") { 
		$song_ids = $playlist->get_random_songs();
		$_REQUEST['action'] = "m3u";
	}
	else {
		$song_ids = $playlist->get_songs();
	}
}
elseif ( $_REQUEST['artist'] ) {
	$song_ids = get_song_ids_from_artist( $_REQUEST['artist'] );
}
/*! 
	@action Random Song
	@discussion takes a genre and catalog and
		returns random songs based upong that
*/
elseif ( $_REQUEST['random'] ) {
	
	if($_REQUEST['genre'][0] != '-1') {
		$matchlist['genre'] = $_REQUEST['genre'];
	}
	if($_REQUEST['catalog'] != '-1') {
		$matchlist['catalog'] = $_REQUEST['catalog'];
	}

	/* Setup the options array */
	$options = array('limit' => $_REQUEST['random'], 'unplayed' => $_REQUEST['unplayed'], 'full_album' => $_REQUEST['full_album'], 'full_artist' => $_REQUEST['full_artist']);
	
	
	$song_ids = get_random_songs($options, $matchlist);
}
elseif ( $_REQUEST['artist_random'] ) {
	$artist = new Artist($_REQUEST['artist_random']);
	$artist->get_count();
	$song_ids = $artist->get_random_songs();
}
elseif ( $_REQUEST['album_random'] ) {
	$album = new Album($_REQUEST['album_random']);
	$song_ids = $album->get_random_songs();
}
elseif ( $_REQUEST['song'] AND !is_array($_REQUEST['song'])) {
	$song_ids = array();
	$song_ids[0] = $_REQUEST['song'];
}
elseif ( $_REQUEST['popular_songs'] ) {
	$song_ids = get_popular_songs($_REQUEST['popular_songs'], 'global');
}
elseif ( $_REQUEST['your_popular_songs'] ) {
	$song_ids = get_popular_songs($_REQUEST['your_popular_songs'], 'your', $user->id);
}


if ( !$_REQUEST['action'] or $_REQUEST['action'] == 'm3u' ) {

	$stream_type = conf('playlist_type');

	if ($user->prefs['play_type'] != "stream" AND $user->prefs['play_type'] != "downsample") { 
		$stream_type = $user->prefs['play_type'];
	}
	$stream = new Stream($stream_type,$song_ids);
	$stream->start();
} // if streaming

elseif ( $_REQUEST['action'] == 'show' ) {
    // Show the song details, or the list
    // of songs.
    // TODO
}


