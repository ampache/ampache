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

/*!
	@header Search page 
 Search stuff.  Can do by artist, album and song title.

 Also case-insensitive for now.

*/

require_once('lib/init.php');

show_template('header');

/* Import/Clean vars */
$action = scrub_in($_REQUEST['action']);

switch ($action) { 
	case 'quick_search':
		/* This needs to be done because we don't know what thing
		 * they used the quick search to search on until after they've
		 * submited it 
		 */
		$_REQUEST['s_all'] = $_REQUEST['search_string'];
		
		if (strlen($_REQUEST['search_string']) < 1) { 
			$GLOBALS['error']->add_error('keyword',_("Error: No Keyword Entered"));
			show_template('show_search');
			break;
		}
	case 'search':
		show_template('show_search');
		$results = run_search($_REQUEST);
		show_search($_REQUEST['object_type'],$results);
	break;
	case 'save_as_track':
		$playlist_id = save_search($_REQUEST);
		$playlist = new Playlist($playlist_id);
		show_confirmation("Search Saved","Your Search has been saved as a track in $playlist->name",conf('web_path') . "/search.php");
	break;
	default:
		show_template('show_search');
	break;
}

/* Show the Footer */
show_footer();
?>
