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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class Download4Method
 */
final class Download4Method
{
    public const ACTION = 'download';

    /**
     * download
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * id     = (string) $song_id| $podcast_episode_id
     * type   = (string) 'song'|'podcast'
     * format = (string) 'mp3'|'ogg', etc //optional SONG ONLY
     */
    public static function download(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['id', 'type'], self::ACTION)) {
            return false;
        }
        $fileid       = $input['id'];
        $type         = $input['type'];
        $format       = $input['format'] ?? null; // mp3, flv or raw
        $transcode_to = $format && $format != 'raw';

        $url    = '';
        $params = '&client=api&action=download&cache=1';
        if ($transcode_to && $type == 'song') {
            $params .= '&transcode_to=' . $format;
        }
        if ($format && $type == 'song') {
            $params .= '&format=' . $format;
        }
        if ($type == 'song') {
            $media = new Song($fileid);
            $url   = $media->play_url($params, AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);
        }
        if ($type == 'podcast_episode' || $type == 'podcast') {
            $media = new Podcast_Episode($fileid);
            $url   = $media->play_url($params, AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);
        }
        if (!empty($url)) {
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        Api4::message('error', 'failed to create: ' . $url, '400', $input['api_format']);

        return true;
    }
}
