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
	case 'flip_state': 
		if (!Access::check('interface','75')) { 
			debug_event('DENIED',$GLOBALS['user']->username . ' attempted to change the state of a song','1'); 
			exit; 
		} 

		$song = new Song($_REQUEST['song_id']); 
		$new_enabled = $song->enabled ? '0' : '1'; 
		$song->update_enabled($new_enabled,$song->id); 
		$song->enabled = $new_enabled; 
		$song->format(); 
		
		//Return the new Ajax::button
		$id = 'button_flip_state_' . $song->id;
		$button = $song->enabled ? 'disable' : 'enable';
		$results[$id] = Ajax::button('?page=song&action=flip_state&song_id=' . $song->id,$button,_(ucfirst($button)),'flip_state_' . $song->id);  

	break;
	default: 
		$results['rfc3514'] = '0x1'; 
	break;
} // switch on action; 

// We always do this
echo xml_from_array($results); 
?>
