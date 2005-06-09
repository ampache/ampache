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

$no_session = true;
require_once('modules/init.php');
require_once(conf('prefix') . "/modules/xmlrpc/xmlrpcs.inc");

/* Setup the vars we are going to need */
$access = new Access();

// ** check that the remote server has access to this catalog
if ($access->check('75',$_SERVER['REMOTE_ADDR'])) {
	$s = new xmlrpc_server( array( "remote_server_query" => array("function"  => "remote_server_query"),
				"remote_song_query" => array("function" => "remote_song_query") ) );
}
else {
	// Access Denied... Sucka!!
	$s = new xmlrpc_server( array( "remote_server_query" => array("function"  => "remote_server_denied")));
}

?>
