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
use Session;
use Song;
use User;

/**
 * Class RecordPlayMethod
 * @package Lib\ApiMethods
 */
final class RecordPlayMethod
{
    private const ACTION = 'record_play';

    /**
     * record_play
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache.
     * Require 100 (Admin) permission to change other user's play history
     *
     * @param array $input
     * id     = (integer) $object_id
     * user   = (integer|string) $user_id OR $username //optional
     * client = (string) $agent //optional
     * date   = (integer) UNIXTIME() //optional
     * @return boolean
     */
    public static function record_play(array $input)
    {
        if (!Api::check_parameter($input, array('id', 'user'), self::ACTION)) {
            return false;
        }
        $api_user  = User::get_from_username(Session::username($input['auth']));
        $play_user = (isset($input['user']) && (int) $input['user'] > 0)
            ? new User((int) $input['user'])
            : User::get_from_username((string) $input['user']);
        // If you are setting plays for other users make sure we have an admin
        if ($play_user->id !== $api_user->id && !Api::check_access('interface', 100, $api_user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        ob_end_clean();
        $object_id = (int) $input['id'];
        $valid     = in_array($play_user->id, User::get_valid_users());
        $date      = (is_numeric(scrub_in($input['date']))) ? (int) scrub_in($input['date']) : time(); //optional

        // validate supplied user
        if ($valid === false) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $play_user->id), '4704', self::ACTION, 'user', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        $agent = ($input['client'])
            ? $input['client']
            : 'api';

        $media = new Song($object_id);
        if (!$media->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'id', $input['api_format']);

            return false;
        }
        debug_event(self::class, 'record_play: ' . $media->id . ' for ' . $play_user->username . ' using ' . $agent . ' ' . (string) time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($play_user->id, $agent, array(), $date)) {
            // scrobble plugins
            User::save_mediaplay($play_user, $media);
        }

        Api::message('successfully recorded play: ' . $media->id . ' for: ' . $play_user->username, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    }
}
