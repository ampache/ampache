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

final class UpdateArtistInfoMethod
{
    /**
     * update_artist_info
     * MINIMUM_API_VERSION=400001
     *
     * Update artist information and fetch similar artists from last.fm
     * Make sure lastfm_api_key is set in your configuration file
     *
     * @param array $input
     * id   = (integer) $artist_id)
     * @return boolean
     */
    public static function update_artist_info($input)
    {
        if (!Api::check_parameter($input, array('id'), 'update_artist_info')) {
            return false;
        }
        if (!Api::check_access('interface', 75, \User::get_from_username(\Session::username($input['auth']))->id, 'update_artist_info', $input['api_format'])) {
            return false;
        }
        $object = (int) $input['id'];
        $item   = new \Artist($object);
        if (!$item->id) {
            Api::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return false;
        }
        // update your object
        // need at least catalog_manager access to the db
        if (!empty(\Recommendation::get_artist_info($object) || !empty(\Recommendation::get_artists_like($object)))) {
            Api::message('success', 'Updated artist info: ' . (string) $object, null, $input['api_format']);

            return true;
        }
        Api::message('error', T_('Failed to update_artist_info or recommendations for ' . (string) $object), '400', $input['api_format']);
        \Session::extend($input['auth']);

        return true;
    }
}
