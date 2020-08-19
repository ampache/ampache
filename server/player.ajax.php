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
    case 'show_broadcasts':
        ob_start();
        require AmpConfig::get('prefix') . UI::find_template('show_broadcasts_dialog.inc.php');
        $results = ob_get_contents();
        ob_end_clean();
        echo $results;

        return false;
    case 'broadcast':
        $broadcast_id = Core::get_get('broadcast_id');
        if (empty($broadcast_id)) {
            $broadcast_id = Broadcast::create(T_('My Broadcast'));
        }

        $broadcast = new Broadcast((int) $broadcast_id);
        if ($broadcast->id) {
            $key  = Broadcast::generate_key();
            $broadcast->update_state(true, $key);
            $results['broadcast'] = Broadcast::get_unbroadcast_link((int) $broadcast_id) . '' .
                '<script>startBroadcast(\'' . $key . '\');</script>';
        }
        break;
    case 'unbroadcast':
        $broadcast_id = Core::get_get('broadcast_id');
        $broadcast    = new Broadcast((int) $broadcast_id);
        if ($broadcast->id) {
            $broadcast->update_state(false);
            $results['broadcast'] = Broadcast::get_broadcast_link() . '' .
                '<script>stopBroadcast();</script>';
        }
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;

// We always do this
echo (string) xoutput_from_array($results);
