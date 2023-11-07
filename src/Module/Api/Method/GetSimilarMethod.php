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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\Model\User;

/**
 * Class GetSimilarMethod
 * @package Lib\ApiMethods
 */
final class GetSimilarMethod
{
    public const ACTION = 'get_similar';

    /**
     * get_similar
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * type   = (string) 'song', 'artist'
     * filter = (integer) artist id or song id
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function get_similar(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('type', 'filter'), self::ACTION)) {
            return false;
        }
        $type      = (string) $input['type'];
        $object_id = (int) $input['filter'];
        // confirm the correct data
        if (!in_array(strtolower($type), array('song', 'artist'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $results = array();
        $similar = array();
        switch ($type) {
            case 'artist':
                $similar = Recommendation::get_artists_like($object_id);
                break;
            case 'song':
                $similar = Recommendation::get_songs_like($object_id);
        }
        foreach ($similar as $child) {
            $results[] = $child['id'];
        }
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'artist':
                        echo Json_Data::artists($results, array(), $user);
                        break;
                    case 'song':
                        echo Json_Data::songs($results, $user);
                }
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'artist':
                        echo Xml_Data::artists($results, array(), $user);
                        break;
                    case 'song':
                        echo Xml_Data::songs($results, $user);
                }
        }

        return true;
    }
}
