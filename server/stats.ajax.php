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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE as one
 */
if (AJAX_INCLUDE != '1') { exit; } 

switch ($_REQUEST['action']) { 
	case 'show_recommend': 
		switch ($_REQUEST['type']) { 
			case 'artist': 
			case 'album': 
			case 'track': 
				// We're good
			break;
			default: 
				$results['rfc3514'] = '0x1'; 
			break 2;
		} // verifying the type

		ob_start(); 
		show_box_top(_('Recommendations')); 
		echo "Loading..."; 
		$ajax_action = Ajax::action('?page=stats&action=recommend&type=' . $_REQUEST['type'] . '&id=' . $_REQUEST['id'],'show_recommend_refresh');  
		Ajax::run($ajax_action); 
		show_box_bottom(); 
		$results['additional_information'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	case 'recommend': 
		switch ($_REQUEST['type']) { 
			case 'artist':
				$headers = array('name'=>_('Name'),'links'=>' '); 
			break;
			case 'album': 
			case 'track': 
				// We're good
			default: 
				$results['rtc3514'] = '0x1'; 
			break 2;
		} 

		// Get the recommendations
		$objects = metadata::recommend_similar($_REQUEST['type'],$_REQUEST['id'],'7'); 

		ob_start(); 
		show_box_top(_('Recommendations')); 
		require_once Config::get('prefix') . '/templates/show_objects.inc.php'; 
		show_box_bottom(); 
		$results['additional_information'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
