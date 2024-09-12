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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Podcast_Episode;

/**
 * Class StreamMethod
 * @package Lib\ApiMethods
 */
final class StreamMethod
{
    public const ACTION = 'stream';

    /**
     * stream
     * MINIMUM_API_VERSION=400001
     *
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     * Search and Playlist will only stream a random object not the whole thing
     *
     * id      = (string) $song_id|$podcast_episode_id|$search_id|$playlist_id
     * type    = (string) 'song', 'podcast_episode', 'search', 'playlist'
     * bitrate = (integer) max bitrate for transcoding in bytes (e.g 192000=192Kb) // Song only
     * format  = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding // Song only
     * offset  = (integer) time offset in seconds
     * length  = (integer) 0,1
     * stats   = (integer) 0,1, if false disable stat recording when playing the object (default: 1) //optional
     */
    public static function stream(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['id', 'type'], self::ACTION)) {
            http_response_code(400);

            return false;
        }

        $object_id = (int)$input['id'];
        $type      = (string) $input['type'];

        if (
            $object_id === 0 &&
            (
                $type == 'playlist' ||
                $type == 'search'
            )
        ) {
            // The API can use searches as playlists so check for those too
            $object_id = (int)str_replace('smart_', '', $input['id']);
            $type      = 'search';
        }

        $maxBitRate    = (int)($input['bitrate'] ?? 0);
        $format        = $input['format'] ?? null; // mp3, flv or raw
        $transcode_to  = $format && $format != 'raw';
        $timeOffset    = $input['offset'] ?? null;
        $contentLength = (int)($input['length'] ?? 0); // Force content-length guessing if transcode
        $recordStats   = (int)($input['stats'] ?? 1);

        $params = '&client=api';
        if ($recordStats == 0) {
            $params .= '&cache=1';
        }

        if ($contentLength == 1) {
            $params .= '&content_length=required';
        }

        if ($transcode_to && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&format=' . $format;
        }

        if ($maxBitRate > 0 && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&bitrate=' . $maxBitRate;
        }

        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if ($type == 'song') {
            $media = new Song($object_id);
            $url   = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }
        if ($type == 'podcast_episode' || $type == 'podcast') {
            $media = new Podcast_Episode($object_id);
            $url   = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }
        if ($type == 'search' || $type == 'playlist') {
            $song_id = Random::get_single_song($type, $user, $object_id);
            $media   = new Song($song_id);
            $url     = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }
        if (!empty($url)) {
            Session::extend($input['auth'], 'api');
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }

        // stream not found
        http_response_code(404);

        return false;
    }
}
