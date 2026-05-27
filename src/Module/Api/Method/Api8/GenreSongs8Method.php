<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;

/**
 * Class GenreSongs8Method
 * @package Lib\Api8Methods
 */
final class GenreSongs8Method
{
    public const ACTION = 'genre_songs';

    /**
     * genre_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this genre
     *
     * filter = (string) UID of Genre
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * cond   = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort   = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     *
     * @param array{
     *     filter: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function genre_songs(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $genre     = new Tag($object_id);
        if ($genre->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $browse = Api::getBrowse($user);
        $browse->set_type('song');

        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), ['name', 'ASC']);

        $browse->set_filter('tag', $object_id);

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json8_Data::set_offset((int)($input['offset'] ?? 0));
                Json8_Data::set_limit($input['limit'] ?? 0);
                Json8_Data::set_count($browse->get_total());
                echo Json8_Data::songs($results, $user, $input['auth']);
                break;
            default:
                Xml8_Data::set_offset((int)($input['offset'] ?? 0));
                Xml8_Data::set_limit($input['limit'] ?? 0);
                Xml8_Data::set_count($browse->get_total());
                echo Xml8_Data::songs($results, $user, $input['auth']);
        }

        return true;
    }
}
