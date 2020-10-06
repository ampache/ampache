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
use JSON_Data;
use XML_Data;

final class DemocraticMethod
{
    /**
     * democratic
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * @param array $input
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid    = (integer) //optional
     */
    public static function democratic($input)
    {
        // Load up democratic information
        $democratic = \Democratic::get_current_playlist();
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $type  = 'song';
                $media = new \Song($input['oid']);
                if (!$media->id) {
                    Api::message('error', T_('Media object invalid or not specified'), '404', $input['api_format']);
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
                $type  = 'song';
                $media = new \Song($input['oid']);
                if (!$media->id) {
                    Api::message('error', T_('Media object invalid or not specified'), '404', $input['api_format']);
                }

                $uid = $democratic->get_uid_from_object_id($media->id, $type);
                $democratic->remove_vote($uid);

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
                $user    = \User::get_from_username(\Session::username($input['auth']));
                \Song::build_cache($democratic->object_ids);
                \Democratic::build_vote_cache($democratic->vote_ids);
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
                Api::message('error', T_('Invalid request'), '400', $input['api_format']);
                break;
        }
        \Session::extend($input['auth']);
    }
}
