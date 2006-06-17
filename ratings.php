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

require_once('lib/init.php');


show_template('header');
$action = scrub_in($_REQUEST['action']);

switch ($action) { 
	case 'set_rating':
		$rating = new Rating($_REQUEST['object_id'],$_REQUEST['rating_type']);
		$rating->set_rating($_REQUEST['rating']);
		show_confirmation(_('Rating Updated'),_('Your rating for this object has been updated'),return_referer());
	break;
	default:

	break;
} // switch on the action

show_footer(); 


?>
