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
 * Genres Pages
 * Nuff Said for now
 */
require_once 'lib/init.php';

show_header();

/**
 * switch on action 
 */
switch($_REQUEST['action']) { 
	case 'show_songs':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$object_ids = $genre->get_songs();
		show_box_top(_('Songs')); 
		require_once Config::get('prefix') . '/templates/show_songs.inc.php'; 
		show_box_bottom(); 
	break;
	case 'show_genre':
	default:
	case 'show_albums':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$object_ids = $genre->get_albums();
		show_box_top(_('Albums')); 
		require Config::get('prefix') . '/templates/show_albums.inc.php';
		show_box_bottom(); 
	break;
	case 'show_artists':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$object_ids = $genre->get_artists();
		show_box_top(_('Artists'));
		require_once Config::get('prefix') . '/templates/show_artists.inc.php';
		show_box_bottom(); 
	break;
} // action



/* Show the Footer */
show_footer();

?>
