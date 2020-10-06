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

use Access;
use Api;
use Session;
use User;

final class PlaylistDeleteMethod
{
    /**
     * playlist_delete
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * @return boolean
     */
    public static function playlist_delete($input)
    {
        if (!Api::check_parameter($input, array('filter'), 'playlist_delete')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new \Playlist($input['filter']);
        if (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id)) {
            Api::message('error', T_('Access denied to this playlist'), '412', $input['api_format']);
        } else {
            $playlist->delete();
            Api::message('success', 'playlist deleted', null, $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
