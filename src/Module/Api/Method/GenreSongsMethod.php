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

use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Session;

/**
 * Class GenreSongsMethod
 * @package Lib\ApiMethods
 */
final class GenreSongsMethod
{
    const ACTION = 'genre_songs';

    /**
     * genre_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this genre
     *
     * @param array $input
     * filter = (string) UID of Genre //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function genre_songs(array $input)
    {
        $songs = Tag::get_tag_objects('song', $input['filter']);
        if (empty($songs)) {
            Api::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        $user = User::get_from_username(Session::username($input['auth']));
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset']);
                Json_Data::set_limit($input['limit']);
                echo Json_Data::songs($songs, $user->id);
                break;
            default:
                Xml_Data::set_offset($input['offset']);
                Xml_Data::set_limit($input['limit']);
                echo Xml_Data::songs($songs, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
