<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Lib\ApiMethods;

use Api;
use Session;
use Song;
use User;

/**
 * Class StreamMethod
 * @package Lib\ApiMethods
 */
final class StreamMethod
{
    private const ACTION = 'stream';

    /**
     * stream
     * MINIMUM_API_VERSION=400001
     *
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     *
     * @param array $input
     * id      = (string) $song_id|$podcast_episode_id
     * type    = (string) 'song', 'podcast'
     * bitrate = (integer) max bitrate for transcoding
     * format  = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding
     * offset  = (integer) time offset in seconds
     * length  = (integer) 0,1
     * @return boolean
     */
    public static function stream(array $input)
    {
        if (!Api::check_parameter($input, array('id', 'type'), self::ACTION)) {
            http_response_code(400);

            return false;
        }
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $user_id   = User::get_from_username(Session::username($input['auth']))->id;

        $maxBitRate    = $input['bitrate'];
        $format        = $input['format']; // mp3, flv or raw
        $original      = $format && $format != 'raw';
        $timeOffset    = $input['offset'];
        $contentLength = (int) $input['length']; // Force content-length guessing if transcode

        $params = '&client=api';
        if ($contentLength == 1) {
            $params .= '&content_length=required';
        }
        if ($original) {
            $params .= '&transcode_to=' . $format;
        }
        if ((int) $maxBitRate > 0) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if ($type == 'song') {
            $url = Song::generic_play_url('Song', $object_id, $params, 'api', function_exists('curl_version'), $user_id, $original);
        }
        if ($type == 'podcast') {
            $url = Song::generic_play_url('Podcast_Episode', $object_id, $params, 'api', function_exists('curl_version'), $user_id, $original);
        }
        if (!empty($url)) {
            Session::extend($input['auth']);
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        // stream not found
        http_response_code(404);

        return false;
    }
}
