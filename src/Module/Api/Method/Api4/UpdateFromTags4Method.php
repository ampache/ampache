<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Api\Api4;

/**
 * Class UpdateFromTags4Method
 */
final class UpdateFromTags4Method
{
    public const ACTION = 'update_from_tags';

    /**
     * update_from_tags
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song from the tag data
     *
     * @param array $input
     * type = (string) 'artist'|'album'|'song'
     * id   = (integer) $artist_id, $album_id, $song_id)
     * @return boolean
     */
    public static function update_from_tags(array $input): bool
    {
        if (!Api4::check_parameter($input, array('type', 'id'), 'update_from_tags')) {
            return false;
        }
        $type   = ObjectTypeToClassNameMapper::map((string)$input['type']);
        $object = (int) $input['id'];

        // confirm the correct data
        if (!in_array($type, array('Artist', 'Album', 'Song'))) {
            Api4::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }
        $item = new $type($object);
        if (!$item->id) {
            Api4::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return false;
        }
        // update your object
        Catalog::update_single_item($type, $object, true);

        Api4::message('success', 'Updated tags for: ' . (string) $object . ' (' . $type . ')', null, $input['api_format']);

        return true;
    } // update_from_tags
}
