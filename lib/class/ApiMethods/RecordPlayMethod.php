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
     * This allows other sources to record play history to Ampache
     *
     * @param array $input
     * id     = (integer) $object_id
     * user   = (integer) $user_id
     * client = (string) $agent //optional
     * date   = (integer) UNIXTIME() //optional
     * @return boolean
     */
    public static function record_play(array $input)
    {
        if (!Api::check_parameter($input, array('id', 'user'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $object_id = (int) $input['id'];
        $user_id   = (int) $input['user'];
        $user      = new User($user_id);
        $valid     = in_array($user->id, User::get_valid_users());
        $date      = (is_numeric(scrub_in($input['date']))) ? (int) scrub_in($input['date']) : time(); //optional

        // validate supplied user
        if ($valid === false) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $user_id), '4704', self::ACTION, 'user', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        $agent = ($input['client'])
            ? $input['client']
            : 'api';

        $item = new Song($object_id);
        if (!$item->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'id', $input['api_format']);

            return false;
        }
        debug_event(self::class, 'record_play: ' . $item->id . ' for ' . $user->username . ' using ' . $agent . ' ' . (string) time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        $item->set_played($user_id, $agent, array(), $date);

        // scrobble plugins
        User::save_mediaplay($user, $item);

        Api::message('successfully recorded play: ' . $item->id, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    }
}
