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
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\Model\User;

/**
 * Class GetSimilar4Method
 */
final class GetSimilar4Method
{
    public const ACTION = 'get_similar';

    /**
     * get_similar
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * @param array $input
     * type   = (string) 'song'|'artist'
     * filter = (integer) artist id or song id
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function get_similar(array $input): bool
    {
        if (!Api4::check_parameter($input, array('type', 'filter'), 'get_similar')) {
            return false;
        }
        $type   = (string) $input['type'];
        $filter = (int) $input['filter'];
        // confirm the correct data
        if (!in_array($type, array('song', 'artist'))) {
            Api4::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }

        $user    = User::get_from_username(Session::username($input['auth']));
        $objects = array();
        $similar = array();
        switch ($type) {
            case 'artist':
                $similar = Recommendation::get_artists_like($filter);
                break;
            case 'song':
                $similar = Recommendation::get_songs_like($filter);
        }
        foreach ($similar as $child) {
            $objects[] = $child['id'];
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::indexes($objects, $type, $user->id);
                break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::indexes($objects, $type, $user->id);
        }

        return true;
    } // get_similar
}
