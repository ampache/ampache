<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Podcast_Episode;

/**
 * Class Stream4Method
 */
final class Stream4Method
{
    public const ACTION = 'stream';

    /**
     * stream
     * MINIMUM_API_VERSION=400001
     *
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     *
     * id      = (string) $song_id|$podcast_episode_id
     * type    = (string) 'song'|'podcast'
     * bitrate = (integer) max bitrate for transcoding
     * format  = (string) 'mp3'|'ogg', etc use 'raw' to skip transcoding SONG ONLY
     * offset  = (integer) time offset in seconds
     * length  = (integer) 0,1
     */
    public static function stream(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('id', 'type'), self::ACTION)) {
            return false;
        }
        $fileid = $input['id'];
        $type   = $input['type'];

        $maxBitRate    = (int)($input['maxBitRate'] ?? 0);
        $format        = $input['format'] ?? null; // mp3, flv or raw
        $transcode_to  = $format && $format != 'raw';
        $timeOffset    = $input['offset'] ?? null;
        $contentLength = (int)($input['length'] ?? 0); // Force content-length guessing if transcode

        $params = '&client=api';
        if ($contentLength == 1) {
            $params .= '&content_length=required';
        }
        if ($transcode_to && $type == 'song') {
            $params .= '&transcode_to=' . $format;
        }
        if ($maxBitRate > 0 && $type == 'song') {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if ($type == 'song') {
            $media = new Song($fileid);
            $url   = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }
        if ($type == 'podcast') {
            $media = new Podcast_Episode($fileid);
            $url   = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }
        if (!empty($url)) {
            Session::extend($input['auth'], 'api');
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        Api4::message('error', 'failed to create: ' . $url, '400', $input['api_format']);

        return true;
    } // stream
}
