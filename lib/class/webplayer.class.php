<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class WebPlayer
{
    /**
     * Check if the playlist is a radio playlist.
     * @param \Stream_Playlist $playlist
     * @return boolean
     */
    public static function is_playlist_radio($playlist)
    {
        $radios = array();

        foreach ($playlist->urls as $item) {
            if ($item->type == "radio") {
                $radios[] = $item;
            }
        }

        return (count($playlist->urls) == 1 && count($radios) > 0 && AmpConfig::get('webplayer_flash'));
    }

    /**
     * Check if the playlist is a video playlist.
     * @param \Stream_Playlist $playlist
     * @return boolean
     */
    public static function is_playlist_video($playlist)
    {
        return (count($playlist->urls) > 0 && $playlist->urls[0]->type == "video");
    }

    /**
     * Get types information for an item.
     * @param \playable_item $item
     * @param string $force_type
     * @return array
     */
    protected static function get_types($item, $force_type= '')
    {
        $types = array('real' => 'mp3', 'player' => '');

        $media   = null;
        $urlinfo = Stream_URL::parse($item->url);
        if ($urlinfo['id'] && Core::is_media($urlinfo['type'])) {
            $media = new $urlinfo['type']($urlinfo['id']);
        } else {
            if ($urlinfo['id'] && $urlinfo['type'] == 'song_preview') {
                $media = new Song_Preview($urlinfo['id']);
            } else {
                if (isset($urlinfo['demo_id'])) {
                    $democratic = new Democratic($urlinfo['demo_id']);
                    if ($democratic->id) {
                        $song_id = $democratic->get_next_object();
                        if ($song_id) {
                            $media = new Song($song_id);
                        }
                    }
                }
            }
        }

        if ($media != null) {
            $ftype = $media->type;

            $transcode     = false;
            $transcode_cfg = AmpConfig::get('transcode');
            // Check transcode is required
            $valid_types = Song::get_stream_types_for_type($ftype, 'webplayer');
            if ($transcode_cfg == 'always' || !empty($force_type) || !in_array('native', $valid_types) || ($types['real'] != $ftype && (!AmpConfig::get('webplayer_flash') || $urlinfo['type'] != 'song'))) {
                if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && in_array('transcode', $valid_types))) {
                    // Transcode forced from client side
                    if (!empty($force_type) && AmpConfig::get('transcode_player_customize')) {
                        debug_event("webplayer.class", "Forcing type to {" . $force_type . "}", 5);
                        // Transcode only if excepted type available
                        $transcode_settings = $media->get_transcode_settings($force_type, 'webplayer');
                        if ($transcode_settings) {
                            $types['real'] = $transcode_settings['format'];
                            $transcode     = true;
                        }
                    }

                    // Transcode is not forced, transcode only if required
                    if (!$transcode) {
                        if ($transcode_cfg == 'always' || !in_array('native', $valid_types)) {
                            $transcode_settings = $media->get_transcode_settings(null, 'webplayer');
                            if ($transcode_settings) {
                                $types['real'] = $transcode_settings['format'];
                                $transcode     = true;
                            }
                        }
                    }
                }
            }

            if (!$transcode) {
                $types['real'] = $ftype;
            }

            if ($urlinfo['type'] == 'song' || $urlinfo['type'] == 'podcast_episode') {
                if ($types['real'] == "ogg" || $types['real'] == "opus") {
                    $types['player'] = "oga";
                } else {
                    if ($types['real'] == "mp4") {
                        $types['player'] = "m4a";
                    }
                }
            } else {
                if ($urlinfo['type'] == 'video') {
                    if ($types['real'] == "ogg") {
                        $types['player'] = "ogv";
                    } else {
                        if ($types['real'] == "webm") {
                            $types['player'] = "webmv";
                        } else {
                            if ($types['real'] == "mp4") {
                                $types['player'] = "m4v";
                            }
                        }
                    }
                }
            }
        } else {
            if ($item->type == 'live_stream') {
                $types['real'] = $item->codec;
                if ($types['real'] == "ogg" || $types['real'] == "opus") {
                    $types['player'] = "oga";
                }
            } else {
                $ext = pathinfo($item->url, PATHINFO_EXTENSION);
                if (!empty($ext)) {
                    $types['real'] = $ext;
                }
            }
        }

        if (empty($types['player'])) {
            $types['player'] = $types['real'];
        }

        debug_event("webplayer.class", "Types {" . json_encode($types) . "}", 5);

        return $types;
    }

    /**
     * Get all supplied types for a playlist.
     * @param \Stream_Playlist $playlist
     * @return array
     */
    public static function get_supplied_types($playlist)
    {
        $jptypes = array();
        foreach ($playlist->urls as $item) {
            $force_type = '';
            if ($item->type == 'broadcast') {
                $force_type = 'mp3';
            }
            $types = self::get_types($item, $force_type);
            if (!in_array($types['player'], $jptypes)) {
                $jptypes[] = $types['player'];
            }
        }

        return $jptypes;
    }

    /**
     * Get add_media javascript.
     * @param \Stream_Playlist $playlist
     * @param string $callback_container
     * @return string
     */
    public static function add_media_js($playlist, $callback_container = '')
    {
        $addjs = "";
        foreach ($playlist->urls as $item) {
            if ($item->type == 'broadcast') {
                $addjs .= $callback_container . "startBroadcastListening('" . $item->url . "');";
                break;
            } else {
                $addjs .= $callback_container . "addMedia(" . self::get_media_js_param($item) . ");";
            }
        }

        return $addjs;
    }

    /**
     * Get play_next javascript.
     * @param \Stream_Playlist $playlist
     * @param string $callback_container
     * @return string
     */
    public static function play_next_js($playlist, $callback_container = '')
    {
        $addjs = "";
        foreach ($playlist->urls as $item) {
            if ($item->type == 'broadcast') {
                $addjs .= $callback_container . "startBroadcastListening('" . $item->url . "');";
                break;
            } else {
                $addjs .= $callback_container . "playNext(" . self::get_media_js_param($item) . ");";
            }
        }

        return $addjs;
    }

    /**
     * Get media javascript parameters.
     * @param \playable_item $item
     * @param string $force_type
     * @return string
     */
    public static function get_media_js_param($item, $force_type = '')
    {
        $js = array();
        foreach (array('title', 'author') as $member) {
            if ($member == "author") {
                $kmember = "artist";
            } else {
                $kmember = $member;
            }

            $js[$kmember] = $item->$member;
        }
        $types = self::get_types($item, $force_type);

        $media    = null;
        $item_url = $item->url;
        $urlinfo  = Stream_URL::parse($item_url);
        $url      = $urlinfo['base_url'];

        if ($urlinfo['id'] && Core::is_media($urlinfo['type'])) {
            $media = new $urlinfo['type']($urlinfo['id']);
        } else {
            if ($urlinfo['id'] && $urlinfo['type'] == 'song_preview') {
                $media = new Song_Preview($urlinfo['id']);
            } else {
                if (isset($urlinfo['demo_id'])) {
                    $democratic = new Democratic($urlinfo['demo_id']);
                    if ($democratic->id) {
                        $song_id = $democratic->get_next_object();
                        if ($song_id) {
                            $media = new Song($song_id);
                        }
                    }
                }
            }
        }

        if ($media != null) {
            $media->format();
            if ($urlinfo['type'] == 'song') {
                $js['artist_id']             = $media->artist;
                $js['album_id']              = $media->album;
                $js['replaygain_track_gain'] = $media->replaygain_track_gain;
                $js['replaygain_track_peak'] = $media->replaygain_track_peak;
                $js['replaygain_album_gain'] = $media->replaygain_album_gain;
                $js['replaygain_album_peak'] = $media->replaygain_album_peak;
            }
            $js['media_id']   = $media->id;
            $js['media_type'] = $urlinfo['type'];

            if ($media->type != $types['real']) {
                $url .= '&transcode_to=' . $types['real'];
            }
            //$url .= "&content_length=required";
        }

        $js['filetype'] = $types['player'];
        $js['url']      = $url;
        if ($item->image_url) {
            $js['poster'] = $item->image_url;
        }

        debug_event("webplayer.class", "Return get_media_js_param {" . json_encode($js) . "}", 5);

        return json_encode($js);
    }
}
