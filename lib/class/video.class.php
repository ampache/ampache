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

class Video extends database_object implements media {

	public $id; 
	public $title; 
	public $enabled; 
	public $file; 	
	public $size; 

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
	 * build_cache
	 * Build a cache based on the array of ids passed, saves lots of little queries
	 */
	public static function build_cache($ids=array()) { 

		if (!is_array($ids) OR !count($ids)) { return false; } 

		$idlist = '(' . implode(',',$ids) . ')'; 

		$sql = "SELECT * FROM `video` WHERE `video`.`id` IN $idlist"; 
		$db_results = Dba::read($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			parent::add_to_cache('video',$row['id'],$row); 
		} 

	} // build_cache

	/**
	 * format
	 * This formats a video object so that it is human readable
	 */
	public function format() { 

		$this->f_title = scrub_out($this->title); 
		$this->f_link = scrub_out($this->title);  
		$this->f_codec = $this->video_codec . ' / ' . $this->audio_codec; 
		$this->f_resolution = $this->resolution_x . 'x' . $this->resolution_y; 
		$this->f_tags = ''; 
		$this->f_length = floor($this->time/60) . ' ' .  _('minutes');

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
	public static function play_url($oid,$sid='',$force_http='') { 

		$video = new Video($oid); 

		if (!$video->id) { return false; } 

		$uid = intval($GLOBALS['user']->id); 
		$oid = intval($video->id); 

		$url = Stream::get_base_url() . "video=true&uid=$uid&oid=$oid"; 

		return $url; 

	} // play_url

	/**
	 * stream_cmd
	 * test and see if the video needs to be natively streamed
	 * if not it returns the transocding command from the config file
	 * we can't use this->type because its been formated for the downsampling
	 */
	public function stream_cmd() { 



	} // stream_cmd

	/**
	 * has_flag
	 * returns true if the video has been flagged and we shouldn't try to re-read
	 * the meta data
	 */
	public function has_flag() { 



	} // has_flag

} // end Video class
