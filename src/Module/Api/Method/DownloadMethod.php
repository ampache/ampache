<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\System\Session;

/**
 * Class DownloadMethod
 * @package Lib\ApiMethods
 */
final class DownloadMethod
{
    public const ACTION = 'download';

    /**
     * download
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * id      = (string) $song_id| $podcast_episode_id
     * type    = (string) 'song', 'podcast_episode', 'search', 'playlist'
     * bitrate = (integer) max bitrate for transcoding, '128', '256' //optional SONG ONLY
     * format  = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding //optional SONG ONLY
     */
    public static function download(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('id', 'type'), self::ACTION)) {
            http_response_code(400);

            return false;
        }
        $object_id  = (int) $input['id'];
        $type       = (string) $input['type'];
        $maxBitRate = (int)($input['bitrate'] ?? 0);
        $format     = $input['format'] ?? null; // mp3, flv or raw

        $params = '&client=api&action=download&cache=1';
        if ($format && in_array($type, array('song', 'search', 'playlist'))) {
            $params .= '&format=' . $format;
        }
        if ($format != 'raw' && $maxBitRate > 0 && in_array($type, array('song', 'search', 'playlist'))) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        $url = '';
        if ($type == 'song') {
            $media = new Song($object_id);
            $url   = $media->play_url($params, AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);
        }
        if ($type == 'podcast_episode' || $type == 'podcast') {
            $media = new Podcast_Episode($object_id);
            $url   = $media->play_url($params, AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);
        }
        if ($type == 'search' || $type == 'playlist') {
            $song_id = Random::get_single_song($type, $user, $object_id);
            $media   = new Song($song_id);
            $url     = $media->play_url($params, AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);
        }
        if (!empty($url)) {
            Session::extend($input['auth'], AccessTypeEnum::API->value);
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }

        // download not found
        http_response_code(404);

        return false;
    }
}
