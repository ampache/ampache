<?php
/*

 Copyright (c) 2006 Karl Vollmer
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

	if (!is_resource(vauth_dbh())) { 
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
	if (!is_array($results)) { 
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
	$value 		= sql_escape($value);
	$key		= sql_escape($key);

	$sql = "UPDATE session SET value='$value', expire='$expire'" . 
		" WHERE id='$key'";
	$db_results = mysql_query($sql, vauth_dbh());

	return $db_results;

} // vauth_sess_write

/**
 * vauth_sess_destory
 * This removes the specified session from the database
 */
function vauth_sess_destory($key) { 

	$key = sql_escape($key);

	/* Remove any database entries */
	$sql = "DELETE FROM session WHERE id='$key'";
	$db_results = mysql_query($sql, vauth_dbh());

	/* Destory the Cookie */
	setcookie (vauth_conf('session_name'),'',time() - 86400);

	return true;

} // vauth_sess_destory

/**
 * vauth_sess_gc
 * This is the randomly called garbage collection function
 */
function vauth_sess_gc($maxlifetime) { 

	$sql = "DELETE FROM session WHERE expire < '" . time() . "'";
	$db_results = mysql_query($sql, vauth_dbh());

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

	$key	= sql_escape($key);

	$sql = "SELECT * FROM session WHERE id='$key' AND expire > '" . time() . "'";
	$db_results = mysql_query($sql, vauth_dbh());

	$results = mysql_fetch_assoc($db_results);

	return $results;

} // vauth_get_session


/**
 * vauth_session_create
 * This is called when you want to create a new session
 * It takes care of setting the initial cookie, and inserting the first chunk
 * of data
 */
function vauth_session_create($data) { 

	/* Set the Cookies Paramaters, this is very very important */
	$cookie_life 	= vauth_conf('cookie_life');
	$cookie_path 	= vauth_conf('cookie_path');
	$cookie_domain	= vauth_conf('cookie_domain');
	$cookie_secure	= vauth_conf('cookkie_secure');

	session_set_cookie_params($cookie_life,$cookie_path,$cookie_domain,$cookie_secure);

	session_name(vauth_conf('session_name'));

	/* Start the Session */
	session_start();

	/* Before a refresh we don't have the cookie, so use session_id() */
	$key = session_id();

	$username 	= sql_escape($data['username']);
	$type		= sql_escape($data['type']);
	$value		= sql_escape($data['value']);

	/* Insert the row */
	$sql = "INSERT INTO session (`id`,`username`,`type`,`value`) " . 
		" VALUES ('$key','$username','$type','$value')";
	$db_results = mysql_query($sql, vauth_dbh());

	return $db_results;

} // vauth_session_create

/**
 * vauth_check_session
 * This checks for an existing session, and if it's still there starts it and returns true 
 */
function vauth_check_session() { 

	/* Make sure we're still valid */
	$session_name = vauth_conf('session_name');
	
	$key = scrub_in($_COOKIE[$session_name]);

	$results = vauth_get_session($key);

	if (!is_array($results)) { 
		return false;
	}

	/* Check for Rememeber Me */
	$cookie_name = vauth_conf('session_name') . "_remember";
	if ($_COOKIE[$cookie_name]) { 
		$month = 86400*30;
		vauth_conf(array('cookie_life'=>$month),1);
	}

	/* Set the Cookie Paramaters */
        session_set_cookie_params(
                vauth_conf('cookie_life'),
                vauth_conf('cookie_path'),
                vauth_conf('cookie_domain'),
                vauth_conf('cookie_secure'));
	
	/* Set Session name so it knows what cookie to get */
	session_name($session_name);

	session_start();

	return true;

} // vauth_check_session

?>
