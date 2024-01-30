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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class PlaylistAddMethod
 * @package Lib\ApiMethods
 */
final class PlaylistAddMethod
{
    public const ACTION = 'playlist_add';

    /**
     * playlist_add_song
     * MINIMUM_API_VERSION=6.3.0
     *
     * This adds a song to a playlist, allowing different song parent types
     *
     * filter = (string) UID of playlist
     * id     = (string) $object_id
     * type   = (string) 'song', 'album', 'artist', 'playlist'
     */
    public static function playlist_add(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter', 'id', 'type'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $playlist    = new Playlist($input['filter']);
        $object_id   = $input['id'];
        $object_type = $input['type'];

        // confirm the correct data
        if (!$playlist->has_access($user->id) && $user->access !== 100) {
            Api::error(T_('Require: 100'), ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);

            return false;
        }

        if (!in_array(strtolower($object_type), array('song', 'album', 'artist', 'playlist'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        if ($object_type === 'playlist' && ((int)$object_id) === 0) {
            $object_id   = str_replace('smart_', '', (string) $object_id);
            $object_type = 'search';
        }

        $className = ObjectTypeToClassNameMapper::map($object_type);
        /** @var Artist|Album|Song|Playlist|Search $item */
        $item = new $className((int)$object_id);
        if ($item->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

            return false;
        }

        $results = array();
        switch ($object_type) {
            case 'song':
                /** @var Song $item */
                $results = $item->getId();
                break;
            case 'album':
            case 'artist':
            case 'playlist':
            case 'search':
                /** @var Artist|Album|Playlist|Search $item */
                $results = $item->get_songs();
                break;
        }
        if (empty($results)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        if ($playlist->add_songs($results)) {
            Api::message('songs added to playlist', $input['api_format']);

            return true;
        }
        Api::message('nothing was added to the playlist', $input['api_format']);

        return false;
    }
}
