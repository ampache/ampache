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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

UI::show_header();

// Check to see if we've got the rights to be here
if (!AmpConfig::get('allow_localplay_playback') || !Access::check('interface', 25)) {
    UI::access_denied();

    return false;
}

/**
 * Check for the refresh mojo, if it's there then require the
 * refresh_javascript include. Must be greater then 5, I'm not
 * going to let them break their servers
 */
$refresh_limit = AmpConfig::get('refresh_limit', 0);
if ($refresh_limit > 5) {
    $ajax_url      = '?page=localplay&action=command&command=refresh';
    require_once AmpConfig::get('prefix') . UI::find_template('javascript_refresh.inc.php');
}

switch ($_REQUEST['action']) {
    case 'show_add_instance':
        // This requires 50 or better
        if (!Access::check('localplay', 75)) {
            UI::access_denied();
            break;
        }

        // Get the current Localplay fields
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $fields    = $localplay->get_instance_fields();
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_add_instance.inc.php');
        break;
    case 'add_instance':
        // This requires 50 or better!
        if (!Access::check('localplay', 75)) {
            UI::access_denied();
            break;
        }

        // Setup the object
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->add_instance($_POST);
        header("Location:" . AmpConfig::get('web_path') . "/localplay.php?action=show_instances");
        break;
    case 'update_instance':
        // Make sure they gots them rights
        if (!Access::check('localplay', 75)) {
            UI::access_denied();
            break;
        }
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->update_instance($_REQUEST['instance'], $_POST);
        header("Location:" . AmpConfig::get('web_path') . "/localplay.php?action=show_instances");
        break;
    case 'edit_instance':
        // Check to make sure they've got the access
        if (!Access::check('localplay', 75)) {
            UI::access_denied();
            break;
        }
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $instance  = $localplay->get_instance($_REQUEST['instance']);
        $fields    = $localplay->get_instance_fields();
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_edit_instance.inc.php');
        break;
    case 'show_instances':
        // First build the Localplay object and then get the instances
        if (!Access::check('localplay', 5)) {
            UI::access_denied();
            break;
        }
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $instances = $localplay->get_instances();
        $fields    = $localplay->get_instance_fields();
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_instances.inc.php');
        break;
    case 'show_playlist':
    default:
        if (!Access::check('localplay', 5)) {
            UI::access_denied();
            break;
        }
        // Init and then connect to our Localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        // Pull the current playlist and require the template
        $objects = $localplay->get();
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_status.inc.php');
        break;
} // end switch action

// Show the Footer
UI::show_query_stats();
UI::show_footer();
