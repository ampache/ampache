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

/* Because this is accessed via Ajax we are going to allow the session_id
 * as part of the get request
 */

// Set that this is an ajax include
define('AJAX_INCLUDE','1');

require_once '../lib/init.php';

$results = '';

debug_event('edit.server.php', 'Called for action: {'.$_REQUEST['action'].'}', '5');

// Post first
$type = $_POST['type'];
if (empty($type)) {
    $type = $_GET['type'];
}
$object_id = $_GET['id'];

if (empty($type)) {
    $object_type = $_GET['object_type'];
} else {
    $object_type = implode('_', explode('_', $type, -1));
}

if (!Core::is_library_item($object_type)) {
    debug_event('edit.server.php', 'Type `' . $type . '` is not based on an item library.', '3');
    exit();
}

$libitem = new $object_type($object_id);
$libitem->format();

$level = '50';
if ($libitem->get_user_owner() == $GLOBALS['user']->id) {
    $level = '25';
}

// Make sure they got them rights
if (!Access::check('interface', $level) || AmpConfig::get('demo_mode')) {
    echo xoutput_from_array(array('rfc3514' => '0x1'));
    exit;
}

switch ($_REQUEST['action']) {
    case 'show_edit_object':
        ob_start();
        require AmpConfig::get('prefix') . '/templates/show_edit_' . $type . '.inc.php';
        $results = ob_get_contents();
    break;
    case 'refresh_updated':
        require AmpConfig::get('prefix') . '/templates/show_' . $type . '.inc.php';
        $results = ob_get_contents();
    break;
    case 'show_edit_playlist':
        ob_start();
        require AmpConfig::get('prefix') . '/templates/show_playlists_dialog.inc.php';
        $results = ob_get_contents();
        ob_end_clean();
    break;
    case 'edit_object':
        // Scrub the data
        foreach ($_POST as $key => $data) {
            $_POST[$key] = unhtmlentities(scrub_in($data));
        }

        // this break generic layer, we should move it somewhere else
        if ($type == 'song_row') {
            $song = new Song($_POST['id']);
            if ($song->user_upload == $GLOBALS['user']->id && AmpConfig::get('upload_allow_edit') && !Access::check('interface','75')) {
                if (isset($_POST['artist'])) unset($_POST['artist']);
                if (isset($_POST['album'])) unset($_POST['album']);
                $levelok = true;
            }
        }

        $new_id = $libitem->update($_POST);
        $libitem = new $object_type($new_id);
        $libitem->format();

        xoutput_headers();
        $results['id'] = $new_id;
        echo xoutput_from_array($results);
        exit;
    default:
        exit;
} // end switch action

ob_end_clean();
echo $results;
