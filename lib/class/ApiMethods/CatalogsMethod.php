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
use Catalog;
use JSON_Data;
use Session;
use XML_Data;

/**
 * Class CatalogsMethod
 * @package Lib\ApiMethods
 */
final class CatalogsMethod
{
    const ACTION = 'catalogs';

    /**
     * catalogs
     * MINIMUM_API_VERSION=420000
     *
     * Get information about catalogs this user is allowed to manage.
     *
     * @param array $input
     * filter = (string) set $filter_type //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function catalogs(array $input)
    {
        // filter for specific catalog types
        $filter   = (in_array($input['filter'], array('music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast'))) ? $input['filter'] : '';
        $catalogs = Catalog::get_catalogs($filter);
        if (empty($catalogs)) {
            Api::empty('catalog', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::catalogs($catalogs);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::catalogs($catalogs);
        }
        Session::extend($input['auth']);

        return true;
    }
}
