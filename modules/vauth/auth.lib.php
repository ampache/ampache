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
 * Authenticate library
 * Yup!
 */

/**
 * authenticate
 * This takes a username and passwords and returns false on failure
 * on success it returns true, and the username + type in an array
 */
function authenticate($username,$password) { 

	/* Don't even try if stop auth is in place */
	if (file_exists(vauth_conf('stop_auth'))) { 
		return false;
	}

	/* Call the functions! */
	$results = vauth_mysql_auth($username,$password);

	return $results;

} // authenticate


/**
 * vauth_mysql_auth
 * This functions does mysql authentication againsts a user table
 * That has a username and a password field change it if you don't like it! 
 */
function vauth_mysql_auth($username,$password) { 

	$username = sql_escape($username);
	$password = sql_escape($password);

        $password_check_sql = "PASSWORD('$password')";

	$sql = "SELECT password FROM user WHERE username='$username'";
	$db_results = mysql_query($sql, vauth_dbh());
	$row = mysql_fetch_row($db_results);

        $sql = "SELECT version()";
        $db_results = mysql_query($sql, vauth_dbh());
        $version = mysql_fetch_row($db_results);
        $mysql_version = substr(preg_replace("/(\d+)\.(\d+)\.(\d+).*/","$1$2$3",$version[0]),0,3);
        
	if ($mysql_version > "409" AND substr($row[0],0,1) !== "*") {
	        $password_check_sql = "OLD_PASSWORD('$password')";
        }

	$sql = "SELECT username FROM user WHERE username='$username' AND password=$password_check_sql";
	$db_results = mysql_query($sql, vauth_dbh());

	$results = mysql_fetch_assoc($db_results);

	if (!$results) { 
		$results['success'] = false;
		$results['error'] = 'Error Username or Password incorrect, please try again';
		return $results;
	}

	$results['type'] 	= 'mysql';
	$results['success']	= true;
	
	return $results;

} // vauth_mysql_auth

?>
