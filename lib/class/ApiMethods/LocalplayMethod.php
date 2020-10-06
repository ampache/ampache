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

use AmpConfig;
use Api;
use XML_Data;

final class LocalplayMethod
{
    /**
     * localplay
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=430000
     *
     * This is for controlling Localplay
     *
     * @param array $input
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status'
     * oid     = (integer) object_id //optional
     * type    = (string) 'Song', 'Video', 'Podcast_Episode', 'Channel', 'Broadcast', 'Democratic', 'Live_Stream' //optional
     * clear   = (integer) 0,1 Clear the current playlist before adding //optional
     * @return bool
     */
    public static function localplay($input)
    {
        if (!Api::check_parameter($input, array('command'), 'localplay')) {
            return false;
        }
        // Load their Localplay instance
        $localplay = new \Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        $result = false;
        $status = false;
        switch ($input['command']) {
            case 'add':
                // for add commands get the object details
                $oid   = (int) $input['oid'];
                $type  = $input['type'] ? (string) $input['type'] : 'Song';
                $clear = (int) $input['clear'];
                // clear before the add
                if ($clear == 1) {
                    $localplay->delete_all();
                }
                $media = array(
                    'object_type' => $type,
                    'object_id' => $oid,
                );
                $playlist = new \Stream_Playlist();
                $playlist->add(array($media));
                foreach ($playlist->urls as $streams) {
                    $result = $localplay->add_url($streams);
                }
                break;
            case 'next':
                $result = $localplay->next();
                break;
            case 'prev':
                $result = $localplay->prev();
                break;
            case 'stop':
                $result = $localplay->stop();
                break;
            case 'play':
                $result = $localplay->play();
                break;
            case 'pause':
                $result = $localplay->pause();
                break;
            case 'volume_up':
                $result = $localplay->volume_up();
                break;
            case 'volume_down':
                $result = $localplay->volume_down();
                break;
            case 'volume_mute':
                $result = $localplay->volume_mute();
                break;
            case 'delete_all':
                $result = $localplay->volume_down();
                break;
            case 'skip':
                $result = $localplay->volume_mute();
                break;
            case 'status':
                $status = $localplay->status();
                break;
            default:
                // They are doing it wrong
                Api::message('error', T_('Invalid request'), '400', $input['api_format']);

                return false;
        } // end switch on command
        $output_array = (!empty($status))
            ? array('localplay' => array('command' => array($input['command'] => $status)))
            : array('localplay' => array('command' => array($input['command'] => make_bool($result))));
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($output_array, JSON_PRETTY_PRINT);
                break;
            default:
                echo XML_Data::keyed_array($output_array);
        }
        \Session::extend($input['auth']);

        return true;
    }
}
