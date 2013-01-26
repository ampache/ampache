<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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

/**
 * AmazonSearch Class
 *
 * This class takes a token (amazon ID)
 * and then allows you to do a search using the REST
 * method. Currently it is semi-hardcoded to do music
 * searches and only return information about the album
 * art.
 *
 */
class AmazonSearch {

	var $base_url_default = "webservices.amazon.com";
	var $url_suffix = "/onca/xml";
	var $base_url;
	var $search;
	var $public_key;
	var $private_key;
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
	var $_proxy_host=""; // Proxy host
	var $_proxy_port=""; // Proxy port
	var $_proxy_user=""; // Proxy user
	var $_proxy_pass=""; // Proxy pass
    
	function AmazonSearch($public_key, $private_key,  $base_url_param = '') {
	  
	  	/* If we have a base url then use it */
   		if ($base_url_param != '') {
			$this->base_url = str_replace('http://', '', $base_url_param); 
			debug_event('amazon-search-results','Retrieving from ' . $base_url_param . $this->url_suffix,'5');
		}
		/* Default Operation */
		else { 
			$this->base_url=$this->base_url_default;
		    	debug_event('amazon-search-results','Retrieving from DEFAULT','5');
		}
		
		$this->public_key = $public_key;
		$this->private_key = $private_key;
	
		$this->_grabtags = array(
			'ASIN', 'ProductName', 'Catalog', 'ErrorMsg',
			'Description', 'ReleaseDate', 'Manufacturer', 'ImageUrlSmall',
			'ImageUrlMedium', 'ImageUrlLarge', 'Author', 'Artist','Title','URL',
			'SmallImage','MediumImage','LargeImage');
	
	} // AmazonSearch

	/**
	 * setProxy
	 * Set the class up to search through an http proxy.
	 * The parameters are the proxy's hostname or IP address (a string)
	 * port, username, and password. These are passed directly to the
	 * Snoopy class when the search is done.
	 */
	function setProxy($host='', $port='', $user='', $pass='') {
		if($host) $this->_proxy_host = $host;
		if($port) $this->_proxy_port = $port;
		if($user) $this->_proxy_user = $user;
		if($pass) $this->_proxy_pass = $pass;
	}

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
		if($this->_proxy_host)
			$snoopy->proxy_host = $this->_proxy_host;
		if($this->_proxy_port)
			$snoopy->proxy_port = $this->_proxy_port;
		if($this->_proxy_user)
			$snoopy->proxy_user = $this->_proxy_user;
		if($this->_proxy_pass)
			$snoopy->proxy_pass = $this->_proxy_pass;

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
		$params = array();
		
		$params['Service'] = 'AWSECommerceService';
		$params['AWSAccessKeyId'] = $this->public_key;
		$params['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		$params['Version'] = '2009-03-31';
		$params['Operation'] = 'ItemSearch';
		$params['Artist'] = $terms['artist'];
		$params['Title'] = $terms['album'];
		$params['Keywords'] = $terms['keywords'];
		$params['SearchIndex'] = $type;
		
		ksort($params);
		
		$canonicalized_query = array();
		
		foreach ($params as $param => $value)
			{
			$param = str_replace("%7E", "~", rawurlencode($param));
			$value = str_replace("%7E", "~", rawurlencode($value));
			
			$canonicalized_query[] = $param."=".$value;
			}
		
		$canonicalized_query = implode('&', $canonicalized_query);
		
		$string_to_sign = 'GET' . "\n" . $this->base_url . "\n" . $this->url_suffix . "\n" . $canonicalized_query;
		
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->private_key, True));
		
		$signature = str_replace("%7E", "~", rawurlencode($signature));
		
		$url = 'http://' . $this->base_url . $this->url_suffix . '?' . $canonicalized_query . '&Signature=' . $signature;
		
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
