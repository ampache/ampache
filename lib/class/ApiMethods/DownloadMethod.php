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
use Podcast_Episode;
use Session;
use Song;
use User;

/**
 * Class DownloadMethod
 * @package Lib\ApiMethods
 */
final class DownloadMethod
{
    private const ACTION = 'download';

    /**
     * download
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * @param array $input
     * id     = (string) $song_id| $podcast_episode_id
     * type   = (string) 'song', 'podcast_episode'
     * format = (string) 'mp3', 'ogg', etc //optional
     * @return boolean
     */
    public static function download(array $input)
    {
        if (!Api::check_parameter($input, array('id', 'type'), self::ACTION)) {
            http_response_code(400);

            return false;
        }
        $object_id = (int) $input['id'];
        $type      = (string) $input['type'];
        $format    = $input['format'];
        $original  = $format && $format != 'raw';
        $user_id   = User::get_from_username(Session::username($input['auth']))->id;

        $params = '&action=download' . '&client=api' . '&cache=1';
        if ($original) {
            $params .= '&transcode_to=' . $format;
        }
        if ($format) {
            $params .= '&format=' . $format;
        }
        $url = '';
        if ($type == 'song') {
            $media = new Song($object_id);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        if ($type == 'podcast_episode' || $type == 'podcast') {
            $media = new Podcast_Episode($object_id);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        if (!empty($url)) {
            Session::extend($input['auth']);
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        // download not found
        http_response_code(404);

        return false;
    }
}
