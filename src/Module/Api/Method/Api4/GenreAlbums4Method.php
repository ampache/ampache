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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;

/**
 * Class GenreAlbums4Method
 */
final class GenreAlbums4Method
{
    public const ACTION = 'genre_albums';

    /**
     * genre_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums associated with the genre in question
     *
     * filter = (string) UID of Genre
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function genre_albums(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $results = Tag::get_tag_objects('album', $input['filter']);
        if (!empty($results)) {
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    Json4_Data::set_offset($input['offset'] ?? 0);
                    Json4_Data::set_limit($input['limit'] ?? 0);
                    echo Json4_Data::albums($results, array(), $user);
                    break;
                default:
                    Xml4_Data::set_offset($input['offset'] ?? 0);
                    Xml4_Data::set_limit($input['limit'] ?? 0);
                    echo Xml4_Data::albums($results, array(), $user);
            }
        }

        return true;
    } // genre_albums
}
