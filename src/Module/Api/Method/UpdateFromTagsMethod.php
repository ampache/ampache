<?php
/*
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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Api\Api;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

/**
 * Class UpdateFromTagsMethod
 * @package Lib\ApiMethods
 */
final class UpdateFromTagsMethod
{
    public const ACTION = 'update_from_tags';

    /**
     * update_from_tags
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song from the tag data
     *
     * type = (string) 'artist', 'album', 'song'
     * id   = (integer) $artist_id, $album_id, $song_id
     */
    public static function update_from_tags(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('type', 'id'), self::ACTION)) {
            return false;
        }
        unset($user);
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];

        // confirm the correct data
        if (!in_array(strtolower($type), array('artist', 'album', 'song'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        $item = new $className($object_id);
        if (!$item->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'id', $input['api_format']);

            return false;
        }
        // update your object
        Catalog::update_single_item($type, $object_id, true);

        Api::message('Updated tags for: ' . (string) $object_id . ' (' . $type . ')', $input['api_format']);

        return true;
    }
}
