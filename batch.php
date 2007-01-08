<?php
/*

 Copyright (c) 2004 batch.php by RosenSama
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
 *	
 *	creates and sends a zip of an album or playlist
 *	zip is just a container w/ no compression
 *
 *	uses  archive.php from
 *	http://phpclasses.mirrors.nyphp.org/browse/file/3191.html
 *	can modify to allow user to select tar, gzip, or bzip2
 *
 *	I believe archive.php requires zlib support to be eanbled
 *	in your PHP build.
 */

require_once('lib/init.php');

//test that batch download is permitted
if (!batch_ok()) { 
	access_denied(); 
	exit; 
}

/* Drop the normal Time limit constraints, this can take a while */
set_time_limit(0);

switch( scrub_in( $_REQUEST['action'] ) ) {
	case 'download_selected':
		$type = scrub_in($_REQUEST['type']);
		if ($type == 'album') { 
			$song_ids = get_songs_from_type($type,$_POST['song'],$_REQUEST['artist_id']);
		}
                elseif ($_REQUEST['playlist_id']) { 
                        $playlist = new Playlist($_REQUEST['playlist_id']);
                        $song_ids = $playlist->get_songs($_REQUEST['song']);
                }
		else { 
			$song_ids = $_POST['song'];
		}
		$name = "selected-" . date("m-d-Y",time());
		$song_files = get_song_files($song_ids);
		set_memory_limit($song_files[1]+32);
		send_zip($name,$song_files[0]);
	break;
	case 'pl':
		$id = scrub_in($_REQUEST['id']);
		$pl = new Playlist($id);
		$song_ids = $pl->get_songs();
		$song_files = get_song_files( $song_ids );
		set_memory_limit($song_files[1]+32);
		send_zip($pl->name, $song_files[0]);
	break;
	case 'alb':
		$id = scrub_in($_REQUEST['id']);
		$alb = new Album($id);
		$song_ids = $alb->get_song_ids();
		$song_files = get_song_files($song_ids);
		set_memory_limit($song_files[1]+32);
		send_zip($alb->name, $song_files[0]);
	break;
	case 'genre':
		$id = scrub_in($_REQUEST['id']); 
		$genre = new Genre($id); 
		$song_ids = $genre->get_songs();
		$song_files = get_song_files($song_ids); 
		set_memory_limit($song_files[1]+32); 
		send_zip($genre->name,$song_files[0]); 
	break;
	default:
		// Rien a faire
	break;
} // action switch		

?>
