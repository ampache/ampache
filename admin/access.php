<?php
/*

 Copyright (c) Ampache.org
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

require '../lib/init.php';

if (!Access::check('interface','100')) { 
	access_denied();
	exit();
}

show_header(); 

switch ($_REQUEST['action']) { 
	case 'delete_record':
		if (!Core::form_verify('delete_access')) { 
			access_denied(); 
			exit; 
		} 
		Access::delete($_REQUEST['access_id']);
		$url = Config::get('web_path') . '/admin/access.php';
		show_confirmation(_('Deleted'),_('Your Access List Entry has been removed'),$url);
	break;
	case 'show_delete_record': 
		if (Config::get('demo_mode')) { break; } 
		$access = new Access($_GET['access_id']); 
		show_confirmation(_('Deletion Request'),_('Are you sure you want to permanently delete') . ' ' . $access->name,
			'admin/access.php?action=delete_record&amp;access_id=' . $access->id,1,'delete_access');
	break; 
	case 'add_host':

		// Make sure we've got a valid form submission
		if (!Core::form_verify('add_acl','post')) { 
			access_denied(); 
			exit; 
		} 

		// We need to pre-process this a little bit as stuff is coming in from all over
		switch ($_GET['method']) { 
			case 'advanced': 
				Access::create($_POST); 
			break; 
			case 'local': 
				$_POST['type'] = 'network'; 
				Access::create($_POST); 
				
				// Create Additional stuff based on the type
				if ($_POST['addtype'] == 'streamnetwork' OR $_POST['addtype'] == 'allnetwork') { 
					$_POST['type'] = 'stream'; 
					Access::create($_POST); 
				} 
				if ($_POST['addtype'] == 'allnetwork') { 
					$_POST['type'] = 'interface'; 
					Access::create($_POST); 
				} 
			break; 
			case 'current': 
				$_POST['start'] = $_SERVER['REMOTE_ADDR']; 
				$_POST['end'] = $_SERVER['REMOTE_ADDR']; 
				$_POST['type'] = 'interface'; 
				Access::create($_POST); 
				$_POST['type'] = 'stream'; 
				Access::create($_POST); 
			break; 
			case 'rpc': 
				$_POST['type'] = 'rpc';
				Access::create($_POST); 

				// Create Additional stuff based on the type
				if ($_POST['addtype'] == 'streamrpc' OR $_POST['addtype'] == 'allrpc') { 
					$_POST['type'] = 'stream'; 
					Access::create($_POST); 
				}
				if ($_POST['addtype'] == 'allrpc') { 
					$_POST['type'] = 'interface'; 
					Access::create($_POST); 
				} 
			break; 
			default: 
				// Do nothing they f'ed something up
			break; 
		} // end switch on method

		if (!Error::occurred()) { 
			$url = Config::get('web_path') . '/admin/access.php';
			show_confirmation(_('Added'),_('Your new Access Control List(s) have been created'),$url);
		} 
		else { 
			switch ($_GET['method']) { 
				case 'rpc': require_once Config::get('prefix') . '/templates/show_add_access_rpc.inc.php'; break;
				case 'local': require_once Config::get('prefix') . '/templates/show_add_access_local.inc.php'; break; 
				case 'current': require_once Config::get('prefix') . '/templates/show_add_access_current.inc.php'; break;
				case 'advanced': require_once Config::get('prefix') . '/templates/show_add_access.inc.php'; break; 
				default: require_once Config::get('prefix') . '/templates/show_access_list.inc.php'; break; 
			} 
		} 
	break;
	case 'update_record':
		if (!Core::form_verify('edit_acl')) { 
			access_denied(); 
			exit; 
		} 
		$access = new Access($_REQUEST['access_id']); 
		$access->update($_POST);
		if (!Error::occurred()) { 
			show_confirmation(_('Updated'),_('Access List Entry updated'),'admin/access.php');
		} 
		else { 
			$access->format(); 
			require_once Config::get('prefix') . '/templates/show_edit_access.inc.php'; 
		}
	break;
	case 'show_add_current': 
		require_once Config::get('prefix') . '/templates/show_add_access_current.inc.php'; 
	break; 
	case 'show_add_rpc': 
		require_once Config::get('prefix') . '/templates/show_add_access_rpc.inc.php'; 
	break; 
	case 'show_add_local': 
		require_once Config::get('prefix') . '/templates/show_add_access_local.inc.php'; 
	break; 
	case 'show_add_advanced':
		require_once Config::get('prefix') . '/templates/show_add_access.inc.php';
	break;
	case 'show_edit_record':
		$access = new Access($_REQUEST['access_id']);
		$access->format(); 
		require_once Config::get('prefix') . '/templates/show_edit_access.inc.php';
	break;
	default:
		$list = array();
		$list = Access::get_access_lists();
		require_once Config::get('prefix') .'/templates/show_access_list.inc.php';
	break;
} // end switch on action
show_footer();
?>
