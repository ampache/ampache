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
use User;
use XML_Data;

/**
 * Class AlbumsMethod
 * @package Lib\ApiMethods
 */
final class AlbumsMethod
{
    const ACTION = 'albums';

    /**
     * albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns albums based on the provided search filters
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term //optional
     * exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add     = self::set_filter(date) //optional
     * update  = self::set_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * include = (array|string) 'songs' //optional
     * @return boolean
     */
    public static function albums(array $input)
    {
        Api::$browse->reset_filters();
        Api::$browse->set_type('album');
        Api::$browse->set_sort('name', 'ASC');
        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);
        Api::set_filter('add', $input['add']);
        Api::set_filter('update', $input['update']);

        $albums  = Api::$browse->get_objects();
        if (empty($albums)) {
            Api::empty('album', $input['api_format']);

            return false;
        }

        ob_end_clean();
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::albums($albums, $include, $user->id);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::albums($albums, $include, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
