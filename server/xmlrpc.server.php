<?php
/*

 Copyright Ampache.org
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

define('NO_SESSION','1');
require_once('../lib/init.php');

/* Set the correct headers */
header("Content-type: text/xml; charset=" . Config::get('site_charset'));
header("Content-Disposition: attachment; filename=xmlrpc-server.xml");

if (Config::get('xml_rpc')) { 
	require_once Config::get('prefix') . "/modules/xmlrpc/xmlrpcs.inc";
	require_once Config::get('prefix') . "/modules/xmlrpc/xmlrpc.inc";
}
else { 
	debug_event('DENIED','Attempted to Access XMLRPC server with xml_rpc disabled','1'); 
	exit(); 
}

// ** check that the remote server has access to this catalog
if (Access::check_network('init-rpc','','5')) {

	// Define an array of classes we need to pull from for the 
	$classes = array('xmlRpcServer'); 	

	foreach ($classes as $class) { 
		$methods = get_class_methods($class); 

		foreach ($methods as $method) { 
			$name = strtolower($class) . '.' . strtolower($method); 
			$functions[$name] = array('function'=>$class . '::' . $method); 
		} 

	} // end foreach of classes

	$server = new xmlrpc_server($functions);
} // test for ACL 

?>
