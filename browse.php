<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
require_once('lib/init.php');

/* Clean up incomming variables */
$action		= scrub_in($_REQUEST['action']);

/* Display the headers and menus */
show_template('header');

switch($action) {
	case 'file':
	case 'album':
		show_alphabet_list('albums','albums.php',$match);
		show_alphabet_form($match,_("Show Albums starting with"),"albums.php?action=match");

		/* Get the results and show them */
		$sql = "SELECT id FROM album WHERE name LIKE '$match%'";

		$view = new View();
		$view->import_session_view();

	        // if we are returning
	        if ($_REQUEST['keep_view']) {
	                $view->initialize();
	        }

	        // If we aren't keeping the view then initlize it
	        elseif ($sql) {
	                $db_results = mysql_query($sql, dbh());
	                $total_items = mysql_num_rows($db_results);
	                if ($match != "Show_all") { $offset_limit = $_SESSION['userdata']['offset_limit']; }
	                $view = new View($sql, 'albums.php','name',$total_items,$offset_limit);
	        }

	        else { $view = false; }

	        if ($view->base_sql) {
	                $albums = get_albums($view->sql);
                	show_albums($albums,$view);
		}		
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
			if ($match != 'Show_All') { $offset_limit = $_SESSION['userdata']['offset_limit']; }
			$view = new View($sql, 'browse.php?action=genre','name',$total_items,$offset_limit);
		}
	
	        if ($view->base_sql) {
			$genres = $genre->get_genres($view->sql);
	                show_genres($genres,$view);
        	}
		
	break;
	default:
	case 'song_title':
		/* Create the Needed Object */
		$song = new Song();


		/* Setup the View Object */
		$view = new View();
		$view->import_session_view();

		$match = scrub_in($_REQUEST['match']);

		require (conf('prefix') . '/templates/show_box_top.inc.php');
	        show_alphabet_list('song_title','browse.php',$match,'song_title');
                /* Detect if it's Browse, and if so don't fill it in */
                if ($match == 'Browse') { $match = ''; }
                show_alphabet_form($match,_('Show Titles Starting With'),"browse.php");
		require (conf('prefix') . '/templates/show_box_bottom.inc.php');
	
		$sql = $song->get_sql_from_match($_REQUEST['match']);

		if ($_REQUEST['keep_view']) { 
			$view->initialize();
		}
		else { 
			$db_results = mysql_query($sql, dbh());
			$total_items = mysql_num_rows($db_results);
			$offset_limit = 999999;
			if ($match != 'Show All') { $offset_limit = $_SESSION['userdata']['offset_limit']; } 
			$view = new View($sql, 'browse.php?action=song_title','title',$total_items,$offset_limit);
		}

		if ($view->base_sql) { 
			$songs = $song->get_songs($view->sql);
			show_songs($songs,0,0);
		}
	break;
	case 'catalog':
	
	break;
} // end Switch $action

/* Show the Footer */
show_footer();
?>
