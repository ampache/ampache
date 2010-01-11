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

class shoutBox {

	public $id; 

	/**
	 * Constructor
	 * This pulls the shoutbox information from the database and returns
	 * a constructed object, uses user_shout table
	 */
	public function __construct($shout_id) { 

		// Load the data from the database
		$this->_get_info($shout_id);

		return true; 

	} // Constructor

	/**
	 * _get_info
	 * does the db call, reads from the user_shout table
	 */
	private function _get_info($shout_id) { 

		$sticky_id = Dba::escape($shout_id); 

		$sql = "SELECT * FROM `user_shout` WHERE `id`='$shout_id'"; 
		$db_results = Dba::query($sql); 

		$data = Dba::fetch_assoc($db_results); 

		foreach ($data as $key=>$value) { 
			$this->$key = $value; 
		} 

		return true; 

	} // _get_info

	/**
	 * get_top	
	 * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
	 * number of objects shown
	 */
	public static function get_top($limit) { 

		$shouts = self::get_sticky(); 

		// If we've already got too many stop here
		if (count($shouts) > $limit) { 
			$shouts = array_slice($shouts,0,$limit);
			return $shouts; 
		} 

		// Only get as many as we need
		$limit = intval($limit) - count($shouts); 
		$sql = "SELECT * FROM `user_shout` WHERE `sticky`='0' ORDER BY `date` DESC LIMIT $limit";  
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$shouts[] = $row['id']; 
		} 

		return $shouts; 

	} // get_top

	/**
	 * get_sticky
	 * This returns all current sticky shoutbox items
	 */
	public static function get_sticky() { 

		$sql = "SELECT * FROM `user_shout` WHERE `sticky`='1' ORDER BY `date` DESC"; 
		$db_results = Dba::query($sql);

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 

	} // get_sticky

	/**
	 * get_object
	 * This takes a type and an ID and returns a created object
	 */
	public static function get_object($type,$object_id) { 

		$allowed_objects = array('song','genre','album','artist','radio'); 

		if (!in_array($type,$allowed_objects)) { 
			return false; 
		} 

		$object = new $type($object_id); 

		return $object; 	

	} // get_object

	/**
	 * get_image
	 * This returns an image tag if the type of object we're currently rolling with 
	 * has an image assoicated with it
	 */
	public function get_image() { 

		switch ($this->object_type) { 
			case 'album': 
				$image_string = "<img class=\"shoutboximage\" height=\"75\" width=\"75\" src=\"" . Config::get('web_path') . "/image.php?id=" . $this->object_id . "&amp;thumb=1\" />"; 
			break; 
			case 'artist': 

			break;
			case 'song': 
				$song = new Song($this->object_id); 
				$image_string = "<img class=\"shoutboximage\" height=\"75\" width=\"75\" src=\"" . Config::get('web_path') . "/image.php?id=" . $song->album . "&amp;thumb=1\" />"; 
			break;
			default: 
				// Rien a faire
			break; 
		} // end switch

		return $image_string; 

	} // get_image

	/**
	 * create
	 * This takes a key'd array of data as input and inserts a new shoutbox entry, it returns the auto_inc id 
	 */
	public static function create($data) { 

		$user 		= Dba::escape($GLOBALS['user']->id); 
		$text 		= Dba::escape(strip_tags($data['comment'])); 
		$date 		= time(); 
		$sticky 	= make_bool($data['sticky']); 
		$object_id 	= Dba::escape($data['object_id']); 
		$object_type	= Dba::escape($data['object_type']); 

		$sql = "INSERT INTO `user_shout` (`user`,`date`,`text`,`sticky`,`object_id`,`object_type`) " . 
			"VALUES ('$user','$date','$text','$sticky','$object_id','$object_type')"; 
		$db_results = Dba::query($sql); 

		$insert_id = Dba::insert_id(); 

		return $insert_id; 

	} // create

	/**
	 * update
	 * This takes a key'd array of data as input and updates a shoutbox entry
	 */
	public static function update($data) { 

		$id		= Dba::escape($data['shout_id']); 
		$text 		= Dba::escape(strip_tags($data['comment'])); 
		$sticky 	= make_bool($data['sticky']); 

		$sql = "UPDATE `user_shout` SET `text`='$text', `sticky`='$sticky' WHERE `id`='$id'";
		$db_results = Dba::query($sql); 

		return true; 

	} // create

        /**
         * format
         * this function takes the object and reformats some values
         */
	
        public function format() {
	    
	    if ( $this->sticky == "0" ) { $this->sticky = "No"; } else { $this->sticky = "Yes"; }
	    
	    $this->date = date("m\/d\/Y - H:i",$this->date);
	    	
	    return true;
	
	} //format

	/**
	 * delete
	 * this function deletes a specific shoutbox entry
	 */

        public function delete($shout_id) {

                // Delete the shoutbox post
		$shout_id = Dba::escape($shout_id); 
                $sql = "DELETE FROM `user_shout` WHERE `id`='$shout_id'";
                $db_results = Dba::query($sql);
		
	} // delete

} // shoutBox class
?>
