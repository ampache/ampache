<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved

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
 * sort_files
 * This script has a lot of stuff to worry about. It's primary duty is to re-organize
 * your files based on some sane, and predefined (in the interface) order using the
 * tag information gathered and updated in ampache. Sort_Pattern defines the directory
 * structure and rename_pattern defines the file pattern. This script should allow you
 * to do both or neither. Oooh and allow you to sort with A,B,C,D prefix
 * 
 * Attempt 1 - Do each file one by one and satisfy the needs of each file by its self (this is going to be slow)
 */

/* Don't do anything just tell me what you would do */
$test_mode = true;

/* m(__)m */
$alphabet_prefix = true;
 
$no_session = '1';
require ("../modules/init.php");

/* First Clean the catalog to we don't try to write anything we shouldn't */

$sql = "SELECT id FROM catalog WHERE catalog_type='local'";
$db_results = mysql_query($sql, dbh());

$catalogs = array();

while ($r = mysql_fetch_row($db_results)) { 

	$catalog = new Catalog($r['0']);
	$songs = $catalog->get_catalog_files();

	/* Foreach through each file and find it a home! */
	foreach ($songs as $song) { 
		/* Find this poor song a home */
		$song->format_song();
		$directory 	= sort_find_home($song,$catalog->sort_pattern,$catalog->path);
		$filename 	= sort_find_filename($song,$catalog->rename_pattern);
	
		echo $directory . "/" . $filename . "\n";
		flush();
	} // end foreach song

} // end foreach catalogs


/************** FUNCTIONS *****************/
/**
 * sort_find_filename 
 * This gets the filename that this file should have, it takes the rename pattern of the catalog
 * along with the song object. Nothing Special Here
 */
function sort_find_filename($song,$rename_pattern) { 

	/* Create the filename that this file should have */
	$album 	= $song->f_album_full;
	$artist = $song->f_artist_full;
	$genre 	= $song->f_genre;
	$track	= $song->track;
	$title	= $song->title;
	$year	= $song->year;
        
	/* Start replacing stuff */
	$replace_array = array('%a','%A','%t','%T','%y','%g');
	$content_array = array($artist,$album,$title,$track,$year,$genre);

	$rename_pattern = str_replace($replace_array,$content_array,$rename_pattern);

	$rename_pattern = preg_replace("/[^A-Za-z0-9\-\_\ \'\,]/","_",$rename_pattern);

	return $rename_pattern;

} // sort_find_filename

/**
 * sort_find_home
 * Get the directory for this file from the catalog and the song info using the sort_pattern 
 * takes into account various artists and the alphabet_prefix
 */
function sort_find_home($song,$sort_pattern,$base) { 

	$home = rtrim($base,"\/");
	$home = rtrim($home,"\\");

	/* Create the filename that this file should have */
	$album 	= $song->f_album_full;
	$artist = $song->f_artist_full;
	$genre 	= $song->f_genre;
	$track	= $song->track;
	$title	= $song->title;
	$year	= $song->year;


	/* Do the various check */
	$album_object = new Album($song->album);
	if ($album_object->artist_count != '1') { 
		$artist = "Various";
	}
	
	/* IF we want a,b,c,d we need to know the first element */
	if ($GLOBALS['alphabet_prefix']) { 
		$sort_pattern = preg_replace("/\/?%o\//","",$sort_pattern);
		$first_element = substr($sort_pattern,0,2);
		$element = sort_element_name($first_element);
		$alphabet = strtoupper(substr(${$element},0,1));

		$home .= "/$alphabet";
	}

	/* Replace everything we can find */
	$replace_array = array('%a','%A','%t','%T','%y','%g');
	$content_array = array($artist,$album,$title,$track,$year,$genre);
	$sort_pattern = str_replace($replace_array,$content_array,$sort_pattern);

	/* Remove non A-Z0-9 chars */
	$sort_pattern = preg_replace("/[^A-Za-z0-9\-\_\ \'\,]/","_",$sort_pattern);
	
	$home .= "/$sort_pattern";

	return $home;

} // sort_find_home

/**
 * sort_element_name
 * gets the name of the %? in a yea.. too much beer 
 */
function sort_element_name($key) { 

	switch ($key) { 
		case '%t':
			return 'title';
		break;
		case '%T':
			return 'track';
		break;
		case '%a':
			return 'artist';
		break;
		case '%A':
			return 'album';
		break;
		case '%y':
			return 'year';
		break;
		case '%g':
			return 'genre';
		break;
		default: 

		break;
	} // switch on key

	return false;

} // sort_element_name

?>
