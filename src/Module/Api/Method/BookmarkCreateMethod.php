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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Video;

/**
 * Class BookmarkCreateMethod
 * @package Lib\ApiMethods
 */
final class BookmarkCreateMethod
{
    public const ACTION = 'bookmark_create';

    /**
     * bookmark_create
     * MINIMUM_API_VERSION=5.0.0
     *
     * Create a placeholder for the current media that you can return to later.
     *
     * filter   = (string) object_id
     * type     = (string) object_type ('song', 'video', 'podcast_episode')
     * position = (integer) current track time in seconds
     * client   = (string) Agent string //optional
     * date     = (integer) UNIXTIME() //optional
     * include  = (integer) 0,1, if true include the object in the bookmark //optional
     */
    public static function bookmark_create(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter', 'type', 'position'], self::ACTION)) {
            return false;
        }
        $object_id = $input['filter'];
        $type      = $input['type'];
        $position  = $input['position'];
        $comment   = (isset($input['client'])) ? scrub_in((string) $input['client']) : null;
        $time      = (isset($input['date'])) ? (int) $input['date'] : time();
        $include   = (bool)($input['include'] ?? false);
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'video', 'podcast_episode'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        if ($className === $type || !$object_id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        /** @var Song|Podcast_Episode|Video $item */
        $item = new $className($object_id);
        if ($item->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $object = [
            'user' => $user->getId(),
            'object_id' => $object_id,
            'object_type' => $type,
            'comment' => $comment,
            'position' => $position
        ];

        // create it then retrieve it
        Bookmark::create($object, $user->getId(), $time);
        $results = Bookmark::getBookmarks($object);
        if (empty($results)) {
            Api::empty(null, $input['api_format']);

            return false;
        }
        // only return the most recent bookmark
        $results = array_slice($results, 0, 1);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::bookmarks($results, $include, false);
                break;
            default:
                echo Xml_Data::bookmarks($results, $include);
        }

        return true;
    }
}
