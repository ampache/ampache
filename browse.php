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

/**
 *
 * Browse By Page
 * This page shows the browse menu, which allows you to browse by many different
 * fields including genre, artist, album, catalog, ??? 
 * this page also handles the actuall browse action
 *
 */

/* Base Require */
require_once 'lib/init.php';

// This page is a little wonky we don't want the sidebar until we know what type we're dealing with
// so we've got a little switch here that creates the type.. this feels hackish...
switch ($_REQUEST['action']) { 
	case 'tag': 
	case 'file': 
	case 'album': 
	case 'artist': 
	case 'playlist': 
	case 'live_stream': 
	case 'video': 
	case 'song': 
		Browse::set_type($_REQUEST['action']); 
		Browse::reset(); 
		Browse::set_simple_browse(true); 
	break; 
} // end switch 

show_header(); 

switch($_REQUEST['action']) {
	case 'file':
	break; 
	case 'album':
		Browse::set_sort('name','ASC');
		Browse::show_objects(); 
	break;
	case 'tag': 
		Browse::set_sort('count','ASC'); 
		// This one's a doozy
		Browse::set_simple_browse(0); 
		Browse::save_objects(Tag::get_tags(Config::get('offset_limit'),array())); 
		$keys = array_keys(Browse::get_saved()); 
		Tag::build_cache($keys); 
		Browse::show_objects(); 
	break; 
	case 'artist':
		Browse::set_sort('name','ASC');
		Browse::show_objects(); 
	break;
	case 'song':
		Browse::set_sort('title','ASC');
		Browse::show_objects(); 
	break;
	case 'live_stream':
		Browse::set_sort('name','ASC');
		Browse::show_objects(); 
	break;
	case 'catalog':
	
	break;
	case 'playlist': 
		Browse::set_sort('type','ASC');
		Browse::set_filter('playlist_type','1');
		Browse::show_objects(); 
	break;
	case 'video': 
		Browse::set_sort('title','ASC'); 
		Browse::show_objects(); 
	break; 
	default: 

	break; 
} // end Switch $action

/* Show the Footer */
show_footer();
?>
