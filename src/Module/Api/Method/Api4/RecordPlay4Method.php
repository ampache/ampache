<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class RecordPlay4Method
 */
final class RecordPlay4Method
{
    public const ACTION = 'record_play';

    /**
     * record_play
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache.
     * Require 100 (Admin) permission to change other user's play history
     *
     * id     = (integer) $object_id
     * user   = (integer|string) $user_id OR $username //optional
     * client = (string) $agent //optional
     * date   = (integer) UNIXTIME() //optional
     */
    public static function record_play(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('id'), self::ACTION)) {
            return false;
        }
        $play_user = $user;
        if (isset($input['user'])) {
            $play_user = ((int)$input['user'] > 0)
                ? new User((int)$input['user'])
                : User::get_from_username((string)$input['user']);
        }
        // validate supplied user
        $valid = ($play_user instanceof User && in_array($play_user->id, static::getUserRepository()->getValid()));
        if ($valid === false) {
            Api4::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }
        // If you are setting plays for other users make sure we have an admin
        if ($play_user->id !== $user->id && !Api4::check_access('interface', 100, $user->id, 'record_play', $input['api_format'])) {
            return false;
        }
        ob_end_clean();
        $object_id = (int) $input['id'];
        $date      = (array_key_exists('date', $input) && is_numeric(scrub_in((string) $input['date']))) ? (int) scrub_in((string) $input['date']) : time(); //optional

        // validate client string or fall back to 'api'
        $agent = scrub_in((string)($input['client'] ?? 'api'));

        $media = new Song($object_id);
        if ($media->isNew()) {
            Api4::message('error', T_('Library item not found'), '404', $input['api_format']);

            return false;
        }
        debug_event(self::class, 'record_play: ' . $media->id . ' for ' . $play_user->username . ' using ' . $agent . ' ' . (string) time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($play_user->id, $agent, array(), $date)) {
            // scrobble plugins
            User::save_mediaplay($play_user, $media);
        }

        Api4::message('success', 'successfully recorded play: ' . $media->id . ' for: ' . $play_user->username, null, $input['api_format']);

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
