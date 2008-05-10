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
		Browse::reset_filters(); 
		Browse::set_type('song'); 
		Browse::set_sort('name','ASC'); 
		Browse::set_static_content(1); 
		Browse::save_objects($object_ids);
		Browse::show_objects($object_ids); 
	break;
	case 'show_genre':
	default:
	case 'show_albums':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$object_ids = $genre->get_albums();
		Browse::reset_filters(); 
		Browse::set_type('album'); 
		Browse::set_sort('name','ASC'); 
		Browse::set_static_content(1); 
		Browse::save_objects($object_ids); 
		Browse::show_objects($object_ids); 
	break;
	case 'show_artists':
		$genre = new Genre($_REQUEST['genre_id']);
		show_genre($_REQUEST['genre_id']);
		$object_ids = $genre->get_artists();
		Browse::reset_filters(); 
		Browse::set_type('artist'); 
		Browse::set_sort('name','ASC'); 
		Browse::set_static_content(1); 
		Browse::save_objects($object_ids); 
		Browse::show_objects($object_ids); 
	break;
} // action



/* Show the Footer */
show_footer();

?>
