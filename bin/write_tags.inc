<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

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

/*
 * Use with caution, this hasn't been heavily tested!!!
 * write_tags.php.inc - This file was written in order to give the ability
 * to write tags changed through the interface back out to the file. This can
 * be especially important when trying to keep a clean file structure.
 */

define('NO_SESSION','1');
$path = dirname(__FILE__); 
$prefix = realpath($path . '/../'); 
require_once $prefix . '/lib/init.php';

// Include getID3 libs, including the ability to write tags
$getID3 = new getID3();
getid3_lib::IncludeDependency(GETID3_INCLUDEPATH . "write.php", __FILE__);

$tagWriter = new getid3_writetags();

$flag = new Flag();
$flaggedIds = $flag->get_approved();

// Loop through every song that has an approved flag
foreach($flaggedIds as $flagged){

	$info = new Flag($flagged);
	$song = new Song($info->object_id);
	$tagWriter->filename = $song->file;

	// Decide on what type of tag format to use, base on song type(a better way maybe?)
	switch($song->type){ 
		case 'mp3':
		case 'mp2':
		case 'mp1':
			$tagFormats = array('id3v1','id3v2.3','ape');
			break;
		case 'ogg':
			$tagFormats = array('vorbiscomment');
			break;
		case 'flac':
			$tagFormats = array('metaflac');
			break;
		default:
			$tagFormats = array();
	} // end switch 

	// Set all of the attributes for the tag to be written(All pulled from the song object)
	$tagWriter->tagformats = $tagFormats;
	$tagData['title'][] = $song->title;
	$tagData['date'][] = $song->year;
	$tagData['year'][] = $song->year;
	$tagData['comment'][] = $song->comment;
	$tagData['size'][] = $song->size;
	$tagData['time'][] = $song->time;
	$tagData['album'][] = $song->get_album_name();
	$tagData['artist'][] = $song->get_artist_name();
	$tagData['genre'][] = $song->get_genre_name();
	$tagData['track'][] = $song->track;
	$tagWriter->tag_data = $tagData;
	
	// Write out the tag
	if(!$tagWriter->WriteTags()){ 
		//Pump out an error if it dies
		var_dump($tagWriter->errors); 
	}

	//  Either way remove the flag cause we have no idea what's happened
	$info->delete_flag();
}
?>

