<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
 * Debug Library
 * This library is loaded when somehow our mojo has
 * been lost, it contains functions for checking sql
 * connections, web paths etc..
*/

/**
 * check_database
 *  checks the local mysql db and make sure life is good
 */
function check_database($host,$username,$pass) {
	
	$dbh = @mysql_connect($host, $username, $pass);

	if (!is_resource($dbh)) {
		return false;
	}
	if (!$host || !$username || !$pass) { 
		return false;
	}
	
	return $dbh; 

} // check_database

/*!
	@function check_database_inserted
	@discussion checks to make sure that you 
		have inserted the database and that the user
		you are using has access to it
*/
function check_database_inserted($dbh,$db_name) { 


	if (!@mysql_select_db($db_name,$dbh)) { 
		return false;
	}

	$sql = "DESCRIBE session";
	$db_results = @mysql_query($sql, $dbh);
	if (!@mysql_num_rows($db_results)) { 
		return false;
	}

	return true;

} // check_database_inserted

/*!
	@function check_php_ver
	@discussion checks the php version and makes
		sure that it's good enough
*/
function check_php_ver($level=0) {

	if (strcmp('5.0.0',phpversion()) > 0) {
		return false;
	}

	return true;

} // check_php_ver

/*!
	@function check_php_mysql
	@discussion checks for mysql support
*/
function check_php_mysql() { 

	if (!function_exists('mysql_query')) { 
		return false;
	}

	return true;

} // check_php_mysql

/*!
	@function check_php_session
	@discussion checks to make sure the needed functions 
		for sessions exist
*/
function check_php_session() {

	if (!function_exists('session_set_save_handler')) { 
		return false;
	}

	return true;

} // check_php_session

/*!
	@function check_php_iconv
	@discussion checks to see if you have iconv installed
*/
function check_php_iconv() { 

	if (!function_exists('iconv')) { 
		return false;
	}

	return true;

} // check_php_iconv

/**
 * check_php_pcre
 * This makes sure they have pcre (preg_???) support
 * compiled into PHP this is required!
 */
function check_php_pcre() { 

	if (!function_exists('preg_match')) { 
		return false;
	}

	return true; 

} // check_php_pcre

/*!
        @function check_config_values()
        @discussion checks to make sure that they have at 
                least set the needed variables
*/
function check_config_values($conf) { 
	
	if (!$conf['database_hostname']) { 
                return false;
        }
        if (!$conf['database_name']) { 
                return false;
        } 
        if (!$conf['database_username']) { 
                return false;
        } 
        if (!$conf['database_password']) { 
                return false;
        }
        if (!$conf['session_length']) { 
                return false;
        }
	if (!$conf['session_name']) { 
		return false;
	}
	if (!isset($conf['session_cookielife'])) { 
		return false;
	}
	if (!isset($conf['session_cookiesecure'])) { 
		return false;
	}
	if (isset($conf['debug'])) {
	    if (!isset($conf['log_path'])) {
		return false;
	    }
	}
	
        return true;

} // check_config_values

/**
 * check_putenv
 * This checks to see if we can manually set the
 * memory limit, and other putenvs we need for 
 * ampache to work correctly 
 */
function check_putenv() { 

	/* Check memory */
	$current = ini_get('memory_limit');
	$current = substr($current_memory,0,strlen($current_memory)-1);
	$new_limit = ($current+1) . "M";
	
	/* Bump it by one meg */
	if (!ini_set(memory_limit,$new_limit)) { 
		return false; 
	}
	
	/* Check if safe mode is on */
	if (ini_get('safe_mode')) { 
		return false; 
	}

	return true;

} // check_putenv

?>
