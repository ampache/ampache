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
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;

/**
 * Class SearchSongs8Method
 * @package Lib\Api8Methods
 */
final class SearchSongs8Method
{
    public const string ACTION = 'search_songs';

    /**
     * search_songs
     * MINIMUM_API_VERSION=380001
     *
     * This searches the songs and returns... songs
     *
     * Refer to the wiki for further information on rule_* types and data
     * https://ampache.org/api/api-xml-methods
     * https://ampache.org/api/api-json-methods
     *
     * operator        = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0|1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'song', 'album', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'genre', 'user', 'video' (song by default) //optional
     * random          = (boolean)  0, 1 (random order of results; default to 0) //optional
     * offset          = (integer) //optional
     * limit           = (integer) //optional
     *
     * @param array<string, mixed> $input
     */
    public static function search_songs(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $data                    = [];
        $data['type']            = 'song';
        $data['rule_1']          = 'anywhere';
        $data['rule_1_input']    = $input['filter'];
        $data['rule_1_operator'] = 0;

        $search_sql = Search::prepare($data, $user);
        $query      = Search::query($search_sql);
        $results    = $query['results'];
        $count      = $query['count'];
        if (empty($results)) {
            Api::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json8_Data::set_offset((int)($input['offset'] ?? 0));
                Json8_Data::set_limit($input['limit'] ?? 0);
                Json8_Data::set_count($count);
                echo Json8_Data::songs($results, $user, $input['auth']);
                break;
            default:
                Xml8_Data::set_offset((int)($input['offset'] ?? 0));
                Xml8_Data::set_limit($input['limit'] ?? 0);
                Xml8_Data::set_count($count);
                echo Xml8_Data::songs($results, $user, $input['auth']);
        }

        return true;
    }
}
