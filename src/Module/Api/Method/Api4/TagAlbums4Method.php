<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;

/**
 * Class TagAlbums4Method
 */
final class TagAlbums4Method
{
    public const ACTION = 'tag_albums';

    /**
     * tag_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums associated with the tag in question
     *
     * @param array $input
     * filter = (string) UID of Tag
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function tag_albums(array $input): bool
    {
        if (!Api4::check_parameter($input, array('filter'), 'tag_albums')) {
            return false;
        }
        $albums = Tag::get_tag_objects('album', $input['filter']);
        if (!empty($albums)) {
            $user = User::get_from_username(Session::username($input['auth']));

            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    Json4_Data::set_offset($input['offset'] ?? 0);
                    Json4_Data::set_limit($input['limit'] ?? 0);
                    echo Json4_Data::albums($albums, array(), $user->id);
                break;
                default:
                    Xml4_Data::set_offset($input['offset'] ?? 0);
                    Xml4_Data::set_limit($input['limit'] ?? 0);
                    echo Xml4_Data::albums($albums, array(), $user->id);
            }
        }

        return true;
    } // tag_albums
}
