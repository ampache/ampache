<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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
require_once("modules/init.php");

/* Clean up incomming variables */
$action		= scrub_in($_REQUEST['action']);

/* Display the headers and menus */
show_template('header');
show_menu_items('Browse'); 
show_browse_menu($_REQUEST['action']);
show_clear();

switch($action) {
	case 'album':
	case 'artist':
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
			if ($match != 'Show_All') { $offset_limit = $_SESSION['userdata']['offset_limit']; }
			$view = new View($sql, 'browse.php?action=genre','name',$total_items,$offset_limit);
		}
	
	        if ($view->base_sql) {
			$genres = $genre->get_genres($view->sql);
	                show_genres($genres,$view);
        	}
		
	break;
	case 'catalog':
	
	break;
	/* Throw recently added, updated here */
	default:

		/* Show Most Popular artist/album/songs */
		show_all_popular();

		/* Show Recent Additions */
		show_all_recent();

	break;

} // end Switch $action

show_clear();

/* Show the Footer */
show_page_footer('Browse', '',$user->prefs['display_menu']);

?>
