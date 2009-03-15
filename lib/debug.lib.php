<?php
/*

 Copyright (c) Ampache.org
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
	if (!$host || !$username) { 
		return false;
	}
	
	return $dbh; 

} // check_database

/**
 * check_database_inserted
 * checks to make sure that you have inserted the database 
 * and that the user you are using has access to it
 */
function check_database_inserted($dbh,$db_name) { 

	$sql = "DESCRIBE session";
	$db_results = Dba::query($sql);

	if (!$db_results) { 
		return false; 
	} 

	// Make sure the whole table is there
	if (Dba::num_rows($db_results) != '7') { 
		return false;
	}

	return true;

} // check_database_inserted

/**
 * check_php_ver
 * checks the php version and makes
 * sure that it's good enough
 */
function check_php_ver($level=0) {

	if (floatval(phpversion()) < 5.1) {
		return false;
	}
	
	// Poor windows users if only their OS wasn't behind the times
	if (strtoupper(substr(PHP_OS,0,3)) == 'WIN' AND floatval(phpversion()) < 5.3) {
		return false; 
	} 

	// Make sure that they have the sha256() algo installed
	if (!function_exists('hash_algos')) { return false; } 
	$algos = hash_algos(); 

	if (!in_array('sha256',$algos)) { 
		return false; 
	} 

	return true;

} // check_php_ver

/**
 * check_php_mysql
 * checks for mysql support by looking for the mysql_query function
 */
function check_php_mysql() { 

	if (!function_exists('mysql_query')) { 
		return false;
	}

	return true;

} // check_php_mysql

/**
 * check_php_session
 * checks to make sure the needed functions 
 * for sessions exist
*/
function check_php_session() {

	if (!function_exists('session_set_save_handler')) { 
		return false;
	}

	return true;

} // check_php_session

/**
 * check_php_iconv
 * checks to see if you have iconv installed
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

/**
 * check_config_values
 * checks to make sure that they have at least set the needed variables
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
	$new_limit = ($current+16) . "M";
	
	/* Bump it by 16 megs (for getid3)*/
	if (!ini_set(memory_limit,$new_limit)) { 
		return false; 
	}

	// Make sure it actually worked
	$current = ini_get('memory_limit'); 

	if ($new_limit != $current) { 
		return false; 
	} 
	
	/* Check if safe mode is on */
	if (ini_get('safe_mode')) { 
		return false; 
	}

	// See if we can override the set_time_limit(); 


	return true;

} // check_putenv

/**
 * check_gettext
 * This checks to see if you've got gettext installed
 */
function check_gettext() { 

	if (!function_exists('gettext')) { 
		return false; 
	} 

	return true; 

} // check_gettext

/**
 * check_mbstring
 * This checks for mbstring support
 */
function check_mbstring() { 

	if (!function_exists('mb_check_encoding')) { 
		return false; 
	} 

	return true; 

} // check_mbstring 

/**
 * generate_config
 * This takes an array of results and re-generates the config file
 * this is used by the installer and by the admin/system page
 */
function generate_config($current) { 

	/* Start building the new config file */
	$distfile = Config::get('prefix') . '/config/ampache.cfg.php.dist';
        $handle = fopen($distfile,'r');
        $dist = fread($handle,filesize($distfile));
        fclose($handle);

        $data = explode("\n",$dist);

        /* Run throught the lines and set our settings */
        foreach ($data as $line) {

	        /* Attempt to pull out Key */
	        if (preg_match("/^;?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$line,$matches)
			|| preg_match("/^;?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $line, $matches)
	                || preg_match("/^;?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$line,$matches)) {

			$key    = $matches[1];
	        	$value  = $matches[2];

	                /* Put in the current value */
			if ($key == 'config_version') { 
				$line = $key . ' = ' . $value; 
			} 
	                elseif (isset($current[$key])) {
	                	$line = $key . ' = "' . $current[$key] . '"';
	                        unset($current[$key]);
			} // if set
			
		} // if key

	        $final .= $line . "\n";

	} // end foreach line

	return $final; 

} // generate_config

/**
 * debug_ok
 * Return an "OK" with the specified string
 */
function debug_result($comment,$status=false,$value=false) { 

	$class = $status ? 'ok' : 'notok'; 
	if (!$value) { 
		$value = $status ? 'OK' : 'ERROR'; 
	} 

	$final = '<span class="' . $class . '">' . scrub_out($value) . '</span> <em>' . $comment . '</em>'; 

	return $final;

} // debug_ok
?>
