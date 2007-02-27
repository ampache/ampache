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

require_once ('../lib/init.php');

if (!$GLOBALS['user']->has_access(100)) { 
	access_denied();
	exit();
}


$action 	= scrub_in($_REQUEST['action']);
$user_id	= scrub_in($_REQUEST['user_id']);

show_template('header');

// Switch on the actions  
switch ($action) {
	case 'edit':
        	if (conf('demo_mode')) { break; }
		$working_user	= new User($user_id); 
		require_once(conf('prefix') . '/templates/show_edit_user.inc.php');
	break;
	case 'update_user':
	        if (conf('demo_mode')) { break; }

		/* Clean up the variables */
		$user_id	= scrub_in($_REQUEST['user_id']);
		$username 	= scrub_in($_REQUEST['username']);
		$fullname 	= scrub_in($_REQUEST['fullname']);
		$email 		= scrub_in($_REQUEST['email']);
		$access 	= scrub_in($_REQUEST['access']);
		$pass1 		= scrub_in($_REQUEST['password_1']);
		$pass2 		= scrub_in($_REQUEST['password_2']);
	
		/* Setup the temp user */	
	    	$working_user = new User($user_id);
	
		/* Verify Input */
		if (empty($username)) { 
			$GLOBALS['error']->add_error('username',_("Error Username Required"));
		}
		if ($pass1 !== $pass2 AND !empty($pass1)) { 
			$GLOBALS['error']->add_error('password',_("Error Passwords don't match"));
		}

		/* If we've got an error then break! */
		if ($GLOBALS['error']->error_state) { 
			require_once(conf('prefix') . '/templates/show_edit_user.inc.php');
			break;
		} // if we've had an oops!

		if ($access != $working_user->access) { 
			$working_user->update_access($access);
		}
		if ($email != $working_user->email) { 
			$working_user->update_email($email);
		}
		if ($username != $working_user->username) { 
			$working_user->update_username($username);
		} 
		if ($fullname != $working_user->fullname) {
			$working_user->update_fullname($fullname);
		}
		if ($pass1 == $pass2 && strlen($pass1)) { 
			$working_user->update_password($pass1);
		} 
		
		show_confirmation(_('User Updated'), $working_user->fullname . "(" . $working_user->username . ")" . _('updated'),'admin/users.php');
	break;
	case 'add_user':
        	if (conf('demo_mode')) { break; }
		$username	= scrub_in($_REQUEST['username']);
		$fullname	= scrub_in($_REQUEST['fullname']);
		$email		= scrub_in($_REQUEST['email']);
		$access		= scrub_in($_REQUEST['access']);
		$pass1		= scrub_in($_REQUEST['password_1']);
		$pass2		= scrub_in($_REQUEST['password_2']);
		if (($pass1 !== $pass2)) { 
			$GLOBALS['error']->add_error('password',_("Error Passwords don't match"));
		}

		if (empty($username)) { 
			$GLOBALS['error']->add_error('username',_("Error Username Required"));
		}
		if (is_numeric($username)) { 
			$GLOBALS['error']->add_error('username',"Error: Due to the incompetance of the programmer numeric usernames would cause the whole of existance to cease. Please add a letter or something"); 
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
			$type = 'new_user';
			require_once(conf('prefix') . '/templates/show_edit_user.inc.php');
			break;
		}
		if ($access == 5){ $access = "Guest";}
		elseif ($access == 25){ $access = "User";}
		elseif ($access == 100){ $access = "Admin";}
		
		show_confirmation("New User Added",$username . " has been created with an access level of " . $access,"admin/users.php");	
	break;
	case 'delete':
        	if (conf('demo_mode')) { break; }
		$working_user = new User($user_id); 
		show_confirmation(_('Deletion Request'),
			_('Are you sure you want to permanently delete') . " $working_user->fullname ($working_user->username)?",
			"admin/users.php?action=confirm_delete&amp;user_id=$user_id",1);
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
	        if (conf('demo_mode')) { break; }
		require_once(conf('prefix') . '/templates/show_add_user.inc.php');
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
		// Setup the View Object
		$view	= new View(); 
		$view->import_session_view(); 

		// If we are returning
		if ($_REQUEST['keep_view']) { 
			$view->initialize(); 
		} 
		else { 
			$sql = "SELECT `id` FROM `user`"; 
			$db_results = mysql_query($sql,dbh()); 
			$total_items = mysql_num_rows($db_results); 
			$view = new View($sql,'admin/users.php','fullname',$total_items,$user->prefs['offset_limit']); 
		}
		
		$users = get_users($view->sql); 
		require_once(conf('prefix') . '/templates/show_users.inc.php'); 
	break;
} // end switch on action

/* Show the footer */
show_footer();

?>
