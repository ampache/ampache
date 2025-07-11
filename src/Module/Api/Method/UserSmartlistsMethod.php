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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Xml_Data;

/**
 * Class UserSmartlistsMethod
 * @package Lib\ApiMethods
 */
final class UserSmartlistsMethod
{
    public const ACTION = 'user_smartlists';

    /**
     * user_smartlists
     * MINIMUM_API_VERSION=6.3.0
     *
     * This returns smartlists (searches) based on the specified filter (Does not include playlists)
     *
     * filter  = (string) Alpha-numeric search term (match all if missing) //optional
     * exact   = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * include = (integer) 0,1, if true include playlist contents //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * cond    = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort    = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     *
     * @param array{
     *     filter?: string,
     *     exact?: int,
     *     include?: string|int,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function user_smartlists(array $input, User $user): bool
    {
        $include = (isset($input['include']) && ((int)$input['include'] === 1 || $input['include'] === 'songs'));
        $browse  = Api::getBrowse($user);
        $browse->set_type('smartplaylist');

        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), ['name', 'ASC']);

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_filter('playlist_user', $user->getId());

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = preg_filter('/^/', 'smart_', $browse->get_objects());
        if (empty($results)) {
            Api::empty('playlist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                Json_Data::set_count($browse->get_total());
                echo Json_Data::playlists($results, $user, $input['auth'], $include);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                Xml_Data::set_count($browse->get_total());
                echo Xml_Data::playlists($results, $user, $input['auth'], $include);
        }

        return true;
    }
}
