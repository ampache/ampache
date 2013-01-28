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

require_once 'lib/init.php';

/* If we are running a demo, quick while you still can! */
if (Config::get('demo_mode') || !Access::check('interface','25')) {
    UI::access_denied();
    exit;
}

$media_ids = array();
$web_path = Config::get('web_path');

/**
 * action switch
 */
switch ($_REQUEST['action']) {
    case 'basket':
        // Pull in our items (multiple types)
        $media_ids = $GLOBALS['user']->playlist->get_items();

        // Check to see if 'clear' was passed if it was then we need to reset the basket
        if ( ($_REQUEST['playlist_method'] == 'clear' || Config::get('playlist_method') == 'clear')) {
            $GLOBALS['user']->playlist->clear();
        }

    break;
    /* This is run if we need to gather info based on a tmp playlist */
    case 'tmp_playlist':
        $tmp_playlist = new Tmp_Playlist($_REQUEST['tmpplaylist_id']);
        $media_ids = $tmp_playlist->get_items();
    break;
    case 'play_favorite':
        $data = $GLOBALS['user']->get_favorites($_REQUEST['type']);
        $media_ids = array();
        switch ($_REQUEST['type']) {
            case 'artist':
            case 'album':
                foreach ($data as $value) {
                    $songs = $value->get_songs();
                    $media_ids = array_merge($media_ids,$songs);
                }
            break;
            case 'song':
                foreach ($data as $value) {
                    $media_ids[] = $value->id;
                }
            break;
        } // end switch on type
    break;
    case 'single_song':
        $media_ids[] = array(
            'object_type' => 'song',
            'object_id' => scrub_in($_REQUEST['song_id'])
        );
    break;
    case 'artist':
        $artist = new Artist($_REQUEST['artist_id']);
        $media_ids = $artist->get_songs();
    break;
    case 'artist_random':
        $artist = new Artist($_REQUEST['artist_id']);
        $artist->get_count();
        $media_ids = $artist->get_random_songs();
    break;
    case 'album_random':
        $album = new Album($_REQUEST['album_id']);
        $media_ids = $album->get_random_songs();
    break;
    case 'album':
        $album = new Album($_REQUEST['album_id']);
        $media_ids = $album->get_songs();
    break;
    case 'playlist':
        $playlist    = new Playlist($_REQUEST['playlist_id']);
        $media_ids    = $playlist->get_songs($_REQUEST['song']);
    break;
    case 'playlist_random':
        $playlist    = new Playlist($_REQUEST['playlist_id']);
        $media_ids    = $playlist->get_random_songs();
    break;
    case 'random':
        if($_REQUEST['genre'][0] != '-1') {
            $matchlist['genre'] = $_REQUEST['genre'];
        }
        if($_REQUEST['catalog'] != '-1') {
            $matchlist['catalog'] = $_REQUEST['catalog'];
        }
        /* Setup the options array */
        $options = array('limit' => $_REQUEST['random'], 'random_type' => $_REQUEST['random_type'],'size_limit'=>$_REQUEST['size_limit']);
        $media_ids = get_random_songs($options, $matchlist);
    break;
    case 'democratic':
        $democratic = new Democratic($_REQUEST['democratic_id']);
        $urls = array($democratic->play_url());
    break;
    case 'download':
        $media_ids[] = array(
            'object_type' => 'song',
            'object_id' => scrub_in($_REQUEST['song_id'])
        );
    default:
    break;
} // end action switch

// See if we need a special streamtype
switch ($_REQUEST['action']) {
    case 'download':
        $stream_type = 'download';
    break;
    case 'democratic':
        // Don't let them loop it
        // FIXME: This looks hacky
        if (Config::get('play_type') == 'democratic') {
            Config::set('play_type', 'stream', true);
        }
    default:
        $stream_type = Config::get('play_type');
        if ($stream_type == 'stream') {
            $stream_type = Config::get('playlist_type');
        }
    break;
}

debug_event('stream.php' , 'Stream Type: ' . $stream_type . ' Media IDs: '. json_encode($media_ids), 5);
$playlist = new Stream_Playlist();
$playlist->add($media_ids);
if (isset($urls)) {
    $playlist->add_urls($urls);
}
// Depending on the stream type, will either generate a redirect or actually do
// the streaming.
$playlist->generate_playlist($stream_type, true);

?>
