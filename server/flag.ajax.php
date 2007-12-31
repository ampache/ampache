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
	case 'reject': 
		if (!Access::check('interface','75')) { 
			$results['rfc3514'] = '0x1'; 
			break; 
		} 

		// Remove the flag from the table 
		$flag = new Flag($_REQUEST['flag_id']); 
		$flag->delete(); 

		$flagged = Flag::get_all(); 
		ob_start(); 
		Browse::set_type('flagged'); 
		Browse::set_static_content(1); 
		Browse::save_objects($flagged); 
		Browse::show_objects($flagged); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 

	break;
	case 'accept': 
		if (!Access::check('interface','75')) { 
			$results['rfc3514'] = '0x1'; 
			break; 
		} 

		$flag = new Flag($_REQUEST['flag_id']); 
		$flag->approve(); 
		$flag->format(); 
		ob_start(); 
		require_once Config::get('prefix') . '/templates/show_flag_row.inc.php'; 
		$results['flagged_' . $flag->id] = ob_get_contents(); 
		ob_end_clean(); 

	break; 
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
