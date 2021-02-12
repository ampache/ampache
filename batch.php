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
if (!defined('NO_SESSION')) {
    if (isset($_REQUEST['ssid'])) {
        define('NO_SESSION', 1);
        require_once $a_root . '/lib/init.php';
        if (!Session::exists('stream', $_REQUEST['ssid'])) {
            UI::access_denied();

            return false;
        }
    } else {
        require_once $a_root . '/lib/init.php';
    }
}

ob_end_clean();
//test that batch download is permitted
if (!defined('NO_SESSION') && !Access::check_function('batch_download')) {
    UI::access_denied();

    return false;
}

/* Drop the normal Time limit constraints, this can take a while */
set_time_limit(0);

$media_ids    = array();
$default_name = "Unknown.zip";
$object_type  = (string) scrub_in(Core::get_request('action'));
$name         = $default_name;

if ($object_type == 'browse') {
    $object_type = Core::get_request('type');
}

if (!check_can_zip($object_type)) {
    debug_event('batch', 'Object type `' . $object_type . '` is not allowed to be zipped.', 2);
    UI::access_denied();

    return false;
}

if (Core::is_playable_item($object_type)) {
    $object_id = $_REQUEST['id'];
    if (!is_array($object_id)) {
        $object_id = array($object_id);
    }
    $media_ids = array();
    foreach ($object_id as $item) {
        debug_event('batch', 'Requested item ' . $item, 5);
        $libitem = new $object_type($item);
        if ($libitem->id) {
            $libitem->format();
            $name      = $libitem->get_fullname();
            $media_ids = array_merge($media_ids, $libitem->get_medias());
        }
    }
} else {
    // Switch on the actions
    switch ($object_type) {
        case 'tmp_playlist':
            $media_ids = Core::get_global('user')->playlist->get_items();
            $name      = Core::get_global('user')->username . ' - Playlist';
            break;
        case 'browse':
            $object_id        = (int) scrub_in(Core::get_post('browse_id'));
            $browse           = new Browse($object_id);
            $browse_media_ids = $browse->get_saved();
            foreach ($browse_media_ids as $media_id) {
                switch ($object_type) {
                    case 'album':
                        $album     = new Album($media_id);
                        $media_ids = array_merge($media_ids, $album->get_songs());
                        break;
                    case 'song':
                        $media_ids[] = $media_id;
                        break;
                    case 'video':
                        $media_ids[] = array('object_type' => 'Video', 'object_id' => $media_id);
                        break;
                } // switch on type
            } // foreach media_id
            $time_format = AmpConfig::get('custom_datetime') ? preg_replace("/[^dmY\s]/", "", (string) AmpConfig::get('custom_datetime')) : "m-d-Y";
            $name        = 'Batch-' . get_datetime($time_format, time());
            break;
        default:
            break;
    } // action switch
}

if (!User::stream_control($media_ids)) {
    debug_event('batch', 'UI::access_denied: Stream control failed for user ' . Core::get_global('user')->username, 3);
    UI::access_denied();

    return false;
}

// Write/close session data to release session lock for this script.
// This to allow other pages from the same session to be processed
// Do NOT change any session variable after this call
session_write_close();

// Take whatever we've got and send the zip
$song_files = get_media_files($media_ids);
if (is_array($song_files['0'])) {
    set_memory_limit($song_files['1'] + 32);
    send_zip($name, $song_files['0']);
}

return false;
