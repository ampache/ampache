<?php
/*

 Copyright (c) 2004 Ampache.org
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

/*!
	@header Preferences page
	Preferences page for whole site, and where
	the admins do editing of other users preferences

*/

require('modules/init.php');

switch(scrub_in($_REQUEST['action'])) { 
	case 'update_preferences':
		$user_id = scrub_in($_REQUEST['user_id']);
		update_preferences($user_id);	
		$preferences = $GLOBALS['user']->get_preferences();
		$GLOBALS['user']->set_preferences();
		get_preferences();
		set_theme();
	break;
	default:
		$user_id = $user->username;
		$preferences = $user->get_preferences();		
	break;

} // End Switch Action

if (!$user->fullname) { 
	$fullname = "Site";
}
else {
	$fullname = $user->fullname;
}


// HEADER
show_template('header');
show_menu_items('Preferences');
show_clear();
// HEADER

// Set Target
$target = "/preferences.php";

// Show the default preferences page
require (conf('prefix') . "/templates/show_preferences.inc");


// FOOTER
show_page_footer ('Preferences', '',$user->prefs['display_menu']);
?>
