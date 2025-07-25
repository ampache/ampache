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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;

/**
 * Class Localplay4Method
 */
final class Localplay4Method
{
    public const ACTION = 'localplay';

    /**
     * localplay
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling Localplay
     *
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status'
     * oid     = (integer) object_id //optional
     * type    = (string) 'Song', 'Video', 'Podcast_Episode', 'Broadcast', 'Democratic', 'Live_Stream' //optional
     * clear   = (integer) 0,1 Clear the current playlist before adding //optional
     *
     * @param array{
     *     command: string,
     *     oid?: string,
     *     type?: string,
     *     clear?: int,
     *     track?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function localplay(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['command'], self::ACTION)) {
            return false;
        }
        unset($user);
        // Load their Localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller', ''));
        if (empty($localplay->type) || !$localplay->connect()) {
            Api4::message('error', T_('Error Unable to connect to localplay controller'), '405', $input['api_format']);

            return false;
        }

        $result  = false;
        $status  = null;
        $command = strtolower($input['command']);
        switch ($command) {
            case 'add':
                // for add commands get the object details
                $object_id = (int)($input['oid'] ?? 0);
                $type      = LibraryItemEnum::tryFrom((string) strtolower($input['type'] ?? '')) ?? LibraryItemEnum::SONG;

                if (!AmpConfig::get('allow_video') && $type === LibraryItemEnum::VIDEO) {
                    Api4::message('error', T_('Access Denied: allow_video is not enabled.'), '400', $input['api_format']);

                    return false;
                }

                $clear = (int)($input['clear'] ?? 0);
                if ($localplay->type === 'mpd') {
                    $localplay->set_block_clear(make_bool((string)$clear));
                }

                // clear before the add
                if ($clear === 1) {
                    $localplay->delete_all();
                }
                $media = [
                    'object_type' => $type,
                    'object_id' => $object_id,
                ];
                $playlist = new Stream_Playlist();
                $playlist->add([$media]);
                foreach ($playlist->urls as $streams) {
                    $result = $localplay->add_url($streams);
                }
                break;
            case 'next':
            case 'skip':
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
                break;
            default:
                // They are doing it wrong
                Api4::message('error', T_('Invalid request'), '405', $input['api_format']);

                return false;
        } // end switch on command

        // bad status calls can happen
        if ($command === 'status' && empty($status)) {
            Api4::message('error', T_('Error Unable to connect to localplay controller'), '405', $input['api_format']);

            return false;
        }

        $results = (!empty($status))
            ? ['localplay' => ['command' => [$input['command'] => $status]]]
            : ['localplay' => ['command' => [$input['command'] => $result]]];
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml4_Data::keyed_array($results);
        }

        return true;
    }
}
