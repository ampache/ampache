<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

require('../modules/init.php');


/* Scrub in the Needed vars */
$action = scrub_in($_REQUEST['action']);
$access_id = scrub_in($_REQUEST['access_id']);
$access = new Access($access_id);

if (!$user->has_access(100)) { 
	header("Location: http://" . conf('web_path') . "/index.php?access=denied");
	exit();
}


show_template('header');

if ( $action == 'show_confirm_delete' ) {
        show_confirm_action(_("Do you really want to delete this Access Record?"), "admin/access.php", "access_id=" . $_REQUEST['access_id'] . "&amp;action=delete_host");
}
/*!
	@action delete_host
	@discussion deletes an access list entry
*/
elseif ( $action == 'delete_host' ) {
	$access->delete($_REQUEST['access_id']);
	show_confirmation(_("Entry Deleted"),_("Your Access List Entry has been removed"),"admin/access.php");

} // delete_host
/*!
	@action add_host
	@discussion add a new access list entry
*/
elseif ($action == 'add_host') { 

	$access->create($_REQUEST['name'], $_REQUEST['start'],$_REQUEST['end'],$_REQUEST['level']);
	show_confirmation(_("Entry Added"),_("Your new Access List Entry has been created"),"admin/access.php");

} // add_host
/*!
	@action show_add_host
	@discussion show the add host box
*/
elseif ( $action == 'show_add_host' ) {
	include(conf('prefix') . "/templates/show_add_access.inc");
}
else { 
	$list = array();
	$list = $access->get_access_list();
	include(conf('prefix') ."/templates/show_access_list.inc");
}

show_footer();
?>
