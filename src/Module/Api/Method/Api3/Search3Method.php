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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Repository\Model\User;

/**
 * Class Search3Method
 */
final class Search3Method
{
    public const ACTION = 'search';

    /**
     * search
     * MINIMUM_API_VERSION=380001
     *
     * This returns the tags (Genres) based on the specified filter
     *
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     exact?: int,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     */
    public static function search(array $input, User $user): void
    {
        AdvancedSearch3Method::advanced_search($input, $user);
    }
}
