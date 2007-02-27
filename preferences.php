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

/*!
	@header Preferences page
	Preferences page for whole site, and where
	the admins do editing of other users preferences

*/

require('lib/init.php');

/* Scrub in the needed mojo */
if (!$_REQUEST['tab']) { $_REQUEST['tab'] = 'interface'; } 
$user_id = scrub_in($_REQUEST['user_id']);


switch(scrub_in($_REQUEST['action'])) { 
	case 'update_user':
		/* Verify permissions */
		if (!$GLOBALS['user']->has_access(25) || conf('demo_mode') || ($GLOBALS['user']->id != $user_id && !$GLOBALS['user']->has_access(100))) { 
			show_access_denied(); 
			exit();
		}
		
		/* Go ahead and update normal stuff */
		$this_user = new User($user_id);
		$this_user->update_fullname($_REQUEST['fullname']);
		$this_user->update_email($_REQUEST['email']);

		
		/* Check for password change */
		if ($_REQUEST['password1'] !== $_REQUEST['password2'] && !empty($_REQUEST['password1'])) { 
			$GLOBALS['error']->add_error('password',_('Error: Password Does Not Match or Empty'));
			break;
		}
		elseif (!empty($_REQUEST['password1'])) { 	
			/* We're good change the mofo! */
			$this_user->update_password($_REQUEST['password1']);
		
			/* Haha I'm fired... it's not an error but screw it */
			$GLOBALS['error']->add_error('password',_('Password Updated'));
		}

		/* Check for stats */
		if ($_REQUEST['clear_stats'] == '1') { 
			$this_user->delete_stats();
		}
	break;
	case 'update_preferences':
		
		/* Do the work */
		update_preferences($user_id);	
		
		/* Reload the Preferences */
		$GLOBALS['user']->set_preferences();

		/* Reset the conf values */
		init_preferences();

		/* Reset the Theme */
		set_theme();
	default:
		if (!$user_id) { $user_id = $GLOBALS['user']->id; }
		$preferences = $GLOBALS['user']->get_preferences(0,$_REQUEST['tab']);		
	break;

} // End Switch Action

if (!$GLOBALS['user']->fullname) { 
	$fullname = "Site";
}
else {
	$fullname = $GLOBALS['user']->fullname;
}


// HEADER
show_template('header');
// HEADER

// Set Target
$target = "/preferences.php";

// Show the default preferences page
require (conf('prefix') . "/templates/show_preferences.inc");


// FOOTER
show_footer();
?>
