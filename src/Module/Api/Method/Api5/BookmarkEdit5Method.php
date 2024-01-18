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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Video;

/**
 * Class BookmarkEdit5Method
 */
final class BookmarkEdit5Method
{
    public const ACTION = 'bookmark_edit';

    /**
     * bookmark_edit
     * MINIMUM_API_VERSION=5.0.0
     *
     * Edit a placeholder for the current media that you can return to later.
     *
     * filter   = (string) object_id
     * type     = (string) object_type ('song', 'video', 'podcast_episode')
     * position = (integer) current track time in seconds
     * client   = (string) Agent string Default: 'AmpacheAPI' //optional
     * date     = (integer) UNIXTIME() //optional
     */
    public static function bookmark_edit(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('filter', 'type', 'position'), self::ACTION)) {
            return false;
        }
        $object_id = $input['filter'];
        $type      = $input['type'];
        $position  = (int)filter_var($input['position'], FILTER_SANITIZE_NUMBER_INT);
        $comment   = (isset($input['client'])) ? scrub_in((string) $input['client']) : 'AmpacheAPI';
        $time      = (isset($input['date'])) ? (int) $input['date'] : time();
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api5::error(T_('Enable: video'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), array('song', 'video', 'podcast_episode'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $className = ObjectTypeToClassNameMapper::map($type);

        if ($className === $type || !$object_id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $item = new $className($object_id);
        /** @var Song|Podcast_Episode|Video $item */
        if ($item->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $object = array(
            'user' => $user->id,
            'object_id' => $object_id,
            'object_type' => $type,
            'comment' => $comment,
            'position' => $position,
        );

        // check for the bookmark first
        $results = Bookmark::getBookmarks($object);
        if (empty($results)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'bookmark', $input['api_format']);

            return false;
        }
        // edit it
        Bookmark::edit($results[0], $object, $time);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::bookmarks($results);
                break;
            default:
                echo Xml5_Data::bookmarks($results);
        }

        return true;
    }
}
