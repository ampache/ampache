<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

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

class openStrands {

	public static $base_url = 'https://www.mystrands.com/services';
	private static $auth_token = ''; 
	private static $authenticated=false; 

	// Some settings to prevent stupid users, or abusive queries
	public $limit = '10'; // Limit results set total, when possible 

	// Stuff dealing with our internal XML parser
	public $_parser;   // The XML parser
	public $_grabtags; // Tags to grab the contents of
	public $_currentTag; // Stupid hack to make things come out right
	public $_containerTag; // This is the 'highest' level tag we care about, everything falls under it
	public $_key; // Element count 
    
   	/**
	 * Constructor
	 * This takes a username and password, and verifys that this account
	 * exists before it will let the person actually try to run any operations
	 */
	public function __construct($username,$password) { 

		// Trust them enough to let them try once
		self::$authenticated = true; 

		// Test login with the provided credientials 
		if (!$this->user_validate($username,$password)) { 
			self::$authenticated = false; 
		} 
		else { 
			self::$authenticated = true; 
			self::$auth_token = Config::get('mystrands_subscriber_id'); 
		} 


	} // __construct

	/**
	 * user_validate
	 * This takes a username, password and returns a key'd array of the
	 * result data
	 */
	public function user_validate($username,$password) { 

		$username = urlencode($username); 
		$password = urlencode($password); 

		$xml_doc = self::run_query("/user/validate?username=$username&hexPass=$password"); 

		// Set the right parent
		$this->_containerTag = 'User'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // user_validate

	/**
	 * search_albums
	 * This searches for albums of the given name in MyStrands
	 */
	public function search_albums($name) { 

		$name = urlencode($name); 

		$xml_doc = self::run_query("/search/albums?searchText=$name&num=$this->limit"); 
		// Set the right parent 
		$this->_containerTag = 'SimpleAlbum'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_albums

	/**
	 * search_artists
	 * This searches for artists of the given name in MyStrands
	 */
	public function search_artists($name) { 

		$name = urlencode($name); 

		$xml_doc = self::run_query("/search/artists?searchText=$name&num=$this->limit"); 

		// Set the right parent
		$this->_containerTag = 'SimpleArtist'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_artists

	/**
	 * lookup_album_tracks
	 * This takes a album ID and then returns the track list for it
	 */
	public function lookup_album_tracks($mystrands_album_id) { 

		$mystrands_album_id = intval($mystrands_album_id); 

		$xml_doc = self::run_query("/lookup/album/tracks?id=$mystrands_album_id"); 
		
		// Set the right parent
		$this->_containerTag = 'AlbumTrack'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_album_tracks

	/**
	 * run_query
	 * This builds a URL, runs it and then returns whatever we found
	 */
	private static function run_query($action) { 

		// Stop them if they are not authenticated
		if (!self::$authenticated) { return false; } 

		// Build the URL
		$url = self::$base_url . $action . '&subscriberId=' . self::$auth_token; 
		$contents = file_get_contents($url); 

		return $contents; 

	} // run_query

	/** 
	 * run_parse
	 * This will create the parser, de-fudge the doc
	 * and set $this->results to the result data
	 */
	public function run_parse($xml_doc) { 

		// Create the parser
		$this->create_parser(); 

		$this->_key = -1; 
		$success = xml_parse($this->_parser,$xml_doc); 

		if (!$success) { 
			return false; 
		} 

		xml_parser_free($this->_parser); 

		$data = $this->results; 
		$this->results = ''; 

		return $data; 

	} // run_parse

	/**
	 * create_parser
	 * this sets up an XML Parser
	 */
	public function create_parser() { 
                $this->_parser = xml_parser_create();

                xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		
                xml_set_object($this->_parser, $this);

                xml_set_element_handler($this->_parser, 'start_element', 'end_element');

                xml_set_character_data_handler($this->_parser, 'cdata');

	} // create_parser
    
    	/** 
	 * start_element
	 * This is part of our internal XML parser
	 */
	function start_element($parser, $tag, $attributes) { 

		// If it's our 'parent' object tag then bump the key
		if ($tag == $this->_containerTag) { 
			$this->_key++; 
			// If we find attributes
			if (count($attributes)) { 
				$this->results[$this->_key][__attributes] = $attributes;
			} 
		} 
		elseif ($this->_key >= '0') { 
			$this->_currentTag = $tag; 
		} // if key is at least 0 

	} // start_element
    
	function cdata($parser, $cdata) {

		$tag 	= $this->_currentTag;

		if (strlen($tag) AND $this->_key >= 0) { 
			  $this->results[$this->_key][$tag] = trim($cdata);
		}
	
	
	} // cdata
    
	function end_element($parser, $tag) {
	
		/* Zero the tag */
		$this->_currentTag = '';
	
    	} // end_element

} // end openstrands

?>
