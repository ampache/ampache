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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

/**
 * Class BookmarkCreateMethod
 * @package Lib\ApiMethods
 */
final class BookmarkCreateMethod
{
    private const ACTION = 'bookmark_create';

    /**
     * bookmark_create
     * MINIMUM_API_VERSION=5.0.0
     *
     * Create a placeholder for the current media that you can return to later.
     *
     * @param array $input
     * filter   = (string) object_id
     * type     = (string) object_type ('song', 'video', 'podcast_episode')
     * position = (integer) current track time in seconds
     * client   = (string) Agent string Default: 'AmpacheAPI' // optional
     * date     = (integer) UNIXTIME() //optional
     * @return boolean
     */
    public static function bookmark_create(array $input)
    {
        if (!Api::check_parameter($input, array('filter', 'position'), self::ACTION)) {
            return false;
        }
        $user      = User::get_from_username(Session::username($input['auth']));
        $object_id = $input['filter'];
        $type      = $input['type'];
        $position  = $input['position'];
        $comment   = (isset($input['client'])) ? $input['client'] : 'AmpacheAPI';
        $time      = (isset($input['date'])) ? (int) $input['date'] : time();
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'video', 'podcast_episode'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        if ($className === $type || !$object_id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

            return false;
        }

        $item = new $className($object_id);
        if (!$item->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $object = array(
            'object_id' => $object_id,
            'object_type' => $type,
            'comment' => $comment,
            'position' => $position
        );

        // create it then retrieve it
        Bookmark::create($object, $user->getId(), $time);
        $bookmark = Bookmark::get_bookmark($object);
        if (empty($bookmark)) {
            Api::empty('bookmark', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::bookmarks($bookmark);
                break;
            default:
                echo Xml_Data::bookmarks($bookmark);
        }
        Session::extend($input['auth']);

        return true;
    }
}
