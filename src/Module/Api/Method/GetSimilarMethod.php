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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
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
     * filter = (string) artist id or song id
     * type   = (string) 'song', 'artist'
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter: string,
     *     type: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function get_similar(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return false;
        }
        $type      = (string) $input['type'];
        $object_id = (int) $input['filter'];
        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'artist'])) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $results = [];
        $similar = [];
        switch ($type) {
            case 'artist':
                $similar = Recommendation::get_artists_like($object_id);
                break;
            case 'song':
                $similar = Recommendation::get_songs_like($object_id);
        }
        foreach ($similar as $child) {
            $results[] = (int)$child['id'];
        }
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                Json_Data::set_count(count($results));
                switch ($type) {
                    case 'artist':
                        echo Json_Data::artists($results, [], $user, $input['auth']);
                        break;
                    case 'song':
                        echo Json_Data::songs($results, $user, $input['auth']);
                }
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                Xml_Data::set_count(count($results));
                switch ($type) {
                    case 'artist':
                        echo Xml_Data::artists($results, [], $user, $input['auth']);
                        break;
                    case 'song':
                        echo Xml_Data::songs($results, $user, $input['auth']);
                }
        }

        return true;
    }
}
