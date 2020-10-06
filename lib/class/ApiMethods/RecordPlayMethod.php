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

final class RecordPlayMethod
{
    /**
     * record_play
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache
     *
     * @param array $input
     * id     = (integer) $object_id
     * user   = (integer) $user_id
     * client = (string) $agent //optional
     * @return boolean
     */
    public static function record_play($input)
    {
        if (!Api::check_parameter($input, array('id', 'user'), 'record_play')) {
            return false;
        }
        ob_end_clean();
        $object_id = $input['id'];
        $user_id   = (int) $input['user'];
        $user      = new \User($user_id);
        $valid     = in_array($user->id, \User::get_valid_users());

        // validate supplied user
        if ($valid === false) {
            Api::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        $agent = ($input['client'])
            ? $input['client']
            : 'api';

        $item = new \Song($object_id);
        if (!$item->id) {
            Api::message('error', T_('Library item not found'), '404', $input['api_format']);

            return false;
        }
        debug_event('api.class', 'record_play: ' . $item->id . ' for ' . $user->username . ' using ' . $agent . ' ' . (string) time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        $item->set_played($user_id, $agent, array(), time());

        // scrobble plugins
        \User::save_mediaplay($user, $item);

        Api::message('success', 'successfully recorded play: ' . $item->id, null, $input['api_format']);
        \Session::extend($input['auth']);

        return true;
    }
}
