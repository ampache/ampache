<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Repository\Model\User;

/**
 * Class Users6Method
 * @package Lib\Api6Methods
 */
final class Users6Method
{
    public const ACTION = 'users';

    /**
     * users
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get ids and usernames for your site
     *
     * @param array{
     *     offset?: string,
     *     limit?: string,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function users(array $input, User $user): bool
    {
        $browse = Api6::getBrowse($user);
        $browse->set_type('user');

        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), ['id', 'ASC']);

        $browse->set_filter('disabled', 0);

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            Api6::empty('user', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json6_Data::set_offset((int)($input['offset'] ?? 0));
                Json6_Data::set_limit($input['limit'] ?? 0);
                Json6_Data::set_count($browse->get_total());
                echo Json6_Data::users($results);
                break;
            default:
                Xml6_Data::set_offset((int)($input['offset'] ?? 0));
                Xml6_Data::set_limit($input['limit'] ?? 0);
                Xml6_Data::set_count($browse->get_total());
                echo Xml6_Data::users($results);
        }

        return true;
    }
}
