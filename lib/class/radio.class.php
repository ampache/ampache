<?php
/*

 Copyright Ampache.org
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
class Radio extends database_object implements media {

	/* DB based variables */
	public $id; 
	public $name; 
	public $site_url; 
	public $url; 
	public $frequency;
	public $call_sign;
	public $catalog; 

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	public function __construct($id) { 

		$info = $this->get_info($id,'live_stream');

		// Set the vars
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

	} // constructor

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

		return true; 

	} // format

	/**
	 * update
	 * This is a static function that takes a key'd array for input
	 * it depends on a ID element to determine which radio element it 
	 * should be updating
	 */
	public static function update($data) { 

		// Verify the incoming data
		if (!$data['id']) { 
			// FIXME: Untranslated
			Error::add('general','Missing ID'); 
		} 

		if (!$data['name']) { 
			// FIXME: Untranslated
			Error::add('general','Name Required'); 
		} 

		$allowed_array = array('https','http','mms','mmsh','mmsu','mmst','rtsp'); 

		$elements = explode(":",$data['url']); 
		
		if (!in_array($elements['0'],$allowed_array)) { 
			// FIXME: Untranslated
			Error::add('general','Invalid URL must be mms:// , https:// or http://'); 
		} 

		if (Error::occurred()) { 
			return false; 
		} 

		// Setup the data
		$name 		= Dba::escape($data['name']); 
		$site_url	= Dba::escape($data['site_url']); 
		$url		= Dba::escape($data['url']); 
		$frequency	= Dba::escape($data['frequency']); 
		$call_sign	= Dba::escape($data['call_sign']); 
		$id		= Dba::escape($data['id']); 

		$sql = "UPDATE `live_stream` SET `name`='$name',`site_url`='$site_url',`url`='$url'" . 
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
			// FIXME: Untranslated
			Error::add('name','Name Required'); 
		} 

		$allowed_array = array('https','http','mms','mmsh','mmsu','mmst','rtsp'); 

		$elements = explode(":",$data['url']); 
		
		if (!in_array($elements['0'],$allowed_array)) { 
			Error::add('url','Invalid URL must be http:// or https://'); 
		} 

		// Make sure it's a real catalog
		$catalog = new Catalog($data['catalog']); 
		if (!$catalog->name) { 
			// FIXME: Untranslated
			Error::add('catalog','Invalid Catalog'); 
		} 

		if (Error::occurred()) { return false; } 

		// Clean up the input
		$name		= Dba::escape($data['name']); 
		$site_url	= Dba::escape($data['site_url']); 
		$url		= Dba::escape($data['url']); 
		$catalog	= $catalog->id; 
		$frequency	= Dba::escape($data['frequency']); 
		$call_sign	= Dba::escape($data['call_sign']); 

		// If we've made it this far everything must be ok... I hope
		$sql = "INSERT INTO `live_stream` (`name`,`site_url`,`url`,`catalog`,`frequency`,`call_sign`) " . 
			"VALUES ('$name','$site_url','$url','$catalog','$frequency','$call_sign')"; 
		$db_results = Dba::query($sql); 

		return $db_results;  

	} // create

	/**
	 * delete
	 * This deletes the current object from the database
	 */
	public function delete() { 

		$id = Dba::escape($this->id); 

		$sql = "DELETE FROM `live_stream` WHERE `id`='$id'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // delete

	/**
	 * native_stream
	 * This is needed by the media interface
	 */
	public function native_stream() { 



	} // native_stream 

	/**
	 * play_url
	 * This is needed by the media interface
	 */
	public static function play_url($oid,$sid='',$force_http='') { 

		$radio = new Radio($oid); 
		
		return $radio->url; 

	} // play_url  

	/**
	 * has_flag
	 * This is needed by the media interface
	 */
	public function has_flag() { 



	} // has_flag

	/**
	 * stream_cmd
	 * Needed by the media interface
	 */
	public function stream_cmd() { 


	} // stream_cmd

} //end of radio class

?>
