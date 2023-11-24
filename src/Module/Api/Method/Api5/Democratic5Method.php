<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class Democratic5Method
 */
final class Democratic5Method
{
    public const ACTION = 'democratic';

    /**
     * democratic
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid    = (integer) //optional
     */
    public static function democratic(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('method'), self::ACTION)) {
            return false;
        }
        // Load up democratic information
        $democratic = Democratic::get_current_playlist($user);
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $type      = 'song';
                $object_id = (int)($input['oid'] ?? 0);
                $media     = new Song($object_id);
                if (!$media->id) {
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'oid', $input['api_format']);
                    break;
                }
                $democratic->add_vote(array(
                    array(
                        'object_type' => $type,
                        'object_id' => $media->id
                    )
                ));

                // If everything was ok
                $results = array(
                    'method' => $input['method'],
                    'result' => true
                );
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml5_Data::keyed_array($results);
                }
                break;
            case 'devote':
                $type      = 'song';
                $object_id = (int)($input['oid'] ?? 0);
                $media     = new Song($object_id);
                if (!$media->id) {
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'oid', $input['api_format']);
                    break;
                }

                $object_id = $democratic->get_uid_from_object_id($media->id, $type);
                $democratic->remove_vote($object_id);

                // Everything was ok
                $results = array(
                    'method' => $input['method'],
                    'result' => true
                );
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml5_Data::keyed_array($results);
                }
                break;
            case 'playlist':
                $results = $democratic->get_items();
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);
                switch ($input['api_format']) {
                    case 'json':
                        echo Json5_Data::democratic($results, $user);
                        break;
                    default:
                        echo Xml5_Data::democratic($results, $user);
                }
                break;
            case 'play':
                $url     = $democratic->play_url($user);
                $results = array(
                    'url' => $url
                );
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml5_Data::keyed_array($results);
                }
                break;
            default:
                Api5::error(T_('Invalid Request'), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'method', $input['api_format']);
                break;
        }

        return true;
    }
}
