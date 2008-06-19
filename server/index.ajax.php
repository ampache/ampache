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
		$albums = get_random_albums('6'); 
		if (count($albums)) { 
			ob_start(); 
			require_once Config::get('prefix') . '/templates/show_random_albums.inc.php'; 
			$results['random_selection'] = ob_get_contents(); 
			ob_end_clean(); 
		} 
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
