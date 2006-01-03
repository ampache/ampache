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

/*!
	@header Users Admin Page
	Handles User management functions

*/

require_once ("../modules/init.php");

if (!$user->has_access(100)) { 
	access_denied();
}


$action = scrub_in($_REQUEST['action']);


show_template('header');

$user_id = scrub_in($_REQUEST['user']);
$temp_user = new User($user_id);
 
switch ($action) {
    case 'edit':
        if (conf('demo_mode')) { break; }
	show_user_form($temp_user->username, 
		$temp_user->fullname,
		$temp_user->email,
		$temp_user->access,
		'edit_user',
		'');
	break;

    case 'update_user':
	        if (conf('demo_mode')) { break; }

		/* Clean up the variables */
		$username = scrub_in($_REQUEST['new_username']);
		$fullname = scrub_in($_REQUEST['new_fullname']);
		$email = scrub_in($_REQUEST['new_email']);
		$access = scrub_in($_REQUEST['user_access']);
		$pass1 = scrub_in($_REQUEST['new_password_1']);
		$pass2 = scrub_in($_REQUEST['new_password_2']);
	
		/* Setup the temp user */	
	    	$thisuser = new User($username);
	
		/* Verify Input */
		if (empty($username)) { 
			$GLOBALS['error']->add_error('username',_("Error Username Required"));
		}
		if ($pass1 !== $pass2 AND !empty($pass1)) { 
			$GLOBALS['error']->add_error('password',_("Error Passwords don't match"));
		}

		/* If we've got an error then break! */
		if ($GLOBALS['error']->error_state) { 
		        show_user_form($thisuser->username,
		                $thisuser->fullname,
		                $thisuser->email,
		                $thisuser->access,
		                'edit_user',
		                '');
			break;
		} // if we've had an oops!

		if ($access != $thisuser->access) { 
			$thisuser->update_access($access);
		}
		if ($email != $thisuser->email) { 
			$thisuser->update_email($email);
		}
		if ($username != $thisuser->username) { 
			$thisuser->update_username($username);
		} 
		if ($fullname != $user->fullname) {
			$thisuser->update_fullname($fullname);
		}
		if ($pass1 == $pass2 && strlen($pass1)) { 
			$thisuser->update_password($pass1);
		} 
		show_confirmation("User Updated", $thisuser->username . "'s information has been updated","admin/users.php");
	break;
    case 'add_user':
        	if (conf('demo_mode')) { break; }
		$username = scrub_in($_REQUEST['new_username']);
		$fullname = scrub_in($_REQUEST['new_fullname']);
		$email = scrub_in($_REQUEST['new_email']);
		$access = scrub_in($_REQUEST['user_access']);
		$pass1 = scrub_in($_REQUEST['new_password_1']);
		$pass2 = scrub_in($_REQUEST['new_password_2']);
		if (($pass1 !== $pass2)) { 
			$GLOBALS['error']->add_error('password',_("Error Passwords don't match"));
		}

		if (empty($username)) { 
			$GLOBALS['error']->add_error('username',_("Error Username Required"));
		}

		/* make sure the username doesn't already exist */
		if (!check_username($username)) { 
			$GLOBALS['error']->add_error('username',_("Error Username already exists"));
		} 

		if (!$GLOBALS['error']->error_state) { 

			/* Attempt to create the user */
			if (!$user->create($username, $fullname, $email, $pass1, $access)) {
				$GLOBALS['error']->add_error('general',"Error: Insert Failed");
			}
			
		} // if no errors
		
		/* If we end up with an error */
		if ($GLOBALS['error']->error_state) { 
		        show_user_form('','$username','$fullname','$email','new_user','');
			break;
		}	
		show_confirmation("New User Added",$username . " has been created with an access level of " . $access,"admin/users.php");	
	break;
    case 'delete':
        if (conf('demo_mode')) { break; }
	show_confirm_action(_("Are you sure you want to permanently delete") . " $temp_user->fullname ($temp_user->username) ?",
		"admin/users.php",
		"action=confirm_delete&amp;user=$temp_user->username");
	break;

    case 'confirm_delete':
        if (conf('demo_mode')) { break; }
    	if ($_REQUEST['confirm'] == _("No")) { show_manage_users(); break; }
	if ($temp_user->delete()) { 
		show_confirmation(_("User Deleted"), "$temp_user->username has been Deleted","admin/users.php");
	}
	else { 
		show_confirmation(_("Delete Error"), _("Unable to delete last Admin User"),"admin/users.php");
	}
	break;
    case 'show_add_user':
        if (conf('demo_mode')) { break; }
	show_user_form('','','','','new_user','');
	break;

    case 'update':
    case 'disabled':
        if (conf('demo_mode')) { break; }
	$level = scrub_in($_REQUEST['level']);
	$thisuser = new User($_REQUEST['user']);
	if ($_SESSION['userdata']['access'] == 'admin') {
		$thisuser->update_access($level);
	} 
	show_manage_users();
	break;

    default:
	show_manage_users();

}

/* Show the footer */
show_footer();

?>
