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
	case 'browse': 

		$object_ids = array(); 

		Browse::set_type($_REQUEST['type']); 

		// Check 'value' with isset because it can null
		//(user type a "start with" word and deletes it)
		if ($_REQUEST['key'] && (isset($_REQUEST['multi_alpha_filter']) OR isset($_REQUEST['value']))) {
			// Set any new filters we've just added
			Browse::set_filter($_REQUEST['key'],$_REQUEST['multi_alpha_filter']); 
		}

		if ($_REQUEST['sort']) {
			// Set the new sort value
			Browse::set_sort($_REQUEST['sort']);
		}

		ob_start();
                Browse::show_objects(false);
                $results['browse_content'] = ob_get_clean();
	break;
	case 'set_sort':

		Browse::set_type($_REQUEST['type']); 

		if ($_REQUEST['sort']) { 
			Browse::set_sort($_REQUEST['sort']); 
		} 

		ob_start(); 
		Browse::show_objects(false); 
		$results['browse_content'] = ob_get_clean(); 
	break; 
	case 'toggle_tag': 
		$type = $_SESSION['tagcloud_type'] ? $_SESSION['tagcloud_type'] : 'song';
		Browse::set_type($type); 
	break; 
	case 'delete_object': 
		switch ($_REQUEST['type']) { 
			case 'playlist': 
				// Check the perms we need to on this
				$playlist = new Playlist($_REQUEST['id']); 
				if (!$playlist->has_access()) { exit; } 

				// Delete it!
				$playlist->delete(); 
				$key = 'playlist_row_' . $playlist->id;
			break;
			case 'live_stream': 
				if (!$GLOBALS['user']->has_access('75')) { exit; } 
				$radio = new Radio($_REQUEST['id']);
				$radio->delete(); 
				$key = 'live_stream_' . $radio->id; 
			break; 
			default: 

			break;
		} // end switch on type

		$results[$key] = ''; 

	break; 
	case 'page': 
		Browse::set_type($_REQUEST['type']); 
		Browse::set_start($_REQUEST['start']); 

		ob_start(); 
		Browse::show_objects(); 
		$results['browse_content'] = ob_get_clean(); 	
	
	break; 
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
