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
use Playlist;
use Session;
use User;
use XML_Data;

final class GetIndexesMethod
{
    /**
     * get_indexes
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=430000
     *
     * This takes a collection of inputs and returns ID + name for the object type
     * Added 'include' to allow indexing all song tracks (enabled for xml by default)
     *
     * @param array $input
     * type    = (string) 'song', 'album', 'artist', 'playlist'
     * filter  = (string) //optional
     * exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add     = self::set_filter(date) //optional
     * update  = self::set_filter(date) //optional
     * include = (integer) 0,1 include songs if available for that object //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * @return boolean
     */
    public static function get_indexes($input)
    {
        if (!Api::check_parameter($input, array('type'), 'get_indexes')) {
            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $type    = (string) $input['type'];
        $include = ((int) $input['include'] == 1 || ($input['api_format'] == 'xml' && !isset($input['include']))) ? true : false;
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist'))) {
            Api::message('error', T_('Incorrect object type') . ' ' . $type, '400', $input['api_format']);

            return false;
        }
        Api::$browse->reset_filters();
        Api::$browse->set_type($type);
        Api::$browse->set_sort('name', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);
        Api::set_filter('add', $input['add']);
        Api::set_filter('update', $input['update']);

        if ($type == 'playlist') {
            Api::$browse->set_filter('playlist_type', $user->id);
            $objects = array_merge(Api::$browse->get_objects(), Playlist::get_smartlists(true, $user->id));
        } else {
            $objects = Api::$browse->get_objects();
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::indexes($objects, $type, $include);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::indexes($objects, $type, true, $include);
        }
        Session::extend($input['auth']);

        return true;
    }
}
