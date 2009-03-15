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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE as one
 */
if (AJAX_INCLUDE != '1') { exit; } 

switch ($_REQUEST['action']) { 
	case 'delete_vote': 
		$democratic = Democratic::get_current_playlist(); 
		$democratic->set_parent(); 
		$democratic->remove_vote($_REQUEST['row_id']); 
		
		ob_start(); 
		$object_ids = $democratic->get_items(); 
		Browse::set_type('democratic');
		Browse::reset(); 
		Browse::set_static_content(1); 
		Browse::show_objects($object_ids); 

		require_once Config::get('prefix') . '/templates/show_democratic_playlist.inc.php'; 
		$results['browse_content'] = ob_get_contents();
		ob_end_clean(); 

	break;
	case 'add_vote': 

		$democratic = Democratic::get_current_playlist(); 
		$democratic->set_parent(); 
		$democratic->add_vote($_REQUEST['object_id'],$_REQUEST['type']); 

		ob_start(); 
		$object_ids = $democratic->get_items(); 
		Browse::set_type('democratic');
		Browse::reset(); 
		Browse::set_static_content(1); 
		Browse::show_objects($object_ids); 

		require_once Config::get('prefix') . '/templates/show_democratic_playlist.inc.php'; 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 

	break; 
	case 'delete': 
		if (!$GLOBALS['user']->has_access('75')) { 
			exit; 
		} 

		$democratic = Democratic::get_current_playlist(); 
		$democratic->set_parent(); 
		$democratic->delete_votes($_REQUEST['row_id']); 

		ob_start(); 
		$object_ids = $democratic->get_items(); 
		Browse::set_type('democratic');
		Browse::reset(); 
		Browse::set_static_content(1); 
		Browse::show_objects($object_ids); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 
	
	break; 
	case 'send_playlist': 
		if (!Access::check('interface','75')) { 
			exit; 
		} 

		$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=democratic&democratic_id=' . scrub_out($_REQUEST['democratic_id']); 
		$results['rfc3514'] = '<script type="text/javascript">reload_util("'.$_SESSION['iframe']['target'].'")</script>';
	break; 
	case 'clear_playlist': 

		if (!Access::check('interface','100')) { 
			exit; 
		} 

		$democratic = new Democratic($_REQUEST['democratic_id']);  
		$democratic->set_parent(); 
		$democratic->clear(); 

		ob_start(); 
		$object_ids = array();  
		Browse::set_type('democratic'); 
		Browse::reset(); 
		Browse::set_static_content(1); 
		Browse::show_objects($object_ids); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 

	break; 
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
