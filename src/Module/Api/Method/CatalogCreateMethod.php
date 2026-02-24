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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\User;

/**
 * Class CatalogAddMethod
 * @package Lib\ApiMethods
 */
final class CatalogCreateMethod
{
    public const ACTION = 'catalog_create';

    /**
     * catalog_add
     * MINIMUM_API_VERSION=6.0.0
     *
     * Create a new catalog
     *
     * name           = (string) catalog_name
     * path           = (string) URL or folder path for your catalog
     * type           = (string) catalog_type default: local ('local', 'beets', 'remote', 'subsonic', 'seafile', 'beetsremote') //optional
     * media_type     = (string) Default: 'music' ('music', 'podcast', 'video') //optional
     * file_pattern   = (string) Pattern used identify tags from the file name. Default '%T - %t' //optional
     * folder_pattern = (string) Pattern used identify tags from the folder name. Default '%a/%A' //optional
     * username       = (string) login to remote catalog ('remote', 'subsonic', 'seafile') //optional
     * password       = (string) password to remote catalog ('remote', 'subsonic', 'seafile', 'beetsremote') //optional
     *
     * @param array{
     *     name: string,
     *     path: string,
     *     type?: string,
     *     beetsdb?: string,
     *     media_type?: string,
     *     file_pattern?: string,
     *     folder_pattern?: string,
     *     username?: string,
     *     password?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function catalog_add(array $input, User $user): bool
    {
        return CatalogAddMethod::catalog_add($input, $user);
    }
}
