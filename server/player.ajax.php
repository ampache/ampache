<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

$results = array();
switch ($_REQUEST['action']) {
    case 'show_broadcasts':
        ob_start();
        require AmpConfig::get('prefix') . UI::find_template('show_broadcasts_dialog.inc.php');
        $results = ob_get_contents();
        ob_end_clean();
        echo $results;
        exit;
    case 'broadcast':
        $broadcast_id = $_GET['broadcast_id'];
        if (empty($broadcast_id)) {
            $broadcast_id = Broadcast::create(T_('My Broadcast'));
        }

        $broadcast = new Broadcast($broadcast_id);
        if ($broadcast->id) {
            $key  = Broadcast::generate_key();
            $broadcast->update_state(true, $key);
            $results['broadcast'] = Broadcast::get_unbroadcast_link($broadcast_id) . '' .
                '<script type="text/javascript">startBroadcast(\'' . $key . '\');</script>';
        }
    break;
    case 'unbroadcast':
        $broadcast_id = $_GET['broadcast_id'];
        $broadcast    = new Broadcast($broadcast_id);
        if ($broadcast->id) {
            $broadcast->update_state(false);
            $results['broadcast'] = Broadcast::get_broadcast_link() . '' .
                '<script type="text/javascript">stopBroadcast();</script>';
        }
    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
