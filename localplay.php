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

require 'lib/init.php';

show_header(); 

// Check to see if we've got the rights to be here
if (!Config::get('allow_localplay_playback') || !Access::check('interface','25')) { 
	access_denied(); 
	exit; 
} 


switch ($_REQUEST['action']) { 
	case 'show_add_instance': 
		// This requires 50 or better
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		
		// Get the current localplay fields
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$fields = $localplay->get_instance_fields(); 
		require_once Config::get('prefix') . '/templates/show_localplay_add_instance.inc.php'; 
	break;
	case 'add_instance': 
		// This requires 50 or better!
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		
		// Setup the object
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$localplay->add_instance($_POST); 
	break;
	case 'update_instance': 
		// Make sure they gots them rights
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$localplay->update_instance($_REQUEST['instance'],$_POST); 
		header("Location:" . Config::get('web_path') . "/localplay.php?action=show_instances"); 
	break; 
	case 'edit_instance': 
		// Check to make sure they've got the access
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$instance = $localplay->get_instance($_REQUEST['instance']); 
		$fields = $localplay->get_instance_fields(); 
		require_once Config::get('prefix') . '/templates/show_localplay_edit_instance.inc.php'; 
	break; 
	case 'show_instances': 
		// First build the localplay object and then get the instances
		if (!Access::check('localplay','5')) { access_denied(); break; } 
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$instances = $localplay->get_instances(); 
		$fields = $localplay->get_instance_fields(); 
		require_once Config::get('prefix') . '/templates/show_localplay_instances.inc.php'; 
	break; 
	default: 
	case 'show_playlist': 
		if (!Access::check('localplay','5')) { access_denied(); break; } 
		// Init and then connect to our localplay instance
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$localplay->connect(); 

		// Pull the current playlist and require the template
		$objects = $localplay->get(); 
		require_once Config::get('prefix') . '/templates/show_localplay_status.inc.php';
		Browse::set_type('playlist_localplay'); 
		Browse::set_static_content(1); 
		Browse::show_objects($objects); 
	break;
} // end switch action

show_footer();
?>
