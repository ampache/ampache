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
use Catalog;
use Session;
use User;

final class SongDeleteMethod
{
    /**
     * song_delete
     *
     * MINIMUM_API_VERSION=430000
     *
     * Delete an existing song.
     *
     * @param array $input
     * filter = (string) UID of song to delete
     * @return boolean
     */
    public static function song_delete($input)
    {
        if (!Api::check_parameter($input, array('filter'), 'song_delete')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $song      = new \Song($object_id);
        $user      = User::get_from_username(Session::username($input['auth']));
        if (!Catalog::can_remove($song, $user->id)) {
            Api::message('error', T_('Access Denied: Unable to delete song'), '412', $input['api_format']);

            return false;
        }
        if ($song->id) {
            if ($song->remove()) {
                Api::message('success', 'song ' . $object_id . ' deleted', null, $input['api_format']);
            } else {
                Api::message('error', 'song ' . $object_id . ' was not deleted', '400', $input['api_format']);
            }
        } else {
            Api::message('error', 'song ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
