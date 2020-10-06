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
use Recommendation;
use Session;
use XML_Data;

final class GetSimilarMethod
{
    /**
     * get_similar
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * @param array $input
     * type   = (string) 'song', 'artist'
     * filter = (integer) artist id or song id
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function get_similar($input)
    {
        if (!Api::check_parameter($input, array('type', 'filter'), 'get_similar')) {
            return false;
        }
        $type   = (string) $input['type'];
        $filter = (int) $input['filter'];
        // confirm the correct data
        if (!in_array($type, array('song', 'artist'))) {
            Api::message('error', T_('Incorrect object type') . ' ' . $type, '400', $input['api_format']);

            return false;
        }

        $objects = array();
        $similar = array();
        switch ($type) {
            case 'artist':
                $similar = Recommendation::get_artists_like($filter);
                break;
            case 'song':
                $similar = Recommendation::get_songs_like($filter);
        }
        foreach ($similar as $child) {
            $objects[] = $child['id'];
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::indexes($objects, $type);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::indexes($objects, $type);
        }
        Session::extend($input['auth']);

        return true;
    }
}
