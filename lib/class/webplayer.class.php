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

class WebPlayer
{
    /**
     * Check if the playlist is a radio playlist.
     * @param \Playlist $playlist
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
     * @param \Playlist $playlist
     * @return boolean
     */
    public static function is_playlist_video($playlist)
    {
        return (count($playlist->urls) > 0 && $playlist->urls[0]->type == "video");
    }

    /**
     * Get browser information.
     * @param string $agent
     * @return array
     */
    public static function browser_info($agent=null)
    {
        // Declare known browsers to look for
        $known = array('msie', 'trident', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko');

        // Clean up agent and build regex that matches phrases for known browsers
        // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
        // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
        $agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';

        // Find all phrases (or return empty array if none found)
        if (!preg_match_all($pattern, $agent, $matches)) return array();

        // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
        // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
        // in the UA).  That's usually the most correct.
        $i = count($matches['browser'])-1;
        return array($matches['browser'][$i] => $matches['version'][$i]);

    }

    /**
     * Get types information for an item.
     * @param \playable_item $item
     * @param string $force_type
     * @return array
     */
    protected static function get_types($item, $force_type='')
    {
        $types = array('real' => 'mp3', 'player' => '');

        $browsers = array_keys(self::browser_info());
        $browser = '';
        if (count($browsers) > 0 ) {
            $browser = $browsers[0];
        }

        $media = null;
        $urlinfo = Stream_URL::parse($item->url);
        if ($urlinfo['id'] && Core::is_media($urlinfo['type'])) {
            $media = new $urlinfo['type']($urlinfo['id']);
        } else if ($urlinfo['id'] && $urlinfo['type'] == 'song_preview') {
            $media = new Song_Preview($urlinfo['id']);
        } else if (isset($urlinfo['demo_id'])) {
            $democratic = new Democratic($urlinfo['demo_id']);
            if ($democratic->id) {
                $song_id = $democratic->get_next_object();
                if ($song_id) {
                    $media = new Song($song_id);
                }
            }
        }

        if (!empty($force_type)) {
            debug_event("webplayer.class.php", "Forcing type to {".$force_type."}", 5);
            $types['real'] = $force_type;
        } else {
            if ($urlinfo['type'] == 'song') {
                // For audio, get the best format according to the browser
                if ($browser == "msie" || $browser == "trident" || $browser == "webkit" || $browser == "safari") {
                    $types['real'] = "mp3";
                } else {
                    $types['real'] = "ogg";
                }
            } else {
                // For video, always use webm
                $types['real'] = "webm";
            }
        }

        if ($media != null) {
            $ftype = $media->type;

            $transcode = false;
            $transcode_cfg = AmpConfig::get('transcode');
            // Check transcode is required
            $ftype_transcode = AmpConfig::get('transcode_' . $ftype);
            $valid_types = Song::get_stream_types_for_type($ftype);
            if ($transcode_cfg == 'always' || !empty($force_type) || $ftype_transcode == 'required' || ($types['real'] != $ftype && (!AmpConfig::get('webplayer_flash') || $urlinfo['type'] != 'song'))) {
                if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && in_array('transcode', $valid_types))) {
                    // Transcode only if excepted type available
                    $transcode_settings = $media->get_transcode_settings($types['real'], null, $urlinfo['type']);
                    if ($transcode_settings && AmpConfig::get('transcode_player_customize')) {
                        $transcode = true;
                    } else {
                        if (!in_array('native', $valid_types)) {
                            $transcode_settings = $media->get_transcode_settings(null);
                            if ($transcode_settings) {
                                $types['real'] = $transcode_settings['format'];
                                $transcode = true;
                            }
                        }
                    }
                }
            }

            if (!$transcode) {
                $types['real'] = $ftype;
            }

            if ($urlinfo['type'] == 'song') {
                if ($types['real'] == "flac" || $types['real'] == "ogg") $types['player'] = "oga";
                else if ($types['real'] == "mp4") $types['player'] = "m4a";
            } else if ($urlinfo['type'] == 'video') {
                if ($types['real'] == "ogg") $types['player'] = "ogv";
                else if ($types['real'] == "webm") $types['player'] = "webmv";
                else if ($types['real'] == "mp4") $types['player'] = "m4v";
            }
        } else if ($item->type == 'live_stream') {
            $types['real'] = $item->codec;
            if ($types['real'] == "flac" || $types['real'] == "ogg") $types['player'] = "oga";
        } else {
            $ext = pathinfo($item->url, PATHINFO_EXTENSION);
            if (!empty($ext)) $types['real'] = $ext;
        }

        if (empty($types['player'])) $types['player'] = $types['real'];

        debug_event("webplayer.class.php", "Types {".json_encode($types)."}", 5);
        return $types;
    }

    /**
     * Get all supplied types for a playlist.
     * @param \Playlist $playlist
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
     * @param \Playlist $playlist
     * @param string $callback_container
     * @return string
     */
    public static function add_media_js($playlist, $callback_container='')
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
     * Get media javascript parameters.
     * @param \playable_item $item
     * @param string $force_type
     * @return string
     */
    public static function get_media_js_param($item, $force_type='')
    {
        $js = array();
        foreach (array('title', 'author') as $member) {
            if ($member == "author")
                $kmember = "artist";
            else
                $kmember = $member;

            $js[$kmember] = $item->$member;
        }
        $url = $item->url;

        $types = self::get_types($item, $force_type);

        $media = null;
        $urlinfo = Stream_URL::parse($url);
        $url = $urlinfo['base_url'];

        if ($urlinfo['id'] && Core::is_media($urlinfo['type'])) {
            $media = new $urlinfo['type']($urlinfo['id']);
        } else if ($urlinfo['id'] && $urlinfo['type'] == 'song_preview') {
            $media = new Song_Preview($urlinfo['id']);
        } else if (isset($urlinfo['demo_id'])) {
            $democratic = new Democratic($urlinfo['demo_id']);
            if ($democratic->id) {
                $song_id = $democratic->get_next_object();
                if ($song_id) {
                    $media = new Song($song_id);
                }
            }
        }

        if ($media != null) {
            if ($urlinfo['type'] == 'song') {
                $js['artist_id'] = $media->artist;
                $js['album_id'] = $media->album;
            }
            $js['song_id'] = $media->id;

            if ($media->type != $types['real']) {
                $url .= '&transcode_to=' . $types['real'];
            }
            //$url .= "&content_length=required";
        }

        $js['filetype'] = $types['player'];
        $js['url'] = $url;
        if ($urlinfo['type'] == 'song') {
            $js['poster'] = $item->image_url . (!AmpConfig::get('ajax_load') ? '&thumb=4' : '');
        }

        debug_event("webplayer.class.php", "Return get_media_js_param {".json_encode($js)."}", 5);

        return json_encode($js);
    }
}
