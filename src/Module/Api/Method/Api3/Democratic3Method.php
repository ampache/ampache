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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Song;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class Democratic3Method
 */
final class Democratic3Method
{
    public const ACTION = 'democratic';

    /**
     * democratic
     * This is for controlling democratic play
     */
    public static function democratic(array $input, User $user): void
    {
        // Load up democratic information
        $democratic = Democratic::get_current_playlist($user);
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $media = new Song($input['oid']);
                if (!$media->id) {
                    echo Xml3_Data::error('400', T_('Media Object Invalid or Not Specified'));
                    break;
                }
                $democratic->add_vote(array(
                    array(
                        'object_type' => 'song',
                        'object_id' => $media->id
                    )
                ));

                // If everything was ok
                $results = array(
                    'method' => $input['method'],
                    'result' => true
                );
                echo Xml3_Data::keyed_array($results);
                break;
            case 'devote':
                $media = new Song($input['oid']);
                if (!$media->id) {
                    echo Xml3_Data::error('400', T_('Media Object Invalid or Not Specified'));
                }

                $uid = $democratic->get_uid_from_object_id($media->id);
                $democratic->remove_vote($uid);

                // Everything was ok
                $results = array(
                    'method' => $input['method'],
                    'result' => true
                );
                echo Xml3_Data::keyed_array($results);
                break;
            case 'playlist':
                $results = $democratic->get_items();
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);
                echo Xml3_Data::democratic($results, $user);
                break;
            case 'play':
                $url     = $democratic->play_url($user);
                $results = array(
                    'url' => $url
                );
                echo Xml3_Data::keyed_array($results);
                break;
            default:
                echo Xml3_Data::error('405', T_('Invalid Request'));
                break;
        } // switch on method
    }
}
