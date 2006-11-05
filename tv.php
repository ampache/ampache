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
if (!conf('allow_democratic_playback') || $GLOBALS['user']->prefs['play_type'] != 'democratic') { 
	access_denied(); 
	exit;
}

/* Clean up the stuff we need */
$action 	= scrub_in($_REQUEST['action']);

switch ($action) { 
	case 'create_playlist':
		/* We need to make ourselfs a new tmp playlist */
		$tmp_playlist	= new tmpPlaylist();
		$id = $tmp_playlist->create('-1','vote','song',$_REQUEST['playlist_id']);	
		
		/* Re-generate the playlist */
		$tmp_playlist = new tmpPlaylist($id);
		$songs = $tmp_playlist->get_items();
		require_once(conf('prefix') . '/templates/show_tv.inc.php');
	break;
	default: 
		$tmp_playlist = get_democratic_playlist('-1'); 
		$songs = $tmp_playlist->get_items();
		require_once(conf('prefix') . '/templates/show_tv.inc.php');
	break;
} // end switch on action


?>
