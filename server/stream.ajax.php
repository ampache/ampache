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
	case 'set_play_type': 
		// Make sure they have the rights to do this
		if (!Preference::has_access('play_type')) { 
			$results['rfc3514'] = '0x1'; 
			break;
		} 

		switch ($_POST['type']) { 
			case 'stream': 
			case 'localplay':
			case 'democratic': 
				$key = 'allow_' . $_POST['type'] . '_playback'; 
				if (!Config::get($key)) { 
					$results['rfc3514'] = '0x1'; 
					break 2; 
				} 
				$new = $_POST['type']; 
			break; 
			case 'xspf_player': 
				$new = $_POST['type']; 
				// Rien a faire
			break; 
			default: 
				$new = 'stream'; 
				$results['rfc3514'] = '0x1'; 
			break 2; 
		} // end switch 

		$current = Config::get('play_type'); 

		// Go ahead and update their preference
		if (Preference::update('play_type',$GLOBALS['user']->id,$new)) { 
			Config::set('play_type',$new,'1'); 
		} 
		

		if (($new == 'localplay' AND $current != 'localplay') OR ($current == 'localplay' AND $new != 'localplay')) { 
			$results['rightbar'] = ajax_include('rightbar.inc.php'); 
		} 

		$results['rfc3514'] = '0x0'; 

	break;
	case 'basket': 

		// Go ahead and see if we should clear the playlist here or not, we might not actually clear it in the session
		// we'll just have to feed it bad data. 
		// FIXME: This is sad, will be fixed when I switch how streaming works. 
                // Check to see if 'clear' was passed if it was then we need to reset the basket
                if ( ($_REQUEST['playlist_method'] == 'clear' || Config::get('playlist_method') == 'clear') AND Config::get('play_type') != 'xspf_player') {
			define('NO_SONGS','1'); 
			ob_start();	
			require_once Config::get('prefix') . '/templates/rightbar.inc.php';  
			$results['rightbar'] = ob_get_clean(); 
                }

		// We need to set the basket up!
		$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=basket&playlist_method=' . scrub_out($_REQUEST['playlist_method']); 
		$results['rfc3514'] = '<script type="text/javascript">reload_util(\''.$_SESSION['iframe']['target'] . '\');</script>'; 
	break;
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
