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

use Ampache\Module\Api\Api4;
use Ampache\Repository\Model\User;

/**
 * Class TagArtists4Method
 */
final class TagArtists4Method
{
    public const ACTION = 'tag_artists';

    /**
     * tag_artists
     * MINIMUM_API_VERSION=380001
     *
     * This returns the artists associated with the tag in question as defined by the UID
     *
     * filter = (string) UID of Album
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter?: string,
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
    public static function tag_artists(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        return GenreArtists4Method::genre_artists($input, $user);
    }
}
