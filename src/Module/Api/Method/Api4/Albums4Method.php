<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Api\Api;
use Ampache\Repository\Model\User;

/**
 * Class Albums4Method
 */
final class Albums4Method
{
    public const ACTION = 'albums';

    /**
     * albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns albums based on the provided search filters
     *
     * filter  = (string) Alpha-numeric search term //optional
     * exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add     = $browse->set_api_filter(date) //optional
     * update  = $browse->set_api_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * include = (array) 'songs' //optional
     *
     * @param array{
     *     filter?: string,
     *     include?: string|string[],
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     */
    public static function albums(array $input, User $user): void
    {
        $browse = Api::getBrowse($user);
        $browse->set_type('album');
        $browse->set_sort('name', 'ASC');
        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $results = $browse->get_objects();
        $include = [];
        if (array_key_exists('include', $input)) {
            if (is_array($input['include'])) {
                foreach ($input['include'] as $item) {
                    if ($item === 'songs' || $item == '1') {
                        $include[] = 'songs';
                    }
                }
            } elseif ($input['include'] === 'songs' || $input['include'] == '1') {
                $include[] = 'songs';
            }
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::albums($results, $include, $user, $input['auth']);
                break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::albums($results, $include, $user, $input['auth']);
        }
    }
}
