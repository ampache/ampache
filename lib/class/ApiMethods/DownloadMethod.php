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

final class DownloadMethod
{
    /**
     * download
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * @param array $input
     * id     = (string) $song_id| $podcast_episode_id
     * type   = (string) 'song', 'podcast'
     * format = (string) 'mp3', 'ogg', etc //optional
     * @return boolean
     */
    public static function download($input)
    {
        if (!Api::check_parameter($input, array('id', 'type'), 'download')) {
            return false;
        }
        $fileid   = $input['id'];
        $type     = $input['type'];
        $format   = $input['format'];
        $original = $format && $format != 'raw';
        $user_id  = \User::get_from_username(\Session::username($input['auth']))->id;

        $url    = '';
        $params = '&action=download' . '&client=api' . '&cache=1';
        if ($original) {
            $params .= '&transcode_to=' . $format;
        }
        if ($format) {
            $params .= '&format=' . $format;
        }
        if ($type == 'song') {
            $url = \Song::generic_play_url('song', $fileid, $params, 'api', function_exists('curl_version'), $user_id, $original);
        }
        if ($type == 'podcast') {
            $url = \Song::generic_play_url('podcast_episode', $fileid, $params, 'api', function_exists('curl_version'), $user_id, $original);
        }
        if (!empty($url)) {
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        Api::message('error', 'failed to create: ' . $url, '400', $input['api_format']);
        \Session::extend($input['auth']);

        return true;
    }
}
