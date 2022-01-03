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

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Session;

/**
 * Class Playlist4Method
 */
final class Playlist4Method
{
    public const ACTION = 'playlist';

    /**
     * playlist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * @return boolean
     */
    public static function playlist(array $input): bool
    {
        if (!Api4::check_parameter($input, array('filter'), 'playlist')) {
            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $list_id = scrub_in($input['filter']);

        if (str_replace('smart_', '', $list_id) === $list_id) {
            // Playlists
            $playlist = new Playlist((int) $list_id);
        } else {
            // Smartlists
            $playlist = new Search((int) str_replace('smart_', '', $list_id), 'song', $user);
        }
        if (!$playlist->type == 'public' && (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            Api4::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::playlists(array($list_id), $user->id);
            break;
            default:
                echo Xml4_Data::playlists(array($list_id), $user->id);
        }

        return true;
    } // playlist
}
