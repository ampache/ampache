<?php
/*

 Copyright (c) Ampache.org
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


/**
 * get_song_files
 * tmakes array of song ids and returns
 *	array of path to actual files
 */
function get_song_files($media_ids) {

	$media_files = array(); 
	
	foreach ($media_ids as $element) {
		if (is_array($element)) { 
			$type = array_shift($element); 
			$media = new $type(array_shift($element)); 
		} 
		else { 
			$media = new Song($element); 
		} 
		if ($media->enabled) { 
	                $total_size += sprintf("%.2f",($media->size/1048576));
	                array_push($media_files, $media->file);
		} 
        }

        return array($media_files,$total_size);
} //get_song_files


/**
 * send_zip
 * takes array of full paths to songs
 * zips them and sends them
 * @param $name	name of the zip file to be created
 * @param $song_files      array of full paths to songs to zip create w/ call to get_song_files
 */
function send_zip( $name, $song_files ) {

	// Check if they want to save it to a file, if so then make sure they've got
	// a defined path as well and that it's writeable
	if (Config::get('file_zip_download') && Config::get('file_zip_path')) { 
		// Check writeable
		if (!is_writable(Config::get('file_zip_path'))) { 
			$in_memory = '1'; 
			debug_event('Error','File Zip Path:' . Config::get('file_zip_path') . ' is not writeable','1'); 
		} 
		else { 
			$in_memory = '0'; 
			$basedir = Config::get('file_zip_path'); 
		} 

	} else {
		$in_memory = '1'; 
	} // if file downloads

	/* Require needed library */
        require_once Config::get('prefix') . '/modules/archive/archive.lib.php';
        $arc = new zip_file( $name . ".zip" );
        $options = array(
                'inmemory'      => $in_memory,   // create archive in memory
		'basedir'	=> $basedir,
                'storepaths'    => 0,   // only store file name, not full path
                'level'         => 0,    // no compression
		'comment'	=> Config::get('file_zip_comment')
        );
	
        $arc->set_options( $options );
        $arc->add_files( $song_files );

	if (count($arc->error)) { 
		debug_event('archive',"Error: unable to add songs",'3');
		return false; 
	} // if failed to add songs
	
        if (!$arc->create_archive()) { 
		debug_event('archive',"Error: unable to create archive",'3');
		return false; 
	} // if failed to create archive
	
        $arc->download_file();

} // send_zip
?>
