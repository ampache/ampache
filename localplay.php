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

require 'lib/init.php';

show_header(); 

// Check to see if we've got the rights to be here
if (!Config::get('allow_localplay_playback') || !$GLOBALS['user']->has_access('25')) { 
	access_denied(); 
} 


switch ($_REQUEST['action']) { 
	case 'show_add_instance': 
		// This requires 50 or better
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		
		// Get the current localplay fields
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$fields = $localplay->get_instance_fields(); 
		require_once Config::get('prefix') . '/templates/show_localplay_add_instance.inc.php'; 
	break;
	case 'add_instance': 
		// This requires 50 or better!
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		
		// Setup the object
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$localplay->add_instance($_POST); 
	break;
	case 'update_instance': 
		// Make sure they gots them rights
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$localplay->update_instance($_REQUEST['instance'],$_POST); 
		header("Location:" . Config::get('web_path') . "/localplay.php?action=show_instances"); 
	break; 
	case 'edit_instance': 
		// Check to make sure they've got the access
		if (!Access::check('localplay','75')) { access_denied(); break; } 
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$instance = $localplay->get_instance($_REQUEST['instance']); 
		$fields = $localplay->get_instance_fields(); 
		require_once Config::get('prefix') . '/templates/show_localplay_edit_instance.inc.php'; 
	break; 
	case 'test_instance': 
		// Check to make sure they've got the rights
		if (!Access::check('localplay','75')) { access_denied(); break; } 
	break; 
	case 'show_instances': 
		// First build the localplay object and then get the instances
		if (!Access::check('localplay','5')) { access_denied(); break; } 
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$instances = $localplay->get_instances(); 
		$fields = $localplay->get_instance_fields(); 
		require_once Config::get('prefix') . '/templates/show_localplay_instances.inc.php'; 
	break; 
	default: 
	case 'show_playlist': 
		if (!Access::check('localplay','5')) { access_denied(); break; } 
		// Init and then connect to our localplay instance
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$localplay->connect(); 

		// Pull the current playlist and require the template
		$objects = $localplay->get(); 
		require_once Config::get('prefix') . '/templates/show_localplay_status.inc.php';
		require_once Config::get('prefix') . '/templates/show_localplay_playlist.inc.php';
	break;
} // end switch action



show_footer();

?>
