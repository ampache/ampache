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
use Democratic;
use JSON_Data;
use Session;
use Song;
use User;
use XML_Data;

/**
 * Class DemocraticMethod
 * @package Lib\ApiMethods
 */
final class DemocraticMethod
{
    private const ACTION = 'democratic';

    /**
     * democratic
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * @param array $input
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid    = (integer) //optional
     * @return boolean
     */
    public static function democratic(array $input)
    {
        if (!Api::check_parameter($input, array('method'), self::ACTION)) {
            return false;
        }
        // Load up democratic information
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $type      = 'song';
                $object_id = (int) $input['oid'];
                $media     = new Song($object_id);
                if (!$media->id) {
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'oid', $input['api_format']);
                    break;
                }
                $democratic->add_vote(array(
                    array(
                        'object_type' => $type,
                        'object_id' => $media->id
                    )
                ));

                // If everything was ok
                $xml_array = array('method' => $input['method'], 'result' => true);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($xml_array, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($xml_array);
                }
                break;
            case 'devote':
                $type      = 'song';
                $object_id = (int) $input['oid'];
                $media     = new Song($object_id);
                if (!$media->id) {
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'oid', $input['api_format']);
                    break;
                }

                $object_id = $democratic->get_uid_from_object_id($media->id, $type);
                $democratic->remove_vote($object_id);

                // Everything was ok
                $xml_array = array('method' => $input['method'], 'result' => true);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($xml_array, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($xml_array);
                }
                break;
            case 'playlist':
                $objects = $democratic->get_items();
                $user    = User::get_from_username(Session::username($input['auth']));
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::democratic($objects, $user->id);
                        break;
                    default:
                        echo XML_Data::democratic($objects, $user->id);
                }
                break;
            case 'play':
                $url       = $democratic->play_url();
                $xml_array = array('url' => $url);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($xml_array, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($xml_array);
                }
                break;
            default:
                Api::error(T_('Invalid request'), '4710', self::ACTION, 'method', $input['api_format']);
                break;
        }
        Session::extend($input['auth']);

        return true;
    }
}
