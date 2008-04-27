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

require_once 'lib/init.php';
ob_end_clean(); 

//test that batch download is permitted
if (!Access::check_function('batch_download')) { 
	access_denied(); 
	exit; 
}

/* Drop the normal Time limit constraints, this can take a while */
set_time_limit(0);

switch ($_REQUEST['action']) {
	case 'tmp_playlist': 
		$tmpPlaylist = new tmpPlaylist($_REQUEST['id']); 
		$data = $tmpPlaylist->get_items(); 
	
		// We have to translate these :(
		foreach ($data as $row) { 
			$song_ids[] = $row['0'];
		} 

		$name = $GLOBALS['user']->username . ' - Playlist';
	break;
	case 'playlist':
		$playlist = new Playlist($_REQUEST['id']);
		$song_ids = $playlist->get_songs();
		$name = $playlist->name;
	break;
	case 'album':
		$album = new Album($_REQUEST['id']);
		$song_ids = $album->get_songs();
		$name = $album->name;
	break;
	case 'artist': 
		$artist = new Artist($_REQUEST['id']); 
		$song_ids = $artist->get_songs(); 
		$name = $artist->name; 
	break;
	case 'genre':
		$id = scrub_in($_REQUEST['id']); 
		$genre = new Genre($id); 
		$song_ids = $genre->get_songs();
		$name = $genre->name; 
	break;
	case 'browse': 
		$song_ids = Browse::get_saved(); 
		$name = 'Batch-' . date("dmY",time()); 
	default:
		// Rien a faire
	break;
} // action switch		

// Take whatever we've got and send the zip
$song_files = get_song_files($song_ids); 
set_memory_limit($song_files['1']+32); 
send_zip($name,$song_files['0']); 

?>
