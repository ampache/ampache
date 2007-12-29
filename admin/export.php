<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/* Switch on Action */
switch ($_REQUEST['action']) {
	case 'export':
		
		// This may take a while
		set_time_limit(0); 
		
		$catalog = new Catalog($_REQUEST['export_catalog']);

		header("Content-Transfer-Encoding: binary");
		header("Cache-control: public");

		switch($_REQUEST['export_format']) {
		case 'itunes':
			header("Content-Type: application/itunes+xml; charset=utf-8");
			header("Content-Disposition: attachment; filename=\"itunes.xml\"");
			$catalog->export('itunes');
		break;
		}
		
	break;
	default:
		show_header(); 
		require_once Config::get('prefix') . '/templates/show_export.inc.php'; 
		show_footer();
	break;
} // end switch on action
?>
