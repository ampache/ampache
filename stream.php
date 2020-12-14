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
require_once $a_root . '/lib/init.php';

if (!Core::get_request('action')) {
    debug_event('stream', "Asked without action. Exiting...", 5);

    return false;
}

if (!defined('NO_SESSION')) {
    /* If we are running a demo, quick while you still can! */
    if (AmpConfig::get('demo_mode') || (AmpConfig::get('use_auth') && !Access::check('interface', 25))) {
        UI::access_denied();

        return false;
    }
}

$media_ids = array();
$web_path  = AmpConfig::get('web_path');

debug_event('stream', "Asked for {" . Core::get_request('action') . "}.", 5);

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'basket':
        // Pull in our items (multiple types)
        $media_ids = Core::get_global('user')->playlist->get_items();

        // Check to see if 'clear' was passed if it was then we need to reset the basket
        if (($_REQUEST['playlist_method'] == 'clear' || AmpConfig::get('playlist_method') == 'clear')) {
            Core::get_global('user')->playlist->clear();
        }
    break;
    /* This is run if we need to gather info based on a tmp playlist */
    case 'tmp_playlist':
        $tmp_playlist = new Tmp_Playlist($_REQUEST['tmpplaylist_id']);
        $media_ids    = $tmp_playlist->get_items();
        break;
    case 'play_favorite':
        $data      = Core::get_global('user')->get_favorites((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        $media_ids = array();
        switch ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS)) {
            case 'artist':
            case 'album':
                foreach ($data as $value) {
                    $songs     = $value->get_songs();
                    $media_ids = array_merge($media_ids, $songs);
                }
                break;
            case 'song':
                foreach ($data as $value) {
                    $media_ids[] = $value->id;
                }
                break;
        } // end switch on type
        break;
    case 'play_item':
        $object_type = $_REQUEST['object_type'];
        $object_ids  = explode(',', Core::get_get('object_id'));

        if (Core::is_playable_item($object_type)) {
            foreach ($object_ids as $object_id) {
                $item      = new $object_type($object_id);
                $media_ids = array_merge($media_ids, $item->get_medias());

                if ($_REQUEST['custom_play_action']) {
                    foreach ($media_ids as $media_id) {
                        if (is_array($media_id)) {
                            $media_id['custom_play_action'] = $_REQUEST['custom_play_action'];
                        }
                    }
                }
            }
        }
        break;
    case 'artist_random':
        $artist    = new Artist($_REQUEST['artist_id']);
        $media_ids = $artist->get_random_songs();
        break;
    case 'album_random':
        $album     = new Album($_REQUEST['album_id']);
        $media_ids = $album->get_random_songs();
        break;
    case 'playlist_random':
        $playlist  = new Playlist($_REQUEST['playlist_id']);
        $media_ids = $playlist->get_random_items();
        break;
    case 'democratic':
        $democratic = new Democratic($_REQUEST['democratic_id']);
        $urls       = array($democratic->play_url());
        break;
    case 'download':
        if (isset($_REQUEST['song_id'])) {
            $media_ids[] = array(
                'object_type' => 'song',
                'object_id' => scrub_in($_REQUEST['song_id'])
            );
        } elseif (isset($_REQUEST['video_id'])) {
            $media_ids[] = array(
                'object_type' => 'video',
                'object_id' => scrub_in($_REQUEST['video_id'])
            );
        } elseif (isset($_REQUEST['podcast_episode_id'])) {
            $media_ids[] = array(
                'object_type' => 'podcast_episode',
                'object_id' => scrub_in($_REQUEST['podcast_episode_id'])
            );
        }
        break;
    default:
        break;
} // end action switch

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'download':
        $stream_type = 'download';
        break;
    case 'democratic':
        $play_type   = AmpConfig::get('play_type');
        $stream_type = ($play_type == 'democratic') ? AmpConfig::get('playlist_type') : $play_type;
        break;
    default:
        $play_type   = AmpConfig::get('play_type');
        $stream_type = ($play_type == 'stream') ? AmpConfig::get('playlist_type') : $play_type;
        break;
}

if (count($media_ids) || isset($urls)) {
    if ($stream_type != 'democratic') {
        if (!User::stream_control($media_ids)) {
            debug_event('stream', 'Stream control failed for user ' . Core::get_global('user')->username, 3);
            UI::access_denied();

            return false;
        }
    }

    if (Core::get_global('user')->id > -1) {
        Session::update_username(Stream::get_session(), Core::get_global('user')->username);
    }

    $playlist = new Stream_Playlist();
    // don't do this if nothing is there
    if (count($media_ids)) {
        debug_event('stream', 'Stream Type: ' . $stream_type . ' Media Count: ' . count($media_ids), 5);
        $playlist->add($media_ids);
    }
    if (isset($urls)) {
        debug_event('stream', 'Stream Type: ' . $stream_type . ' Loading URL: ' . $urls[0], 5);
        $playlist->add_urls($urls);
    }
    // Depending on the stream type, will either generate a redirect or actually do the streaming.
    $playlist->generate_playlist($stream_type, false);
} else {
    debug_event('stream.php', 'No item. Ignoring...', 5);
}
