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

debug_event('show_edit.server.php', 'Called for action: {'.$_REQUEST['action'].'}', '5');

switch ($_REQUEST['action']) {
    case 'show_edit_object':
        $level = '50';
        switch ($_GET['type']) {
            case 'album_row':
                $album = new Album($_GET['id']);
                $album->format();
            break;
            case 'artist_row':
                $artist = new Artist($_GET['id']);
                $artist->format();
            break;
            case 'song_row':
                $song = new Song($_GET['id']);
                $song->format();
            break;
            case 'live_stream_row':
                $radio = new Radio($_GET['id']);
                $radio->format();
            break;
            case 'playlist_row':
            case 'playlist_title':
                $playlist = new Playlist($_GET['id']);
                $playlist->format();
                // If the current user is the owner, only user is required
                if ($playlist->user == $GLOBALS['user']->id) {
                    $level = '25';
                }
            break;
            case 'smartplaylist_row':
            case 'smartplaylist_title':
                $playlist = new Search('song', $_GET['id']);
                $playlist->format();
                if ($playlist->user == $GLOBALS['user']->id) {
                    $level = '25';
                }
            break;
            case 'channel_row':
                $channel = new Channel($_GET['id']);
                $channel->format();
            break;
            case 'broadcast_row':
                $broadcast = new Broadcast($_GET['id']);
                $broadcast->format();
            break;
            case 'tag_row':
                $tag = new Tag($_GET['id']);
                break;
            default:
                exit();
        } // end switch on type

        // Make sure they got them rights
        if (!Access::check('interface', $level)) {
            break;
        }

        ob_start();
        require AmpConfig::get('prefix') . '/templates/show_edit_' . $_GET['type'] . '.inc.php';
        $results = ob_get_contents();
        ob_end_clean();
    break;
    default:
        exit();
} // end switch action

echo $results;
