<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

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

switch ($_REQUEST['action']) { 
	case 'get_advanced': 
		$object_ids = Random::advanced($_POST); 

		// We need to add them to the active playlist
		foreach ($object_ids as $object_id) { 
			$GLOBALS['user']->playlist->add_object($object_id,'song'); 
		} 

	case 'advanced':
	default: 
		require_once Config::get('prefix') . '/templates/show_random.inc.php';	
/*		require_once Config::get('prefix') . '/templates/show_random_rules.inc.php';*/

	break;
} // end switch 

show_footer(); 
?>
