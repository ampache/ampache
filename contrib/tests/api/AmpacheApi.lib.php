<?php 


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

} // end AmpacheApi class

?>
