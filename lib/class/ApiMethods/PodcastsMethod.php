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

use AmpConfig;
use Api;
use JSON_Data;
use Session;
use XML_Data;

final class PodcastsMethod
{
    /**
     * podcasts
     * MINIMUM_API_VERSION=420000
     *
     * Get information about podcasts.
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term
     * include = (string) 'episodes' (include episodes in the response) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * @return boolean
     */
    public static function podcasts($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        Api::$browse->reset_filters();
        Api::$browse->set_type('podcast');
        Api::$browse->set_sort('title', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);
        Api::set_filter('add', $input['add']);
        Api::set_filter('update', $input['update']);

        $podcasts = Api::$browse->get_objects();
        $episodes = $input['include'] == 'episodes';

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::podcasts($podcasts, $episodes);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::podcasts($podcasts, $episodes);
        }
        Session::extend($input['auth']);

        return true;
    }
}
