<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

/**
 * Class AdvancedSearch4Method
 */
final class AdvancedSearch4Method
{
    public const ACTION = 'advanced_search';

    /**
     * advanced_search
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
     * You can pass multiple rules as well as joins to create in depth search results
     *
     * Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
     * Use operator ('and'|'or') to choose whether to join or separate each rule when searching.
     *
     * Rule arrays must contain the following:
     *   * rule name (e.g. rule_1, rule_2)
     *   * rule operator (e.g. rule_1_operator, rule_2_operator)
     *   * rule input (e.g. rule_1_input, rule_2_input)
     *
     * Refer to the wiki for further information on rule_* types and data
     * https://github.com/ampache/ampache/wiki/XML-methods
     *
     * operator        = (string) 'and'|'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0|1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'song', 'album', 'song_artist', 'album_artist', 'artist', 'playlist', 'label', 'user', 'video' (song by default)
     * offset          = (integer)
     * limit           = (integer)
     */
    public static function advanced_search(array $input, User $user): void
    {
        ob_end_clean();

        $type = 'song';
        if (isset($input['type'])) {
            $type = $input['type'];
        }
        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = $type;
        $results        = Search::run($data, $user);

        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'song_artist':
                    case 'album_artist':
                    case 'artist':
                        echo Json4_Data::artists($results, array(), $user);
                        break;
                    case 'album':
                        echo Json4_Data::albums($results, array(), $user);
                        break;
                    case 'playlist':
                        echo Json4_Data::playlists($results, $user);
                        break;
                    case 'user':
                        echo Json4_Data::users($results);
                        break;
                    case 'video':
                        echo Json4_Data::videos($results, $user);
                        break;
                    default:
                        echo Json4_Data::songs($results, $user);
                        break;
                }
                break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'artist':
                        echo Xml4_Data::artists($results, array(), $user);
                        break;
                    case 'album':
                        echo Xml4_Data::albums($results, array(), $user);
                        break;
                    case 'playlist':
                        echo Xml4_Data::playlists($results, $user);
                        break;
                    case 'user':
                        echo Xml4_Data::users($results);
                        break;
                    case 'video':
                        echo Xml4_Data::videos($results, $user);
                        break;
                    default:
                        echo Xml4_Data::songs($results, $user);
                        break;
                }
        }
    }
}
