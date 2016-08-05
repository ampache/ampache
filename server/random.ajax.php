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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

$results = array();
switch ($_REQUEST['action']) {
    case 'song':
        $songs = Random::get_default();

        if (!count($songs)) {
            $results['rfc3514'] = '0x1';
            break;
        }

        foreach ($songs as $song_id) {
            $GLOBALS['user']->playlist->add_object($song_id, 'song');
        }
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    case 'album':
        $album_id = Album::get_random();

        if (!$album_id) {
            $results['rfc3514'] = '0x1';
            break;
        }

        $album = new Album($album_id[0]);
        $songs = $album->get_songs();
        foreach ($songs as $song_id) {
            $GLOBALS['user']->playlist->add_object($song_id, 'song');
        }
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    case 'artist':
        $artist_id = Random::artist();

        if (!$artist_id) {
            $results['rfc3514'] = '0x1';
            break;
        }

        $artist = new Artist($artist_id);
        $songs  = $artist->get_songs();
        foreach ($songs as $song_id) {
            $GLOBALS['user']->playlist->add_object($song_id, 'song');
        }
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    case 'playlist':
        $playlist_id = Random::playlist();

        if (!$playlist_id) {
            $results['rfc3514'] = '0x1';
            break;
        }

        $playlist = new Playlist($playlist_id);
        $items    = $playlist->get_items();
        foreach ($items as $item) {
            $GLOBALS['user']->playlist->add_object($item['object_id'], $item['object_type']);
        }
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    case 'advanced_random':
        $object_ids = Random::advanced('song', $_POST);

        // First add them to the active playlist
        if (is_array($object_ids)) {
            foreach ($object_ids as $object_id) {
                $GLOBALS['user']->playlist->add_object($object_id, 'song');
            }
        }
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');

        // Now setup the browse and show them below!
        $browse = new Browse();
        $browse->set_type('song');
        $browse->save_objects($object_ids);
        ob_start();
        $browse->show_objects();
        $results['browse'] = ob_get_contents();
        ob_end_clean();
    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
