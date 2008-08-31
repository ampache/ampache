<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

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

require_once 'lib/init.php';

show_header(); 

/**
 * action switch 
 */
switch ($_REQUEST['action']) { 
	case 'quick_search':
		/* This needs to be done because we don't know what thing
		 * they used the quick search to search on until after they've
		 * submited it 
		 */
		$_REQUEST['s_all'] = $_REQUEST['search_string'];
		
		if (strlen($_REQUEST['search_string']) < 1) { 
			Error::add('keyword',_('Error: No Keyword Entered'));
			require_once Config::get('prefix') . '/templates/show_search.inc.php'; 
			break;
		}
	case 'search':
		require_once Config::get('prefix') . '/templates/show_search.inc.php'; 
		require_once Config::get('prefix') . '/templates/show_search_options.inc.php'; 
		$results = run_search($_REQUEST);
		Browse::set_type('song'); 
		Browse::reset(); 
		Browse::show_objects($results); 
	break;
	case 'save_as_track':
		$playlist_id = save_search($_REQUEST);
		$playlist = new Playlist($playlist_id);
		show_confirmation("Search Saved","Your Search has been saved as a track in $playlist->name",conf('web_path') . "/search.php");
	break;
	default:
		require_once Config::get('prefix') . '/templates/show_search.inc.php'; 
	break;
}

/* Show the Footer */
show_footer();
?>
