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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class UpdateArt4Method
 */
final class UpdateArt4Method
{
    public const ACTION = 'update_art';

    /**
     * update_art
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song running the gather_art process
     * Doesn't overwrite existing art by default.
     *
     * type      = (string) 'artist'|'album'
     * id        = (integer) $artist_id, $album_id
     * overwrite = (integer) 0,1 //optional
     */
    public static function update_art(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('type', 'id'), self::ACTION)) {
            return false;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, 'update_art', $input['api_format'])) {
            return false;
        }
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $overwrite = array_key_exists('overwrite', $input) && (int)$input['overwrite'] == 0;

        // confirm the correct data
        if (!in_array(strtolower($type), array('artist', 'album'))) {
            Api4::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return true;
        }
        $className = ObjectTypeToClassNameMapper::map($type);
        /** @var Artist|Album $item */
        $item      = new $className($object_id);
        if ($item->isNew()) {
            Api4::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return true;
        }
        // update your object
        if (Catalog::gather_art_item($type, $object_id, $overwrite, true)) {
            Api4::message('success', 'Gathered new art for: ' . (string) $object_id . ' (' . $type . ')', null, $input['api_format']);

            return true;
        }
        Api4::message('error', T_('Failed to update_art for ' . (string) $object_id), '400', $input['api_format']);

        return true;
    }
}
