<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

require 'lib/init.php';

// Switch on the action 
switch($_REQUEST['action']) { 
	case 'update_preferences':
		if (($_REQUEST['method'] == 'admin' OR $_REQUEST['method'] == 'user') && !$GLOBALS['user']->has_access('100')) { 
			access_denied(); 
			exit; 
		} 
		
		/* Reset the Theme */
		if ($_REQUEST['method'] == 'admin') { 
			$user_id = '-1'; 
			$fullname = _('Server'); 
			$_REQUEST['action'] = 'admin'; 
		}
		elseif ($_REQUEST['method'] == 'user') { 
			$user_id = $_REQUEST['user_id']; 
			$client = new User($user_id); 
			$fullname = $client->fullname; 
		} 
		else { 
			$user_id = $GLOBALS['user']->id; 
			$fullname = $GLOBALS['user']->fullname; 
		} 

		/* Update and reset preferences */
		update_preferences($user_id);	
		init_preferences();

		$preferences = $GLOBALS['user']->get_preferences($user_id,$_REQUEST['tab']);		
	break;
	case 'admin': 
		// Make sure only admins here
		if (!$GLOBALS['user']->has_access('100')) { 
			access_denied(); 
			exit;
		} 
		$fullname= _('Server');
		$preferences = $GLOBALS['user']->get_preferences(-1,$_REQUEST['tab']); 
	break;
	case 'user':
		if (!$GLOBALS['user']->has_access('100')) { 
			access_denied(); 
			exit; 
		} 
		$client = new User($_REQUEST['user_id']); 
		$fullname = $client->fullname; 
		$preferences = $client->get_preferences(0,$_REQUEST['tab']); 
	break; 
	case 'update_user': 
		// Make sure we're a user and they came from the form
		if (!$GLOBALS['user']->has_access('25') || $_POST['form_string'] != $_SESSION['forms']['account']) { 
			access_denied(); 
			exit; 
		} 
		// Remove the value
		unset($_SESSION['forms']['account']); 

		// Don't let them change access, or username here
		unset($_POST['access']); 
		$_POST['username'] = $GLOBALS['user']->username; 

		if (!$GLOBALS['user']->update($_POST)) { 
			Error::add('general',_('Error Update Failed')); 
		} 
		else { 
			$_REQUEST['action'] = 'confirm'; 
			$title = _('Updated'); 
			$text = _('Your Account has been updated'); 
			$next_url = Config::get('web_path') . '/preferences.php?tab=account'; 
		} 
	break;
	default: 
		$fullname = $GLOBALS['user']->fullname; 
		$preferences = $GLOBALS['user']->get_preferences(0,$_REQUEST['tab']); 
	break;
} // End Switch Action

show_header(); 

/**
 * switch on the view
 */
switch ($_REQUEST['action']) { 
	case 'confirm': 
		show_confirmation($title,$text,$next_url,$cancel); 
	break;
	default: 
		// Show the default preferences page
		require Config::get('prefix') . '/templates/show_preferences.inc.php';
	break;
} // end switch on action

show_footer();
?>
