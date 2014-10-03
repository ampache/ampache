<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

require_once '../lib/init.php';

if (!$GLOBALS['user']->has_access(100)) {
    UI::access_denied();
    exit();
}


/* Always show the header */
UI::show_header();

switch ($_REQUEST['action']) {
    case 'install_localplay':
        $localplay = new Localplay($_REQUEST['type']);
        if (!$localplay->player_loaded()) {
            Error::add('general', T_('Install Failed, Controller Error'));
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

        header("Location:" . AmpConfig::get('web_path') . '/admin/modules.php?action=show_localplay');
    break;
    case 'install_catalog_type':
        $type = (string) scrub_in($_REQUEST['type']);
        $catalog = Catalog::create_catalog_type($type);
        if ($catalog == null) {
            Error::add('general', T_('Install Failed, Catalog Error'));
            Error::display('general');
            break;
        }

        $catalog->install();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_catalog_types';
        $title  = T_('Plugin Installed');
        $body   = '';
        show_confirmation($title ,$body, $url);
    break;
    case 'confirm_uninstall_localplay':
        $type = (string) scrub_in($_REQUEST['type']);
        $url = AmpConfig::get('web_path') . '/admin/modules.php?action=uninstall_localplay&amp;type=' . $type;
        $title = T_('Are you sure you want to remove this plugin?');
        $body = '';
        show_confirmation($title,$body,$url,1);
    break;
    case 'confirm_uninstall_catalog_type':
        $type = (string) scrub_in($_REQUEST['type']);
        $url = AmpConfig::get('web_path') . '/admin/modules.php?action=uninstall_catalog_type&amp;type=' . $type;
        $title = T_('Are you sure you want to remove this plugin?');
        $body = '';
        show_confirmation($title,$body,$url,1);
    break;
    case 'uninstall_localplay':
        $type = (string) scrub_in($_REQUEST['type']);

        $localplay = new Localplay($type);
        $localplay->uninstall();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_localplay';
        $title  = T_('Plugin Deactivated');
        $body   = '';
        show_confirmation($title,$body,$url);
    break;
    case 'uninstall_catalog_type':
        $type = (string) scrub_in($_REQUEST['type']);

        $catalog = Catalog::create_catalog_type($type);
        if ($catalog == null) {
            Error::add('general', T_('Uninstall Failed, Catalog Error'));
            Error::display('general');
            break;
        }
        $catalog->uninstall();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_catalog_types';
        $title  = T_('Plugin Deactivated');
        $body   = '';
        show_confirmation($title, $body, $url);
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
            $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
            $title = T_('Unable to Install Plugin');
            $body = '';
            show_confirmation($title,$body,$url);
            break;
        }

        // Don't trust the plugin to this stuff
        User::rebuild_all_preferences();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
        $title    = T_('Plugin Activated');
        $body    = '';
        show_confirmation($title,$body,$url);
    break;
    case 'confirm_uninstall_plugin':
        $plugin = scrub_in($_REQUEST['plugin']);
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=uninstall_plugin&amp;plugin=' . $plugin;
        $title    = T_('Are you sure you want to remove this plugin?');
        $body    = '';
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
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
        $title  = T_('Plugin Deactivated');
        $body   = '';
        show_confirmation($title,$body,$url);
    break;
    case 'upgrade_plugin':
        /* Verify that this plugin exists */
        $plugins = Plugin::get_plugins();
        if (!array_key_exists($_REQUEST['plugin'],$plugins)) {
            debug_event('plugins','Error: Invalid Plugin: ' . $_REQUEST['plugin'] . ' selected','1');
            break;
        }
        $plugin = new Plugin($_REQUEST['plugin']);
        $plugin->upgrade();
        User::rebuild_all_preferences();
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
        $title  = T_('Plugin Upgraded');
        $body   = '';
        show_confirmation($title, $body, $url);
    break;
    case 'show_plugins':
        $plugins = Plugin::get_plugins();
        UI::show_box_top(T_('Plugins'), 'box box_localplay_plugins');
        require_once AmpConfig::get('prefix') . '/templates/show_plugins.inc.php';
        UI::show_box_bottom();
    break;
    case 'show_localplay':
        $controllers = Localplay::get_controllers();
        UI::show_box_top(T_('Localplay Controllers'), 'box box_localplay_controllers');
        require_once AmpConfig::get('prefix') . '/templates/show_localplay_controllers.inc.php';
        UI::show_box_bottom();
    break;
    case 'show_catalog_types':
        $catalogs = Catalog::get_catalog_types();
        UI::show_box_top(T_('Catalog Types'), 'box box_catalog_types');
        require_once AmpConfig::get('prefix') . '/templates/show_catalog_types.inc.php';
        UI::show_box_bottom();
    break;
    default:
        // Rien a faire
    break;
} // end switch

UI::show_footer();
