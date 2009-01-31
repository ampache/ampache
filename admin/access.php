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
		Access::delete($_REQUEST['access_id']);
		$url = Config::get('web_path') . '/admin/access.php';
		show_confirmation(_('Deleted'),_('Your Access List Entry has been removed'),$url);
	break;
	case 'add_host':
		Access::create($_POST); 
		$url = Config::get('web_path') . '/admin/access.php';
		show_confirmation(_('Added'),_('Your new Access List Entry has been created'),$url);
	break;
	case 'update_record':
		$access = new Access($_REQUEST['access_id']); 
		$access->update($_POST);
		show_confirmation(_('Updated'),_('Access List Entry updated'),'admin/access.php');
	break;
	case 'show_add_current': 

	break; 
	case 'show_add_rpc': 
	break; 
	case 'show_add_local': 
	
	break; 
	case 'show_add_advanced':
		require_once Config::get('prefix') . '/templates/show_add_access.inc.php';
	break;
	case 'show_edit_record':
		$access = new Access($_REQUEST['access_id']);
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
