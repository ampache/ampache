<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

/* Because this is accessed via Ajax we are going to allow the session_id
 * as part of the get request
 */

// Set that this is an ajax include
define('AJAX_INCLUDE', '1');

require_once '../lib/init.php';

$results = '';

debug_event('edit.server.php', 'Called for action: {' . $_REQUEST['action'] . '}', '5');

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

if (!Core::is_library_item($object_type) && $object_type != 'share') {
    debug_event('edit.server.php', 'Type `' . $type . '` is not based on an item library.', '3');
    exit();
}

$libitem = new $object_type($object_id);
$libitem->format();

$level = '50';
if ($libitem->get_user_owner() == $GLOBALS['user']->id) {
    $level = '25';
}
if ($_REQUEST['action'] == 'show_edit_playlist') {
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
        require AmpConfig::get('prefix') . UI::find_template('show_edit_' . $type . '.inc.php');
        $results = ob_get_contents();
    break;
    case 'refresh_updated':
        require AmpConfig::get('prefix') . UI::find_template('show_' . $type . '.inc.php');
        $results = ob_get_contents();
    break;
    case 'show_edit_playlist':
        ob_start();
        require AmpConfig::get('prefix') . UI::find_template('show_playlists_dialog.inc.php');
        $results = ob_get_contents();
        ob_end_clean();
    break;
    case 'edit_object':
        // Scrub the data, walk recursive through array
        $entities = function (&$data) use (&$entities) {
            foreach ($data as $key => $value) {
                $data[$key] = is_array($value) ? $entities($value) : unhtmlentities(scrub_in($value));
            }
            return $data;
        };
        $entities($_POST);

        $libitem = new $object_type($_POST['id']);
        if ($libitem->get_user_owner() == $GLOBALS['user']->id && AmpConfig::get('upload_allow_edit') && !Access::check('interface', 50)) {
            // TODO: improve this uniqueless check
            if (isset($_POST['user'])) {
                unset($_POST['user']);
            }
            if (isset($_POST['artist'])) {
                unset($_POST['artist']);
            }
            if (isset($_POST['artist_name'])) {
                unset($_POST['artist_name']);
            }
            if (isset($_POST['album'])) {
                unset($_POST['album']);
            }
            if (isset($_POST['album_name'])) {
                unset($_POST['album_name']);
            }
            if (isset($_POST['album_artist'])) {
                unset($_POST['album_artist']);
            }
            if (isset($_POST['album_artist_name'])) {
                unset($_POST['album_artist_name']);
            }
            if (isset($_POST['edit_tags'])) {
                $_POST['edit_tags'] = Tag::clean_to_existing($_POST['edit_tags']);
            }
            if (isset($_POST['edit_labels'])) {
                $_POST['edit_labels'] = Label::clean_to_existing($_POST['edit_labels']);
            }
            // Check mbid and *_mbid match as it is used as identifier
            if (isset($_POST['mbid'])) {
                $_POST['mbid'] = $libitem->mbid;
            }
            if (isset($_POST['mbid_group'])) {
                $_POST['mbid_group'] = $libitem->mbid_group;
            }
        }

        $libitem->format();
        $new_id  = $libitem->update($_POST);
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
