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

class AmpacheApi { 

	// General Settings
	private $server; 
	private $username; 
	private $password; 
	private $api_secure; 

	// Response variables
	private $api_session;  

	// Constructed variables
	private $api_url; 
	private $api_state; 

	// XML Parser variables
	private $XML_currentTag;
	private $XML_parser;
	private $XML_results; 
	protected $XML_grabtags = array(); 
	protected $XML_skiptags = array('root'); 

	/**
	 * Constructor
	 * This takes an array of input, if enough information is provided then it will 
	 * attempt to connect to the API right away, otherwise it will simply return an
	 * object that can be later configured and then connected
	 */
	public function __construct($config=array()) { 

		// If we got something, then configure!
		if (is_array($config) AND count($config)) { 
			$this->configure($config); 
		} 

		// If we've been READY'd then go ahead and attempt to connect
		if ($this->state() == 'READY') { 
			$this->connect();
		} 

	} // constructor

	/**
 	 * connect
	 * This attempts to connect to the ampache instance, for now we assume the newer version
	 */
	public function connect() { 

		// Setup the handshake
		$timestamp = time(); 
		$key = hash('sha256',$this->password); 
		$passphrase = hash('sha256',$time . $key); 

		$url = $this->api_url . "?action=handshake&timestamp=$timestamp&passphrase=$passphrase&version=350001&user=" . $this->username; 

		

	} // connect

	/**
	 * configure
	 * This function takes an array of elements and configures the AmpaceApi object
	 * it doesn't really do much more, it is it's own function so we can call it 
	 * from the constructor or directly, if we so desire. 
	 */
	public function configure($config=array()) { 

		if (!is_array($config)) {
			trigger_error('AmpacheApi::configure received a non-array value'); 
			return false; 
		} 

		if (isset($config['username'])) {
			$this->username = htmlentities($config['username'],ENT_QUOTES,'UTF-8'); 
		} 
		if (isset($config['password'])) { 
			$this->password = htmlentities($config['password'],ENT_QUOTES,'UTF-8'); 
		} 
		if (isset($config['server'])) { 
			// Replace any http:// in the URL with ''
			$config['server'] = str_replace('http://','',$config['server']); 
			$this->server = htmlentities($config['server'],ENT_QUOTES,'UTF-8'); 
		} 	
		if (isset($config['api_secure'])) { 
			// This should be a boolean response
			$this->api_secure = $config['api_secure'] ? true : false; 
		} 


		// Once we've loaded the config variables we can build some of the final values
		$this->api_url = ($this->api_secure ? 'https://' : 'http://') . $this->server; 

		// See if we have enough to authenticate, if so change the state
		if ($this->username AND $this->password AND $this->server) { 
			$this->set_state('ready'); 
		} 

		return true; 

	} // configure

	/**
	 * set_state
	 * This sets the current state of the API, it is used mostly internally but
	 * the state can be accessed externally so it could be used to check and see 
	 * where the API is at, at this moment
	 */
	public function set_state($state); 

		// Very simple for now, maybe we'll do something more with this later
		$this->api_state = strtoupper($state); 

	} // set_state

	/**
	 * state
	 * This returns the state of the API 
	 */
	public function state() { 

		return $this->api_state; 

	} // state

	/**
	 * send_command
	 * This sends an API command, with options to the currently connected
	 * host, and returns a nice clean keyed array 
	 */
	public function send_command($command,$options=array()) { 

		if ($this->state != 'READY') { 
			trigger_error('AmpacheApi::send_command API in non-ready state, unable to send');
			return false; 
		} 
		if (!trim($command)) { 
			trigger_error('AmpacheApi::send_command no command specified'); 
			return false; 
		} 	
		if (!$this->validate_command($command)) { 
			trigger_error('AmpacheApi::send_command Invalid/Unknown command ' . $command . ' issued'); 
			return false; 
		} 

		$url = $this->api_url . '?action=' . urlencode($command); 

		foreach ($options as $key=>$value) { 
			if (!trim($key)) { 
				trigger_error('AmpacheApi::send_command unable to append empty variable to command'); 
				continue; 
			} 
			$url .= '&' . urlencode($key . '=' . $value); 
		} 

	} // send_command

	/**
	 * validate_command
	 * This takes the specified command, and checks it against the known
	 * commands for the current version of Ampache. If no version is known yet
	 * This it will return FALSE for everything except ping and handshake. 
	 */
	public function validate_command($command) { 



	} // validate_command

	/////////////////////////// XML PARSER FUNCTIONS /////////////////////////////

	/**
	 * XML_create_parser
	 * This creates the xml parser and sets the options
	 */
	public function XML_create_parser() { 

		$this->XML_parser = xml_parser_create(); 
		xml_parser_set_option($this->XML_parser,XML_OPTION_CASE_FOLDING,false); 
		xml_set_object($this->XML_parser,$this); 
		xml_set_element_handler($this->XML_parser,'XML_start_element','XML_end_element'); 
		xml_set_character_data_handler($this->XML_parser,'XML_cdata'); 

	} // XML_create_parser

	/**
	 * XML_cdata
	 * This is called for the content of the XML tag
	 */
	public function XML_cdata($parser,$cdata) { 


	} // XML_cdata

	public function XML_start_element($parser,$tag) { 

		

	} // start_element

	public function XML_end_element($parser,$tag) { 


	} // end_element

} // end AmpacheApi class


?>
