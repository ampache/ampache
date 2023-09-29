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

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class UpdateArtMethod
 * @package Lib\ApiMethods
 */
final class UpdateArtMethod
{
    public const ACTION = 'update_art';

    /**
     * update_art
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song running the gather_art process
     * Doesn't overwrite existing art by default.
     *
     * @param array $input
     * @param User $user
     * type      = (string) 'artist', 'album'
     * id        = (integer) $artist_id, $album_id
     * overwrite = (integer) 0,1 //optional
     * @return boolean
     */
    public static function update_art(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('type', 'id'), self::ACTION)) {
            return false;
        }

        if (!Api::check_access('interface', 75, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $type      = (string)$input['type'];
        $object_id = (int)$input['id'];
        $overwrite = array_key_exists('overwrite', $input) && (int)$input['overwrite'] == 0;
        $art_url   = AmpConfig::get('web_path') . '/image.php?object_id=' . $object_id . '&object_type=' . $type;

        // confirm the correct data
        if (!in_array(strtolower($type), array('artist', 'album'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return true;
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        $item = new $className($object_id);
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

        return true;
    }
}
