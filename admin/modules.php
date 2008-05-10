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

require_once '../lib/init.php';

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
	exit();
}


/* Always show the header */
show_header(); 

switch ($_REQUEST['action']) { 
	case 'install_localplay': 
		$localplay = new Localplay($_REQUEST['type']); 
		if (!$localplay->player_loaded()) { 
			Error::add('general',_('Install Failed, Controller Error')); 
			Error::display('general'); 
			break;
		} 	
		// Install it!
		$localplay->install(); 

		// Go ahead and enable Localplay (Admin->System) as we assume they want to do that
		// if they are enabling this
		Preference::update('allow_localplay_playback','-1','1'); 
		Preference::update('localplay_level',$GLOBALS['user']->id,'100'); 
		Preference::update('localplay_controller',$GLOBALS['user']->id,$localplay->type);

		header("Location:" . Config::get('web_path') . '/admin/modules.php?action=show_localplay'); 
	break;
	case 'confirm_uninstall_localplay': 
		$type = scrub_in($_REQUEST['type']); 
		$url = Config::get('web_path') . '/admin/modules.php?action=uninstall_localplay&amp;type=' . $type; 
		$title = _('Are you sure you want to remove this plugin?'); 
		$body = ''; 
		show_confirmation($title,$body,$url,1); 
	break;
	case 'uninstall_localplay': 
		$type = scrub_in($_REQUEST['type']); 

		$localplay = new Localplay($type); 
		$localplay->uninstall(); 
			
                /* Show Confirmation */
                $url    = Config::get('web_path') . '/admin/modules.php?action=show_localplay';
                $title  = _('Plugin Deactivated');
                $body   = '';
                show_confirmation($title,$body,$url);
	break;
	case 'install_plugin':
		/* Verify that this plugin exists */
		$plugins = Plugin::get_plugins();  
		if (!array_key_exists($_REQUEST['plugin'],$plugins)) { 
			debug_event('plugins','Error: Invalid Plugin: ' . $_REQUEST['plugin'] . ' selected','1'); 
			break;
		}
		$plugin = new Plugin($_REQUEST['plugin']); 
		if (!$plugin->install()) { 
			debug_event('plugins','Error: Plugin Install Failed, ' . $_REQUEST['plugin'],'1'); 
			$url    = Config::get('web_path') . '/admin/modules.php?action=show_plugins';
			$title = _('Unable to Install Plugin'); 
			$body = ''; 
			show_confirmation($title,$body,$url); 
			break; 
		} 

		// Don't trust the plugin to this stuff
		User::rebuild_all_preferences(); 
		
		/* Show Confirmation */
		$url	= Config::get('web_path') . '/admin/modules.php?action=show_plugins';
		$title	= _('Plugin Activated'); 
		$body	= '';
		show_confirmation($title,$body,$url); 
	break;
	case 'confirm_uninstall_plugin':
		$plugin = scrub_in($_REQUEST['plugin']); 
		$url	= Config::get('web_path') . '/admin/modules.php?action=uninstall_plugin&amp;plugin=' . $plugin;
		$title	= _('Are you sure you want to remove this plugin?'); 
		$body	= '';
		show_confirmation($title,$body,$url,1); 
	break; 
	case 'uninstall_plugin':
		/* Verify that this plugin exists */
                $plugins = Plugin::get_plugins(); 
                if (!array_key_exists($_REQUEST['plugin'],$plugins)) {
                        debug_event('plugins','Error: Invalid Plugin: ' . $_REQUEST['plugin'] . ' selected','1');
                        break;
                }
                $plugin = new Plugin($_REQUEST['plugin']);
		$plugin->uninstall(); 

		// Don't trust the plugin to do it
		User::rebuild_all_preferences(); 

                /* Show Confirmation */
                $url    = Config::get('web_path') . '/admin/modules.php?action=show_plugins';
                $title  = _('Plugin Deactivated');
                $body   = '';
                show_confirmation($title,$body,$url);
	break;
	case 'upgrade_plugin':

	break;
	case 'show_plugins': 
		$plugins = Plugin::get_plugins(); 	
		show_box_top(_('Plugins')); 
		require_once Config::get('prefix') . '/templates/show_plugins.inc.php'; 
		show_box_bottom(); 
	break;
	case 'show_localplay': 
		$controllers = Localplay::get_controllers(); 
		show_box_top(_('Localplay Controllers')); 
		require_once Config::get('prefix') . '/templates/show_localplay_controllers.inc.php'; 
		show_box_bottom(); 
	break;
	default: 
		// Rien a faire
	break;
} // end switch

show_footer(); 


?>
