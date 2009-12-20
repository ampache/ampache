<?php
/*

 Copyright (c) Ampache.org
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

		if (!is_array($results)) { 
			debug_event('SESSION','Unable to read session from key ' . $key . ', no data found','1'); 
			return false; 
		} 

		return $results['value']; 

	} // read

	/**
	 * write
	 * This saves the sessoin information into the database
	 */
	public static function write($key,$value) { 

		if (NO_SESSION_UPDATE == '1') { return true; } 

		$length		= Config::get('session_length'); 
		$value		= Dba::escape($value); 
		$key 		= Dba::escape($key); 
		// Check to see if remember me cookie is set, if so use remember length, otherwise use the session length
		$expire		= isset($_COOKIE[Config::get('session_name') . '_remember']) ? time() + Config::get('remember_length') : time() + Config::get('session_length');  

		$sql = "UPDATE `session` SET `value`='$value', `expire`='$expire' WHERE `id`='$key'"; 
		$db_results = Dba::query($sql); 

		debug_event('SESSION','Writing to ' . $key . ' with expire ' . $expire . ' ' . Dba::error(),'6'); 

		return $db_results; 

	} // write

	/**
	 * destroy
	 * This removes the specified session from the database
	 */
	public static function destroy($key) { 

		$key = Dba::escape($key); 

		if (!strlen($key)) { return false; } 

		// Remove anything and EVERYTHING
		$sql = "DELETE FROM `session` WHERE `id`='$key'"; 
		$db_results = Dba::query($sql); 

		debug_event('SESSION','Deleting Session with key:' . $key,'6'); 

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
		$db_results = Dba::write($sql); 

		$sql = "DELETE FROM `tmp_browse` USING `tmp_browse` LEFT JOIN `session` ON `session`.`id`=`tmp_browse`.`sid` " . 
			"WHERE `session`.`id` IS NULL"; 
		$db_results = Dba::write($sql); 

		return true; 

	} // gc

	/**
	 * logout
	 * This is called when you want to log out and nuke your session
	 * This is the function used for the Ajax logouts, if no id is passed
	 * it tries to find one from the session
	 */
	public static function logout($key='') { 

		// If no key is passed try to find the session id
		$key = $key ? $key : session_id(); 
		
		// Nuke the cookie before all else
		self::destroy($key); 

		// Do a quick check to see if this is an AJAX'd logout request
		// if so use the iframe to redirect
		if (AJAX_INCLUDE == '1') {
			ob_end_clean();
			ob_start();

			/* Set the correct headers */
			header("Content-type: text/xml; charset=" . Config::get('site_charset'));
			header("Content-Disposition: attachment; filename=ajax.xml");
			header("Expires: Tuesday, 27 Mar 1984 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Pragma: no-cache");

			$target = Config::get('web_path') . '/login.php';
			$results['rfc3514'] = '<script type="text/javascript">reload_logout("'.$target.'")</script>';
			echo xml_from_array($results);
		}


		/* Redirect them to the login page */
		if (AJAX_INCLUDE != '1') {
			header ('Location: ' . Config::get('web_path') . '/login.php');
		}

		exit; 

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

		session_name(Config::get('session_name')); 

		/* Start the session */
		self::ungimp_ie(); 
		session_start(); 

	} // create_cookie, just watch out for the cookie monster

	/**
	 * create_remember_cookie
	 * This function just creates the remember me cookie, nothing special
	 */
	public static function create_remember_cookie() { 

		$remember_length = Config::get('remember_length'); 
		$session_name = Config::get('session_name'); 

		Config::set('cookie_life',$remember_length,'1');
		setcookie($session_name . '_remember',"Rappelez-vous, rappelez-vous le 27 mars",time() + $remember_length,'/');

	} // create_remember_cookie

	/**
	 * session_create
	 * This is called when you want to create a new session
	 * it takes care of setting the initial cookie, and inserting the first chunk of 
	 * data, nifty ain't it!
	 */
	public static function session_create($data) { 

		// Regenerate the session ID to prevent fixation
		switch ($data['type']) { 
			case 'xml-rpc': 
			case 'api': 
				$key = md5(uniqid(rand(), true));
			break;	
			case 'mysql': 
			default: 
				session_regenerate_id(); 

				// Before refresh we don't have the cookie so we have to use session ID
				$key = session_id(); 
			break; 
		} // end switch on data type 
		
		$username       = Dba::escape($data['username']);
		$ip             = $_SERVER['REMOTE_ADDR'] ? Dba::escape(inet_pton($_SERVER['REMOTE_ADDR'])) : '0'; 
		$type           = Dba::escape($data['type']);
		$value          = Dba::escape($data['value']);
		$agent		= Dba::escape(substr($_SERVER['HTTP_USER_AGENT'],0,254)); 
		$expire         = Dba::escape(time() + Config::get('session_length'));

		/* We can't have null things here people */
		if (!strlen($value)) { $value = ' '; }

		/* Insert the row */
		$sql = "INSERT INTO `session` (`id`,`username`,`ip`,`type`,`agent`,`value`,`expire`) " .
			" VALUES ('$key','$username','$ip','$type','$agent','$value','$expire')";
		$db_results = Dba::query($sql);

		if (!$db_results) {
			debug_event('SESSION',"Session Creation Failed with Query: $sql and " . Dba::error(),'1');
			return false; 
		}

		debug_event('SESSION','Session Created:' . $key,'6'); 

		return $key;

	} // session_create

	/**
	 * check_session
	 * This checks for an existing sessoin and if it's still valid then go ahead and start it and return
	 * true
	 */
	public static function check_session() { 

		$session_name = Config::get('session_name'); 

		// No cookie n go!
		if (!isset($_COOKIE[$session_name])) { return false; }

		// Check for a remember me
		if (isset($_COOKIE[$session_name . '_remember'])) { 
			self::create_remember_cookie(); 
		} 

		// Setup the cookie params before we start the session this is vital
		session_set_cookie_params(
			Config::get('cookie_life'),
			Config::get('cookie_path'),
			Config::get('cookie_domain'),
			Config::get('cookie_secure')); 
		
		// Set name
		session_name($session_name); 

		// Ungimp IE and go
		self::ungimp_ie(); 
		session_start(); 

		return true; 

	} // check_session

	/**
	 * session_exists
	 * This checks to see if the specified session of the specified type
	 * exists, it also provides an array of key'd data that may be required
	 * based on the type
	 */
	public static function session_exists($type,$key,$data=array()) { 

		// Switch on the type they pass
		switch ($type) { 
			case 'xml-rpc': 
			case 'api': 
				$key = Dba::escape($key); 
				$time = time(); 
				$sql = "SELECT * FROM `session` WHERE `id`='$key' AND `expire` > '$time' AND `type`='$type'"; 
				$db_results = Dba::read($sql); 

				if (Dba::num_rows($db_results)) { 
					return true; 
				} 
			break; 
			//FIXME: This should use the IN() mojo and compare against enabled auths
			case 'interface':
				$key = Dba::escape($key); 
				$time = time(); 
				$sql = "SELECT * FROM `session` WHERE `id`='$key' AND `expire` > '$time' AND `type`!='api' AND `type`!='xml-rpc'"; 
				$db_results = Dba::read($sql); 

				if (Dba::num_rows($db_results)) { 
					return true; 
				} 
			break; 
			case 'stream': 
				$key	= Dba::escape($key); 
				$ip	= Dba::escape(inet_pton($data['ip'])); 
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
	 * session_extend
	 * This should really be extend_session but hey you gotta go with the flow
	 * this takes a SID and extends it's expire
	 */
	public static function session_extend($sid) { 

		$sid = Dba::escape($sid); 
		$expire = isset($_COOKIE[Config::get('session_name') . '_remember']) ? time() + Config::get('remember_length') : time() + Config::get('session_length');

		$sql = "UPDATE `session` SET `expire`='$expire' WHERE `id`='$sid'"; 
		$db_results = Dba::query($sql); 

		debug_event('SESSION','Session:' . $sid . ' Has been Extended to ' . $expire,'6'); 

		return $db_results; 

	} // session_extend

	/**
	 * _auto_init
	 * This function is called when the object is included, this sets up the session_save_handler
	 */
	public static function _auto_init() { 

		if (!function_exists('session_start')) { 
			header("Location:" . Config::get('web_path') . "/test.php"); 
			exit; 
		} 

		session_set_save_handler(array('vauth','open'),array('vauth','close'),array('vauth','read'),array('vauth','write'),array('vauth','destroy'),array('vauth','gc')); 

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

		if (strstr($agent,'MSIE') || strstr($agent,'Internet Explorer/')) { 
			session_cache_limiter('public'); 
		} 

		return true; 

	} // ungimp_ie

	/**
 	 * authenticate
	 * This takes a username and password and then returns true or false
	 * based on what happens when we try to do the auth then
	 */
	public static function authenticate($username,$password) { 

		// Foreach the auth methods
		foreach (Config::get('auth_methods') as $method) { 

			// Build the function name and call the custom method on this class
			$function_name = $method . '_auth'; 
			
			if (!method_exists('vauth',$function_name)) { continue; } 

			$results = self::$function_name($username,$password); 

			// If we achive victory return
			if ($results['success']) { break; } 

		} // end foreach 
		
		return $results; 

	} // authenticate

	/**
	 * mysql_auth
	 * This is the core function of authentication by ampache. It checks their current password
	 * and then tries to figure out if it can use the new SHA password hash or if it needs to fall
	 * back on the mysql method
	 */
	private static function mysql_auth($username,$password) { 

		$username = Dba::escape($username); 
		$password = Dba::escape($password); 

		if (!strlen($password) OR !strlen($username)) { 
			Error::add('general',_('Error Username or Password incorrect, please try again')); 
			return false; 
		} 

		// We have to pull the password in order to figure out how to handle it *cry*
		$sql = "SELECT `password` FROM `user` WHERE `username`='$username'"; 
		$db_results = Dba::read($sql); 
		$row = Dba::fetch_assoc($db_results); 

		// If it's using the old method then roll with that
		if (substr($row['password'],0,1) == '*' OR strlen($row['password']) < 32) { 
			$response = self::vieux_mysql_auth($username,$password); 
			return $response; 
		} 

		// Use SHA2 now... cooking with fire, SHA3 in 2012 *excitement*
		$password = hash('sha256',$password); 
	
		$sql = "SELECT `username`,`id` FROM `user` WHERE `password`='$password' AND `username`='$username'"; 	
		$db_results = Dba::read($sql); 

		$row = Dba::fetch_assoc($db_results); 

		if (!count($row)) { 
			Error::add('general',_('Error Username or Password incorrect, please try again')); 
			return false; 
		} 

		$row['type']	= 'mysql';
		$row['success']     = true;

		return $row;

	} // mysql_auth

	/**
 	 * vieux_mysql_auth
	 * This is a private function, it should only be called by authenticate
	 */
	private static function vieux_mysql_auth($username,$password) { 

		$password_check_sql = "PASSWORD('$password')";

		// This has to still be here because lots of people use old_password in their config file
		$sql = "SELECT `password` FROM `user` WHERE `username`='$username'"; 
		$db_results = Dba::query($sql); 
		$row = Dba::fetch_assoc($db_results); 

		$sql = "SELECT version()";
		$db_results = Dba::query($sql);
		$version = Dba::fetch_row($db_results);
		$mysql_version = substr(preg_replace("/(\d+)\.(\d+)\.(\d+).*/","$1$2$3",$version[0]),0,3);

		if ($mysql_version > "409" AND substr($row['password'],0,1) !== "*") {
			$password_check_sql = "OLD_PASSWORD('$password')";
		}

		$sql = "SELECT `username`,`id` FROM `user` WHERE `username`='$username' AND `password`=$password_check_sql";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		if (!$results) {
			Error::add('general',_('Error Username or Password incorrect, please try again'));
			return false;
		}

		if (Config::get('prevent_multiple_logins')) {
			$client = new User($results['id']);
			$current_ip = $client->is_logged_in();
			if ($current_ip AND $current_ip != inet_pton($_SERVER['REMOTE_ADDR'])) {
				debug_event('Login','Concurrent Login Failure, attempted to login from ' . $_SERVER['REMOTE_ADDR'] . ' and already logged in','1'); 
				Error::add('general','User Already Logged in');
				return false;
			}
		} // if prevent_multiple_logins

		$results['type']        = 'mysql';
		$results['password']	= 'old'; 
		$results['success']     = true;

		return $results;

	} // vieux_mysql_auth

	/**
	 * local_auth
	 * Check to make sure the pam_auth function is implemented (module is installed) then check the credentials
	 */
	private static function local_auth($username,$password) {
		if (!function_exists('pam_auth')) {
				$results['success'] = false;
				$results['error'] = "The PAM authentication PHP module is not installed.";
				return $results;
		}

		if (pam_auth($username, $password, &$results['error'])) {
			$results['success'] = true;
			$results['type'] = 'local';
			$results['username'] = $username;
		}
		else {
			$results['success'] = false;
			$results['error'] = "PAM login attempt failed";
		}

		return $results;
	} // local_auth

	/**
	 * ldap_auth
	 * Step one, connect to the LDAP server and perform a search for teh username provided. 
	 * If its found, attempt to bind using that username and the password provided.
	 * Step two, figure out if they are authorized to use ampache:
	 * TODO: need implimented still:
	 *      * require-group "The DN fetched from the LDAP directory (or the username passed by the client) occurs in the LDAP group"
	 *      * require-dn "Grant access if the DN in the directive matches the DN fetched from the LDAP directory"
	 *      * require-attribute "an attribute fetched from the LDAP directory matches the given value"
	 */
	private static function ldap_auth($username,$password) { 

		$ldap_username  = Config::get('ldap_username');
		$ldap_password  = Config::get('ldap_password');

		/* Currently not implemented */
		$require_group  = Config::get('ldap_require_group');

		// This is the DN for the users (required)
		$ldap_dn        = Config::get('ldap_search_dn');

		// This is the server url (required)
		$ldap_url       = Config::get('ldap_url');

		// This is the ldap filter string (required)
		$ldap_filter    = Config::get('ldap_filter');

		//This is the ldap objectclass (required)
		$ldap_class     = Config::get('ldap_objectclass');

		$ldap_name_field        = Config::get('ldap_name_field');
		$ldap_email_field       = Config::get('ldap_email_field');

		if ($ldap_link = ldap_connect($ldap_url) ) {

			/* Set to Protocol 3 */
			ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, 3);

			// bind using our auth, if we need to, for initial search for username
			if (!ldap_bind($ldap_link, $ldap_username, $ldap_password)) {
				$results['success'] = false;
				$results['error'] = "Could not bind to LDAP server.";
				return $results;
			} // If bind fails

			$sr = ldap_search($ldap_link, $ldap_dn, "(&(objectclass=$ldap_class)($ldap_filter=$username))");
			$info = ldap_get_entries($ldap_link, $sr);

			if ($info["count"] == 1) {
				$user_entry = ldap_first_entry($ldap_link, $sr);
				$user_dn    = ldap_get_dn($ldap_link, $user_entry);
				// bind using the user..
				$retval = ldap_bind($ldap_link, $user_dn, $password);

				if ($retval) {
					ldap_close($ldap_link);
					$results['success'] = true;
					$results['type'] = "ldap";
					$results['username'] = $username;
					$results['name'] = $info[0][$ldap_name_field][0];
					$results['email'] = $info[0][$ldap_email_field][0];

					return $results;

				} // if we get something good back

			} // if something was sent back 

		} // if failed connect 

		/* Default to bad news */
		$results['success'] = false;
		$results['error'] = "LDAP login attempt failed";
	
		return $results;

	} // ldap_auth

	/**
	 * http_auth
	 * This auth method relies on HTTP auth from Apache
	 * This is not a very secure method of authentication
	 * defaulted to off. Because if they can load the page they
	 * are considered to be authenticated we need to look and
	 * see if their user exists and if not, by golly we just 
	 * go ahead and created it. NOT SECURE!!!!!
	 */
	public static function http_auth($username) { 

		/* Check if the user exists */
		if ($user = User::get_from_username($username)) {
			$results['success']     = true;
			$results['type']	= 'mysql';
			$results['username']    = $username;
			$results['name']	= $user->fullname;
			$results['email']       = $user->email;
			return $results;
		}

		/* If not then we auto-create the entry as a user.. :S */
		$user_id = $user->create($username,$username,'',md5(rand()),'25');
		$user = new User($user_id);

		$results['success']     = true;
		$results['type']        = 'mysql';
		$results['username']    = $username;
		$results['name']        = $user->fullname;
		$results['email']       = $user->email;
		return $results;

	} // http_auth

} // end of vauth class

?>
