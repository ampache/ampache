<?php
/*

 Copyright (c) 2006 - 2007 Karl Vollmer
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

	/* Foreach Through the methods we are allowed to use */
	foreach (vauth_conf('auth_methods') as $method) { 

		/* Build Function name and call custom function */
		$function = 'vauth_' . $method . '_auth';	
		$results = $function($username,$password);

		/* If we find something break */
		if ($results['success']) { break; } 
	} // end foreach

	return $results;

} // authenticate


/**
 * vauth_mysql_auth
 * This functions does mysql authentication againsts a user table
 * That has a username and a password field change it if you don't like it! 
 */
function vauth_mysql_auth($username,$password) { 

	$username = Dba::escape($username);
	$password = Dba::escape($password);

        $password_check_sql = "PASSWORD('$password')";

	$sql = "SELECT `user`.`password`,`session`,`ip`,`user`,`id` FROM `user` " . 
		"LEFT JOIN `session` ON `session`.`username`=`user`.`username` " . 
		"WHERE `user`.`username`='$username'";
	$db_results = Dba::query($sql);
	$row = Dba::fetch_assoc($db_results);

	// If they don't have a password kick em ou
	if (!$row['password']) { 
		Error::add('general','Error Username or Password incorrect, please try again'); 
		return false; 
	} 

	if (Config::get('prevent_multiple_logins')) { 
		$client = new User($row['id']); 
		$ip = $client->is_logged_in(); 
		if ($current_ip != ip2int($_SERVER['REMOTE_ADDR'])) { 
			Error::add('general','User Already Logged in'; 
			return false; 
		} 


        $sql = "SELECT version()";
        $db_results = Dba::query($sql);
        $version = Dba::fetch_row($db_results);
        $mysql_version = substr(preg_replace("/(\d+)\.(\d+)\.(\d+).*/","$1$2$3",$version[0]),0,3);
	
	if ($mysql_version > "409" AND substr($row['password'],0,1) !== "*") {
	        $password_check_sql = "OLD_PASSWORD('$password')";
        }

	$sql = "SELECT username FROM user WHERE username='$username' AND password=$password_check_sql";
	$db_results = Dba::query($sql);

	$results = Dba::fetch_assoc($db_results);

	if (!$results) { 
		Error::add('general','Error Username or Password incorrect, please try again'); 
		return false; 
	}

	$results['type'] 	= 'mysql';
	$results['success']	= true;
	
	return $results;

} // vauth_mysql_auth

/**
 * vauth_ldap_auth
 * Step one, connect to the LDAP server and perform a search for teh username provided. 
 * If its found, attempt to bind using that username and the password provided.
 * Step two, figure out if they are authorized to use ampache:
 * TODO: need implimented still:
 * 	* require-group "The DN fetched from the LDAP directory (or the username passed by the client) occurs in the LDAP group"
 *      * require-dn "Grant access if the DN in the directive matches the DN fetched from the LDAP directory"
 *      * require-attribute "an attribute fetched from the LDAP directory matches the given value"
 */
function vauth_ldap_auth($username, $password) {

	$ldap_username	= vauth_conf('ldap_username');
	$ldap_password	= vauth_conf('ldap_password');

	/* Currently not implemented */
	$require_group	= vauth_conf('ldap_require_group'); 

	// This is the DN for the users (required)
    	$ldap_dn	= vauth_conf('ldap_search_dn');
    
	// This is the server url (required)
    	$ldap_url	= vauth_conf('ldap_url');

	// This is the ldap filter string (required)
	$ldap_filter	= vauth_conf('ldap_filter');
    	
	//This is the ldap objectclass (required)
	$ldap_class	= vauth_conf('ldap_objectclass');

	$ldap_name_field	= vauth_conf('ldap_name_field');
	$ldap_email_field	= vauth_conf('ldap_email_field');

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


} // vauth_ldap_auth


/**
 * vauth_http_auth
 * This auth method relies on HTTP auth from Apache
 * This is not a very secure method of authentication
 * defaulted to off. Because if they can load the page they
 * are considered to be authenticated we need to look and
 * see if their user exists and if not, by golly we just 
 * go ahead and created it. NOT SECURE!!!!!
 */
function vauth_http_auth($username) { 

	/* Check if the user exists */
	if ($user = new User($username)) { 
		$results['success'] 	= true;
		$results['type'] 	= 'mysql';
		$results['username'] 	= $username;
		$results['name']	= $user->fullname;
		$results['email']	= $user->email;
		return $results;
	}


	/* If not then we auto-create the entry as a user.. :S */
	$user->create($username,$username,'',md5(rand()),'25');
	$user = new User($username);	

	$results['success'] 	= true;
	$results['type'] 	= 'mysql';
	$results['username'] 	= $username;
	$results['name']	= $user->fullname;
	$results['email']	= $user->email;
	return $results;	

} // vauth_http_auth

?>
