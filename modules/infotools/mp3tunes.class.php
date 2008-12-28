<?php
/*

 Copyright (c) Ampache.org
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

/**
 *
 * This class returns the XML data as a array of key'd arrays, attributes
 * are stored in [][__attributes] = array(); Any questions, recommendations
 * bugfixes or the like please contact mystrands@ampache.org or visit us on
 * irc.ampache.org #ampache 
 *
 * There are some bugs in this library, and some things that are a little
 * redundent, I will be fixing it in future releases. 
 * 
 * - Karl Vollmer
 * 
 * REQUIREMENTS: 
 * - fopen wrappers enabled to allow file_get_contents(URL); 
 * - PHP5
 */
class mp3tunes {

	// Base URLS
	private static $base_url = array('auth'=>'https://shop.mp3tunes.com/api/',
				'general'=>'http://ws.mp3tunes.com/api/',
				'content'=>'http://content.mp3tunes.com/api/'); 
	private static $api_version = 'v1'; 

	// Partner Token (You will need to put your token here)
	private static $partner_token = '9651695141';

	// Internal Variables
	private static $authenticated=false; 
	private static $session_id=''; 
	private $limit = '100'; 

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

		// Check to see if we've already authenticated
		if (self::$authenticated) { return true; }  
		if (!self::$partner_token) { echo 'No Auth Token, quiting'; return false; } 

		// Trust them enough to let them try once
		self::$authenticated = true; 

		// Test login with the provided credientials 
		$auth_data = $this->user_validate($username,$password);
			
		if (!$auth_data) { 
			self::$authenticated = false; 
		} 
		else { 
			self::$authenticated = true; 
			self::$session_id = $auth_data['0']['session_id']; 	
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

		$xml_doc = self::run_query('auth',"/login?username=$username&password=$password"); 

		// Set the right parent
		$this->_containerTag = 'mp3tunes'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // user_validate

	/**
	 * user_lookup_profile
	 * This can only be called after you have authenticated, it returns account information
	 */
	public function user_lookup_profile() { 

		$xml_doc = self::run_query('general',"/accountData"); 

		// Set the right parent
		$this->_containerTag = 'user'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // user_lookup_profile

	/**
	 * last_update
	 * This returns a timestamp of the last time that the locker was updated
	 * usefull for caching operations
	 */
	public function last_update() { 

		$xml_doc = self::run_query('general','/lastUpdate?type=locker'); 

		// Set the xml parent
		$this->_containerTag = 'mp3tunes'; 
		
		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // last_update

	/**
	 * lookup_artists
	 * This returns the data for a given mp3tunes artist (based on ID) 
	 * If it's an array of ids pass as [] = ID, [] = ID
	 */
	public function lookup_artists($ids) { 

		// This allows multiple artists in a single query
		// so build the string if needed
		if (is_array($ids)) { 
			foreach ($ids as $id) { 
				$string .= '&artist_id=' . intval($id); 
			} 
			$string = ltrim($string,'&');
			$string = '?' . $string; 
		} 
		else { 
			$string = '?artist_id=' . intval($ids); 
		} 

		$xml_doc = self::run_query('general',"/lockerData$string&type=artist"); 

		// Set the right parent
		$this->_containerTag = 'artistList'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_artists

	/**
	 * lookup_albums
	 * This returns the data for a given mp3tunes album (based on ID)
	 * If it's an array of ids pass as [] = ID, [] = ID
	 */
	public function lookup_albums($ids) { 

		// This allows multiple albums in a single query, 
		// so build it accordingly
		if (is_array($ids)) { 
			foreach ($ids as $id) { 
				$string .= '&album_id=' . intval($id); 
			} 
			$string = ltrim($string,'&'); 
			$string = '?' . $string; 
		} 
		else { 
			$string = '?album_id=' . intval($ids); 
		} 

		$xml_doc = self::run_query('general',"/lockerData$string&type=album"); 

		// Set the right parent
		$this->_containerTag = 'albumList'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_albums

	/**
	 * lookup_tracks
	 * This returns the data for a given mp3tunes track (based on ID)
	 * If it's an array of ids pass as [] = ID, [] = ID
	 */
	public function lookup_tracks($ids) { 

		// This allows for multiple entires, so build accordingly
		if (is_array($ids)) { 
			foreach ($ids as $id) { 
				$string .= '&track_id=' . intval($id); 
			} 
			$string = ltrim($string,'&'); 
			$string = '?' . $string; 
		} // end if array
		else { 
			$string = '?track_id=' . intval($ids); 
		} 

		$xml_doc = self::run_query('general',"/lockerData$string&type=track"); 

		// Set the parent
		$this->_containerTag = 'trackList'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_tracks

	/**
	 * lookup_album_tracks
	 * This takes a album ID and then returns the track list for it
	 */
	public function lookup_album_tracks($mystrands_album_id,$alias='') { 

		$mystrands_album_id = intval($mystrands_album_id); 

		$xml_doc = self::run_query("/lookup/album/tracks?id=$mystrands_album_id$alias_txt"); 
		
		// Set the right parent
		$this->_containerTag = 'AlbumTrack'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_album_tracks

	/**
	 * lookup_artist_albums
	 * This returns a list of the albums for a given artist ID you can 
	 * pass an optional limit and offset
	 */
	public function lookup_artist_albums($artist_id,$limit='',$offset='') { 

		$artist_id = intval($artist_id); 

		$limit 	= $limit ? intval($limit) : $this->limit; 
		$offset	= $offset ? intval($offset) : '0'; 

		$xml_doc = self::run_query("/lookup/artist/albums?id=$artist_id&num=$limit&skip=$offset"); 

		// Set the container
		$this->_containerTag = 'SimpleAlbum'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_artist_albums

	/**
	 * search_albums
	 * This searches for albums of the given name in your locker
	 */
	public function search_albums($name,$limit='',$offset='') { 

		$name = urlencode($name); 

                $limit  = $limit ? intval($limit) : $this->limit;
		$offset = $offset ? intval($offset) : '0';

		$xml_doc = self::run_query('general',"/lockerSearch?s=$name&count=$limit&set=$offset&type=album"); 

		// Set the right parent 
		$this->_containerTag = 'albumList'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_albums

	/**
	 * search_artists
	 * This searches for artists of the given name in your locker
	 */
	public function search_artists($name,$limit='',$offset='') { 

		$name = urlencode($name); 

                $limit  = $limit ? intval($limit) : $this->limit;
		$offset = $offset ? intval($offset) : '0';

		$xml_doc = self::run_query('general',"/lockerSearch?s=$name&count=$limit&set=$offset&type=artist"); 

		// Set the right parent
		$this->_containerTag = 'artistList'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_artists

	/**
	 * search_tracks
	 * This searches for tracks on Mystrands based on the search text
	 */
	public function search_tracks($name,$limit='',$offset='') { 

		$name = urlencode($name); 

		$limit  = $limit ? intval($limit) : $this->limit;
		$offset = $offset ? intval($offset) : '0';

		$xml_doc = self::run_query('general',"/lockerSearch?s=$name&count=$limit&set=$offset&type=track"); 

		// Set the right parent
		$this->_containerTag = 'trackList'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_tracks

	/**
	 * set_auth_token
	 * This function should be called on load to set the auth_token, if you don't
	 * just hardcode it, also allows realtime switching between auth tokens 
	 * so that users can use their own limits when querying
	 */
	public static function set_auth_token($token) { 

		// Set it
		self::$auth_token = $token; 

		//FIXME: We should log this event? 

	} // set_auth_token

	/**
	 * run_query
	 * This builds a URL, runs it and then returns whatever we found
	 */
	private static function run_query($method,$action) { 

		// Stop them if they are not authenticated
		if (!self::$authenticated) { return false; } 
		if (!self::$base_url[$method]) { echo "Invalid Method:$method"; return false; } 
		
		// Build up the sid
		$sid = '&partner_token=' . self::$partner_token; 
		if (self::$session_id) { $sid .= '&sid=' . self::$session_id; } 
		if (!strstr("?",$action)) { $action .= '?output=xml'; } 
		else { $actoin .= '&output=xml'; } 

		// Build the URL
		$url = self::$base_url[$method] . self::$api_version . $action . $sid;  
		
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

		if (!$data) { return array(); } 

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
				// If it's not already an array, make it one so merge always works
				if (!isset($this->results[$this->_key][__attributes])) { 
					$this->results[$this->_key][__attributes] = array(); 
				} 
				$this->results[$this->_key][__attributes] = array_merge($this->results[$this->_key][__attributes],$attributes); 
			} // end if there are attributes 
		} 
		elseif ($this->_key >= '0') { 
			$this->_currentTag = $tag; 
		} // if key is at least 0 

	} // start_element
    
	function cdata($parser, $cdata) {

		$tag 	= $this->_currentTag;

		if (strlen($tag) AND $this->_key >= 0) { 
			  $this->results[$this->_key][$tag] .= trim($cdata);
		}
	
	
	} // cdata
    
	function end_element($parser, $tag) {
	
		/* Zero the tag */
		$this->_currentTag = '';
	
    	} // end_element

} // end mp3tunes

?>
