<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*!
	@header AmazonSearch Class
	@discussion This class takes a token (amazon ID)
		and then allows you to do a search using the REST
		method. Currently it is semi-hardcoded to do music
		searches and only return information abou the album
		art
*/
class AmazonSearch {

	var $base_url_default = "http://webservices.amazon.com";
	var $url_suffix = "/onca/xml?";
	var $base_url;
	var $search;
	var $token;
	var $results=array();  // Array of results
	var $_parser;   // The XML parser
	var $_grabtags; // Tags to grab the contents of
	var $_sourceTag; // source tag don't ask
	var $_subTag; // Stupid hack to make things come our right
	var $_currentTag; // Stupid hack to make things come out right
	var $_currentTagContents;
	var $_currentPage=0;
	var $_maxPage=1;
	var $_default_results_pages=1;
    
	function AmazonSearch($token,  $base_url_param = '', $associates_id = 'none') {
	  
	  	/* If we have a base url then use it */
   		if ($base_url_param != '') {
			$this->base_url = $base_url_param . $this->url_suffix; 
			debug_event('amazon-search-results','Retrieving from ' . $base_url_param . $this->url_suffix,'5');
		}
		/* Default Operation */
		else { 
			$this->base_url=$this->base_url_default . $this->url_suffix;
		    	debug_event('amazon-search-results','Retrieving from DEFAULT','5');
		}
		
		$this->token = $token;
		$this->associates_id = $associates_id;
	
		$this->_grabtags = array(
			'ASIN', 'ProductName', 'Catalog', 'ErrorMsg',
			'Description', 'ReleaseDate', 'Manufacturer', 'ImageUrlSmall',
			'ImageUrlMedium', 'ImageUrlLarge', 'Author', 'Artist','Title','URL',
			'SmallImage','MediumImage','LargeImage');
	
	} // AmazonSearch
    
	/*!	
		@create_parser
		@discussion this sets up an XML Parser
	*/
	function create_parser() { 
                $this->_parser = xml_parser_create();

                xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		
                xml_set_object($this->_parser, $this);

                xml_set_element_handler($this->_parser, 'start_element', 'end_element');

                xml_set_character_data_handler($this->_parser, 'cdata');

	} // create_parser
    
	/*!
		@function search
		@discussion do a full search on the url they pass
	*/
	function run_search($url) {

		/* Create the Parser */
		$this->create_parser();
	
		$snoopy = new Snoopy;
		$snoopy->fetch($url);
		$contents = $snoopy->results;
	
		if (!xml_parse($this->_parser, $contents)) {
			debug_event('amazon-search-results','Error:' . sprintf('XML error: %s at line %d',xml_error_string(xml_get_error_code($this->_parser)),xml_get_current_line_number($this->_parser)),'1');
		}
		
		xml_parser_free($this->_parser);
	
	} // run_search
    
	/*!
		@function search
		@discussion takes terms and a type
	*/
	function search($terms, $type='Music') {

		$url = $this->base_url . "Service=AWSECommerceService&SubscriptionId=" . $this->token .
			"&Operation=ItemSearch&Artist=" . urlencode($terms['artist']) . "&Title=" . urlencode($terms['album']) . 
			"&Keywords=" . urlencode($terms['keywords']) . "&SearchIndex=" . $type;
			
		debug_event('amazon-search-results',"_currentPage = " .  $this->_currentPage,'3');
		if($this->_currentPage != 0){
		  $url = $url . "&ItemPage=" . ($this->_currentPage+1);
		}
		$this->run_search($url);

		unset($this->results['ASIN']);

		return $this->results;

	} // search
    
	/*!
		@function lookup
		@discussion this takes a ASIN and looks up the
			item in question, possible to pass array
			of asin's 
	*/
	function lookup($asin, $type='Music') { 

		if (is_array($asin)) { 
			foreach ($asin as $key=>$value) { 
				$url = $this->base_url . "Service=AWSECommerceService&SubscriptionId=" . $this->token .
					"&Operation=ItemLookup&ItemId=" . $key . "&ResponseGroup=Images";
				$this->run_search($url);
			}
		} // if array of asin's
		else { 
	                $url = $this->base_url . "Service=AWSECommerceService&SubscriptionId=" . $this->token .
        	                "&Operation=ItemLookup&ItemId=" . $asin . "&ResponseGroup=Images";
                        $this->run_search($url);
		} // else

		unset($this->results['ASIN']);

		return $this->results;

	} // lookup
    
	function start_element($parser, $tag, $attributes) { 

		if ($tag == "ASIN") { 
			$this->_sourceTag = $tag;
		}
		if ($tag == "SmallImage" || $tag == "MediumImage" || $tag == "LargeImage") {
			$this->_subTag = $tag;
		} 

		/* If it's in the tag list, don't grab our search results though */
		if (strlen($this->_sourceTag)) {
			$this->_currentTag = $tag;		
		} 
		else {
		  if($tag != "TotalPages"){
        	    $this->_currentTag = '';
		  }else{
		    $this->_currentTag = $tag;		

		  }
		}

    } // start_element
    
    function cdata($parser, $cdata) {

	$tag 	= $this->_currentTag;
	$subtag = $this->_subTag;
	$source = $this->_sourceTag;

	switch ($tag) { 
		case 'URL':
			$this->results[$source][$subtag] = trim($cdata);
			break;
		case 'ASIN':
			$this->_sourceTag = trim($cdata);
			break;
         	case 'TotalPages':
			debug_event('amazon-search-results',"TotalPages= ". trim($cdata),'5');
			$this->_maxPage = trim($cdata);
                        break;
		default:
			if (strlen($tag)) { 
			  $this->results[$source][$tag] = trim($cdata);
			}
			break;
	} // end switch


    } // cdata
    
	function end_element($parser, $tag) {
	
		/* Zero the tag */
		$this->_currentTag = '';
	
    	} // end_element

} // end AmazonSearch

?>
