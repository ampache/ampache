<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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


/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) { exit; }

$results = array();
switch ($_REQUEST['action']) {
    case 'show_broadcasts':
        ob_start();
        require AmpConfig::get('prefix') . '/templates/show_broadcasts_dialog.inc.php';
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
        $broadcast = new Broadcast($broadcast_id);
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
