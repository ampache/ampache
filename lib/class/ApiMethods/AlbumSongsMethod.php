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

use Album;
use AmpConfig;
use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class AlbumSongsMethod
 * @package Lib\ApiMethods
 */
class AlbumSongsMethod
{
    private const ACTION = 'album_songs';

    /**
     * album_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of a specified album
     *
     * @param array $input
     * filter = (string) UID of Album
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function album_songs(array $input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $album     = new Album($object_id);
        if (!$album->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        ob_end_clean();
        // songs for all disks
        $songs = array();
        $user  = User::get_from_username(Session::username($input['auth']));
        if (AmpConfig::get('album_group')) {
            $disc_ids = $album->get_group_disks_ids();
            foreach ($disc_ids as $discid) {
                $disc     = new Album($discid);
                $allsongs = $disc->get_songs();
                foreach ($allsongs as $songid) {
                    $songs[] = $songid;
                }
            }
        } else {
            // songs for just this disk
            $songs = $album->get_songs();
        }
        if (empty($songs)) {
            Api::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::songs($songs, $user->id);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::songs($songs, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
