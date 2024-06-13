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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

final class ArtistAlbumsMethod
{
    public const ACTION = 'artist_albums';

    /**
     * artist_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums of an artist
     *
     * filter       = (string) UID of artist
     * album_artist = (integer) 0,1, if true return albums where the UID is an album_artist of the object //optional
     * offset       = (integer) //optional
     * limit        = (integer) //optional
     * cond         = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort         = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     */
    public static function artist_albums(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        $object_id = (int)$input['filter'];
        $artist    = new Artist($object_id);
        if ($artist->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $browse = Api::getBrowse($user);
        $browse->set_type('album');
        $original_year = AmpConfig::get('use_original_year') ? "original_year" : "year";
        $sort_type     = AmpConfig::get('album_sort');
        switch ($sort_type) {
            case 'name_asc':
                $sort  = 'name';
                $order = 'ASC';
                break;
            case 'name_desc':
                $sort  = 'name';
                $order = 'DESC';
                break;
            case 'year_asc':
                $sort  = $original_year;
                $order = 'ASC';
                break;
            case 'year_desc':
                $sort  = $original_year;
                $order = 'DESC';
                break;
            default:
                $sort  = 'name_' . $original_year;
                $order = 'ASC';
        }
        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), [$sort,$order]);

        $typeFilter = (array_key_exists('album_artist', $input) && (int)$input['album_artist'] == 1)
            ? 'album_artist'
            : 'artist';
        $browse->set_filter($typeFilter, $object_id);

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('album', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::albums($results, [], $user);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::albums($results, [], $user);
        }

        return true;
    }
}
