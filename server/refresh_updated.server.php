<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

define('AJAX_INCLUDE','1');

require_once '../lib/init.php';

debug_event('refresh_updated.server.php', 'Called for action: {'.$_REQUEST['action'].'}', '5');

/* Switch on the action passed in */
switch ($_REQUEST['action']) {
    case 'refresh_song':
        $song = new Song($_REQUEST['id']);
        $song->format();
        require AmpConfig::get('prefix') . '/templates/show_song_row.inc.php';
    break;
    case 'refresh_album':
        $album = new Album($_REQUEST['id']);
        $album->format();
        require AmpConfig::get('prefix') . '/templates/show_album_row.inc.php';
    break;
    case 'refresh_artist':
        $artist = new Artist($_REQUEST['id'], $_SESSION['catalog']);
        $artist->format();
        require AmpConfig::get('prefix') . '/templates/show_artist_row.inc.php';
    break;
    case 'refresh_playlist':
        $playlist = new Playlist($_REQUEST['id']);
        $playlist->format();
        $count = $playlist->get_song_count();
        require AmpConfig::get('prefix') . '/templates/show_playlist_row.inc.php';
    break;
    case 'refresh_smartplaylist':
        $playlist = new Search('song', $_REQUEST['id']);
        $playlist->format();
        require AmpConfig::get('prefix') . '/templates/show_smartplaylist_row.inc.php';
    break;
    case 'refresh_livestream':
        $radio = new Radio($_REQUEST['id']);
        $radio->format();
        require AmpConfig::get('prefix') . '/templates/show_live_stream_row.inc.php';
    break;
    case 'refresh_channel':
        $channel = new Channel($_REQUEST['id']);
        $channel->format();
        require AmpConfig::get('prefix') . '/templates/show_channel_row.inc.php';
    break;
	case 'refresh_broadcast':
        $broadcast = new Broadcast($_REQUEST['id']);
        $broadcast->format();
        require AmpConfig::get('prefix') . '/templates/show_broadcast_row.inc.php';
    break;
} // switch on the action
