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
	case 'browse': 

		$object_ids = array(); 

                if ($_REQUEST['key'] && $_REQUEST['value']) {
                        // Set any new filters we've just added
			Browse::set_filter($_REQUEST['key'],$_REQUEST['value']); 
                }
                if ($_REQUEST['sort']) {
                        // Set the new sort value
                        Browse::set_sort($_REQUEST['sort']);
                }

		// Refresh the browse div with our new filter options if we're not static
		if (!Browse::$static_content) { 
	                $object_ids = Browse::get_objects();
		} 

		ob_start();
                Browse::show_objects($object_ids);
                $results['browse_content'] = ob_get_contents();
                ob_end_clean();
	break;
	case 'set_sort':
		if ($_REQUEST['sort']) { 
			Browse::set_sort($_REQUEST['sort']); 
		} 

		// Refresh the browse div with our new hotness
		$object_ids = Browse::get_saved(); 

		ob_start(); 
		Browse::show_objects($object_ids); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 
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
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
