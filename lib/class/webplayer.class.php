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

class WebPlayer
{
    public static function is_playlist_radio($playlist)
    {
        $radioas = array();

        foreach ($playlist->urls as $item) {
            if ($item->type == "radio") {
                $radios[] = $item;
            }
        }

        return (count($playlist->urls) == 1 && count($radios) > 0 && AmpConfig::get('webplayer_flash'));
    }
    
    public static function is_playlist_video($playlist)
    {
        return (count($playlist->urls) > 0 && $playlist->urls[0]->type == "video");
    }

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
    
    protected static function get_types($item) {

        $types = array('real' => 'mp3', 'player' => '');
    
        $browsers = array_keys(self::browser_info());
        if (count($browsers) > 0 ) {
            $browser = $browsers[0];
        }
        if ($browser == "msie" || $browser == "trident" || $browser == "webkit" || $browser == "safari") {
            $types['real'] = "mp3";
        } else {
            $types['real'] = "ogg";
        }
        
        $urlinfo = Stream_URL::parse($item->url);
        if ($urlinfo['id'] && $urlinfo['type'] == 'song') {
            $song = new Song($urlinfo['id']);
            $ftype = $song->type;
            
            $transcode = false;
            $transcode_cfg = AmpConfig::get('transcode');
            // Check transcode is required
            if ($transcode_cfg == 'always' || $types['real'] != $ftype) {
                $valid_types = Song::get_stream_types_for_type($ftype);
                if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && in_array('transcode', $valid_types))) {
                    // Transcode only if excepted type available
                    $transcode_settings = $song->get_transcode_settings($types['real']);
                    if ($transcode_settings) {
                        $transcode = true;
                    } else {
                        if (!in_array('native', $valid_types)) {
                            $transcode_settings = $song->get_transcode_settings(null);
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
            if ($types['real'] == "flac" || $types['real'] == "ogg") $types['player'] = "oga";
        } else if ($urlinfo['id'] && $urlinfo['type'] == 'video') {
            $video = new Video($urlinfo['id']);
            $types['real'] = pathinfo($video->file, PATHINFO_EXTENSION);
            
            if ($types['real'] == "ogg") $types['player'] = "ogv";
            else if ($types['real'] == "webm") $types['player'] = "webmv";
        } else {
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            if (!empty($ext)) $types['player'] = $ext;
        }
        
        if (empty($types['player'])) $types['player'] = $types['real'];
        
        return $types;
    }
    
    public static function get_supplied_types($playlist)
    {
        $jptypes = array();
        foreach ($playlist->urls as $item) {
            $types = self::get_types($item);
            if (!in_array($types['player'], $jptypes)) {
                $jptypes[] = $types['player'];
            }
        }
        
        return $jptypes;
    }

    public static function add_media_js($playlist, $callback='addMedia')
    {
        $addjs = "";
        foreach ($playlist->urls as $item) {
            $js = array();
            foreach (array('title', 'author') as $member) {
                if ($member == "author")
                    $kmember = "artist";
                else
                    $kmember = $member;

                $js[$kmember] = $item->$member;
            }
            $url = $item->url;

            $types = self::get_types($item);
            $urlinfo = Stream_URL::parse($url);
            if ($urlinfo['id'] && $urlinfo['type'] == 'song') {
                $song = new Song($urlinfo['id']);

                $js['artist_id'] = $song->artist;
                $js['album_id'] = $song->album;
                $js['song_id'] = $song->id;

                if ($song->type != $types['real']) {
                    $url .= '&transcode_to=' . $types['real'];
                }
                //$url .= "&content_length=required";
            }
            
            $js['filetype'] = $types['player'];
            $js['url'] = $url;
            $js['poster'] = $item->image_url . (!$iframed ? '&thumb=4' : '');
            
            $addjs .= $callback . "(" . json_encode($js) . ");";
        }

        return $addjs;
    }
}
