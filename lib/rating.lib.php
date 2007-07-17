<?php
/*

 Copyright 2001 - 2007 Ampache.org
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
 * show_rating
 * This takes an artist id and includes the right file
 */
function show_rating($object_id,$type) { 

	$rating = new Rating($object_id,$type);
	
	require Config::get('prefix') . '/templates/show_object_rating.inc.php';

} // show_rating

//  andy90s rating patch added
function show_rating_static($object_id,$type) {

    $rating = new Rating($object_id,$type);

    require Config::get('prefix') . '/templates/show_object_rating_static.inc.php';

} // show_rating_static

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
