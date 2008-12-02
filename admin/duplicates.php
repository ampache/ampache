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

require_once '../lib/init.php';

if (!Access::check('interface','100')) { 
	access_denied(); 
	exit;
}

show_header(); 

/* Switch on Action */
switch ($_REQUEST['action']) {
	case 'find_duplicates':
		$duplicates = Catalog::get_duplicate_songs($_REQUEST['search_type']); 
		require_once Config::get('prefix') . '/templates/show_duplicate.inc.php'; 
		require_once Config::get('prefix') . '/templates/show_duplicates.inc.php'; 	
	break;
	default:
		require_once Config::get('prefix') . '/templates/show_duplicate.inc.php'; 
	break;
} // end switch on action

show_footer();
?>
