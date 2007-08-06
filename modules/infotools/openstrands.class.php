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

/**
 * Ampache openStrands Class, Version 0.1
 * This class interacts with the OpenStrands API (http://mystrands.com)
 * It requires a valid authtoken, and on creation of an instance requires
 * a valid username and password, Password is expected as an MD5 hash for
 * security reasons
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
class openStrands {

	public static $base_url = 'https://www.mystrands.com/services';
	private static $auth_token = ''; 
	private static $authenticated=false; 

	// Some settings to prevent stupid users, or abusive queries
	public $limit = '10'; // Limit results set total, when possible 
	public static $alias; // Store the users alias here after authing
	private $filter = '';  // The filter for this instance of the class
 
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
		if (!self::$auth_token) { echo 'No Auth Token, quiting'; return false; } 

		// Trust them enough to let them try once
		self::$authenticated = true; 

		// Test login with the provided credientials 
		$auth_data = $this->user_validate($username,$password);
			
		if (!$auth_data) { 
			self::$authenticated = false; 
		} 
		else { 
			self::$authenticated = true; 
			self::$alias = $auth_data['0']['Alias']; 
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
	 * user_lookup_profile
	 * This takes a username and the md5 password and returns the users profile information
	 */
	public function user_lookup_profile($username,$password) { 

		$username = urlencode($username); 
		$password = urlencode($password); 

		$xml_doc = self::run_query("/user/lookup/profile?username=$username&hexPass=$password"); 

		// Set the right parent
		$this->_containerTag = 'User'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // user_lookup_profile

	/**
	 * lookup_artists
	 * This returns the data for a given mystrands artist (based on ID) 
	 * If it's an array of ids pass as [] = ID, [] = ID
	 */
	public function lookup_artists($ids) { 

		// This allows multiple artists in a single query
		// so build the string if needed
		if (is_array($ids)) { 
			foreach ($ids as $id) { 
				$string .= '&id=' . intval($id); 
			} 
			$string = ltrim($string,'&');
			$string = '?' . $string; 
		} 
		else { 
			$string = '?id=' . intval($ids); 
		} 

		$xml_doc = self::run_query("/lookup/artists$string"); 

		// Set the right parent
		$this->_containerTag = 'SimpleArtist'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_artists

	/**
	 * lookup_albums
	 * This returns the data for a given mystrands album (based on ID)
	 * If it's an array of ids pass as [] = ID, [] = ID
	 */
	public function lookup_albums($ids) { 

		// This allows multiple albums in a single query, 
		// so build it accordingly
		if (is_array($ids)) { 
			foreach ($ids as $id) { 
				$string .= '&id=' . intval($id); 
			} 
			$string = ltrim($string,'&'); 
			$string = '?' . $string; 
		} 
		else { 
			$string = '?id=' . intval($ids); 
		} 

		$xml_doc = self::run_query("/lookup/albums$string"); 

		// Set the right parent
		$this->_containerTag = 'SimpleAlbum'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_albums

	/**
	 * lookup_tracks
	 * This returns the data for a given mystrands track (based on ID)
	 * If it's an array of ids pass as [] = ID, [] = ID
	 */
	public function lookup_tracks($ids) { 

		// This allows for multiple entires, so build accordingly
		if (is_array($ids)) { 
			foreach ($ids as $id) { 
				$string .= '&id=' . intval($id); 
			} 
			$string = ltrim($string,'&'); 
			$string = '?' . $string; 
		} // end if array
		else { 
			$string = '?id=' . intval($ids); 
		} 

		$xml_doc = self::run_query("/lookup/tracks$string"); 

		// Set the parent
		$this->_containerTag = 'SimpleTrack'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // lookup_tracks

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
	 * This searches for albums of the given name in MyStrands
	 */
	public function search_albums($name,$limit='',$offset='') { 

		$name = urlencode($name); 

                $limit  = $limit ? intval($limit) : $this->limit;
		$offset = $offset ? intval($offset) : '0';

		$xml_doc = self::run_query("/search/albums?searchText=$name&num=$limit&skip=$offset"); 

		// Set the right parent 
		$this->_containerTag = 'SimpleAlbum'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_albums

	/**
	 * search_artists
	 * This searches for artists of the given name in MyStrands
	 */
	public function search_artists($name,$limit='',$offset='') { 

		$name = urlencode($name); 

                $limit  = $limit ? intval($limit) : $this->limit;
		$offset = $offset ? intval($offset) : '0';

		$xml_doc = self::run_query("/search/artists?searchText=$name&num=$limit&skip=$offset"); 

		// Set the right parent
		$this->_containerTag = 'SimpleArtist'; 

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

		$xml_doc = self::run_query("/search/tracks?searchText=$name&num=$limit&skip=$offset"); 

		// Set the right parent
		$this->_containerTag = 'SimpleTrack'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // search_tracks

	/**
	 * recommend_artists
	 * This generates recomendations for other artists, takes at least one artist (ID/Name) as
	 * as seed, filters allowed, this user is used unless another alias is passed
	 * $values should be ['id'][] = ID, ['id'][] = ID, ['name'][] = NAME, ['name'][] = NAME
	 */
	public function recommend_artists($values,$limit='',$offset='',$alias='') { 

                if (!is_array($values['id'])) { $values['id'] = array(); } 
                if (!is_array($values['name'])) { $values['name'] = array(); } 

		// Build out the ids first
		foreach ($values['id'] as $id) { 
			$id_string .= '&id=' . intval($id); 
		} 
		// Now for z-names
		foreach ($values['name'] as $name) { 
			$name_string .= '&name=' . urlencode($name); 
		} 

		// Clean up remaining stuff
		$filters	= $this->filter; 
		$offset		= $offset ? intval($offset) : '0'; 
		$limit		= $limit ? intval($limit) : $this->limit; 
		$alias		= $alias ? urlencode($alias) : urlencode(self::$alias); 

		$xml_doc = self::run_query("/recommend/artists?alias=$alias$id_string$name_string&num=$limit&skip=$offset$filters"); 

		$this->_containerTag = 'SimpleArtist'; 

		$data = $this->run_parse($xml_doc); 

		return $data; 

	} // recommend_artists

	/**
	 * recommend_albums
	 * This produces recommended albums based on sets of artist|album filters allowed, this user is used unless different
	 * alias is passed. Values are integrity checked to the best of my lazyness
	 * $values should be ['id'][] = ARTISTID|ALBUMID, ['name'][] = ARTIST|ALBUM
	 */
	public function recommend_albums($values,$limit='',$offset='',$alias='') { 

		if (!is_array($values['id'])) { $values['id'] = array(); } 
		if (!is_array($values['name'])) { $values['name'] = array(); }  

                // Build out the ids first
                foreach ($values['id'] as $id) {
			if (!preg_match("/\d+\|\d+/",$id)) { next; } 
                        $id_string .= '&id=' . intval($id);
                }
                // Now for z-names
                foreach ($values['name'] as $name) {
			// Only add stuff that's valid
			if (!preg_match("/.+\|.+/",$name)) { next; } 
                        $name_string .= '&name=' . urlencode($name);
                }

                // Clean up remaining stuff
                $filters        = $this->filter;
                $offset         = $offset ? intval($offset) : '0';
                $limit          = $limit ? intval($limit) : $this->limit;
                $alias          = $alias ? urlencode($alias) : urlencode(self::$alias);

                $xml_doc = self::run_query("/recommend/albums?alias=$alias$id_string$name_string&num=$limit&skip=$offset$filters");
                
		$this->_containerTag = 'SimpleAlbum';

                $data = $this->run_parse($xml_doc);

                return $data;
	
	} // recommend_albums

        /**
         * recommend_tracks
         * This produces recommended tracks based on sets of artist|album|track filters allowed, this user is used unless different
         * alias is passed. Values are integrity checked to the best of my lazyness
         * $values should be ['id'][] = ARTISTID|ALBUMID|TRACKID, ['name'][] = ARTIST|ALBUM|TRACK
         */
        public function recommend_tracks($values,$limit='',$offset='',$alias='') {

                if (!is_array($values['id'])) { $values['id'] = array(); }
                if (!is_array($values['name'])) { $values['name'] = array(); }

                // Build out the ids first
                foreach ($values['id'] as $id) {
                        if (!preg_match("/\d+\|\d+\|\d+/",$id)) { next; }
                        $id_string .= '&id=' . intval($id);
                }
                // Now for z-names
                foreach ($values['name'] as $name) {
                        // Only add stuff that's valid
                        if (!preg_match("/.+\|.+\|.+/",$name)) { next; }
                        $name_string .= '&name=' . urlencode($name);
                }

                // Clean up remaining stuff
                $filters        = $this->filter;
                $offset         = $offset ? intval($offset) : '0';
                $limit          = $limit ? intval($limit) : $this->limit;
                $alias          = $alias ? urlencode($alias) : urlencode(self::$alias);

                $xml_doc = self::run_query("/recommend/tracks?alias=$alias$id_string$name_string&num=$limit&skip=$offset$filters");

                $this->_containerTag = 'SimpleTrack';

                $data = $this->run_parse($xml_doc);

                return $data;

        } // recommend_tracks

	/**
	 * set_filter
	 * This builds out the filter for this object, it's a per instance filter 
	 */
	public function set_filter($field,$fuzzy,$level,$values) { 

		// Make sure they pick a sane field
		switch ($field) { 
			case 'Artist': 
			case 'Album':
			case 'Year': 
			case 'Track': 
			case 'Genre':
			case 'PersonalTag':
			case 'CommunityTag':
			case 'PersonalTrackTag': 
			case 'CommunityTrackTag': 
			case 'PersonalArtistTag': 
			case 'CommunityArtistTag': 
			case 'UserHistory': 
				// We're good
			break;
			default: 
				return false; 
			break;
		} // end switch on passed filed 

		$level = intval($level); 
	
		// Default to very hard
		if ($level > 9 || $level < 0) { $level = 0; } 

		// Clean up the fuzzyness
		$fuzzy = make_bool($fuzzy); 
		if ($fuzzy) { $fuzzy = 'True'; } 
		else { $fuzzy = 'False'; }

		if (is_array($values)) { 
			foreach ($values as $value) { 
				$value_string .= $value; 
			} 
		} 
		else { 
			$value_string = $values; 
		} 

		$filter_string = "&filter=$field,$fuzzy,$level|$value_string"; 

		$this->filter .= $filter_string; 

	} // set_filter

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

} // end openstrands

?>
