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

/* Because this is accessed via Ajax we are going to allow the session_id
 * as part of the get request
 */

// Set that this is an ajax include
define('AJAX_INCLUDE', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

xoutput_headers();

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;
if ($page) {
    debug_event('ajax.server', 'Called for page: {' . $page . '}', 5);
}

switch ($page) {
    case 'stats':
        require_once AmpConfig::get('prefix') . '/server/stats.ajax.php';

        return false;
    case 'browse':
        require_once AmpConfig::get('prefix') . '/server/browse.ajax.php';

        return false;
    case 'random':
        require_once AmpConfig::get('prefix') . '/server/random.ajax.php';

        return false;
    case 'playlist':
        require_once AmpConfig::get('prefix') . '/server/playlist.ajax.php';

        return false;
    case 'localplay':
        require_once AmpConfig::get('prefix') . '/server/localplay.ajax.php';

        return false;
    case 'tag':
        require_once AmpConfig::get('prefix') . '/server/tag.ajax.php';

        return false;
    case 'stream':
        require_once AmpConfig::get('prefix') . '/server/stream.ajax.php';

        return false;
    case 'song':
        require_once AmpConfig::get('prefix') . '/server/song.ajax.php';

        return false;
    case 'democratic':
        require_once AmpConfig::get('prefix') . '/server/democratic.ajax.php';

        return false;
    case 'index':
        require_once AmpConfig::get('prefix') . '/server/index.ajax.php';

        return false;
    case 'catalog':
        require_once AmpConfig::get('prefix') . '/server/catalog.ajax.php';

        return false;
    case 'search':
        require_once AmpConfig::get('prefix') . '/server/search.ajax.php';

        return false;
    case 'player':
        require_once AmpConfig::get('prefix') . '/server/player.ajax.php';

        return false;
    case 'user':
        require_once AmpConfig::get('prefix') . '/server/user.ajax.php';

        return false;
    case 'podcast':
        require_once AmpConfig::get('prefix') . '/server/podcast.ajax.php';

        return false;
    default:
        break;
} // end switch on page

$action = Core::get_request('action');

// Switch on the actions
switch ($action) {
    case 'refresh_rightbar':
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
        break;
    case 'current_playlist':
        switch ($_REQUEST['type']) {
            case 'delete':
                Core::get_global('user')->playlist->delete_track($_REQUEST['id']);
            break;
        } // end switch

        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    // Handle the users basketcases...
    case 'basket':
        $object_type = $_REQUEST['type'] ?: $_REQUEST['object_type'];
        $object_id   = $_REQUEST['id'] ?: $_REQUEST['object_id'];

        if (Core::is_playable_item($object_type)) {
            if (!is_array($object_id)) {
                $object_id = array($object_id);
            }
            foreach ($object_id as $item) {
                $object = new $object_type($item);
                $medias = $object->get_medias();
                Core::get_global('user')->playlist->add_medias($medias, (bool) AmpConfig::get('unique_playlist'));
            }
        } else {
            switch ($_REQUEST['type']) {
                case 'browse_set':
                    $browse  = new Browse($_REQUEST['browse_id']);
                    $objects = $browse->get_saved();
                    foreach ($objects as $object_id) {
                        Core::get_global('user')->playlist->add_object($object_id, 'song');
                    }
                    break;
                case 'album_random':
                    $data = explode('_', $_REQUEST['type']);
                    $type = $data['0'];
                    foreach ($_REQUEST['id'] as $i) {
                        $object = new $type($i);
                        $songs  = $object->get_random_songs();
                        foreach ($songs as $song_id) {
                            Core::get_global('user')->playlist->add_object($song_id, 'song');
                        }
                    }
                    break;
                case 'artist_random':
                case 'tag_random':
                    $data   = explode('_', $_REQUEST['type']);
                    $type   = $data['0'];
                    $object = new $type($_REQUEST['id']);
                    $songs  = $object->get_random_songs();
                    foreach ($songs as $song_id) {
                        Core::get_global('user')->playlist->add_object($song_id, 'song');
                    }
                    break;
                case 'playlist_random':
                    $playlist = new Playlist($_REQUEST['id']);
                    $items    = $playlist->get_random_items();
                    foreach ($items as $item) {
                        Core::get_global('user')->playlist->add_object($item['object_id'], $item['object_type']);
                    }
                    break;
                case 'clear_all':
                    Core::get_global('user')->playlist->clear();
                    break;
            }
        }

        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
        break;
    /* Setting ratings */
    case 'set_rating':
        if (User::is_registered()) {
            ob_start();
            $rating = new Rating(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), Core::get_get('rating_type'));
            $rating->set_rating(Core::get_get('rating'));
            Rating::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), Core::get_get('rating_type'));
            $key           = "rating_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . Core::get_get('rating_type');
            $results[$key] = ob_get_contents();
            ob_end_clean();
        } else {
            $results['rfc3514'] = '0x1';
        }
    break;
    /* Setting userflags */
    case 'set_userflag':
        if (User::is_registered()) {
            ob_start();
            $flagtype = Core::get_get('userflag_type');
            $userflag = new Userflag(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), $flagtype);
            $userflag->set_flag($_GET['userflag']);
            Userflag::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), $flagtype);
            $key           = "userflag_" . Core::get_get('object_id') . "_" . $flagtype;
            $results[$key] = ob_get_contents();
            ob_end_clean();
        } else {
            $results['rfc3514'] = '0x1';
        }
        break;
    case 'action_buttons':
        ob_start();
        if (AmpConfig::get('ratings')) {
            echo " <div id='rating_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) . "'>";
            Rating::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
            echo "</div> |";
        }
        if (AmpConfig::get('userflags')) {
            echo " <div id='userflag_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) . "'>";
            Userflag::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
            echo "</div>";
        }
        $results['action_buttons'] = ob_get_contents();
        ob_end_clean();
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // end switch action

// Go ahead and do the echo
echo (string) xoutput_from_array($results);
