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
use Song;
use User;

/**
 * Class SongDeleteMethod
 * @package Lib\ApiMethods
 */
final class SongDeleteMethod
{
    private const ACTION = 'song_delete';

    /**
     * song_delete
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete an existing song.
     *
     * @param array $input
     * filter = (string) UID of song to delete
     * @return boolean
     */
    public static function song_delete(array $input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $song      = new Song($object_id);
        if (!$song->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        if (!Catalog::can_remove($song, $user->id)) {
            Api::error(T_('Require: 75'), '4742', self::ACTION, 'account', $input['api_format']);

            return false;
        }
        if ($song->remove()) {
            Api::message('song ' . $object_id . ' deleted', $input['api_format']);
        } else {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $object_id), '4710', self::ACTION, 'system', $input['api_format']);
        }
        Catalog::count_table('song');
        Session::extend($input['auth']);

        return true;
    }
}
