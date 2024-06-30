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
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class AdvancedSearchMethod
 * @package Lib\ApiMethods
 */
final class AdvancedSearchMethod
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
     * Use operator ('and', 'or') to choose whether to join or separate each rule when searching.
     *
     * Rule arrays must contain the following:
     *   * rule name (e.g. rule_1, rule_2)
     *   * rule operator (e.g. rule_1_operator, rule_2_operator)
     *   * rule input (e.g. rule_1_input, rule_2_input)
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
     */
    public static function advanced_search(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['rule_1', 'rule_1_operator', 'rule_1_input'], self::ACTION)) {
            return false;
        }

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = $type;
        $results        = Search::run($data, $user);
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'album':
                        echo Json_Data::albums($results, [], $user);
                        break;
                    case 'song_artist':
                    case 'album_artist':
                    case 'artist':
                        echo Json_Data::artists($results, [], $user);
                        break;
                    case 'label':
                        echo Json_Data::labels($results);
                        break;
                    case 'playlist':
                        echo Json_Data::playlists($results, $user);
                        break;
                    case 'podcast':
                        echo Json_Data::podcasts($results, $user);
                        break;
                    case 'podcast_episode':
                        echo Json_Data::podcast_episodes($results, $user);
                        break;
                    case 'genre':
                    case 'tag':
                        echo Json_Data::genres($results);
                        break;
                    case 'user':
                        echo Json_Data::users($results);
                        break;
                    case 'video':
                        echo Json_Data::videos($results, $user);
                        break;
                    default:
                        echo Json_Data::songs($results, $user);
                        break;
                }
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'album':
                        echo Xml_Data::albums($results, [], $user);
                        break;
                    case 'artist':
                        echo Xml_Data::artists($results, [], $user);
                        break;
                    case 'label':
                        echo Xml_Data::labels($results, $user);
                        break;
                    case 'playlist':
                        echo Xml_Data::playlists($results, $user);
                        break;
                    case 'podcast':
                        echo Xml_Data::podcasts($results, $user);
                        break;
                    case 'podcast_episode':
                        echo Xml_Data::podcast_episodes($results, $user);
                        break;
                    case 'genre':
                    case 'tag':
                        echo Xml_Data::genres($results, $user);
                        break;
                    case 'user':
                        echo Xml_Data::users($results);
                        break;
                    case 'video':
                        echo Xml_Data::videos($results, $user);
                        break;
                    default:
                        echo Xml_Data::songs($results, $user);
                        break;
                }
        }

        return true;
    }
}
