<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

/**
 * Art
 * This class handles the images / artwork in ampache
 * This was initially in the album class, but was pulled out
 * to be more general, and apply to albums, artists, movies etc
 */
class Art extends database_object {

	public $type; 
	public $uid; // UID of the object not ID because it's not the ART.ID
	public $raw; // Raw art data
	public $raw_mime; 
	
	public $thumb; 
	public $thumb_mime; 
	

	/**
	 * Constructor
	 * Art constructor, takes the UID of the object and the
	 * object type. 
	 */
	public function __construct($uid,$type) {

		$this->type = Art::validate_type($type); 
		$this->uid = $uid; 		

	} // constructor

	/** 
	 * validate_type
	 * This validates the type
	 */ 
	public static function validate_type($type) { 

		switch ($type) { 
			case 'album': 
			case 'artist': 
			case 'video': 
				return $type; 
			break; 
			default: 
				return 'album'; 
			break; 
		} 

	} // validate_type

	/**
	 * extension
	 * This returns the file extension for the currently loaded art
	 */
	public function extension($raw=false) { 

		// No mime no extension!
		if (!$this->raw_mime) { return false; } 

		$mime = $raw ? $this->raw_mime : $this->thumb_mime; 
		$data = explode("/",$mime); 
		$extension = $data['1']; 

		if ($extension == 'jpeg') { $extension = 'jpg'; } 

		return $extension; 

	} // extension 

	/** 
	 * get
	 * This returns the art for our current object, this can
	 * look in the database and will return the thumb if it 
	 * exists, if it doesn't depending on settings it will try
	 * to create it. 
	 */
	public function get($raw=false) { 

		// Get the data either way
		if (!$this->get_db()) { 
			return false; 
		} 

		if ($raw) { 
			return $this->raw; 
		} 
		else { 
			return $this->thumb; 
		} 		
			
	} // get


	/**
	 * get_db
	 * This pulls the information out from the database, depending
	 * on if we want to resize and if there is not a thumbnail go
	 * ahead and try to resize
	 */
	public function get_db() { 

		$type = Dba::escape($this->type); 
		$id = Dba::escape($this->uid); 

		$sql = "SELECT `thumb`,`thumb_mime`,`art`,`art_mime` FROM `" . $type . "_data` WHERE `" . $type . "_id`='$id'"; 
		$db_results = Dba::read($sql); 
		
		$results = Dba::fetch_assoc($db_results); 

		// If we get nothing or there is non mime type return false
		if (!count($results) OR !strlen($results['art_mime'])) { return false; } 

		// If there is no thumb, and we want thumbs
		if (!strlen($results['thumb_mime']) AND Config::get('resize_images')) { 
			$data = $this->generate_thumb($results['art'],array('width'=>275,'height'=>275),$results['art_mime']); 
			// If it works save it!
			if ($data) { 
				$this->save_thumb($data['thumb'],$data['thumb_mime']); 
				$results['thumb'] = $data['thumb']; 
				$results['thumb_mime'] = $data['thumb_mime']; 
			} 
			else { 
				debug_event('Art','Unable to retrive/generate thumbnail for ' . $type . '::' . $id,1); 
			} 
		} // if no thumb, but art and we want to resize

		$this->raw = $results['art']; 
		$this->raw_mime = $results['art_mime']; 
		$this->thumb = $results['thumb']; 
		$this->thumb_mime = $results['thumb_mime']; 

		return true; 

	} // get_db

	/**
	 * save_thumb
	 * This saves the thumbnail that we're passing
	 */
	public function save_thumb($source,$mime) { 

		// Quick sanity check
		if (strlen($source) < 5 OR !strlen($mime)) { 
			debug_event('Art','Unable to save thumbnail, invalid data passed',1); 
			return false; 
		} 
		
		$source = Dba::escape($source); 
		$mime = Dba::escape($mime); 
		$uid = Dba::escape($this->uid); 
		$type = Dba::escape($this->type); 
		
		$sql = "UPDATE `" . $type . "_data` SET `thumb`='$source', `thumb_mime`='$mime' " . 
			"WHERE `" . $type . "_id`='$uid'"; 
		$db_results = Dba::write($sql); 

	} // save_thumb

	/**
	 * generate_thumb
	 * this automaticly resizes the image for thumbnail viewing
	 * only works on gif/jpg/png this function also checks to make
	 * sure php-gd is enabled
	 */
	public function generate_thumb($image,$size,$mime) { 

		$data = explode("/",$mime); 
		$type = strtolower($data['1']); 

		if (!function_exists('gd_info')) { 
			debug_event('Art','PHP-GD Not found - unable to resize art',1); 
			return false; 
		} 

		// Check and make sure we can resize what you've asked us to	
		if (($type == 'jpg' OR $type == 'jpeg') AND !(imagetypes() & IMG_JPG)) { 
			debug_event('Art','PHP-GD Does not support JPGs - unable to resize',1); 
			return false; 
		} 
		if ($type == 'png' AND !imagetypes() & IMG_PNG) { 
			debug_event('Art','PHP-GD Does not support PNGs - unable to resize',1); 
			return false; 
		} 
		if ($type == 'gif' AND !imagetypes() & IMG_GIF) { 
			debug_event('Art','PHP-GD Does not support GIFs - unable to resize',1); 
			return false; 
		} 
		if ($type == 'bmp' AND !imagetypes() & IMG_WBMP) { 
			debug_event('Art','PHP-GD Does not support BMPs - unable to resize',1); 
			return false; 
		} 
	
		$source = imagecreatefromstring($image); 	

		if (!$source) { 
			debug_event('Art','Failed to create Image from string - Source Image is damaged / malformed',1); 
			return false; 
		} 

		$source_size = array('height'=>imagesy($source),'width'=>imagesx($source)); 

		// Create a new blank image of the correct size
		$thumbnail = imagecreatetruecolor($size['width'],$size['height']); 

		if (!imagecopyresampled($thumbnail,$source,0,0,0,0,$size['width'],$size['height'],$source_size['width'],$source_size['height'])) { 
			debug_event('Art','Unable to create resized image',1); 
			return false; 
		} 

		// Start output buffer
		ob_start(); 
		
		// Generate the image to our OB
		switch ($type) { 
			case 'jpg': 
			case 'jpeg': 
				imagejpeg($thumbnail,null,75); 
				$mime_type = image_type_to_mime_type(IMAGETYPE_JPEG); 
			break; 
			case 'gif': 
				imagegif($thumbnail); 
				$mime_type = image_type_to_mime_type(IMAGETYPE_GIF); 
			break; 
			// Turn bmps into pngs
			case 'bmp': 
				$type = 'png'; 
			case 'png': 
				imagepng($thumbnail); 
				$mime_type = image_type_to_mime_type(IMAGETYPE_PNG); 
			break; 
		} // resized
		
		$data = ob_get_contents(); 
		ob_end_clean(); 
	
		if (!strlen($data)) { 
			debug_event('Art','Unknown Error resizing art',1); 
			return false; 
		} 

		return array('thumb'=>$data,'thumb_mime'=>$mime_type); 
			
	} // generate_thumb

} // Art
