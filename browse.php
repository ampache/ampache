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


/* Show the Footer */
show_page_footer('Browse', '',$user->prefs['display_menu']);

?>
