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
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\User;

/**
 * Class CatalogsMethod
 * @package Lib\ApiMethods
 */
final class CatalogsMethod
{
    public const ACTION = 'catalogs';

    /**
     * catalogs
     * MINIMUM_API_VERSION=420000
     *
     * Get information about catalogs this user is allowed to manage.
     *
     * filter = (string) set $filter_type 'music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast' //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * cond   = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort   = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     */
    public static function catalogs(array $input, User $user): bool
    {
        $browse = Api::getBrowse($user);
        $browse->set_type('catalog');
        $browse->set_filter('user', $user->getId());
        if (
            isset($input['filter']) &&
            in_array($input['filter'], ['music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast'])
        ) {
            // filter for specific catalog types
            $browse->set_filter('gather_type', $input['filter']);
        }

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('catalog', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::catalogs($results);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::catalogs($results, $user);
        }

        return true;
    }
}
