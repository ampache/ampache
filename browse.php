<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All Rights Reserved

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
 *
 * Browse By Page
 * This page shows the browse menu, which allows you to browse by many different
 * fields including genre, artist, album, catalog, ??? 
 * this page also handles the actuall browse action
 * @package Web Interface
 * @catagory Browse
 * @author Karl Vollmer 06/24/05
 *
 */

/* Base Require */
require_once 'lib/init.php';

/* Display the headers and menus */
require_once Config::get('prefix') . '/templates/header.inc.php';
echo '<div id="browse_content">'; 

switch($_REQUEST['action']) {
	case 'file':
	case 'album':
		Browse::set_type('album'); 
		$album_ids = Browse::get_objects(); 
		Browse::show_objects($album_ids); 
	break;
	case 'artist':
                show_alphabet_list('artists','artists.php');
                show_alphabet_form('',_("Show Artists starting with"),"artists.php?action=match");
                show_artists();
	break;
	case 'genre':
		/* Create the Needed Object */
		$genre = new Genre();

		/* Setup the View object */
		$view = new View();
		$view->import_session_view();
		$genre->show_match_list($_REQUEST['match']);
		$sql = $genre->get_sql_from_match($_REQUEST['match']);

		if ($_REQUEST['keep_view']) { 
			$view->initialize();
		}
		else { 
			$db_results = mysql_query($sql, dbh());
			$total_items = mysql_num_rows($db_results);
			$offset_limit = 999999;
			if ($match != 'Show_All') { $offset_limit = $user->prefs['offset_limit']; }
			$view = new View($sql, 'browse.php?action=genre','name',$total_items,$offset_limit);
		}
	
	        if ($view->base_sql) {
			$genres = $genre->get_genres($view->sql);
	                show_genres($genres,$view);
        	}
		
	break;
	case 'song':
		Browse::set_type('song'); 
		$song_ids = Browse::get_objects(); 
		Browse::show_objects($song_ids); 

	break;
	case 'catalog':
	
	break;
	default: 



	break; 
} // end Switch $action

echo '</div>';

/* Show the Footer */
show_footer();
?>
