<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All Rights Reserved

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

require_once("modules/init.php");

show_template('header'); 

show_menu_items('Profile'); 

$action = scrub_in($_REQUEST['action']);
$username		= $user->username;
$password	 	= $_REQUEST['password'];
$confirm_password	= scrub_in($_REQUEST['confirm_password']);
$fullname   		= scrub_in($_REQUEST['fullname']);
$email			= scrub_in($_REQUEST['email']);
$offset			= scrub_in($_REQUEST['offset_limit']);
$user_id		= scrub_in($_REQUEST['user_id']);


switch ($action) { 

	case 'Change Password':
	case 'change_password':
		/* Make sure the passwords match */
		if ($confirm_password !== $password || empty($password) ) { 
			$error->add_error('password',_("Error: Password Does Not Match or Empty"));
			show_edit_profile($username);	
			break;
		}
		/* Make sure they have the rights */
		if (!$user->has_access(25) || conf('demo_mode')) { 
			$error->add_error('password',_("Error: Insufficient Rights"));
			show_edit_profile($username);
			break;
		}
		$this_user = new User($user_id);
		$this_user->update_password($password);
		show_confirmation("User Updated","Password updated for " . $this_user->username,"user.php?action=show_edit_profile");
	break;
	case 'Update Profile':
	case 'update_user':
		if (!$user->has_access(25) || conf('demo_mode')) { 
			$error->add_error('general',_("Error: Insufficient Rights"));
			show_edit_profile($username);
			break;
		} // no rights!
		$this_user = new User($user_id);
		$this_user->update_fullname($fullname);
		$this_user->update_email($email);
		$this_user->update_offset($offset);
		show_confirmation("User Updated","User Information for " . $this_user->username . " has been updated","user.php?action=show_edit_profile");
	break;
	case 'Clear Stats':
	case 'clear_stats':
		$this_user = new User($user_id);
		$this_user->delete_stats();
		show_confirmation("Statistics Cleared","Your Personal Statistics have been cleared","user.php?action=show_edit_profile");
	break;
	case 'show_edit_profile':
	default:
		show_edit_profile($username);
	break;
} // end action switch

show_menu_items('Profile');
?>
