<?php

/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

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
require_once("modules/init.php");

show_template('header');
show_menu_items('Browse'); 
show_browse_menu('Genre');
show_clear();

$action = scrub_in($_REQUEST['action']);

switch($action) { 
	case 'show_songs':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$songs = $genre->get_songs();
		show_songs($songs);
	break;
	case 'show_albums':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$albums = $genre->get_albums();
		show_albums($albums);
	break;
	case 'show_artists':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$artists = $genre->get_artists();
		require (conf('prefix') . '/templates/show_artists.inc');
	break;
	case 'show_genre':
	default: 
		show_genre($_REQUEST['genre_id']);
	break;
} // action



show_clear();

/* Show the Footer */
show_page_footer('Browse', '',$user->prefs['display_menu']);

?>
