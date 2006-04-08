<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

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

require('../modules/init.php');

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
	exit();
}


$action = scrub_in($_REQUEST['action']);

/* Always show the header */
show_template('header');

switch ($action) { 
	case 'insert_localplay_preferences':
		$type = scrub_in($_REQUEST['type']);
		insert_localplay_preferences($type);
		$url 	= conf('web_path') . '/admin/modules.php';
		$title 	= _('Module Activated');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	default: 
		require_once (conf('prefix') . '/templates/show_modules.inc.php');
	break;
} // end switch

show_footer(); 


?>
