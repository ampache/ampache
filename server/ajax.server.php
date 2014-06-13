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

xoutput_headers();

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;

debug_event('ajax.server.php', 'Called for page: {'.$page.'}', '5');

switch ($page) {
    case 'stats':
        require_once AmpConfig::get('prefix') . '/server/stats.ajax.php';
        exit;
    case 'browse':
        require_once AmpConfig::get('prefix') . '/server/browse.ajax.php';
        exit;
    case 'random':
        require_once AmpConfig::get('prefix') . '/server/random.ajax.php';
        exit;
    case 'playlist':
        require_once AmpConfig::get('prefix') . '/server/playlist.ajax.php';
        exit;
    case 'localplay':
        require_once AmpConfig::get('prefix') . '/server/localplay.ajax.php';
        exit;
    case 'tag':
        require_once AmpConfig::get('prefix') . '/server/tag.ajax.php';
        exit;
    case 'stream':
        require_once AmpConfig::get('prefix') . '/server/stream.ajax.php';
        exit;
    case 'song':
        require_once AmpConfig::get('prefix') . '/server/song.ajax.php';
        exit;
    case 'democratic':
        require_once AmpConfig::get('prefix') . '/server/democratic.ajax.php';
        exit;
    case 'index':
        require_once AmpConfig::get('prefix') . '/server/index.ajax.php';
        exit;
    case 'catalog':
        require_once AmpConfig::get('prefix') . '/server/catalog.ajax.php';
        exit;
    case 'search':
        require_once AmpConfig::get('prefix') . '/server/search.ajax.php';
        exit;
    case 'player':
        require_once AmpConfig::get('prefix') . '/server/player.ajax.php';
        exit;
    case 'content':
        require_once AmpConfig::get('prefix') . '/server/content.ajax.php';
        exit;
    default:
        // A taste of compatibility
    break;
} // end switch on page

switch ($_REQUEST['action']) {
    case 'refresh_rightbar':
        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    /* Controls the editing of objects */
    case 'edit_object':
        debug_event('ajax_server', "Editing object...", '5');

        // Scrub the data
        foreach ($_POST as $key => $data) {
            $_POST[$key] = unhtmlentities(scrub_in($data));
            debug_event('ajax_server', $key.'='.$_POST[$key], '5');
        }

        $level = '50';
        $levelok = false;

        if ($_POST['type'] == 'playlist_row' || $_POST['type'] == 'playlist_title') {
            $playlist = new Playlist($_POST['id']);
            if ($GLOBALS['user']->id == $playlist->user) {
                $level = '25';
            }
        }
        if ($_POST['type'] == 'smartplaylist_row' ||
            $_POST['type'] == 'smartplaylist_title') {
            $smartpl = new Search('song', $_POST['id']);
            if ($GLOBALS['user']->id == $smartpl->user) {
                $level = '25';
            }
        }

        if ($_POST['type'] == 'song_row') {
            $song = new Song($_POST['id']);
            if ($song->user_upload == $GLOBALS['user']->id && AmpConfig::get('upload_allow_edit') && !Access::check('interface','75')) {
                if (isset($_POST['artist'])) unset($_POST['artist']);
                if (isset($_POST['album'])) unset($_POST['album']);
                $levelok = true;
            }
        }

        // Make sure we've got them rights
        if (!$levelok && (!Access::check('interface', $level) || AmpConfig::get('demo_mode'))) {
            $results['rfc3514'] = '0x1';
            break;
        }

        $new_id = '';
        switch ($_POST['type']) {
            case 'album_row':
                $key = 'album_' . $_POST['id'];
                $album = new Album($_POST['id']);
                $songs = $album->get_songs();
                $new_id = $album->update($_POST);
                if ($new_id != $_POST['id']) {
                    $album = new Album($new_id);
                }
                $album->format();
            break;
            case 'artist_row':
                $key = 'artist_' . $_POST['id'];
                $artist = new Artist($_POST['id']);
                $songs = $artist->get_songs();
                $new_id = $artist->update($_POST);
                if ($new_id != $_POST['id']) {
                    $artist = new Artist($new_id);
                }
                $artist->format();
            break;
            case 'song_row':
                $key = 'song_' . $_POST['id'];
                if (isset($song)) {
                    $song->update($_POST);
                    $song->format();
                }
            break;
            case 'playlist_row':
            case 'playlist_title':
                $key = 'playlist_row_' . $_POST['id'];
                if (isset($playlist)) {
                    $playlist->update($_POST);
                    $playlist->format();
                    $count = $playlist->get_song_count();
                }
            break;
            case 'smartplaylist_row':
            case 'smartplaylist_title':
                $key = 'smartplaylist_row_' . $_POST['id'];
                if (isset($smartpl)) {
                    $smartpl->name = $_POST['name'];
                    $smartpl->type = $_POST['pl_type'];
                    $smartpl->update();
                    $smartpl->format();
                }
            break;
            case 'live_stream_row':
                $key = 'live_stream_' . $_POST['id'];
                Radio::update($_POST);
                $radio = new Radio($_POST['id']);
                $radio->format();
            break;
            case 'channel_row':
                $key = 'channel_row_' . $_POST['id'];
                $channel = new Channel($_POST['id']);
                $channel->update($_POST);
            break;
            case 'broadcast_row':
                $key = 'broadcast_row_' . $_POST['id'];
                $broadcast = new Broadcast($_POST['id']);
                $broadcast->update($_POST);
            break;
            case 'tag_row':
                $tag = new Tag($_POST['id']);
                $tag->update($_POST['name']);
                if ($_POST['select_tags']) {
                    $merge_to = Tag::construct_from_name($_POST['select_tags']);
                    if ($merge_to->id) {
                        $tag->merge($merge_to->id, ($_POST['merge_persist'] == '1'));
                    }
                }
            break;
            default:
                $key = 'rfc3514';
                echo xoutput_from_array(array($key=>'0x1'));
                exit;
        } // end switch on type

        $results['id'] = $new_id;
    break;
    case 'current_playlist':
        switch ($_REQUEST['type']) {
            case 'delete':
                $GLOBALS['user']->playlist->delete_track($_REQUEST['id']);
            break;
        } // end switch

        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    // Handle the users basketcases...
    case 'basket':
        switch ($_REQUEST['type']) {
            case 'album':
                foreach ($_REQUEST['id'] as $i) {
                    $object = new $_REQUEST['type']($i);
                    $songs = $object->get_songs();
                    foreach ($songs as $song_id) {
                        $GLOBALS['user']->playlist->add_object($song_id, 'song');
                    }
                }
            break;
            case 'artist':
            case 'tag':
                $object = new $_REQUEST['type']($_REQUEST['id']);
                $songs = $object->get_songs();
                foreach ($songs as $song_id) {
                    $GLOBALS['user']->playlist->add_object($song_id,'song');
                }
            break;
            case 'browse_set':
                $browse = new Browse($_REQUEST['browse_id']);
                $objects = $browse->get_saved();
                foreach ($objects as $object_id) {
                    $GLOBALS['user']->playlist->add_object($object_id,'song');
                }
            break;
            case 'album_random':
                $data = explode('_',$_REQUEST['type']);
                $type = $data['0'];
                foreach ($_REQUEST['id'] as $i) {
                    $object = new $type($i);
                    $songs = $object->get_random_songs();
                    foreach ($songs as $song_id) {
                        $GLOBALS['user']->playlist->add_object($song_id, 'song');
                    }
                }
            break;
            case 'artist_random':
            case 'tag_random':
                $data = explode('_',$_REQUEST['type']);
                $type = $data['0'];
                $object = new $type($_REQUEST['id']);
                $songs = $object->get_random_songs();
                foreach ($songs as $song_id) {
                    $GLOBALS['user']->playlist->add_object($song_id,'song');
                }
            break;
            case 'playlist':
                $playlist = new Playlist($_REQUEST['id']);
                $items = $playlist->get_items();
                foreach ($items as $item) {
                    $GLOBALS['user']->playlist->add_object($item['object_id'], $item['object_type']);
                }
            break;
            case 'playlist_random':
                $playlist = new Playlist($_REQUEST['id']);
                $items = $playlist->get_random_items();
                foreach ($items as $item) {
                    $GLOBALS['user']->playlist->add_object($item['object_id'], $item['object_type']);
                }
            break;
            case 'smartplaylist':
                $playlist = new Search('song', $_REQUEST['id']);
                $items = $playlist->get_items();
                foreach ($items as $item) {
                    $GLOBALS['user']->playlist->add_object($item['object_id'],$item['object_type']);
                }
            break;
            case 'clear_all':
                $GLOBALS['user']->playlist->clear();
            break;
            case 'live_stream':
                $object = new Radio($_REQUEST['id']);
                // Confirm its a valid ID
                if ($object->name) {
                    $GLOBALS['user']->playlist->add_object($object->id,'radio');
                }
            break;
            case 'video':
                $GLOBALS['user']->playlist->add_object($_REQUEST['id'],'video');
            break;
            case 'album_preview':
                $songs = Song_preview::get_song_previews($_REQUEST['mbid']);
                foreach ($songs as $song) {
                    if (!empty($song->file)) {
                        $GLOBALS['user']->playlist->add_object($song->id, 'song_preview');
                    }
                }
            break;
            case 'song_preview':
                $GLOBALS['user']->playlist->add_object($_REQUEST['id'],'song_preview');
            break;
            case 'song':
            default:
                $GLOBALS['user']->playlist->add_object($_REQUEST['id'],'song');
            break;
        } // end switch

        $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
    break;
    /* Setting ratings */
    case 'set_rating':
        ob_start();
        $rating = new Rating($_GET['object_id'], $_GET['rating_type']);
        $rating->set_rating($_GET['rating']);
        Rating::show($_GET['object_id'], $_GET['rating_type']);
        $key = "rating_" . $_GET['object_id'] . "_" . $_GET['rating_type'];
        $results[$key] = ob_get_contents();
        ob_end_clean();
    break;
    /* Setting userflags */
    case 'set_userflag':
        ob_start();
        $userflag = new Userflag($_GET['object_id'], $_GET['userflag_type']);
        $userflag->set_flag($_GET['userflag']);
        Userflag::show($_GET['object_id'], $_GET['userflag_type']);
        $key = "userflag_" . $_GET['object_id'] . "_" . $_GET['userflag_type'];
        $results[$key] = ob_get_contents();
        ob_end_clean();
    break;
    case 'action_buttons':
        ob_start();
        if (AmpConfig::get('ratings')) {
            echo " <div id='rating_" . $_GET['object_id'] . "_" . $_GET['object_type'] . "'>";
            Rating::show($_GET['object_id'], $_GET['object_type']);
            echo "</div> |";
        }
        if (AmpConfig::get('userflags')) {
            echo " <div id='userflag_" . $_GET['object_id'] . "_" . $_GET['object_type'] . "'>";
            Userflag::show($_GET['object_id'], $_GET['object_type']);
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
echo xoutput_from_array($results);
