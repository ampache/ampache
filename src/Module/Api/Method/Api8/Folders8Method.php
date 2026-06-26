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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\User;

/**
 * Class List8Method
 * @package Lib\Api8Methods
 */
final class Folders8Method
{
    public const string ACTION = 'folders';

    /**
     * folders
     * MINIMUM_API_VERSION=8.0.0
     *
     * Return children of a parent object in a folder traversal style
     *
     * filter = (string) path name filter (Default: '/') //optional
     * exact = (integer) 0,1, if true filter is exact rather then fuzzy (Default: 1) //optional
     * add = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     * cond = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     *
     * @param array{
     *     filter?: string,
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
     */
    public static function folders(array $input, User $user): bool
    {
        $browse = Api::getBrowse($user);
        $browse->set_type('folder');

        $path_name = $input['filter'] ?? '/';
        $folder    = ($path_name === '/')
            ? new Folder(-1)
            : self::getFolderRepository()->getByPathName($path_name);

        if ($folder === null || $folder->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $path_name), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 0) ? 'alpha_match' : 'exact_match';
        if ($path_name === '/') {
            if ($method === 'exact_match') {
                $browse->set_filter('int_id', $folder->getId());
            }
        } else {
            $browse->set_api_filter($method, $path_name);
        }

        $browse->set_filter('catalog', User::get_user_catalogs($user->getId()));
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('browse', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json8_Data::set_offset((int) ($input['offset'] ?? 0));
                Json8_Data::set_limit($input['limit'] ?? 0);
                Json8_Data::set_count(count($results));
                echo Json8_Data::folders($results, $folder, $user, $input['auth']);
                break;
            default:
                Xml8_Data::set_offset((int) ($input['offset'] ?? 0));
                Xml8_Data::set_limit($input['limit'] ?? 0);
                Xml8_Data::set_count(count($results));
                echo Xml8_Data::folders($results, $folder, $user, $input['auth']);
        }

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getFolderRepository(): FolderRepositoryInterface
    {
        global $dic;

        return $dic->get(FolderRepositoryInterface::class);
    }
}
