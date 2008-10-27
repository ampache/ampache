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

/*!
	@function log_event
        @discussion logs an event either to a database
        	or to a defined log file based on config options
*/
function log_event($username='Unknown',$event_name,$event_description,$log_name='ampache') { 
	/* Set it up here to make sure it's _always_ the same */
        $log_time = time();

	/* must have some name */
	if (!strlen($log_name)) { $log_name = 'ampache'; } 

	$username = $username ? $username : 'ampache'; 

        $log_filename   = Config::get('log_path') . "/$log_name." . date("Ymd",$log_time) . ".log";
        $log_line       = date("Y-m-d H:i:s",$log_time) . " [$username] ($event_name) -> $event_description \n";  

	$log_write = error_log($log_line, 3, $log_filename);
	
	if (!$log_write) { 
		echo "Warning: Unable to write to log ($log_filename) Please check your log_path variable in ampache.cfg.php";
	}

} // log_event

/*!
	@function ampache_error_handler
	@discussion an error handler for ampache that traps
		as many errors as it can and logs em
*/
function ampache_error_handler($errno, $errstr, $errfile, $errline) { 

	/* Default level of 1 */
	$level = 1;
	
	switch ($errno) { 
		case '2':
			$error_name = "Runtime Error";
			break;
		case '128':
		case '8':
		case '32':
			return true;
			break;
		case '1':
			$error_name = "Fatal run-time Error";
			break;
		case '4':
			$error_name = "Parse Error";
			break;
		case '16':
			$error_name = "Fatal Core Error";
			break;
		case '64':
			$error_name = "Zend run-time Error";
			break;
		default:
			$error_name = "Error";
			$level = 2;
			break;
	} // end switch
	

	/* Don't log var: Deprecated we know shutup!
	 * Yea now getid3() spews errors I love it :(
	 */
	if (strstr($errstr,"var: Deprecated. Please use the public/private/protected modifiers") OR
	    strstr($errstr,"getimagesize() [") OR strstr($errstr,"Non-static method getid3") OR
	    strstr($errstr,"Assigning the return value of new by reference is deprecated")) { 
		return false; 
	}

	if (strstr($errstr,"date.timezone")) { 
		$error_name = "Warning"; 
		$errstr = "You have not set a valid timezone (date.timezone) in your php.ini file. This may cause display issues with dates. This warning is non-critical and not caused by Ampache."; 
	} 

	/* The XML-RPC lib is broken, well kind of 
	 * shut your pie hole 
	 */
	if (strstr($errstr,"used as offset, casting to integer")) { 
		return false; 
	}

	$log_line = "[$error_name] $errstr in file $errfile($errline)";
	debug_event('PHP Error',$log_line,$level);
	
	// When a dir is defined lets log it to a logfile
	if (Config::get('log_path') != "") 
		log_event("ampache","PHP Error", $log_line);
	
} // ampache_error_handler

/**
 * debug_event
 * This function is called inside ampache, it's actually a wrapper for the
 * log_event. It checks for conf('debug') and conf('debug_level') and only
 * calls log event if both requirements are met.
 */
function debug_event($type,$message,$level,$file='',$username='') { 

	if (!Config::get('debug') || $level > Config::get('debug_level')) { 
		return false;
	}

	if (!$username) { 
		$username = $GLOBALS['user']->username;
	}

	log_event($username,$type,$message,$file);

} // debug_event

?>
