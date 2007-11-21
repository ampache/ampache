<?php
/*

 Copyright (c) 2006 - 2007 Karl Vollmer
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

/**
 * Session Library
 * This sets up the custom session handler mojo
 * and then contains the functions that the session handler references
 */

/* Always register the customer handler */
session_set_save_handler(
	'vauth_sess_open',
	'vauth_sess_close',
	'vauth_sess_read',
	'vauth_sess_write',
	'vauth_sess_destory',
	'vauth_sess_gc');

/** 
 * vauth_sess_open
 * This is the function for opening a new session, we just verify that we have a 
 * database connection, nothing more (since this is a dbh session handler 
 */
function vauth_sess_open($save_path,$session_name) { 

	if (!is_resource(Dba::dbh())) { 
		vauth_error('Session open failed, no database handle');
		return false;
	}

	return true;

} // vauth_sess_open

/**
 * vauth_sess_close
 * Placeholder function, don't have anything to do in this one for now
 */
function vauth_sess_close() { 
	return true;
} // vauth_sess_close

/**
 * vauth_sess_read
 * Takes a Key and looks in the database, and returns the value
 */
function vauth_sess_read($key) { 

	$results = vauth_get_session($key);
	if (isset($results['value']) AND strlen($results['value']) < 1) { 
		vauth_error('Unable to read session data');
		return '';
	}
	
	/* Return the value column from the db */
	return $results['value'];

} // vauth_sess_read

/**
 * vauth_sess_write
 * Saves the session information to the database
 */
function vauth_sess_write($key,$value) { 

	$length 	= vauth_conf('session_length');
	$expire 	= time() + intval($length);
	$value 		= Dba::escape($value);
	$key		= Dba::escape($key);

        /* Check for Rememeber Me */
        $cookie_name = vauth_conf('session_name') . "_remember";
        if (isset($_COOKIE[$cookie_name])) {
		$expire = time() + vauth_conf('remember_length');		
	}

	$sql = "UPDATE session SET value='$value', expire='$expire'" . 
		" WHERE id='$key'";
	$db_results = Dba::query($sql);

	return $db_results;

} // vauth_sess_write

/**
 * vauth_sess_destory
 * This removes the specified session from the database
 */
function vauth_sess_destory($key) { 

	$key = Dba::escape($key);

	/* Remove any database entries */
	$sql = "DELETE FROM `session` WHERE `id`='$key'";
	$db_results = Dba::query($sql);

	/* Destory the Cookie */
	setcookie (vauth_conf('session_name'),'',time() - 86400);

	return true;

} // vauth_sess_destory

/**
 * vauth_sess_gc
 * This is the randomly called garbage collection function
 */
function vauth_sess_gc($maxlifetime) { 

	$sql = "DELETE FROM `session` WHERE `expire` < '" . time() . "'";
	$db_results = Dba::query($sql);

	// Randomly collect the api session table
	$sql = "DELETE FROM `session_api` WHERE `expire` < '" . time() . "'"; 
	$db_results = Dba::query($sql); 

	return true;

} // vauth_sess_gc

/**
 * vauth_logout
 * This logs you out of your vauth session
 */
function vauth_logout($key) { 

	vauth_sess_destory($key);
	return true;

} // vauth_logout

/**
 * vauth_get_session
 * This returns the data for the specified session
 */
function vauth_get_session($key) { 

	$key	= Dba::escape($key);

	$sql = "SELECT * FROM `session` WHERE `id`='$key' AND `expire` > '" . time() . "'";
	$db_results = Dba::query($sql);

	$results = Dba::fetch_assoc($db_results);

	if (!count($results)) { 
		return false; 
	}

	return $results;

} // vauth_get_session

/**
 * vauth_session_cookie
 * This is seperated into it's own cookie because of some flaws in specific
 * webservers *cough* IIS *cough* which prevent us from setting at cookie
 * at the same time as a header redirect. As such on login view a cookie is set
 */
function vauth_session_cookie() { 

        /* Set the Cookies Paramaters, this is very very important */
        $cookie_life    = vauth_conf('cookie_life');
        $cookie_path    = vauth_conf('cookie_path');
	$cookie_domain	= false;
        $cookie_secure  = vauth_conf('cookie_secure');
        
        session_set_cookie_params($cookie_life,$cookie_path,$cookie_domain,$cookie_secure);

        session_name(vauth_conf('session_name'));

        /* Start the Session */
	vauth_ungimp_ie();
        session_start(); 	

} // vauth_session_cookie

/**
 * vauth_session_create
 * This is called when you want to create a new session
 * It takes care of setting the initial cookie, and inserting the first chunk
 * of data
 */
function vauth_session_create($data) { 
	
	// Regenerate the session ID to prevent fixation
	session_regenerate_id();

	/* function that creates the cookie for us */
	vauth_session_cookie();

	/* Before a refresh we don't have the cookie, so use session_id() */
	$key = session_id();

	$username 	= Dba::escape($data['username']);
	$ip		= Dba::escape(ip2int($_SERVER['REMOTE_ADDR'])); 
	$type		= Dba::escape($data['type']);
	$value		= Dba::escape($data['value']);
	$expire		= Dba::escape(time() + vauth_conf('session_length'));

	/* We can't have null things here people */
	if (!strlen($value)) { $value = ' '; } 

	/* Insert the row */
	$sql = "INSERT INTO session (`id`,`username`,`ip`,`type`,`value`,`expire`) " . 
		" VALUES ('$key','$username','$ip','$type','$value','$expire')";
	$db_results = Dba::query($sql);

	if (!$db_results) { 
		vauth_error("Session Creation Failed with Query: $sql and " . mysql_error());
	}

	return $db_results;

} // vauth_session_create

/**
 * vauth_check_session
 * This checks for an existing session, and if it's still there starts it and returns true 
 */
function vauth_check_session() { 

	/* Make sure we're still valid */
	$session_name = vauth_conf('session_name');
	
	if (!isset($_COOKIE[$session_name])) { return false; } 
	
	$key = scrub_in($_COOKIE[$session_name]);
	$results = vauth_get_session($key);
	
	if (!is_array($results)) { 
		return false;
	}

	/* Check for Rememeber Me */
	$cookie_name = vauth_conf('session_name') . "_remember";
	if (isset($_COOKIE[$cookie_name])) { 
		$extended = vauth_conf('remember_length');
		vauth_conf(array('cookie_life'=>$extended),1);
		setcookie($cookie_name, '1', time() + $extended,'/',vauth_conf('cookie_domain'));
	}

	/* Set the Cookie Paramaters */
        session_set_cookie_params(
                vauth_conf('cookie_life'),
                vauth_conf('cookie_path'),
                vauth_conf('cookie_domain'),
                vauth_conf('cookie_secure'));
	
	/* Set Session name so it knows what cookie to get */
	session_name($session_name);

	vauth_ungimp_ie();
	session_start();
	
	return true;

} // vauth_check_session

/**
 * vauth_ungimp_ie
 * This function sets the cache limiting to public if you are running 
 * some flavor of IE. The detection used here is very conservative so feel free
 * to fix it. This only has to be done if we're rolling HTTPS
 */
function vauth_ungimp_ie() { 

	if ($_SERVER['HTTPS'] != 'on') { return true; } 

	/* Now try to detect IE */
	$agent = trim($_SERVER['HTTP_USER_AGENT']);
	
	if ((preg_match('|MSIE ([0-9.]+)|', $agent)) || (preg_match('|Internet Explorer/([0-9.]+)|', $agent))) {
		session_cache_limiter('public');
	}
		      
	return true;

} // vauth_ungimp_ie

?>
