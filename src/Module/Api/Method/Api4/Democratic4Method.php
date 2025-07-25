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

use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

/**
 * Class Democratic4Method
 */
final class Democratic4Method
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
     *
     * @param array{
     *     method: string,
     *     oid?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function democratic(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['method'], self::ACTION)) {
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
                if ($media->isNew()) {
                    Api4::message('error', T_('Media object invalid or not specified'), '400', $input['api_format']);
                    break;
                }
                $democratic->add_vote(
                    [
                        [
                            $type,
                            $media->id
                        ]
                    ]
                );

                // If everything was ok
                $results = [
                    'method' => $input['method'],
                    'result' => true
                ];
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml4_Data::keyed_array($results);
                }
                break;
            case 'devote':
                $type      = 'song';
                $object_id = (int)($input['oid'] ?? 0);
                $media     = new Song($object_id);
                if ($media->isNew()) {
                    Api4::message('error', T_('Media object invalid or not specified'), '400', $input['api_format']);
                    break;
                }

                $object_id = $democratic->get_uid_from_object_id($media->id, $type);
                if ($object_id) {
                    $democratic->remove_vote($object_id);
                }

                // Everything was ok
                $results = [
                    'method' => $input['method'],
                    'result' => true
                ];
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml4_Data::keyed_array($results);
                }
                break;
            case 'playlist':
                $results = $democratic->get_items();
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);
                switch ($input['api_format']) {
                    case 'json':
                        echo Json4_Data::democratic($results, $user, $input['auth']);
                        break;
                    default:
                        echo Xml4_Data::democratic($results, $user, $input['auth']);
                }
                break;
            case 'play':
                $url     = $democratic->play_url($user);
                $results = ['url' => $url];
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml4_Data::keyed_array($results);
                }
                break;
            default:
                Api4::message('error', T_('Invalid request'), '405', $input['api_format']);
                break;
        } // switch on method

        return true;
    }
}
