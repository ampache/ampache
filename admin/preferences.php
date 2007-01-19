<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

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

require('../lib/init.php');

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
}

$user_id = scrub_in($_REQUEST['user_id']);
$action  = scrub_in($_REQUEST['action']);
if (!$user_id) { $user_id ='-1'; } 

$temp_user = new User($user_id);
$temp_user->username = $user_id;

show_template('header');

switch($action) { 
	case 'user':
		$fullname = "ADMIN - " . $temp_user->fullname;
		$preferences = $temp_user->get_preferences();
	break;
	case 'update_preferences':
		if (conf('demo_mode')) { break; }
		update_preferences($user_id);	
		if ($user_id != '-1') { 
			$fullname = "ADMIN - " . $temp_user->fullname;
			$_REQUEST['action'] = 'user';
			$preferences = $temp_user->get_preferences();
		}
		else {
			$fullname = _('Site');
			init_preferences();
			$GLOBALS['user']->set_preferences();
			set_theme();
			$preferences = $temp_user->get_preferences();
		}
	break;
	case 'fix_preferences':
		$temp_user->fix_preferences($user_id);
		$preferences = $temp_user->get_preferences($user_id);
	break;
	case 'set_preferences':
		/* Update the preferences */
		foreach ($_REQUEST['prefs'] as $name=>$level) { 
			update_preference_level($name,$level);
		} // end foreach preferences
	case 'show_set_preferences':
		/* Get all preferences */
		$preferences = get_preferences();
		require_once(conf('prefix') . '/templates/show_preference_admin.inc.php');
	break;
	default:
		$preferences = $temp_user->get_preferences();
		$fullname = _('Site');
	break;

} // End Switch Action


// OMG HORRIBLE HACK Beatings for the programmer 
if ($action != 'show_set_preferences' AND $action != 'set_preferences') { 
	// Set Target
	$target = "/admin/preferences.php";

	// Show the default preferences page
	require (conf('prefix') . "/templates/show_preferences.inc");
}

// FOOTER
show_footer();


?>
