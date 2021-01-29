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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    return false;
}

$results = array();
$action  = Core::get_request('action');

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'set_instance':
        // Make sure they they are allowed to do this
        if (!Access::check('localplay', 5)) {
            debug_event('localplay.ajax', 'Error attempted to set instance without required level', 1);

            return false;
        }

        $type = $_REQUEST['instance'] ? 'localplay' : 'stream';

        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->set_active_instance($_REQUEST['instance']);
        Preference::update('play_type', Core::get_global('user')->id, $type);

        // We should also refesh the sidebar
        ob_start();
        require_once AmpConfig::get('prefix') . UI::find_template('sidebar.inc.php');
        $results['sidebar-content'] = ob_get_contents();
        ob_end_clean();
        break;
    case 'command':
        // Make sure they are allowed to do this
        if (!Access::check('localplay', 50)) {
            debug_event('localplay.ajax', 'Attempted to control Localplay without sufficient access', 1);

            return false;
        }

        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        // Switch on valid commands
        switch ($_REQUEST['command']) {
            case 'refresh':
                ob_start();
                $objects = $localplay->get();
                require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_status.inc.php');
                $results['localplay_status'] = ob_get_contents();
                ob_end_clean();
                break;
            case 'prev':
            case 'next':
            case 'stop':
            case 'play':
            case 'pause':
                $command = scrub_in($_REQUEST['command']);
                $localplay->$command();
                break;
            case 'volume_up':
            case 'volume_down':
            case 'volume_mute':
                $command = scrub_in($_REQUEST['command']);
                $localplay->$command();

                // We actually want to refresh something here
                ob_start();
                $objects = $localplay->get();
                require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_status.inc.php');
                $results['localplay_status'] = ob_get_contents();
                ob_end_clean();
                break;
            case 'delete_all':
                $localplay->delete_all();
                ob_start();
                $browse = new Browse();
                $browse->set_type('playlist_localplay');
                $browse->set_static_content(true);
                $browse->save_objects(array());
                $browse->show_objects(array());
                $browse->store();
                $results[$browse->get_content_div()] = ob_get_contents();
                ob_end_clean();
                break;
            case 'skip':
                $localplay->skip((int) filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT));
                $objects = $localplay->get();
                ob_start();
                $browse = new Browse();
                $browse->set_type('playlist_localplay');
                $browse->set_static_content(true);
                $browse->save_objects($objects);
                $browse->show_objects($objects);
                $browse->store();
                $results[$browse->get_content_div()] = ob_get_contents();
                ob_end_clean();
                break;
            default:
                break;
        } // end whitelist

        break;
    case 'delete_track':
        // Load Connect... yada yada
        if (!Access::check('localplay', 50)) {
            debug_event('localplay.ajax', 'Attempted to delete track without access', 1);

            return false;
        }
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        // Scrub in the delete request
        $id = (int) filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        $localplay->delete_track($id);

        // Wait in case we just deleted what we were playing
        sleep(3);
        $objects = $localplay->get();
        $status  = $localplay->status();

        ob_start();
        $browse = new Browse();
        $browse->set_type('playlist_localplay');
        $browse->set_static_content(true);
        $browse->save_objects($objects);
        $browse->show_objects($objects);
        $browse->store();
        $results[$browse->get_content_div()] = ob_get_contents();
        ob_end_clean();

        break;
    case 'delete_instance':
        // Make sure that you have access to do this...
        if (!Access::check('localplay', 75)) {
            debug_event('localplay.ajax', 'Attempted to delete instance without access', 1);

            return false;
        }

        // Scrub it in
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->delete_instance($_REQUEST['instance']);

        $key           = 'localplay_instance_' . $_REQUEST['instance'];
        $results[$key] = '';
        break;
    case 'repeat':
        // Make sure that they have access to do this again no clue
        if (!Access::check('localplay', 50)) {
            debug_event('localplay.ajax', 'Attempted to set repeat without access', 1);

            return false;
        }

        // Scrub her in
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();
        $localplay->repeat(make_bool($_REQUEST['value']));

        ob_start();
        $objects = $localplay->get();
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_status.inc.php');
        $results['localplay_status'] = ob_get_contents();
        ob_end_clean();

        break;
    case 'random':
        // Make sure that they have access to do this
        if (!Access::check('localplay', 50)) {
            debug_event('localplay.ajax', 'Attempted to set random without access', 1);

            return false;
        }

        // Scrub her in
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();
        $localplay->random(make_bool($_REQUEST['value']));

        ob_start();
        $objects = $localplay->get();
        require_once AmpConfig::get('prefix') . UI::find_template('show_localplay_status.inc.php');
        $results['localplay_status'] = ob_get_contents();
        ob_end_clean();

        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;

// We always do this
echo (string) xoutput_from_array($results);
