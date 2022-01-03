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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Song;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class DeletedSongsMethod
 * @package Lib\ApiMethods
 */
final class DeletedSongsMethod
{
    const ACTION = 'deleted_songs';

    /**
     * deleted_songs
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=420000
     *
     * Returns songs that have been deleted from the server
     *
     * @param array $input
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function deleted_songs(array $input): bool
    {
        $songs = Song::get_deleted();
        if (empty($songs)) {
            Api::empty('deleted_songs', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::deleted('song', $songs);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::deleted('song', $songs);
        }

        return true;
    }
}
