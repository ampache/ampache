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
use JSON_Data;
use Session;
use Stream_URL;
use User;
use XML_Data;

/**
 * Class UrlToSongMethod
 * @package Lib\ApiMethods
 */
final class UrlToSongMethod
{
    private const ACTION = 'url_to_song';

    /**
     * url_to_song
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * @param array $input
     * url = (string) $url
     * @return boolean
     */
    public static function url_to_song(array $input)
    {
        if (!Api::check_parameter($input, array('url'), self::ACTION)) {
            return false;
        }
        // Don't scrub, the function needs her raw and juicy
        $data = Stream_URL::parse($input['url']);
        if (empty($data['id'])) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'url', $input['api_format']);

            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::songs(array($data['id']), $user->id);
                break;
            default:
                echo XML_Data::songs(array($data['id']), $user->id);
        }

        return true;
    }
}
