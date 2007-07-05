<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

class LastFMSearch {

	protected $base_url = "http://ws.audioscrobbler.com/1.0/album";
	public $results=array();  // Array of results
	private $_parser;   // The XML parser
	protected $_grabtags = array('coverart','large','medium','small');
	private $_subTag; // Stupid hack to make things come our right
	private $_currentTag; // Stupid hack to make things come out right
    
	public function __construct() {

		// Rien a faire
	
	} // LastFMSearch
    
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
	
		$snoopy = new Snoopy;
		$snoopy->fetch($url);
		$contents = $snoopy->results;

		if ($contents == 'Artist not found') { 
			debug_event('lastfm','Error: Artist not found with ' . $url,'3'); 
			return false; 
		}  
		
		if (!xml_parse($this->_parser, $contents)) {
			debug_event('lastfm','Error:' . sprintf('XML error: %s at line %d',xml_error_string(xml_get_error_code($this->_parser)),xml_get_current_line_number($this->_parser)),'1');
		}
		
		xml_parser_free($this->_parser);
	
	} // run_search
    
	/*!
		@function search
		@discussion takes terms and a type
	*/
	function search($artist,$album) {

		$url = $this->base_url . '/' . urlencode($artist) . '/' . urlencode($album) . '/info.xml';		
		
		debug_event('lastfm','Searching:' . $url,'3');
		
		$this->run_search($url);

		return $this->results;

	} // search
    
    
	function start_element($parser, $tag, $attributes) { 

		if ($tag == 'coverart') { 
			$this->_currentTag = $tag;
		}
		if ($tag == 'small' || $tag == 'medium' || $tag == 'large') {
			$this->_subTag = $tag;
		} 

    } // start_element
    
    function cdata($parser, $cdata) {


	if (!$this->_currentTag || !$this->_subTag || !trim($cdata)) { return false; } 

	$tag 	= $this->_currentTag;
	$subtag = $this->_subTag;

	$this->results[$tag][$subtag] = trim($cdata);

    } // cdata
    
	function end_element($parser, $tag) {
	
		if ($tag == 'coverart') { $this->_currentTag = ''; } 
	
    	} // end_element

} // end LastFMSearch

?>
