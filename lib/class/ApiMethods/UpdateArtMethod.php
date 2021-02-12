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
use Catalog;
use Session;
use User;

/**
 * Class UpdateArtMethod
 * @package Lib\ApiMethods
 */
final class UpdateArtMethod
{
    private const ACTION = 'update_art';

    /**
     * update_art
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song running the gather_art process
     * Doesn't overwrite existing art by default.
     *
     * @param array $input
     * type      = (string) 'artist', 'album'
     * id        = (integer) $artist_id, $album_id)
     * overwrite = (integer) 0,1 //optional
     * @return boolean
     */
    public static function update_art(array $input)
    {
        if (!Api::check_parameter($input, array('type', 'id'), self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $overwrite = (int) $input['overwrite'] == 0;
        $art_url   = AmpConfig::get('web_path') . '/image.php?object_id=' . $object_id . '&object_type=artist&auth=' . $input['auth'];

        // confirm the correct data
        if (!in_array($type, array('artist', 'album'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return true;
        }
        $item = new $type($object_id);
        if (!$item->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'id', $input['api_format']);

            return false;
        }
        // update your object
        if (Catalog::gather_art_item($type, $object_id, $overwrite, true)) {
            Api::message('Gathered new art for: ' . (string) $object_id . ' (' . $type . ')', $input['api_format'], array('art' => $art_url));

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api::error(sprintf(T_('Bad Request: %s'), $object_id), '4710', self::ACTION, 'system', $input['api_format']);
        Session::extend($input['auth']);

        return true;
    }
}
