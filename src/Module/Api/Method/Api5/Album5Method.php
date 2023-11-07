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

declare(strict_types=1);

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;

/**
 * Class Album5Method
 */
final class Album5Method
{
    public const ACTION = 'album';

    /**
     * album
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single album based on the UID provided
     *
     * filter  = (string) UID of Album
     * include = (array|string) 'songs' //optional
     */
    public static function album(array $input, User $user): bool
    {
        $objectId = $input['filter'] ?? null;

        if ($objectId === null) {
            Api5::error(sprintf(T_('Bad Request: %s'), $objectId), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $album = new Album((int) $objectId);

        if ($album->isNew()) {
            Api5::empty('album', $input['api_format']);

            return false;
        }

        ob_end_clean();
        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        }

        switch ($input['api_format']) {
            case 'json':
                Json5_Data::set_offset($input['offset'] ?? 0);
                Json5_Data::set_limit($input['limit'] ?? 0);
                echo Json5_Data::albums(array($album->getId()), $include, $user);
                break;
            default:
                Xml5_Data::set_offset($input['offset'] ?? 0);
                Xml5_Data::set_limit($input['limit'] ?? 0);
                echo Xml5_Data::albums(array($album->getId()), $include, $user);
        }

        return true;
    }
}
