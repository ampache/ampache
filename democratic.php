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

require_once 'lib/init.php';

/* Make sure they have access to this */
if (!Config::get('allow_democratic_playback')) { 
	access_denied(); 
	exit;
}

show_header(); 

// Switch on their action
switch ($_REQUEST['action']) { 
	case 'show_create': 
		if (!$GLOBALS['user']->has_access('75')) { 
			access_denied(); 
			break;
		} 

		// Show the create page
		require_once Config::get('prefix') . '/templates/show_create_democratic.inc.php'; 

	break; 
	case 'create': 
		// Only power users here 
		if (!$GLOBALS['user']->has_access('75')) { 
			access_denied(); 
			break;
		} 
		// Create the playlist
		//FIXME: don't use hardcoded id value here, needs db rework to fix this
		Democratic::create('-1','vote','song',$_REQUEST['democratic']); 
		header("Location: " . Config::get('web_path') . "/democratic.php?action=manage_playlists"); 	
	break; 
	case 'create_playlist':
		/* Only Admins Here */
		if (!$GLOBALS['user']->has_access(100)) { 
			access_denied(); 
			break;
		}
		/* We need to make ourselfs a new tmp playlist */
		$tmp_playlist	= new tmpPlaylist();
		$id = $tmp_playlist->create('-1','vote','song',$_REQUEST['playlist_id']);	
		
		/* Re-generate the playlist */
		$tmp_playlist = new tmpPlaylist($id);
		$songs = $tmp_playlist->get_items();
		require_once(conf('prefix') . '/templates/show_tv.inc.php');
	break;
	/* This clears the entire democratic playlist, admin only */
	case 'clear_playlist':
		if (!$GLOBALS['user']->has_access(100)) { 
			access_denied(); 
			break;
		}

		$tmp_playlist = new tmpPlaylist($_REQUEST['tmp_playlist_id']); 
		$tmp_playlist->clear_playlist(); 
		require_once(conf('prefix') . '/templates/header.inc');
		show_confirmation(_('Playlist Cleared'),'',conf('web_path') . '/tv.php'); 
		require_once(conf('prefix') . '/templates/footer.inc'); 
	break;
	/* This sends the playlist to the 'method' of their chosing */
	case 'send_playlist':
		/* Only Admins Here */
		if (!$GLOBALS['user']->has_access(100)) { 
			access_denied(); 
			break;
		}
		// Tmp just to make this work
		header("Location: " . Config::get('web_path') . "/stream.php?action=democratic"); 
		exit; 
	break;
	case 'manage_playlists': 
		if (!$GLOBALS['user']->has_access('75')) { 
			access_denied(); 
			break;
		} 
		// Get all of the non-user playlists
		$playlists = Democratic::get_playlists(); 

		require_once Config::get('prefix') . '/templates/show_manage_democratic.inc.php'; 

	break;
	case 'update_playlist':
		/* Only Admins Here */
		if (!$GLOBALS['user']->has_access(100)) { 
			access_denied();
			break;
		}
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmp_playlist_id']);
		$tmp_playlist->update_playlist($_REQUEST['playlist_id']);
	case 'show_playlist': 
	default: 
		$democratic = Democratic::get_current_playlist(); 
		$objects = $democratic->get_items();
		require_once Config::get('prefix') . '/templates/show_democratic.inc.php';
	break;
} // end switch on action

show_footer(); 

?>
