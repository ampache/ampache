<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\User;

/**
 * Class UsersMethod
 * @package Lib\ApiMethods
 */
final class UsersMethod
{
    public const ACTION = 'users';

    /**
     * users
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get ids and usernames for your site
     * @param array{
     *  api_format: string,
     *  limit?: string,
     *  offset?: string,
     *  cond?: string,
     * } $input
     */
    public static function users(array $input, User $user): bool
    {
        $browse = Api::getBrowse();
        $browse->set_type('user');

        $sort = array_map('trim', explode(',', $input['sort'] ?? 'id,ASC'));
        $sort_name = $sort[0] ?: 'id';
        $sort_type = $sort[1] ?? 'ASC';
        $browse->set_sort($sort_name, $sort_type);

        $browse->set_filter('disabled', 0);
        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('user', $input['api_format']);

            return false;
        }
        unset($user);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::users($results);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::users($results);
        }

        return true;
    }
}
