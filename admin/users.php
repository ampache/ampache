<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

require_once '../lib/init.php';
if (!$GLOBALS['user']->has_access(100)) { 
	access_denied();
	exit();
}

$user_id	= scrub_in($_REQUEST['user_id']);

show_header(); 

// Switch on the actions  
switch ($_REQUEST['action']) {
	case 'update_user':
	        if (Config::get('demo_mode')) { break; }

		/* Clean up the variables */
		$user_id	= scrub_in($_REQUEST['user_id']);
		$username 	= scrub_in($_REQUEST['username']);
		$fullname 	= scrub_in($_REQUEST['fullname']);
		$email 		= scrub_in($_REQUEST['email']);
		$access 	= scrub_in($_REQUEST['access']);
		$pass1 		= scrub_in($_REQUEST['password_1']);
		$pass2 		= scrub_in($_REQUEST['password_2']);
	
		/* Setup the temp user */	
	    	$client = new User($user_id);
	
		/* Verify Input */
		if (empty($username)) { 
			Error::add('username',_("Error Username Required"));
		}
		if ($pass1 !== $pass2 && !empty($pass1)) { 
			Error::add('password',_("Error Passwords don't match"));
		}

		/* If we've got an error then break! */
		if (Error::$state) { 
			$_REQUEST['action'] = 'show_edit';
			break;
		} // if we've had an oops!

		if ($access != $client->access) { 
			$client->update_access($access);
		}
		if ($email != $client->email) { 
			$client->update_email($email);
		}
		if ($username != $client->username) { 
			$client->update_username($username);
		} 
		if ($fullname != $client->fullname) {
			$client->update_fullname($fullname);
		}
		if ($pass1 == $pass2 && strlen($pass1)) { 
			$client->update_password($pass1);
		} 
		
		show_confirmation(_('User Updated'), $client->fullname . "(" . $client->username . ")" . _('updated'),'admin/users.php');
	break;
	case 'add_user':
        	if (Config::get('demo_mode')) { break; }
		$username	= scrub_in($_REQUEST['username']);
		$fullname	= scrub_in($_REQUEST['fullname']);
		$email		= scrub_in($_REQUEST['email']);
		$access		= scrub_in($_REQUEST['access']);
		$pass1		= scrub_in($_REQUEST['password_1']);
		$pass2		= scrub_in($_REQUEST['password_2']);

		if ($pass1 !== $pass2) { 
			Error::add('password',_("Error Passwords don't match"));
		}

		if (empty($username)) { 
			Error::add('username',_('Error Username Required'));
		}

		/* make sure the username doesn't already exist */
		if (!User::check_username($username)) { 
			Error::add('username',_('Error Username already exists'));
		} 

		if (!Error::$state) { 
			/* Attempt to create the user */
			$user_id = User::create($username, $fullname, $email, $pass1, $access);
			if (!$user_id) { 
				Error::add('general',"Error: Insert Failed");
			}
			
		} // if no errors
		else { 
			$_REQUEST['action'] = 'show_add_user';
			break;
		}
		if ($access == 5){ $access = _('Guest');}
		elseif ($access == 25){ $access = _('User');}
		elseif ($access == 100){ $access = _('Admin');}
		
		show_confirmation(_('New User Added'),__('%user% has been created with an access level of ' . $access,'%user%',$username),'admin/users.php');	
	break;
	case 'delete':
        	if (conf('demo_mode')) { break; }
		$working_user = new User($user_id); 
		show_confirmation(_('Deletion Request'),
			_('Are you sure you want to permanently delete') . " $working_user->fullname ($working_user->username)?",
			"admin/users.php?action=confirm_delete&amp;user_id=$user_id",1);
	break;
	case 'enable':
		$working_user = new User($user_id); 
		$working_user->enable(); 
		show_confirmation(_('User Enabled'),'','admin/users.php'); 
	break;
	case 'disable':
		$working_user = new User($user_id); 
		if ($working_user->disable()) { 
			show_confirmation(_('User Disabled'),'','admin/users.php'); 
		} 
		else { 
			show_confirmation(_('Error'),_('Unable to Disabled last Administrator'),'admin/users.php'); 
		} 
	break;

} // End Work Switch


/**
 * This is the second half, it handles displaying anything
 * the first half (work half) potentially has 'adjusted' the user
 * input
 */
switch ($_REQUEST['action']) { 
	case 'show_edit':
        	if (Config::get('demo_mode')) { break; }
		$client	= new User($user_id); 
		require_once Config::get('prefix') . '/templates/show_edit_user.inc.php';
	break;
	case 'confirm_delete':
	        if (conf('demo_mode')) { break; }
		$working_user = new User($_REQUEST['user_id']); 
		if ($working_user->delete()) { 
			show_confirmation(_('User Deleted'), "$working_user->username has been Deleted","admin/users.php");
		}
		else { 
			show_confirmation(_('Delete Error'), _("Unable to delete last Admin User"),"admin/users.php");
		}
	break;
	/* Show IP History for the Specified User */
	case 'show_ip_history':
		/* get the user and their history */
		$working_user	= new User($_REQUEST['user_id']); 
		if (!isset ($_REQUEST['all'])){
		$history	= $working_user->get_ip_history('',1);
		} else {
		$history	= $working_user->get_ip_history('','');
		}
		require (conf('prefix') . '/templates/show_ip_history.inc.php');
	break;
	case 'show_add_user':
	        if (Config::get('demo_mode')) { break; }
		require_once Config::get('prefix') . '/templates/show_add_user.inc.php';
	break;
	case 'show_inactive':
		$view	= new View(); 
		$view->import_session_view(); 

		// If we are returning
		if ($_REQUEST['keep_view']) { 
			$view->initialize(); 
		} 
		else {
		
			$inactive = time() - ($_REQUEST['days'] * 24 * 60 *60);

			$sql = "SELECT `id`,`last_seen` FROM `user` where last_seen <= $inactive"; 
			$db_results = mysql_query($sql,dbh()); 
			$total_items = mysql_num_rows($db_results); 
			$view = new View($sql,'admin/users.php','fullname',$total_items,$user->prefs['offset_limit']); 
		}
		
		$users = get_users($view->sql); 
		require_once(conf('prefix') . '/templates/show_users.inc.php'); 
	
	break;
	default:
		Browse::set_type('user'); 
		$user_ids = Browse::get_objects(); 
		Browse::show_objects($user_ids); 
	break;
} // end switch on action

/* Show the footer */
show_footer();

?>
