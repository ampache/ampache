<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 

*/

/**
 * Radio Class
 * This handles the internet radio stuff, that is inserted into live_stream
 * this can include podcasts or what-have-you
 */
class Radio {

	/* DB based variables */
	public $id; 
	public $name; 
	public $site_url; 
	public $url; 
	public $frequency;
	public $call_sign;
	public $genre; 
	public $catalog; 

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	public function __construct($id) { 

		$this->id = intval($id);

		if (!$this->id) { return false; }

		$info = $this->_get_info();

		// Set the vars
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

	} // constructor

	/**
	 * _get_info
	 * Private function for getting the information for this object from the database 
	 */
	private function _get_info() { 

		$id = Dba::escape($this->id);

		$sql = "SELECT * FROM `live_stream` WHERE `id`='$id'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);
		
		return $results;

	} // _get_info

	/**
	 * format
	 * This takes the normal data from the database and makes it pretty
	 * for the users, the new variables are put in f_??? and f_???_link
	 */
	public function format() { 

		// Default link used on the rightbar
		$this->f_link		= "<a href=\"$this->url\">$this->name</a>"; 

		$this->f_name_link	= "<a target=\"_blank\" href=\"$this->site_url\">$this->name</a>"; 
		$this->f_callsign	= scrub_out($this->call_sign); 
		$this->f_frequency	= scrub_out($this->frequency); 

		$genre = new Genre($this->genre); 
		$genre->format(); 
		$this->f_genre		= $genre->f_link; 

		return true; 

	} // format

	/**
	 * get_url	
	 * This returns the URL for this live stream
	 */
	public function get_url() { 

		

	} // get_url 

	/**
	 * update
	 * This is a static function that takes a key'd array for input
	 * it depends on a ID element to determine which radio element it 
	 * should be updating
	 */
	public static function update($data) { 

		// Verify the incoming data
		if (!$data['id']) { 
			Error::add('general','Missing ID'); 
		} 

		if (!$data['name']) { 
			Error::add('general','Name Required'); 
		} 

		if (!preg_match("/^https?:\/\/.+/",$data['url'])) { 
			Error::add('general','Invalid URL must be https:// or http://'); 
		} 

		$genre = new Genre($data['genre']); 
		if (!$genre->name) { 
			Error::add('general','Invalid Genre Selected'); 
		} 

		if (Error::$state) { 
			return false; 
		} 

		// Setup the data
		$name 		= Dba::escape($data['name']); 
		$site_url	= Dba::escape($data['site_url']); 
		$url		= Dba::escape($data['url']); 
		$frequency	= Dba::escape($data['frequency']); 
		$call_sign	= Dba::escape($data['call_sign']); 
		$genre		= Dba::escape($data['genre']); 
		$id		= Dba::escape($data['id']); 

		$sql = "UPDATE `live_stream` SET `name`='$name',`site_url`='$site_url',`url`='$url',`genre`='$genre'" . 
			",`frequency`='$frequency',`call_sign`='$call_sign' WHERE `id`='$id'"; 
		$db_results = Dba::query($sql); 

		return $db_results; 

	} // update

	/**
	 * create
	 * This is a static function that takes a key'd array for input
	 * and if everything is good creates the object. 
	 */
	public static function create($data) { 

		// Make sure we've got a name
		if (!strlen($data['name'])) { 
			Error::add('name','Name Required'); 
		} 

		if (!preg_match("/^https?:\/\/.+/",$data['url'])) { 
			Error::add('url','Invalid URL must be http:// or https://'); 
		} 

		// Make sure it's a real genre
		$genre = new Genre($data['genre']); 
		if (!$genre->name) { 
			Error::add('genre','Invalid Genre'); 
		} 

		// Make sure it's a real catalog
		$catalog = new Catalog($data['catalog']); 
		if (!$catalog->name) { 
			Error::add('catalog','Invalid Catalog'); 
		} 

		if (Error::$state) { return false; } 

		// Clean up the input
		$name		= Dba::escape($data['name']); 
		$site_url	= Dba::escape($data['site_url']); 
		$url		= Dba::escape($data['url']); 
		$genre		= $genre->id; 
		$catalog	= $catalog->id; 
		$frequency	= Dba::escape($data['frequency']); 
		$call_sign	= Dba::escape($data['call_sign']); 

		// If we've made it this far everything must be ok... I hope
		$sql = "INSERT INTO `live_stream` (`name`,`site_url`,`url`,`genre`,`catalog`,`frequency`,`call_sign`) " . 
			"VALUES ('$name','$site_url','$url','$genre','$catalog','$frequency','$call_sign')"; 
		$db_results = Dba::query($sql); 

		return $db_results;  

	} // create

} //end of radio class

?>
