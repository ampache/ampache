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
	case 'random_albums': 
		$albums = Album::get_random_albums('6'); 
		if (is_array($albums) AND count($albums)) { 
			ob_start(); 
			require_once Config::get('prefix') . '/templates/show_random_albums.inc.php'; 
			$results['random_selection'] = ob_get_clean(); 
		} 
		else { 
			$results['random_selection'] = '<!-- No albums of the moment could be found -->'; 
		} 
	break;
	case 'reloadnp': 
		ob_start(); 
		show_now_playing(); 
		$results['now_playing'] = ob_get_clean(); 
		ob_start(); 
		$data = Song::get_recently_played(); 
		Song::build_cache(array_keys($data)); 
		if (count($data)) { 
                        require_once Config::get('prefix') . '/templates/show_recently_played.inc.php';
		} 
		$results['recently_played'] = ob_get_clean(); 
	break; 
	case 'sidebar': 
                switch ($_REQUEST['button']) {
                        case 'home':
                        case 'modules':
                        case 'localplay':
                        case 'player':
                        case 'preferences':
                                $button = $_REQUEST['button'];
                        break;
                        case 'admin':
                                if (Access::check('interface','100')) { $button = $_REQUEST['button']; }
                                else { exit; }
                        break;
                        default:
                                exit;
                        break;
                } // end switch on button  

                ob_start();
                $_SESSION['state']['sidebar_tab'] = $button;
                require_once Config::get('prefix') . '/templates/sidebar.inc.php';
                $results['sidebar'] = ob_get_contents();
                ob_end_clean();
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
