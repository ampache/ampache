<?php
/*

 Copyright 2001 - 2006 Ampache.org
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
 * show_rating
 * This takes an artist id and includes the right file
 */
function show_rating($object_id,$type) { 

	$rating = new Rating($object_id,$type);
	
	switch (conf('ratings')) { 
		case 'normal':
			include(conf('prefix') . '/templates/show_object_rating.inc.php');
		break;
		case 'flash':
			include(conf('prefix') . '/templates/show_object_rating_flash.inc.php');
		break;
		default:
			return false;
		break;
	} // end flash switch

	return true;

} // show_rating

/**
 * get_rating_name
 * This takes a score and returns the name that we should use 
 */
function get_rating_name($score) { 

	switch ($score) { 
		case '0':
			return _("Don't Play");
		break;
		case '1':
			return _("It's Pretty Bad");
		break;
		case '2':
			return _("It's Ok");
		break;
		case '3':
			return _("It's Pretty Good");
		break;
		case '4':
			return _("I Love It!");
		break;
		case '5':
			return _("It's Insane");
		break;
		// I'm fired
		default:
			return _("Off the Charts!");
		break;
	} // end switch

	return true;
	
} // get_rating_name

?>
