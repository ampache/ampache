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
use JSON_Data;
use Session;
use User;
use XML_Data;

final class SongMethod
{
    /**
     * song
     * MINIMUM_API_VERSION=380001
     *
     * return a single song
     *
     * @param array $input
     * filter = (string) UID of song
     * @return boolean
     */
    public static function song($input)
    {
        if (!Api::check_parameter($input, array('filter'), 'song')) {
            return false;
        }
        $song_id = scrub_in($input['filter']);
        $user    = User::get_from_username(Session::username($input['auth']));

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::songs(array((int) $song_id), $user->id);
                break;
            default:
                echo XML_Data::songs(array((int) $song_id), $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
