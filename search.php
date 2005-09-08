<?php
/*

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

require_once("modules/init.php");

show_template('header');
show_menu_items('Search'); 
show_clear();

/* Import/Clean vars */
$action = scrub_in($_REQUEST['action']);

switch ($action) { 
	case 'quick_search':
		/* This needs to be done because we don't know what thing
		 * they used the quick search to search on until after they've
		 * submited it 
		 */
		$string_name = $_REQUEST['search_object'][0] . '_string';
		$_REQUEST[$string_name] = $_REQUEST['search_string'];
		unset($string_name);
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

if ($_REQUEST['action'] === 'search') {
	run_search($_REQUEST['search_string'], $_REQUEST['search_field'], $_REQUEST['search_type']);
}


show_clear();
show_page_footer ('Search', '',$user->prefs['display_menu']);
?>
