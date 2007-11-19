<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
 * This is accessed remotly to allow outside scripts access to ampache information 
 * as such it needs to verify the session id that is passed 
 */

define('NO_SESSION','1');
require_once '../lib/init.php';

/** 
 * Verify the existance of the Session they passed in we do allow them to
 * login via this interface so we do have an exception for action=login
 */
if (!Access::session_exists(array(),$_REQUEST['auth'],'api') AND $_REQUEST['action'] != 'handshake') { 
	debug_event('Access Denied','Invalid Session or unthorized access attempt to API','5'); 
	exit(); 
}

/* Set the correct headers */
header("Content-type: text/xml; charset=utf-8");


switch ($_REQUEST['action']) { 
	case 'handshake': 

		// Send the data we were sent to the API class so it can be chewed on 

	break; 
	default:
		// Rien a faire
	break;
} // end switch action
?>
