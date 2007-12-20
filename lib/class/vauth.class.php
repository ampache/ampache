<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All Rights Reserved

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
/** 
 * Vauth
 * This class handles all of the session related stuff in Ampache
 * it takes over for the vauth libs, and takes some stuff out of other
 * classes where it didn't belong
*/
class vauth {

	/* Variables from DB */


	/**
	 * Constructor
	 * This should never be called
	 */
	private function __construct() { 

		// Rien a faire

	} // __construct

	/**
	 * open
	 * This function is for opening a new session so we just verify that we have
	 * a database connection, nothing more is needed
	 */
	public static function open($save_path,$session_name) { 

		if (!is_resource(Dba::dbh())) { 
			debug_event('SESSION','Error no database connection session failed','1'); 
			return false; 
		} 

		return true; 

	} // open

	/**
	 * close
	 * This is run on the end of a sessoin, nothing to do here for now
	 */
	public static function close() { 

		return true; 

	} // close

	/**
	 * read
	 * This takes a key and then looks in the database and returns the value
	 */
	public static function read($key) { 

		$results = self::get_session_data($key); 
		if (strlen($results['value']) < 1) { 
			debug_event('SESSION','Error unable to read session from key ' . $key . ' no data found','1'); 
			return ''; 
		} 

		return $results['value']; 

	} // read

	/**
	 * write
	 * This saves the sessoin information into the database
	 */
	public static function write($key,$value) { 


		$length		= Config::get('session_length'); 
		$value		= Dba::escape($value); 
		$key 		= Dba::escape($key); 
		// Check to see if remember me cookie is set, if so use remember length, otherwise use the session length
		$expire		= isset($_COOKIE[Config::get('session_name') . '_remember']) ? time() + Config::get('remember_length') : time() + Config::get('session_length');  

		$sql = "UPDATE `session` SET `value`='$value', `expire`='$expire' WHERE `id`='$key'"; 
		$db_results = Dba::query($sql); 

		return $db_results; 

	} // write

	/**
	 * destroy
	 * This removes the specified sessoin from the database
	 */
	public static function destroy($key) { 

		$key = Dba::escape($key); 

		// Remove anything and EVERYTHING
		$sql = "DELETE FROM `session` WHERE `id`='$key'"; 
		$db_results = Dba::query($sql); 

		// Destory our cookie!
		setcookie(Config::get('session_name'),'',time() - 86400); 

		return true;

	} // destroy

	/**
	 * gc
	 * This function is randomly called and it cleans up the poo
	 */
	public static function gc($maxlifetime) { 

		$sql = "DELETE FROM `session` WHERE `expire` < '" . time() . "'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // gc

	/**
	 * logout
	 * This is called when you want to log out and nuke your session
	 * //FIXME: move all logout logic here
	 */
	public static function logout($key) { 

		self::destroy($key); 
		return true; 

	} // logout

	/**
	 * get_session_data
	 * This takes a key and returns the raw data from the database, nothing to
	 * see here move along people
	 */
	public static function get_session_data($key) { 

		$key = Dba::escape($key); 

		$sql = "SELECT * FROM `session` WHERE `id`='$key' AND `expire` > '" . time() . "'"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		if (!count($results)) { 
			return false; 
		} 

		return $results; 

	} // get_session_data

	/**
	 * create_cookie
	 * This is seperated into it's own function because of some flaws in specific
	 * webservers *cough* IIS *cough* which prevent us from setting a cookie at the
	 * same time as a header redirect. As such on view of a login a cookie is set with
	 * the proper name
	 */
	public static function create_cookie() { 

		/* Setup the cookie prefs before we throw down, this is very important */
		$cookie_life	= Config::get('cookie_life'); 
		$cookie_path	= Config::get('cookie_path'); 
		$cookie_domain	= false; 
		$cookie_secure	= Config::get('cookie_secure'); 

		session_set_cookie_params($cookie_life,$cookie_path,$cookie_domain,$cookie_secure); 

		/* Start the session */
		self::ungimp_ie(); 
		session_start(); 

	} // create_cookie, just watch out for the cookie monster

	/**
	 * session_create
	 * This is called when you want to create a new session
	 * it takes care of setting the initial cookie, and inserting the first chunk of 
	 * data, nifty ain't it!
	 */
	public static function session_create($data) { 

		// Regenerate the session ID to prevent fixation
		session_regenerate_id(); 
	
		// Create our cookie!
		self::create_cookie(); 

		// Before refresh we don't have the cookie so we have to use session ID
		$key = session_id(); 
		
	        $username       = Dba::escape($data['username']);
	        $ip             = Dba::escape(ip2int($_SERVER['REMOTE_ADDR']));
	        $type           = Dba::escape($data['type']);
	        $value          = Dba::escape($data['value']);
		$agent		= Dba::escape($_SERVER['HTTP_USER_AGENT']); 
	        $expire         = Dba::escape(time() + vauth_conf('session_length'));

	        /* We can't have null things here people */
	        if (!strlen($value)) { $value = ' '; }

	        /* Insert the row */
	        $sql = "INSERT INTO `session` (`id`,`username`,`ip`,`type`,`agent`,`value`,`expire`) " .
	                " VALUES ('$key','$username','$ip','$type','$agent','$value','$expire')";
	        $db_results = Dba::query($sql);

	        if (!$db_results) {
	                debug_event('SESSION',"Session Creation Failed with Query: $sql and " . Dba::error(),'1');
	        }

	        return $db_results;

	} // session_create

	/**
	 * check_session
	 * This checks for an existing sessoin and if it's still valid then go ahead and start it and return
	 * true
	 */
	public static function check_session() { 

		// No cookie n go!
		if (!isset($_COOKIE[Config::get('session_name')]) { return false; }

		$key = scrub_in($_COOKIE[Config::get('session_name')]); 
		$data = self::get_session_data($key); 

		if (!is_array($results)) { 
			return false; 
		} 

		// Check for a remember me
		if (isset($_COOKIE[Config::get('session_name') . '_remember'])) { 
			Config::set('cookie_life',Config::get('remember_length'),'1'); 
			setcookie(Config::get('session_name') . '_remember',time() + Config::get('remember_length'),'/',Config::get('cookie_domain')); 
		} 

		// Setup the cookie params before we start the session this is vital
		session_set_cookie_params(
			Config::get('cookie_life'),
			Config::get('cookie_path'),
			Config::get('cookie_domain'),
			Config::get('cookie_secure')); 
		
		// Set name
		session_name(Config::get('session_name')); 

		// Ungimp IE and go
		self::ungimp_io(); 
		session_start(); 

		return true; 

	} // check_session

	/**
	 * session_exists
	 * This checks to see if the specified session of the specified type
	 * exists, it also provides an array of key'd data that may be required
	 * based on the type
	 */
	public static function session_exists($data,$key,$type) { 

		// Switch on the type they pass
		switch ($type) { 
			case 'xml-rpc': 
			case 'interface':
			case 'api': 
				$key = Dba::escape($key); 
				$time = time(); 
				$sql = "SELECT * FROM `session` WHERE `id`='$key' AND `expire` > '$time' AND `type`='$type'"; 
				$db_results = Dba::query($sql); 

				if (Dba::num_rows($db_results)) { 
					return true; 
				} 
			break; 
			case 'stream': 
				$key	= Dba::escape($key); 
				$ip	= ip2int($data['ip']);
				$agent	= Dba::escape($data['agent']); 
				$sql = "SELECT * FROM `session_stream` WHERE `id`='$key' AND `expire` > '$time' AND `ip`='$ip' AND `agent`='$agent'"; 
				$db_results = Dba::query($sql); 

				if (Dba::num_rows($db_results)) { 
					return true; 
				} 
				
			break; 
			default: 
				return false; 
			break; 
		} // type

		// Default to false
		return false; 

	} // session_exists

	/**
	 * _auto_init
	 * This function is called when the object is included, this sets up the session_save_handler
	 */
	public static function _auto_init() { 

		session_set_save_handler('vauth::open','vauth::close','vauth::read','vauth::write','vauth::destroy','vauth::gc'); 

	} // auto init

	/**
	 * ungimp_ie
	 * This function sets the cache limiting to public if you are running
	 * some flavor of IE. The detection used here is very conservative so feel free
	 * to fix it. This only has to be done if we're rolling HTTPS
	 */
	public static function ungimp_ie() { 

		// If no https, no ungimpage required
		if ($_SERVER['HTTPS'] != 'on') { return true; } 

		// Try to detect IE
		$agent = trim($_SERVER['HTTP_USER_AGENT']); 

		if ((preg_match('|MSIE ([0-9).]+)|',$agent)) || preg_match('|Internet Explorer/([0-9.]+)|',$agent))) { 
			session_cache_limiter('public'); 
		} 

		return true; 

	} // ungimp_ie

} // end of vauth class

?>
