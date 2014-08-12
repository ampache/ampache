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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) { exit; }

$results = array();
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
        $browse->set_type('playlist_song');
        $browse->add_supplemental_object('playlist',$playlist->id);
        $browse->save_objects($object_ids);
        $browse->show_objects($object_ids);
        $browse->store();

        $results[$browse->get_content_div()] = ob_get_clean();
    break;
    case 'append_item':
        // Only song item are supported with playlists

        debug_event('playlist', 'Appending items to playlist {'.$_REQUEST['playlist_id'].'}...', '5');

        if (!isset($_REQUEST['playlist_id']) || empty($_REQUEST['playlist_id'])) {
            if (!Access::check('interface','25')) {
                debug_event('DENIED','Error:' . $GLOBALS['user']->username . ' does not have user access, unable to create playlist','1');
                break;
            }

            $name = $GLOBALS['user']->username . ' - ' . date("Y-m-d H:i:s",time());
            $playlist_id = Playlist::create($name,'public');
            if (!$playlist_id) {
                break;
            }
            $playlist = new Playlist($playlist_id);
        } else {
            $playlist = new Playlist($_REQUEST['playlist_id']);
        }

        if (!$playlist->has_access()) {
            break;
        }

        $songs = array();
        $item_id = $_REQUEST['item_id'];

        switch ($_REQUEST['item_type']) {
            case 'smartplaylist':
                $smartplaylist = new Search($item_id, 'song');
                $items = $playlist->get_items();
                foreach ($items as $item) {
                    $songs[] = $item['object_id'];
                }
            break;
            case 'album':
                debug_event('playlist', 'Adding all songs of album(s) {'.$item_id.'}...', '5');
                $albums_array = explode(',', $item_id);
                foreach ($albums_array as $a) {
                    $album = new Album($a);
                    $asongs = $album->get_songs();
                    foreach ($asongs as $song_id) {
                        $songs[] = $song_id;
                    }
                }
            break;
            case 'artist':
                debug_event('playlist', 'Adding all songs of artist {'.$item_id.'}...', '5');
                $artist = new Artist($item_id);
                $asongs = $artist->get_songs();
                foreach ($asongs as $song_id) {
                    $songs[] = $song_id;
                }
            break;
            case 'song_preview':
            case 'song':
                debug_event('playlist', 'Adding song {'.$item_id.'}...', '5');
                $songs = explode(',', $item_id);
            break;
            default:
                debug_event('playlist', 'Adding all songs of current playlist...', '5');
                $objects = $GLOBALS['user']->playlist->get_items();
                foreach ($objects as $object_data) {
                    $type = array_shift($object_data);
                    if ($type == 'song') {
                        $songs[] = array_shift($object_data);
                    }
                }
            break;
        }

        if (count($songs) > 0) {
            Ajax::set_include_override(true);
            $playlist->add_songs($songs, true);

            /*$playlist->format();
            $object_ids = $playlist->get_items();
            ob_start();
            require_once AmpConfig::get('prefix') . '/templates/show_playlist.inc.php';
            $results['content'] = ob_get_contents();
            ob_end_clean();*/
            debug_event('playlist', 'Items added successfully!', '5');
        } else {
            debug_event('playlist', 'No item to add. Aborting...', '5');
        }
    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
}

echo xoutput_from_array($results);
