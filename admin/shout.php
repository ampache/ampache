<?php
/*

 Copyright (c) 2001 - 2007 ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

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

// Switch on the incomming action
switch ($_REQUEST['action']) { 
	case 'edit_shout': 
		$shout_id = $_POST['shout_id']; 
		$update = shoutBox::update($_POST); 
		show_confirmation(_('Shoutbox Post Updated'),'','admin/shout.php');
	break; 
        case 'show_edit':
                $shout = new shoutBox($_REQUEST['shout_id']);
		$object = shoutBox::get_object($shout->object_type,$shout->object_id); 
		$object->format();
		$client = new User($shout->user);
		$client->format();
                require_once Config::get('prefix') . '/templates/show_edit_shout.inc.php';
        break;
	case 'delete':
		$shout_id = shoutBox::delete($_REQUEST['shout_id']);
		 show_confirmation(_('Shoutbox Post Deleted'),'','admin/shout.php');
	break;
	default:
		Browse::set_type('shoutbox'); 
		Browse::set_simple_browse(1); 
		$shoutbox_ids = Browse::get_objects(); 
		Browse::show_objects($shoutbox_ids); 
	break; 
} // end switch on action

show_footer(); 
?>
