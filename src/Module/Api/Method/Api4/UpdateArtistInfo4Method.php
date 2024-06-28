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

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Util\Recommendation;

/**
 * Class UpdateArtistInfoMethod
 */
final class UpdateArtistInfo4Method
{
    public const ACTION = 'update_artist_info';

    /**
     * update_artist_info
     * MINIMUM_API_VERSION=400001
     *
     * Update artist information and fetch similar artists from last.fm
     * Make sure lastfm_api_key is set in your configuration file
     *
     * id   = (integer) $artist_id
     */
    public static function update_artist_info(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('id'), self::ACTION)) {
            return false;
        }
        if (!Api4::check_access('interface', 75, $user->id, 'update_artist_info', $input['api_format'])) {
            return false;
        }
        $object = (int) $input['id'];
        $item   = new Artist($object);
        if ($item->isNew()) {
            Api4::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return false;
        }
        // update your object
        // need at least catalog_manager access to the db
        if (!empty(Recommendation::get_artist_info($object) || !empty(Recommendation::get_artists_like($object)))) {
            Api4::message('success', 'Updated artist info: ' . (string) $object, null, $input['api_format']);

            return true;
        }
        Api4::message('error', T_('Failed to update_artist_info or recommendations for ' . (string) $object), '400', $input['api_format']);

        return true;
    }
}
