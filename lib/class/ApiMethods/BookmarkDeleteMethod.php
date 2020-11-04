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
use Core;
use Session;
use bookmark;
use User;

/**
 * Class BookmarkDeleteMethod
 * @package Lib\ApiMethods
 */
final class BookmarkDeleteMethod
{
    private const ACTION = 'bookmark_delete';

    /**
     * bookmark_delete
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete an existing bookmark. (if it exists)
     *
     * @param array $input
     * filter = (string) object_id to delete
     * type   = (string) object_type  ('song', 'video', 'podcast_episode')
     * client = (string) Agent string Default: 'AmpacheAPI' // optional
     * @return boolean
     */
    public static function bookmark_delete(array $input)
    {
        if (!Api::check_parameter($input, array('filter','type'), self::ACTION)) {
            return false;
        }
        $user      = User::get_from_username(Session::username($input['auth']));
        $object_id = $input['filter'];
        $type      = $input['type'];
        $comment   = (isset($input['client'])) ? (string) $input['client'] : 'AmpacheAPI';
        // confirm the correct data
        if (!in_array($type, array('song', 'video', 'podcast_episode'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

            return false;
        }
        if (!Core::is_library_item($type) || !$object_id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

            return false;
        }

        $item = new $type($object_id);
        if (!$item->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $object = array(
            'user' => $user->id,
            'object_id' => $object_id,
            'object_type' => $type,
            'comment' => $comment
        );

        $find = Bookmark::get_bookmark($object);
        if (empty($find)) {
            Api::empty('bookmark', $input['api_format']);

            return false;
        }

        $bookmark = Bookmark::delete($object);
        if (!$bookmark) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Api::message('Deleted Bookmark: ' . $object_id, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // bookmark_delete
}
