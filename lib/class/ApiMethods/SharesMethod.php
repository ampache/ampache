<?php

declare(strict_types=0);

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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use JSON_Data;
use Session;
use XML_Data;

/**
 * Class SharesMethod
 * @package Lib\ApiMethods
 */
final class SharesMethod
{
    private const ACTION = 'shares';

    /**
     * shares
     * MINIMUM_API_VERSION=420000
     *
     * Get information about shared media this user is allowed to manage.
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function shares(array $input)
    {
        if (!AmpConfig::get('share')) {
            Api::error(T_('Enable: share'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Api::$browse->reset_filters();
        Api::$browse->set_type('share');
        Api::$browse->set_sort('title', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);
        Api::set_filter('add', $input['add']);
        Api::set_filter('update', $input['update']);

        $shares = Api::$browse->get_objects();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::shares($shares);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::shares($shares);
        }
        Session::extend($input['auth']);

        return true;
    }
}
