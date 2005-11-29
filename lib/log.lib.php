<?php
/*

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

/*!
	@function log_event
        @discussion logs an event either to a database
        	or to a defined log file based on config options
*/
function log_event($username='Unknown',$event_name,$event_description,$log_name='ampache') { 

	/* Set it up here to make sure it's _always_ the same */
        $log_time = time();

	set_time_limit(0);

        $log_filename   = conf('log_path') . "/$log_name." . date("Ymd",$log_time) . ".log";
        $log_line       = date("Y-m-d H:i:s",$log_time) . " { $username } ( $event_name ) - $event_description \n";  


	error_log($log_line, 3, $log_filename) or die("Error: Unable to write to log ($log_filename)");

} // log_event

/*!
	@function ampache_error_handler
	@discussion an error handler for ampache that traps
		as many errors as it can and logs em
*/
function ampache_error_handler($errno, $errstr, $errfile, $errline) { 
	
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
			break;
	} // end switch


	$log_line = "[$error_name] $errstr on line $errline in $errfile";
	log_event($_SESSION['userdata']['username'],'error',$log_line,'ampache-error');
	
} // ampache_error_handler

?>
