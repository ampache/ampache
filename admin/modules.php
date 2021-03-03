<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!Core::get_global('user')->has_access(100)) {
    UI::access_denied();

    return false;
}

/* Always show the header */
UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'install_localplay':
        $localplay = new Localplay(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        if (!$localplay->player_loaded()) {
            AmpError::add('general', T_('Failed to enable the Localplay module'));
            AmpError::display('general');
            break;
        }
        // Install it!
        $localplay->install();

        // Go ahead and enable Localplay (Admin->System) as we assume they want to do that
        // if they are enabling this
        Preference::update('allow_localplay_playback', -1, '1');
        Preference::update('localplay_level', Core::get_global('user')->id, '100');
        Preference::update('localplay_controller', Core::get_global('user')->id, $localplay->type);

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_localplay';
        $title  = T_('No Problem');
        $body   = T_('Localplay has been enabled');
        show_confirmation($title, $body, $url);
        break;
    case 'install_catalog_type':
        $type    = (string) scrub_in(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        $catalog = Catalog::create_catalog_type($type);
        if ($catalog == null) {
            AmpError::add('general', T_('Failed to enable the Catalog module'));
            AmpError::display('general');
            break;
        }

        $catalog->install();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_catalog_types';
        $title  = T_('No Problem');
        $body   = T_('The Module has been enabled');
        show_confirmation($title, $body, $url);
        break;
    case 'confirm_uninstall_localplay':
        $type  = (string) scrub_in(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        $url   = AmpConfig::get('web_path') . '/admin/modules.php?action=uninstall_localplay&amp;type=' . $type;
        $title = T_('Are You Sure?');
        $body  = T_('This will disable the Localplay module');
        show_confirmation($title, $body, $url, 1);
        break;
    case 'confirm_uninstall_catalog_type':
        $type  = (string) scrub_in(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        $url   = AmpConfig::get('web_path') . '/admin/modules.php?action=uninstall_catalog_type&amp;type=' . $type;
        $title = T_('Are You Sure?');
        $body  = T_('This will disable the Catalog module');
        show_confirmation($title, $body, $url, 1);
        break;
    case 'uninstall_localplay':
        $type = (string) scrub_in(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));

        $localplay = new Localplay($type);
        $localplay->uninstall();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_localplay';
        $title  = T_('No Problem');
        $body   = T_('The Module has been disabled');
        show_confirmation($title, $body, $url);
        break;
    case 'uninstall_catalog_type':
        $type = (string) scrub_in(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));

        $catalog = Catalog::create_catalog_type($type);
        if ($catalog == null) {
            AmpError::add('general', T_("Unable to disable the Catalog module."));
            AmpError::display('general');
            break;
        }
        $catalog->uninstall();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_catalog_types';
        $title  = T_('No Problem');
        $body   = T_('The Module has been disabled');
        show_confirmation($title, $body, $url);
        break;
    case 'install_plugin':
        /* Verify that this plugin exists */
        $plugins = Plugin::get_plugins();
        if (!array_key_exists($_REQUEST['plugin'], $plugins)) {
            debug_event('modules', 'Error: Invalid Plugin: ' . Core::get_request('plugin') . ' selected', 1);
            break;
        }
        $plugin = new Plugin($_REQUEST['plugin']);
        if (!$plugin->install()) {
            debug_event('modules', 'Error: Plugin Install Failed, ' . Core::get_request('plugin'), 1);
            $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
            $title  = T_("There Was a Problem");
            $body   = T_('Unable to install this Plugin');
            show_confirmation($title, $body, $url);
            break;
        }

        // Don't trust the plugin to this stuff
        User::rebuild_all_preferences();

        /* Show Confirmation */
        $url   = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
        $title = T_('No Problem');
        $body  = T_('The Plugin has been enabled');
        show_confirmation($title, $body, $url);
        break;
    case 'confirm_uninstall_plugin':
        $plugin = (string) scrub_in($_REQUEST['plugin']);
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=uninstall_plugin&amp;plugin=' . $plugin;
        $title  = T_('Are You Sure?');
        $body   = T_('This will disable the Plugin and remove your settings');
        show_confirmation($title, $body, $url, 1);
        break;
    case 'uninstall_plugin':
        /* Verify that this plugin exists */
        $plugins = Plugin::get_plugins();
        if (!array_key_exists($_REQUEST['plugin'], $plugins)) {
            debug_event('modules', 'Error: Invalid Plugin: ' . Core::get_request('plugin') . ' selected', 1);
            break;
        }
        $plugin = new Plugin($_REQUEST['plugin']);
        $plugin->uninstall();

        // Don't trust the plugin to do it
        User::rebuild_all_preferences();

        /* Show Confirmation */
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
        $title  = T_('No Problem');
        $body   = T_('The Plugin has been disabled');
        show_confirmation($title, $body, $url);
        break;
    case 'upgrade_plugin':
        /* Verify that this plugin exists */
        $plugins = Plugin::get_plugins();
        if (!array_key_exists($_REQUEST['plugin'], $plugins)) {
            debug_event('modules', 'Error: Invalid Plugin: ' . Core::get_request('plugin') . ' selected', 1);
            break;
        }
        $plugin = new Plugin($_REQUEST['plugin']);
        $plugin->upgrade();
        User::rebuild_all_preferences();
        $url    = AmpConfig::get('web_path') . '/admin/modules.php?action=show_plugins';
        $title  = T_('No Problem');
        $body   = T_('The Plugin has been upgraded');
        show_confirmation($title, $body, $url);
        break;
    case 'show_plugins':
        $plugins = Plugin::get_plugins();
        UI::show_box_top(T_('Manage Plugins'), 'box box_localplay_plugins');
        require_once AmpConfig::get('prefix') . UI::find_template('show_plugins.inc.php');
        UI::show_box_bottom();
        break;
    case 'show_localplay':
        $controllers = Localplay::get_controllers();
        UI::show_box_top(T_('Localplay Controllers'), 'box box_localplay_controllers');
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_controllers.inc.php');
        UI::show_box_bottom();
        break;
    case 'show_catalog_types':
        $catalogs = Catalog::get_catalog_types();
        UI::show_box_top(T_('Catalog Types'), 'box box_catalog_types');
        require_once AmpConfig::get('prefix') . UI::find_template('show_catalog_types.inc.php');
        UI::show_box_bottom();
        break;
    default:
        break;
} // end switch

// Show the Footer
UI::show_query_stats();
UI::show_footer();
