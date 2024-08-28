<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Playback;

use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Media;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\Video;

class WebPlayer
{
    /**
     * Check if the playlist is a radio playlist.
     * @param Stream_Playlist $playlist
     */
    public static function is_playlist_radio($playlist): bool
    {
        $radios = [];

        foreach ($playlist->urls as $item) {
            if ($item->type == "radio") {
                $radios[] = $item;
            }
        }

        return (count($playlist->urls) == 1 && count($radios) > 0 && AmpConfig::get('webplayer_flash'));
    }

    /**
     * Check if the playlist is a video playlist.
     * @param Stream_Playlist $playlist
     */
    public static function is_playlist_video($playlist): bool
    {
        return (count($playlist->urls) > 0 && $playlist->urls[0]->type == "video");
    }

    /**
     * Check if the playlist is a random playlist.
     * @param Stream_Playlist $playlist
     */
    public static function is_playlist_random($playlist): bool
    {
        return (count($playlist->urls) > 0 && $playlist->urls[0]->title == "Random");
    }

    /**
     * Check if the playlist is a democratic playlist.
     * @param Stream_Playlist $playlist
     */
    public static function is_playlist_democratic($playlist): bool
    {
        return (count($playlist->urls) > 0 && $playlist->urls[0]->title == "Democratic");
    }

    /**
     * Get types information for an item.
     * @param Stream_URL $item
     * @param string $force_type
     * @param array $urlinfo
     * @param array $transcode_cfg
     * @return array
     */
    protected static function get_types($item, $urlinfo, $transcode_cfg, $force_type = ''): array
    {
        $types = ['real' => 'mp3', 'player' => ''];

        if ($item->codec && array_key_exists('type', $urlinfo)) {
            $transcode = self::can_transcode($urlinfo['type'], $item->codec, $types, $urlinfo, $transcode_cfg, $force_type);
            $types     = self::get_media_types($urlinfo, $types, $item->codec, $transcode);
        } elseif ($media = self::get_media_object($urlinfo)) {
            $transcode = self::can_transcode(strtolower(get_class($media)), $media->type, $types, $urlinfo, $transcode_cfg, $force_type);
            $types     = self::get_media_types($urlinfo, $types, $media->type, $transcode);
        } elseif ($item->type == 'live_stream') {
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

        if (empty($types['player'])) {
            $types['player'] = $types['real'];
        }

        return $types;
    }

    /**
     * Check if the playlist is a video playlist.
     * @param array $urlinfo
     * @return Media|null
     */
    public static function get_media_object($urlinfo)
    {
        if (array_key_exists('id', $urlinfo) && InterfaceImplementationChecker::is_media($urlinfo['type'])) {
            $className = ObjectTypeToClassNameMapper::map($urlinfo['type']);

            return new $className($urlinfo['id']);
        }
        if (array_key_exists('id', $urlinfo) && $urlinfo['type'] == 'song_preview') {
            return new Song_Preview($urlinfo['id']);
        }

        return null;
    }

    /**
     * Check if the playlist is a video playlist.
     * @param array $urlinfo
     * @param array $types
     * @param string $file_type
     * @param bool $transcode
     * @return array
     */
    public static function get_media_types($urlinfo, $types, $file_type, $transcode): array
    {
        $types['real'] = ($transcode)
            ? Stream::get_transcode_format($file_type, null, 'webplayer', $urlinfo['type'])
            : $file_type;

        if ($urlinfo['type'] == 'song' || $urlinfo['type'] == 'podcast_episode') {
            if ($types['real'] == "ogg" || $types['real'] == "opus") {
                $types['player'] = "oga";
            } elseif ($types['real'] == "mp4") {
                $types['player'] = "m4a";
            }
        }
        if ($urlinfo['type'] == 'video') {
            if ($types['real'] == "ogg") {
                $types['player'] = "ogv";
            } elseif ($types['real'] == "webm") {
                $types['player'] = "webmv";
            } elseif ($types['real'] == "mp4") {
                $types['player'] = "m4v";
            }
        }

        return $types;
    }

    /**
     * Check if we can transcode this file type
     * @param string $media_type
     * @param string $file_type
     * @param array $types
     * @param array $urlinfo
     * @param string $force_type
     * @param array $transcode_cfg
     */
    public static function can_transcode(
        $media_type,
        $file_type,
        $types,
        $urlinfo,
        $transcode_cfg,
        $force_type = ''
    ): bool {
        $transcode = false;

        // Check transcode is required
        $valid_types = Stream::get_stream_types_for_type($file_type);
        if (
            $transcode_cfg == 'always' ||
            !empty($force_type) ||
            !in_array('native', $valid_types) ||
            (
                $types['real'] != $file_type &&
                (
                    !AmpConfig::get('webplayer_flash') ||
                    $urlinfo['type'] != 'song'
                )
            )
        ) {
            if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && in_array('transcode', $valid_types))) {
                // Transcode forced from client side
                if (!empty($force_type) && AmpConfig::get('transcode_player_customize')) {
                    debug_event(self::class, "Forcing type to {{$force_type}}", 5);
                    // Transcode only if excepted type available
                    $transcode_settings = Stream::get_transcode_settings_for_media($file_type, $force_type, 'webplayer', $media_type);
                    if (!empty($transcode_settings)) {
                        $transcode = true;
                    }
                }

                // Transcode is not forced, transcode only if required
                if (!$transcode) {
                    if ($transcode_cfg == 'always' || !in_array('native', $valid_types)) {
                        $transcode_settings = Stream::get_transcode_settings_for_media($file_type, $force_type, 'webplayer', $media_type);
                        if (!empty($transcode_settings)) {
                            $transcode = true;
                        }
                    }
                }
            }
        }

        return $transcode;
    }

    /**
     * Get add_media javascript.
     * @param Stream_Playlist $playlist
     * @param string $callback_container
     */
    public static function add_media_js($playlist, $callback_container = ''): string
    {
        $transcode_cfg = AmpConfig::get('transcode', 'default');
        $addjs         = "";
        foreach ($playlist->urls as $item) {
            if ($item->type == 'broadcast') {
                $addjs .= $callback_container . "startBroadcastListening('" . $item->url . "');";
                break;
            } else {
                $addjs .= $callback_container . "addMedia(" . self::get_media_js_param($item, $transcode_cfg) . ");";
            }
        }

        return $addjs;
    }

    /**
     * Get play_next javascript.
     * @param Stream_Playlist $playlist
     * @param string $callback_container
     */
    public static function play_next_js($playlist, $callback_container = ''): string
    {
        $transcode_cfg = AmpConfig::get('transcode', 'default');
        $addjs         = "";
        // play next for groups of items needs to be reversed to be in correct order
        foreach (array_reverse($playlist->urls) as $item) {
            if ($item->type == 'broadcast') {
                $addjs .= $callback_container . "startBroadcastListening('" . $item->url . "');";
                break;
            } else {
                $addjs .= $callback_container . "playNext(" . self::get_media_js_param($item, $transcode_cfg) . ");";
            }
        }

        return $addjs;
    }

    /**
     * Get media javascript parameters.
     * @param Stream_URL $item
     * @param string $force_type
     * @param array $transcode_cfg
     */
    public static function get_media_js_param($item, $transcode_cfg, $force_type = ''): string
    {
        $json = [];
        foreach (['title', 'author'] as $member) {
            if ($member == "author") {
                $kmember = "artist";
            } else {
                $kmember = $member;
            }

            $json[$kmember] = $item->$member;
        }

        $url_data = Stream_Url::parse($item->url);
        $types    = self::get_types($item, $url_data, $transcode_cfg, $force_type);
        $url      = $url_data['base_url'];
        $media    = self::get_media_object($url_data);
        // stream urls that don't send a type (democratic playlists)
        $item->type = (empty($item->type) && !empty($url_data['type']))
            ? $url_data['type']
            : $item->type;

        //debug_event(self::class, "get_media_js_param: " . print_r($item, true), 3);
        if ($media != null) {
            /** @var Live_Stream|Podcast_Episode|Song|Song_Preview|Video $media */
            if ($url_data['type'] == 'song' && $media instanceof Song) {
                $json['artist_id'] = $media->artist;
                if (AmpConfig::get('album_group')) {
                    $json['album_id'] = $media->album;
                } else {
                    $json['albumdisk_id'] = $media->get_album_disk();
                }
                $json['album_name'] = $media->get_album_disk_fullname();
                // get replaygain from the song_data table
                $media->fill_ext_info('replaygain_track_gain, replaygain_track_peak, replaygain_album_gain, replaygain_album_peak, r128_track_gain, r128_album_gain');
                $json['replaygain_track_gain'] = $media->replaygain_track_gain;
                $json['replaygain_track_peak'] = $media->replaygain_track_peak;
                $json['replaygain_album_gain'] = $media->replaygain_album_gain;
                $json['replaygain_album_peak'] = $media->replaygain_album_peak;
                $json['r128_track_gain']       = $media->r128_track_gain;
                $json['r128_album_gain']       = $media->r128_album_gain;
                $json['duration']              = $media->time;

                // this should probably only be in songs
                if ($media->type != $types['real']) {
                    $pos = strrpos($url, '&');
                    if ($pos !== false) {
                        $url = substr($url, 0, $pos) . '&transcode_to=' . $types['real'] . '&' . substr($url, $pos + 1);
                    } else {
                        $url .= '&transcode_to=' . $types['real'];
                    }
                }
            }
            $json['media_id']   = $media->id;
            $json['media_type'] = $url_data['type'];
        } else {
            // items like live streams need to keep an id for us as well
            switch ($item->type) {
                case 'live_stream':
                    $regex           = "/radio=([0-9]*)/";
                    $types['player'] = $item->codec;
                    break;
                case 'democratic':
                    $regex           = "/demo_id=([0-9]*)/";
                    $types['player'] = 'mp3';
                    break;
                case 'random':
                    $regex           = "/random_id=([0-9]*)/";
                    $types['player'] = 'mp3';
                    break;
                default:
                    $regex = "/" . $item->type . "=([0-9]*)/";
                    break;
            }
            if (!empty($item->info_url)) {
                preg_match($regex, $item->info_url, $matches);
                $json['media_id'] = $matches[1] ?? null;
            }
            if (!empty($url)) {
                preg_match($regex, $item->url, $matches);
                $json['media_id'] = $matches[1] ?? null;
            }
            $json['media_type'] = $item->type;
        }

        $json['filetype'] = $types['player'];
        $json['url']      = $url;
        if ($item->image_url) {
            $json['poster'] = $item->image_url;
        }
        //debug_event(self::class, "get_media_js_param: " . print_r($json, true), 3);

        return json_encode($json) ?: '';
    }
}
