<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
require_once('lib/init.php');

$dbh = dbh();
$web_path = conf('web_path');

/* Make sure they have access to this */
if (!conf('allow_democratic_playback')) { 
	access_denied(); 
	exit;
}

/* Clean up the stuff we need */
$action 	= scrub_in($_REQUEST['action']);


switch ($action) { 
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

		$stream_type = scrub_in($_REQUEST['play_type']);
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmp_playlist_id']);
		$stream = new Stream($stream_type,array()); 
		$stream->manual_url_add(unhtmlentities($tmp_playlist->get_vote_url()));
		$stream->start();
		if ($stream_type != 'localplay') { exit; } 
	break;
	case 'update_playlist':
		/* Only Admins Here */
		if (!$GLOBALS['user']->has_access(100)) { 
			access_denied();
			break;
		}
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmp_playlist_id']);
		$tmp_playlist->update_playlist($_REQUEST['playlist_id']);
	/* Display the default tv page */
	default: 
		$tmp_playlist = get_democratic_playlist('-1'); 
		$songs = $tmp_playlist->get_items();
		require_once(conf('prefix') . '/templates/show_tv.inc.php');
	break;
} // end switch on action

show_footer(); 

?>
