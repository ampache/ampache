<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

/**
 * Class PlayerMethod
 * @package Lib\ApiMethods
 */
final class PlayerMethod
{
    public const ACTION = 'player';

    /**
     * player
     * MINIMUM_API_VERSION=6.4.0
     *
     * Inform the server about the state of your client. (Song you are playing, Play/Pause state, etc.)
     * Return the `now_playing` state when completed
     *
     * filter  = (integer) $object_id
     * type    = (string)  $object_type ('song', 'podcast_episode', 'video'), DEFAULT 'song'//optional
     * state   = (string)  'play', 'stop', DEFAULT 'play' //optional
     * time    = (integer) current song time in whole seconds, DEFAULT 0 //optional
     * client  = (string)  $agent, DEFAULT 'api' //optional
     */
    public static function player(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        ob_end_clean();
        $object_id = (int)$input['filter'];
        $type      = $input['type'] ?? 'song';

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'podcast_episode', 'video'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $state = $input['state'] ?? 'play';
        if (!in_array(strtolower($state), ['play', 'stop'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $state), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'state', $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if ($className === $type || !$object_id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        /** @var Song|Podcast_Episode|Video $media */
        $media = new $className($object_id);
        if ($media->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

            return false;
        }

        $time     = time();
        $position = (array_key_exists('time', $input) && is_numeric(scrub_in((string) $input['time'])))
            ? (int) scrub_in((string) $input['time'])
            : 0;
        // validate client string or fall back to 'api'
        $agent = scrub_in((string)($input['client'] ?? 'api'));

        if ($state === 'play') {
            // make sure the now_playing state is set
            Stream::garbage_collection();
            Stream::insert_now_playing((int)$media->id, $user->getId(), ((int)($media->time) - $position), (string)$user->username, $type, ($time - $position));

            // internal scrobbling (user_activity and object_count tables)
            if (
                $media instanceof Song &&
                $media->set_played($user->id, $agent, [], ($time - $position))
            ) {
                // scrobble plugins
                User::save_mediaplay($user, $media);
            }
        } else {
            // A stop/paused state isn't playing. Remove it.
            Stream::delete_now_playing((string)$user->username, (int)$media->id, $type, $user->getId());
        }

        // return the now playing state for that user
        $results = Stream::get_now_playing($user->getId());
        if (empty($results)) {
            Api::empty('now_playing', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::now_playing($results);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::now_playing($results);
        }

        return true;
    }
}
