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

namespace Ampache\Module\Api\Method;

use Ampache\Model\Tag;
use Ampache\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Session;

final class TagSongsMethod
{
    /**
     * tag_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this genre
     *
     * @param array $input
     * filter = (string) UID of Genre
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function tag_songs($input)
    {
        if (!Api::check_parameter($input, array('filter'), 'tag_songs')) {
            return false;
        }
        $songs = Tag::get_tag_objects('song', $input['filter']);
        $user  = User::get_from_username(Session::username($input['auth']));

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        if (!empty($songs)) {
            switch ($input['api_format']) {
                case 'json':
                    JSON_Data::set_offset($input['offset']);
                    JSON_Data::set_limit($input['limit']);
                    echo JSON_Data::songs($songs, $user->id);
                    break;
                default:
                    XML_Data::set_offset($input['offset']);
                    XML_Data::set_limit($input['limit']);
                    echo XML_Data::songs($songs, $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    }
}
