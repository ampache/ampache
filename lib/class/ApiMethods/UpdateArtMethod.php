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

final class UpdateArtMethod
{
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
    public static function update_art($input)
    {
        if (!Api::check_parameter($input, array('type', 'id'), 'update_art')) {
            return false;
        }
        if (!Api::check_access('interface', 75, \User::get_from_username(\Session::username($input['auth']))->id, 'update_art', $input['api_format'])) {
            return false;
        }
        $type      = (string) $input['type'];
        $object    = (int) $input['id'];
        $overwrite = (int) $input['overwrite'] == 0;
        $art_url   = AmpConfig::get('web_path') . '/image.php?object_id=' . $object . '&object_type=artist&auth=' . $input['auth'];

        // confirm the correct data
        if (!in_array($type, array('artist', 'album'))) {
            Api::message('error', T_('Incorrect object type') . ' ' . $type, '400', $input['api_format']);

            return true;
        }
        $item = new $type($object);
        if (!$item->id) {
            Api::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return true;
        }
        // update your object
        if (\Catalog::gather_art_item($type, $object, $overwrite, true)) {
            Api::message('success', 'Gathered new art for: ' . (string) $object . ' (' . $type . ')', null, $input['api_format'], array('art' => $art_url));

            return true;
        }
        Api::message('error', T_('Failed to update_art for ' . (string) $object), '400', $input['api_format']);
        \Session::extend($input['auth']);

        return true;
    }
}
