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
    case 'delete_track':
        // Create the object and remove the track
        $playlist = new Playlist($_REQUEST['playlist_id']);
        $playlist->format();
        if ($playlist->has_access()) {
            $playlist->delete_track($_REQUEST['track_id']);
            // This could have performance issues
            $playlist->regenerate_track_numbers();
        }

        $object_ids = $playlist->get_items();
        ob_start();
        $browse = new Browse();
        $browse->set_type('playlist_media');
        $browse->add_supplemental_object('playlist', $playlist->id);
        $browse->save_objects($object_ids);
        $browse->show_objects($object_ids);
        $browse->store();

        $results[$browse->get_content_div()] = ob_get_clean();
        break;
    case 'append_item':
        // Only song item are supported with playlists
        if (!isset($_REQUEST['playlist_id']) || empty($_REQUEST['playlist_id'])) {
            if (!Access::check('interface', 25)) {
                debug_event('playlist.ajax', 'Error:' . Core::get_global('user')->username . ' does not have user access, unable to create playlist', 1);
                break;
            }

            $name        = $_REQUEST['name'];
            if (empty($name)) {
                $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
                $name        = Core::get_global('user')->username . ' - ' . get_datetime($time_format, time());
            }
            $playlist_id = (int) Playlist::create($name, 'private');
            if ($playlist_id < 1) {
                break;
            }
            $playlist = new Playlist($playlist_id);
        } else {
            $playlist = new Playlist($_REQUEST['playlist_id']);
        }

        if (!$playlist->has_access()) {
            break;
        }
        debug_event('playlist.ajax', 'Appending items to playlist {' . $playlist->id . '}...', 5);

        $medias    = array();
        $item_id   = $_REQUEST['item_id'];
        $item_type = $_REQUEST['item_type'];

        if (!empty($item_type) && Core::is_playable_item($item_type)) {
            debug_event('playlist.ajax', 'Adding all medias of ' . $item_type . '(s) {' . $item_id . '}...', 5);
            $item_ids = explode(',', $item_id);
            foreach ($item_ids as $iid) {
                $libitem = new $item_type($iid);
                $medias  = array_merge($medias, $libitem->get_medias());
            }
        } else {
            debug_event('playlist.ajax', 'Adding all medias of current playlist...', 5);
            $medias = Core::get_global('user')->playlist->get_items();
        }

        if (count($medias) > 0) {
            Ajax::set_include_override(true);
            $playlist->add_medias($medias, (bool) AmpConfig::get('unique_playlist'));

            debug_event('playlist.ajax', 'Items added successfully!', 5);
            ob_start();
            display_notification(T_('Added to playlist'));
            $results['rfc3514'] = ob_get_clean();
        } else {
            debug_event('playlist.ajax', 'No item to add. Aborting...', 5);
        }
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
}

echo (string) xoutput_from_array($results);
