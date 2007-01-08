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

require_once('../../lib/init.php');


$dbh = dbh();
$web_path = conf('web_path');

/* Attempt to build the temp playlist */
$action 	= scrub_in($_REQUEST['action']);

switch ($action) { 
	default:
	case 'tmp_playlist':
		// Set for hackage!
		$_REQUEST['flash_hack'] = 1;
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmp_id']);
		$items = $tmp_playlist->get_items();
		$stream = new Stream('xspf',$items);
		$stream->start();
	break;
	case 'show':
		$play_info = "?tmp_id=" . scrub_out($_REQUEST['tmpplaylist_id']);
		require_once (conf('prefix') . '/templates/show_xspf_player.inc.php');
	break;
} // end switch


?>
