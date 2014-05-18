<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class LastFMSearch {

	protected $base_url = "http://ws.audioscrobbler.com/1.0/album";
	protected $base_url_v2 = "http://ws.audioscrobbler.com/2.0/"; 
	protected $api_key = "d5df942424c71b754e54ce1832505ae2";
	public $results=array();  // Array of results
	private $_parser;   // The XML parser
	protected $_grabtags = array('size','coverart','large','medium','small');
	private $_subTag; // Stupid hack to make things come our right
	private $_currentTag; // Stupid hack to make things come out right
	private $_proxy_host; // Proxy host
	private $_proxy_port; // Proxy port
	private $_proxy_user; // Proxy username
	private $_proxy_pass; // Proxy password
    
	public function __construct($proxy='', $port='', $user='', $pass='') {

		// Rien a faire
	
	} // LastFMSearch

	/**
	 * setProxy
	 * Set the class up to search through an http proxy.  
	 * The parameters are the proxy's hostname or IP address (a string)
	 * port, username, and password. These are passed directly to the
	 * Requests class when the search is done.
	 */
	public function setProxy($host='', $port='', $user='', $pass='') {
		if($host) $this->_proxy_host = $host;
		if($port) $this->_proxy_port = $port;
		if($user) $this->_proxy_user = $user;
		if($pass) $this->_proxy_pass = $pass;
	}
    
	/**
	 * create_parser
	 * this sets up an XML Parser that we can use to parse the XML
	 * document we recieve
	 */
	public function create_parser() { 
                $this->_parser = xml_parser_create();

                xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		
                xml_set_object($this->_parser, $this);

                xml_set_element_handler($this->_parser, 'start_element', 'end_element');

                xml_set_character_data_handler($this->_parser, 'cdata');

	} // create_parser
    
	/**
	 * search
	 * do a full search on the url they pass
	 */
	public function run_search($url) {

		/* Create the Parser */
		$this->create_parser();
	
		$options = array();
		if($this->_proxy_host)
        {
            $proxy = array();
            $proxy[] = $this->_proxy_host . ( $this->_proxy_port ? ':' . $this->_proxy_port : '');
            if($this->_proxy_user)
            {
                $proxy[] = $this->_proxy_user;
                $proxy[] = $this->_proxy_pass;
            }
            $options['proxy'] = $proxy;
        }

		debug_event("lastfm", "Start get from url", "5");
		$request = Requests::get($url, array(), $options);
		$contents = $request->body;

		if ($contents == 'Artist not found') { 
			debug_event('lastfm','Error: Artist not found with ' . $url,'3'); 
			return false; 
		}
		elseif($contents == 'No such album for this artist') {
			debug_event('lastfm','Error: No such album for this artist with '. $url, '3');
			return false;
		} 
		
		if (!xml_parse($this->_parser, $contents)) {
			debug_event('lastfm','Error:' . sprintf('XML error: %s at line %d',xml_error_string(xml_get_error_code($this->_parser)),xml_get_current_line_number($this->_parser)),'1');
		}
		
		xml_parser_free($this->_parser);
	
	} // run_search
    
	/**
	 * album_search
	 * takes terms and a type
	 */
	public function album_search($artist,$album) {

		$url = $this->base_url . '/' . urlencode($artist) . '/' . urlencode($album) . '/info.xml';		
		
		debug_event('LastFM','Album Search: ' . $url,'3');
		
		$this->run_search($url);

		return $this->results;

	} // album_search

	/**
	 * artist_search
	 * Search for an artists information!
	 * This uses v2 of the API, need to update the rest of this class to use v2
	 */
	public function artist_search($artist) { 
	
		$url = $this->base_url_v2 . '?method=artist.getImages&artist=' . urlencode($artist) . '&limit=10';	
		
		//FIXME: This should be done by run_search
		$url .= '&api_key=' . urlencode($this->api_key); 

		debug_event('LastFM','Album Search: ' . $url,'3');

		$this->run_search($url); 

		return $this->results; 

	} // artist_search 
    
    	/**
 	 * start_element
	 * This function is called when we see the start of an xml element
	 */
	public function start_element($parser, $tag, $attributes) { 

		if ($tag == 'coverart' OR $tag == 'sizes') { 
			$this->_currentTag = $tag;
		}
		if ($tag == 'small' || $tag == 'medium' || $tag == 'large') {
			$this->_subTag = $tag;
		} 
		if ($tag == 'size' AND $attributes['name'] == 'original') { 
			if (!isset($this->results[$this->_currentTag][$tag])) { 
				$this->_subTag = $tag; 
			} 
		} 
		elseif ($tag == 'size') { 
			unset($this->_subTag); 
		} 

	} // start_element

	/**
 	 * cdata
	 * This is called for the content of an XML tag
	 */
	public function cdata($parser, $cdata) {
		if (!$this->_currentTag || !$this->_subTag || !trim($cdata)) { return false; } 

		$tag 	= $this->_currentTag;
		$subtag = $this->_subTag;

		$this->results[$tag][$subtag] = trim($cdata);

	} // cdata

	/**
	 * end_element
	 * This is called on the close of an XML tag
	 */ 
	public function end_element($parser, $tag) {
	
		if ($tag == 'coverart') { $this->_currentTag = ''; } 
		if ($tag == 'sizes') { $this->_currentTag = ''; } 
	
	} // end_element

} // end LastFMSearch

?>
