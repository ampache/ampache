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
use Artist;
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class ArtistMethod
 * @package Lib\ApiMethods
 */
final class ArtistMethod
{
    private const ACTION = 'artist';

    /**
     * artist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single artist based on the UID of said artist
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term
     * include = (array|string) 'albums', 'songs' //optional
     * @return boolean
     */
    public static function artist(array $input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $artist    = new Artist((int) $object_id);
        if (!$artist->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::artists(array($object_id), $include, $user->id);
                break;
            default:
                echo XML_Data::artists(array($object_id), $include, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
