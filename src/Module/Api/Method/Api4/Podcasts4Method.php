<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * Class Podcasts4Method
 */
final class Podcasts4Method
{
    public const ACTION = 'podcasts';

    /**
     * podcasts
     * MINIMUM_API_VERSION=420000
     *
     * Get information about podcasts.
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term
     * include = (string) 'episodes' (include episodes in the response) // optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * @return boolean
     */
    public static function podcasts(array $input): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        $browse = Api4::getBrowse();
        $browse->reset_filters();
        $browse->set_type('podcast');
        $browse->set_sort('title', 'ASC');

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);

        $podcasts = $browse->get_objects();
        $episodes = $input['include'] == 'episodes';
        $user     = User::get_from_username(Session::username($input['auth']));
        $user_id  = $user->id;

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::podcasts($podcasts, $user_id, $episodes);
                break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::podcasts($podcasts, $user_id, $episodes);
        }

        return true;
    } // podcasts
}
