<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/**
 * This is accessed remotly to allow outside scripts access to ampache information 
 * as such it needs to verify the session id that is passed 
 */

$no_session = true;
require_once('../lib/init.php');

/* Verify the existance of the Session they passed in */
if (!session_exists($_REQUEST['sessid'])) { exit(); }

$GLOBALS['user'] = new User($_REQUEST['user_id']);
$action = scrub_in($_REQUEST['action']);

/* Set the correct headers */
header("Content-type: application/xhtml+xml");

switch ($action) { 
	/* Returns an array of artist information */
	case 'get_artists': 
		$sql = "SELECT id FROM artist ORDER BY name";
		$db_results = mysql_query($sql,dbh());
		
		while ($r = mysql_fetch_assoc($db_results)) { 
			$artist = new Artist($r['id']);
			$artist->format_artist();
			$artist_id = "id_" . $artist->id;
			$results[$artist_id] = $artist->full_name;
		} // end while results

		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	case 'get_albums':
		$sql = "SELECT id FROM album ORDER BY name";
		$db_results = mysql_query($sql,dbh()); 

		while ($r = mysql_fetch_assoc($db_results)) { 
			$album = new Album($r['id']);
			$album_id = "id_" . $album->id;
			$results[$album_id] = array('year'=>$album->year,'name'=>$album->name);
		} // end while results

		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	case 'get_genres':
		$sql = "SELECT id FROM genre ORDER BY name";
		$db_results = mysql_query($sql,dbh());
	
		while ($r = mysql_fetch_assoc($db_results)) { 
			$genre = new Genre($r['id']); 
			$genre_id = "id_" . $genre->id;
			$results[$genre_id] = $genre->name;
		}
		
		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	/* Return results of a quick search */
	case 'search': 
		/* We need search string */
		$_REQUEST['s_all'] = $_REQUEST['search_string'];	
		if (strlen($_REQUEST['s_all']) < 1) { break; } 
		$data = run_search($_REQUEST);

		/* Unfortuantly these are song objects, which are not good for
		 * xml.. turn it into an array 
		 */
		foreach ($data as $song) { 
			$song_id 	= 'id_' . $song->id; 
			$genre 		= $song->get_genre_name();
			$artist 	= $song->get_artist_name();
			$album		= $song->get_album_name();
			$results[$song_id] = array('title'=>$song->title,
						'genre'=>$genre,
						'artist'=>$artist,
						'album'=>$album);	
		} // end foreach song	

		$xml_doc = xml_from_array($results);
		echo $xml_doc;

	break;	
	default:
		// Rien a faire
	break;
} // end switch action
?>
