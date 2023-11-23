<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\User;

/**
 * Class TagSongs5Method
 */
final class TagSongs5Method
{
    public const ACTION = 'tag_songs';

    /**
     * tag_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this genre
     */
    public static function tag_songs(array $input, User $user): void
    {
        unset($user);
        Api5::error(T_('Depreciated'), ErrorCodeEnum::DEPRECATED, self::ACTION, 'removed', $input['api_format']);
    }
}
