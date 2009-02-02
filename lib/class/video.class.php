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

class Video extends database_object {

	public $id; 
	public $title; 
	public $file; 	

	/**
	 * Constructor
	 * This pulls the shoutbox information from the database and returns
	 * a constructed object, uses user_shout table
	 */
	public function __construct($id) { 

		// Load the data from the database
		$info = $this->get_info($id);
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

		return true; 

	} // Constructor

	/**
	 * format
	 * This formats a video object so that it is human readable
	 */
	public function format() { 

		$this->f_title = scrub_out($this->title); 
		$this->f_link = scrub_out($this->title);  

	} // format

	/**
	 * native_stream
	 * This returns true or false on the downsampling mojo
	 */
	public function native_stream() { 

		return true; 

	} // native_stream

	/**
	 * play_url
	 * This returns a "PLAY" url for the video in question here, this currently feels a little
	 * like a hack, might need to adjust it in the future
	 */
	public static function play_url($id) { 

		$video = new Video($id); 

		if (!$video->id) { return false; } 

		$uid = intval($GLOBALS['user']->id); 
		$oid = intval($video->id); 

		$url = Stream::get_base_url() . "video=true&uid=$uid&oid=$oid"; 

		return $url; 

	} // play_url

} // end Video class
