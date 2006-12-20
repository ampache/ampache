<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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


/*
	@header Debug Library
	This library is loaded when somehow our mojo has
	been lost, it contains functions for checking sql
	connections, web paths etc..
*/

/*!
	@function read_config_file
	@discussion checks to see if the config
		file is readable, overkill I know..
	@param level	0 is readable, 1 detailed info
*/


function read_config_file($file,$level=0) { 

	$fp = @fopen($file, 'r');
	
	if (!$level) { 
		return is_resource($fp);
	}


} // read_config_file

/*!
	@function check_database
	@discussion checks the local mysql db
		and make sure life is good
*/
function check_database($host,$username,$pass,$level=0) {
	
	$dbh = @mysql_connect($host, $username, $pass);

	if (!is_resource($dbh)) {
		$error['error_state'] = true;
		$error['mysql_error'] = mysql_errno() . ": " . mysql_error() . "\n";
	}
	if (!$host || !$username || !$pass) { 
		$error['error_state'] = true;
		$error['mysql_error'] .= "<br />HOST:$host<br />User:$username<br />Pass:$pass<br />";
	}
	
	if ($error['error_state']) { return false; }		

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

	if (strcmp('4.1.2',phpversion()) > 0) {
		$error['error_state'] = true;
		$error['php_ver'] = phpversion();
	}

	if ($error['error_state']) { return false; }

	return true;

} // check_php_ver

/*!
	@function check_php_mysql
	@discussion checks for mysql support
*/
function check_php_mysql() { 

	if (!function_exists('mysql_query')) { 
		$error['error_state'] 	= true;
		$error['php_mysql']	= false;
	}

	if ($error['error_state']) { return false; }

	return true;

} // check_php_mysql

/*!
	@function check_php_session
	@discussion checks to make sure the needed functions 
		for sessions exist
*/
function check_php_session() {

	if (!function_exists('session_set_save_handler')) { 
		$error['error_state']	= true;
		$error['php_session']	= false;
	}

	if ($error['error_state']) { return false; }

	return true;

} // check_php_session

/*!
	@function check_php_iconv
	@discussion checks to see if you have iconv installed
*/
function check_php_iconv() { 

	if (!function_exists('iconv')) { 
		$error['error_state'] 	= true;
		$error['php_iconv']	= false;
	}

	if ($error['error_state']) { return false; }

	return true;

} // check_php_iconv

/**
 * check_php_pcre
 * This makes sure they have pcre (preg_???) support
 * compiled into PHP this is required!
 */
function check_php_pcre() { 

	if (!function_exists('preg_match')) { 
		$error['error_state']	= true;
		$error['php_pcre']	= false;
	}

	if ($error['error_state']) { return false; } 

	return true; 

} // check_php_pcre

/*!
        @function check_config_values()
        @discussion checks to make sure that they have at 
                least set the needed variables
*/
function check_config_values($conf) { 
		$error = new Error();        
	if (!$conf['local_host']) { 
                return false;
        }
        if (!$conf['local_db']) { 
                return false;
        } 
        if (!$conf['local_username']) { 
                return false;
        } 
        if (!$conf['local_pass']) { 
                return false;
        }
        if (!$conf['local_length']) { 
                return false;
        }
	if (!$conf['sess_name']) { 
		return false;
	}
	if (!isset($conf['sess_cookielife'])) { 
		return false;
	}
	if (!isset($conf['sess_cookiesecure'])) { 
		return false;
	}
	if (isset($conf['debug'])) {
	    if (!isset($conf['log_path'])) {
		$error->add_error('log_path',_("You defined the option \"debug = on\" but didn't define a log path for the log to be stored"));
		return false;
	    }
	}
	
        return true;

} // check_config_values

/*!
	@function debug_read_config
	@discussion this is the same as the read config function
		except it will pull config values with a # before them
		(basicly adding a #config="value" check) and not
		ever dieing on a config file error
*/
function debug_read_config($config_file,$debug) { 

	$fp = @fopen($config_file,'r');
	if(!is_resource($fp)) return false;
	$file_data = fread($fp,filesize($config_file));
	fclose($fp);
    
	// explode the var by \n's
	$data = explode("\n",$file_data);
	if($debug) echo "<pre>";
	$count = 0;

	if (!count($data)) { 
		debug_event('debug_read_config','Error Unable to Read config file','1'); 	
		return false; 
	} 

	$results = array();
    
	foreach($data as $value) {
	        $count++;
        
	        $value = trim($value);
       
	        if (preg_match("/^#?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$value,$matches)
	                        || preg_match("/^#?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $value, $matches)
	                        || preg_match("/^#?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$value,$matches)) {


                	if (is_array($results[$matches[1]]) && isset($matches[2]) ) {
	                        if($debug) echo "Adding value <strong>$matches[2]</strong> to existing key <strong>$matches[1]</strong>\n";
	                        array_push($results[$matches[1]], $matches[2]);
	                }

	                elseif (isset($results[$matches[1]]) && isset($matches[2]) ) {
	                        if($debug) echo "Adding value <strong>$matches[2]</strong> to existing key $matches[1]</strong>\n";
        	                $results[$matches[1]] = array($results[$matches[1]],$matches[2]);
	                }

	                elseif ($matches[2] !== "") {
	                        if($debug) echo "Adding value <strong>$matches[2]</strong> for key <strong>$matches[1]</strong>\n";
	                        $results[$matches[1]] = $matches[2];
        	        }

	                // if there is something there and it's not a comment
	                elseif ($value{0} !== "#" AND strlen(trim($value)) > 0 AND !$test AND strlen($matches[2]) > 0) {
        	                echo "Error Invalid Config Entry --> Line:$count"; return false;
	                } // elseif it's not a comment and there is something there
	
	                else {
	                        if($debug) echo "Key <strong>$matches[1]</strong> defined, but no value set\n";
	                }

        	} // end else

	} // foreach

	if (isset($config_name) && isset(${$config_name}) && count(${$config_name})) {
		$results[$config_name] = ${$config_name};
	}

	if($debug) echo "</pre>";

	return $results;

} // debug_read_config

/*!
	@function debug_compare_configs
	@discussion this takes two config files, and then compares
		the results and returns an array of the values
		that are missing from the first one passed
*/
function debug_compare_configs($config,$dist_config) { 

	

	/* Get the results from the two difference configs including #'d values */
	$results 	= debug_read_config($config,0);
	$dist_results 	= debug_read_config($dist_config,0);

	$missing = array();

	foreach ($dist_results as $key=>$value) { 

		if (!isset($results[$key])) { 
			/* If it's an array we need to split it out */
			if (is_array($value)) { 
				foreach ($value as $element) { 
					$missing[$key][] = $element; 
				}
			}
			else { 
				$missing[$key] = $value;
			} // end else not array
		} // if it's not set 
		
	} // end foreach conf

	return $missing;

} // debug_compare_configs


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
