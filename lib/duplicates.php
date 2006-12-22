<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
 * get_duplicate_songs
 * This function takes a search type and returns a list of all songs that
 * are likely to be duplicates based on the search type selected
 */
function get_duplicate_songs($search_type) {

	// Setup the base SQL
	$sql = "SELECT song.id as song,artist.name,album.name,title,count(title) as ctitle".
	  	" FROM song,artist,album ".
		" WHERE song.artist=artist.id AND song.album=album.id AND song.title<>'' ".
		" GROUP BY title";

	// Additional constraints
	if ($search_type=="artist_title"||$search_type=="artist_album_title") { 
		$sql = $sql.",artist";
	}

	if ($search_type=="artist_album_title") { 
		$sql = $sql.",album";
	}

	// Final componets
	$sql = $sql." HAVING count(title) > 1";
	$sql = $sql." ORDER BY ctitle";

	$db_results = mysql_query($sql, dbh());

	$arr = array();

	while ($flag = mysql_fetch_assoc($db_results)) {
        	$arr[] = $flag;
	} // end while
	
	return $arr;

} // get_duplicate_songs

/**
 * get_duplicate_info
 * This takes a song, search type and auto flag and returns the duplicate songs in the correct
 * order, if AUTO is selected it sorts them by longest, higest bitrate, largest filesize, checking
 * the last one as most likely bad
 */
function get_duplicate_info($song,$search_type,$auto='') {
	// Get the artist name
	$artist = $song->get_artist_name();

	// Build the SQL 
 	$sql = "SELECT song.id as songid,song.title as song,file,bitrate,size,time," . 
		"album.name AS album,album.id as albumid, artist.name AS artist,artist.id as artistid".
		" FROM song,artist,album ".
		" WHERE song.artist=artist.id AND song.album=album.id ".
		"  AND song.title= '".sql_escape($song->title)."'";

	if ($search_type == "artist_title" || $search_type == "artist_album_title") { 
		$sql .="  AND artist.id = '".$song->artist."'";
	}
	if ($search_type == "artist_album_title" ) { 
		$sql .="  AND album.id = '".$song->album."'";
	}

	if ($auto) { 
		$sql .= " ORDER BY time,bitrate,size"; 
	} 

	$db_results = mysql_query($sql, dbh());

	$arr = array();

	while ($flag = mysql_fetch_assoc($db_results)) {
        	$arr[] = $flag;
	} // end while

	return $arr;

} // get_duplicate_info

/*!
	@function show_duplicate_songs
	@discussion
*/
function show_duplicate_songs($flags,$search_type) {

	require_once(conf('prefix').'/templates/show_list_duplicates.inc.php');

} // show_duplicate_songs

/*!
	@function show_duplicate_searchbox
	@discussion
*/
function show_duplicate_searchbox($search_type) {

	require_once(conf('prefix') . '/templates/show_duplicates.inc.php');

} // show_duplicate_searchbox

?>
