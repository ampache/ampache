<?php
/*
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\User;

/**
 * Class LocalplayMethod
 * @package Lib\ApiMethods
 */
final class LocalplayMethod
{
    public const ACTION = 'localplay';

    /**
     * localplay
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This is for controlling Localplay
     *
     * @param array $input
     * @param User $user
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status'
     * oid     = (integer) object_id //optional
     * type    = (string) 'Song', 'Video', 'Podcast_Episode', 'Broadcast', 'Democratic', 'Live_Stream' //optional
     * clear   = (integer) 0,1 Clear the current playlist before adding //optional
     * track   = (integer) used in conjunction with skip to skip to the track id (use localplay_songs to get your track list) //optional
     * id
     * @return boolean
     */
    public static function localplay(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('command'), self::ACTION)) {
            return false;
        }
        // localplay is actually meant to be behind permissions
        $level = AmpConfig::get('localplay_level', 100);
        if (!Api::check_access('localplay', $level, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        // Load their Localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        if (empty($localplay->type) || !$localplay->connect()) {
            Api::error(T_('Unable to connect to localplay controller'), '4710', self::ACTION, 'account', $input['api_format']);

            return false;
        }

        $result = false;
        $status = false;
        switch ($input['command']) {
            case 'add':
                // for add commands get the object details
                $object_id = (int)($input['oid'] ?? 0);
                $type      = $input['type'] ? (string) $input['type'] : 'Song';
                if (!AmpConfig::get('allow_video') && $type == 'Video') {
                    Api::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

                    return false;
                }
                $clear       = (int)($input['clear'] ?? 0);
                // clear before the add
                if ($clear == 1) {
                    $localplay->delete_all();
                }
                $media = array(
                    'object_type' => $type,
                    'object_id' => $object_id,
                );
                $playlist = new Stream_Playlist();
                $playlist->add(array($media), '&client=' . $localplay->type);
                foreach ($playlist->urls as $streams) {
                    $result = $localplay->add_url($streams);
                }
                break;
            case 'skip':
                // localplay_songs 'track' starts at 1 but localplay starts at 0 behind the scenes
                $result = $localplay->skip((int)$input['track'] - 1);
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
                $result = $localplay->delete_all();
                break;
            case 'status':
                $status = $localplay->status();
                if ($input['api_format'] == 'json') {
                    $status['repeat'] = (bool)$status['repeat'];
                    $status['random'] = (bool)$status['random'];
                }
                break;
            default:
                // They are doing it wrong
                Api::error(T_('Bad Request'), '4710', self::ACTION, 'command', $input['api_format']);

                return false;
        } // end switch on command
        $results = (!empty($status))
            ? array('localplay' => array('command' => array($input['command'] => $status)))
            : array('localplay' => array('command' => array($input['command'] => make_bool($result))));
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::keyed_array($results);
        }

        return true;
    }
}
