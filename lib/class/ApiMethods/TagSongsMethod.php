<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Lib\ApiMethods;

use Api;
use Session;

/**
 * Class TagSongsMethod
 * @package Lib\ApiMethods
 */
final class TagSongsMethod
{
    private const ACTION = 'tag_songs';

    /**
     * tag_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this genre
     *
     * @param array $input
     */
    public static function tag_songs(array $input)
    {
        Api::error(T_('Depreciated'), '4706', self::ACTION, 'removed', $input['api_format']);
        Session::extend($input['auth']);
    }
}
