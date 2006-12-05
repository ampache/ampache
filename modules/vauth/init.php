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
 * init script
 * This script requires all of the additional libraries and does a little error checking to 
 * make sure that we've got the variables we need to make everything work. 
 * Be default you should include this file then call the vauth_init() function
 * passing in an array of the elements we need (see more docs that in theory I'll write)
 */

/**
 * vauth_init
 * This function loads in the extra lib files and checks the data we've got
 * If it doesn't find everything it needs it will return use PHP's Error method
 * to throw an exception and return false
 */
function vauth_init($data) { 

	/* Check for the variables we are going to need first */
	if (isset($data['auth_methods']['mysql'])) { 	
		if (!isset($data['mysql_hostname'])) { 
			vauth_error('No Mysql Hostname Defined [mysql_hostname]');
			$error_status = true;
		}
		if (!isset($data['mysql_db'])) { 
			vauth_error('No Mysql Database Defined [mysql_db]');
			$error_status = true;
		}
		if (!isset($data['mysql_username'])) { 
			vauth_error('No Mysql Username Defined [mysql_username]');
			$error_status = true;
		}
		if (!isset($data['mysql_password'])) { 
			vauth_error('No Mysql Password Defined [mysql_password]');
			$error_status = true;
		}
	} // if we're doing mysql auth

	if (isset($data['auth_methods']['ldap'])) { 
		
		if (!isset($data['ldap_url'])) { 
			vauth_error('No LDAP server defined [ldap_url]');
			$error_status = true;
		}
		if (!isset($data['ldap_name_field'])) { 
			vauth_error('No Name Field defined [ldap_name_field]');
		}
		if (!isset($data['ldap_email_field'])) { 
			vauth_error('No E-mail Field defined [ldap_email_field]');
		}
		if (!isset($data['ldap_username'])) { 
			vauth_error('No Bind Username defined [ldap_username]');
		}
		if (!isset($data['ldap_password'])) { 
			vauth_error('No Bind Password defined [ldap_password]');
		}

	} // if we're doing ldap auth

	if (isset($data['auth_methods']['http'])) { 
	

	} // if we're doing http auth

	if (!isset($data['stop_auth'])) { 
		vauth_error('No Stop File Defined [stop_auth]');
		$error_status = true;
	}

	if (!isset($data['session_length'])) { 
		vauth_error('No Session Length Defined [session_length]');
		$error_status = true;
	}

	if (!isset($data['session_name'])) { 
		vauth_error('No Session Name Defined [session_name]');
		$error_status = true;
	}

	if (!isset($data['cookie_life'])) { 
		vauth_error('No Cookie Life Defined [cookie_life]');
		$error_status = true;
	}

	if (!isset($data['cookie_secure'])) { 
		vauth_error('Cookie Secure Not Defined [cookie_secure]');
		$error_status = true;
	}

	if (!isset($data['cookie_path'])) { 
		vauth_error('Cookie Path Not Defined [cookie_path]');
		$error_status = true;
	}
	
	if (!isset($data['cookie_domain'])) { 
		vauth_error('Cookie Domain Not Defined [cookie_domain]');
		$error_status = true;
	}

	/* For now we won't require it */
	if (!isset($data['remember_length'])) { 
		$data['remember_length'] = '900';
	}
	
	/* If an error has occured then return false */
	if (isset($error_status)) { return false; }

	/* Load the additional libraries that we may or may not need... */
	require_once 'dbh.lib.php';
	require_once 'session.lib.php';
	require_once 'auth.lib.php';

	vauth_conf($data);
	
	return true;

} // vauth_init

/**
 * vauth_error
 * This function throws a PHP error with whatever went wrong. If you don't use a custom
 * Error handler this will get spit out the screen, otherwise well whatever you do with it
 * is what is going to happen to it... amazing huh!
 */
function vauth_error($string) { 

	trigger_error($string,E_USER_WARNING);
	return true;

} // vauth_error


/**
 * vauth_conf
 * This is a function with a static array that we store the configuration variables in
 * So we don't have to worry about globalizing anything
 */
function vauth_conf($param,$clobber=0) { 

        static $params = array();
	
	// We are trying to set variables
	if(is_array($param)) {
                foreach ($param as $key=>$val) {
			if(!$clobber && isset($params[$key])) {
                                vauth_error("Attempting to clobber $key = $val");
                                return false;
                        }
                        $params[$key] = $val;
                }
                return true;
        }
	// We are attempting to retrive a variable
        else {
                if(isset($params[$param])) return $params[$param];
                else return;
        }

} // vauth_conf

?>
