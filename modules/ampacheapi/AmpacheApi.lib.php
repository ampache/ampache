<?php 
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2012 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
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

class AmpacheApi { 

    // General Settings
    private $server; 
    private $username; 
    private $password; 
    private $api_secure; 

    // Handshake variables
    private $handshake; 
    private $handshake_time; // Used to figure out how stale our data is

    // Response variables
    private $api_session;  

    // Constructed variables
    private $api_url; 
    private $api_state='UNCONFIGURED'; 
    private $api_auth; 

    // XML Parser variables
    private $XML_currentTag;
    private $XML_subTag; 
    private $XML_parser;
    private $XML_results; 
    private $XML_position=0;  
    protected $XML_grabtags = array(); 
    protected $XML_skiptags = array('root'); 
    protected $XML_parenttags = array('artist','album','song','tag','video','playlist','result',
                        'auth','version','update','add','clean','songs',
                        'artists','albums','tags','videos','api','playlists','catalogs');

    // Library static version information
    protected $LIB_version = '350001'; 
    private $API_version = ''; 

    private $DEBUG=false; 

    /**
     * Constructor
     * This takes an array of input, if enough information is provided then it will 
     * attempt to connect to the API right away, otherwise it will simply return an
     * object that can be later configured and then connected
     */
    public function __construct($config=array()) { 

        // See if we are setting debug first
        if ($config['debug']) { 
            $this->debug($config['debug']); 
        } 

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

        if ($this->debug) { echo "CONNECT:: Using $this->username / $this->password\n"; } 

        // Setup the handshake
        $results = array(); 
        $timestamp = time(); 
        $key = hash('sha256',$this->password); 
        $passphrase = hash('sha256',$timestamp . $key); 

        $options = array('timestamp'=>$timestamp,'auth'=>$passphrase,'version'=>$this->LIB_version,'user'=>$this->username); 

        $response = $this->send_command('handshake',$options); 

        $this->parse_response($response); 
        
        // We want the first response
        $data = $this->get_response(); 
        foreach ($data as $value) { 
            $results = array_merge($results,$value); 
        } 

        if (!$results['auth']) { 
            $this->set_state('error'); 
            return false; 
        } 
        $this->api_auth = $results['auth'];  
        $this->set_state('connected'); 
        // Define when we pulled this, it is not wine, it does
        // not get better with age
        $this->handshake_time = time(); 
        $this->handshake = $results; 

    } // connect

    /**
     * configure
     * This function takes an array of elements and configures the AmpaceApi object
     * it doesn't really do much more, it is it's own function so we can call it 
     * from the constructor or directly, if we so desire. 
     */
    public function configure($config=array()) { 

        if ($this->debug) { echo "CONFIGURE :: Checking Passed config options\n"; } 

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
        $this->api_url = ($this->api_secure ? 'https://' : 'http://') . $this->server . '/server/xml.server.php';  

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
    public function set_state($state) { 

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
     * info
     * Returns the information gathered by the handshake 
     * not raw so we can formated it if we wanted? 
     */
    public function info() { 

        if ($this->state() != 'CONNECTED') { 
            throw new Exception('AmpacheApi::info API in non-ready state, unable to return info'); 
        } 

        return $this->handshake; 

    } // info

    /**
     * send_command
     * This sends an API command, with options to the currently connected
     * host, and returns a nice clean keyed array 
     */
    public function send_command($command,$options=array()) { 
        
        if ($this->debug) { echo "SEND COMMAND:: $command"; print_r($options,1); echo "\n"; } 

        if ($this->state() != 'READY' AND $this->state() != 'CONNECTED') { 
            throw new Exception('AmpacheApi::send_command API in non-ready state, unable to send');
        } 
        if (!trim($command)) { 
            throw new Exception('AmpacheApi::send_command no command specified'); 
        }     
        if (!$this->validate_command($command)) { 
            throw new Exception('AmpacheApi::send_command Invalid/Unknown command ' . $command . ' issued'); 
        } 

        $url = $this->api_url . '?action=' . urlencode($command); 

        foreach ($options as $key=>$value) { 
            if (!trim($key)) { 
                // Non fatal don't need to except it
                trigger_error('AmpacheApi::send_command unable to append empty variable to command'); 
                continue; 
            } 
            $url .= '&' . urlencode($key) . '=' . urlencode($value); 
        } 

        // IF Auth is set then we append it so you don't have to think about it, also do username
        if ($this->api_auth) { 
            $url .= '&auth=' . urlencode($this->api_auth) . '&username=' . urlencode($this->username); 
        } 

        $data = file_get_contents($url); 
        return $data; 

    } // send_command

    /**
     * validate_command
     * This takes the specified command, and checks it against the known
     * commands for the current version of Ampache. If no version is known yet
     * This it will return FALSE for everything except ping and handshake. 
     */
    public function validate_command($command) { 

        return true; 

    } // validate_command

    /**
     * parse_response
     * This takes an XML document and dumps it into $this->results but before
     * it does that it will clean up anything that was there before, so I hope
     * you've saved!
     */
    public function parse_response($response) { 

        // Reset the results
        $this->XML_results = array(); 
        $this->XML_position = 0; 
        
        $this->XML_create_parser(); 

        if (!xml_parse($this->XML_parser,$response)) { 
            throw new Exception('AmpacheApi::parse_response was unable to parse XML document'); 
        } 

        xml_parser_free($this->XML_parser); 
        return true; 

    } // parse_response

    /**
     * get_response
     * This returns the raw response from the last parsed response
     */
    public function get_response() { 

        return $this->XML_results; 

    } // get_response

    /**
     * debug
     * set debug to true?
     */
    private function debug($value) { 

        $this->debug = intval($value); 

    } // debug

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

        $cdata = trim($cdata); 

        if (!$this->XML_currentTag || !$cdata) { return false; } 

        if ($this->XML_subTag) { 
            $this->XML_results[$this->XML_position][$this->XML_currentTag][$this->XML_subTag] = $cdata; 
        } 
        else { 
            $this->XML_results[$this->XML_position][$this->XML_currentTag] = $cdata; 
        } 


    } // XML_cdata

    public function XML_start_element($parser,$tag,$attributes) { 

        // Skip it!
        if (in_array($tag,$this->XML_skiptags)) { return false; } 
        
        if (!in_array($tag,$this->XML_parenttags) OR $this->XML_currentTag) { 
            $this->XML_subTag = $tag; 
        } 
        else {     
            $this->XML_currentTag = $tag; 
        } 

        if (count($attributes)) { 
            if (!$this->XML_subTag) { 
                $this->XML_results[$this->XML_position][$this->XML_currentTag]['self'] = $attributes; 
            }
            else { 
                $this->XML_results[$this->XML_position][$this->XML_currentTag][$this->XML_subTag]['self'] = $attributes; 
            }
        } 

    } // start_element

    public function XML_end_element($parser,$tag) { 

        if ($tag != $this->XML_currentTag) { 
            $this->XML_subTag = false; 
        } 
        else { 
            $this->XML_currentTag = false; 
            $this->XML_position++; 
        } 


    } // end_element

} // end AmpacheApi class
?>
