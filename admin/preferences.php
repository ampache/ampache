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

/**
 * Preferences page
 * Preferences page for whole site, and where
 * the admins do editing of other users preferences
 * @package Preferences
 * @catagory Admin
 * @author Karl Vollmer
 */

require('../modules/init.php');


if (!$user->has_access(100)) {
	access_denied();
}

$user_id = scrub_in($_REQUEST['user_id']);

switch(scrub_in($_REQUEST['action'])) { 

	case 'user':
		$temp_user = new User($user_id);
		$fullname = "ADMIN - " . $temp_user->fullname;
		$preferences = $temp_user->get_preferences($user_id);
	break;
	case 'update_preferences':
		if (conf('demo_mode')) { break; }
		update_preferences($user_id);	
		if ($user_id != '-1') { 
			$temp_user = new User($user_id);
			$fullname = "ADMIN - " . $temp_user->fullname;
			$preferences = $temp_user->get_preferences();
		}
		else {
			$preferences = get_site_preferences();
		}
	break;
	case 'fix_preferences':
		$temp_user = new User();
		$temp_user->fix_preferences($user_id);
		$preferences = $temp_user->get_preferences($user_id);
	break;
	default:
		$user_id = -1;
		$preferences = get_site_preferences();	
		$fullname = "Site";
	break;

} // End Switch Action


// HEADER
show_template('header');
// HEADER

// Set Target
$target = "/admin/preferences.php";

// Show the default preferences page
require (conf('prefix') . "/templates/show_preferences.inc");


// FOOTER
show_footer();


?>
