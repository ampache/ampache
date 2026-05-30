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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;

/**
 * Class AdvancedSearch6Method
 * @package Lib\Api6Methods
 */
final class AdvancedSearch6Method
{
    public const string ACTION = 'advanced_search';

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
     *
     * @param array<string, mixed> $input
     */
    public static function advanced_search(array $input, User $user): bool
    {
        if (!Api6::check_parameter($input, ['rule_1', 'rule_1_operator', 'rule_1_input'], self::ACTION)) {
            return false;
        }

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';
        // confirm the correct data
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
            Api6::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api6::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        if ($type == 'label' && !AmpConfig::get('label')) {
            Api6::error('Enable: label', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = $type;
        $search_sql     = Search::prepare($data, $user);
        $query          = Search::query($search_sql);
        $results        = $query['results'];
        $count          = $query['count'];
        if (empty($results)) {
            Api6::empty($type, $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json6_Data::set_offset((int)($input['offset'] ?? 0));
                Json6_Data::set_limit($input['limit'] ?? 0);
                Json6_Data::set_count($count);
                switch ($type) {
                    case 'album':
                        echo Json6_Data::albums($results, [], $user, $input['auth']);
                        break;
                    case 'song_artist':
                    case 'album_artist':
                    case 'artist':
                        echo Json6_Data::artists($results, [], $user, $input['auth']);
                        break;
                    case 'label':
                        echo Json6_Data::labels($results);
                        break;
                    case 'playlist':
                        echo Json6_Data::playlists($results, $user, $input['auth']);
                        break;
                    case 'podcast':
                        echo Json6_Data::podcasts($results, $user, $input['auth']);
                        break;
                    case 'podcast_episode':
                        echo Json6_Data::podcast_episodes($results, $user, $input['auth']);
                        break;
                    case 'genre':
                    case 'tag':
                        echo Json6_Data::genres($results);
                        break;
                    case 'user':
                        echo Json6_Data::users($results);
                        break;
                    case 'video':
                        echo Json6_Data::videos($results, $user, $input['auth']);
                        break;
                    default:
                        echo Json6_Data::songs($results, $user, $input['auth']);
                        break;
                }
                break;
            default:
                Xml6_Data::set_offset((int)($input['offset'] ?? 0));
                Xml6_Data::set_limit($input['limit'] ?? 0);
                Xml6_Data::set_count($count);
                switch ($type) {
                    case 'album':
                        echo Xml6_Data::albums($results, [], $user, $input['auth']);
                        break;
                    case 'artist':
                        echo Xml6_Data::artists($results, [], $user, $input['auth']);
                        break;
                    case 'label':
                        echo Xml6_Data::labels($results, $user);
                        break;
                    case 'playlist':
                        echo Xml6_Data::playlists($results, $user, $input['auth']);
                        break;
                    case 'podcast':
                        echo Xml6_Data::podcasts($results, $user, $input['auth']);
                        break;
                    case 'podcast_episode':
                        echo Xml6_Data::podcast_episodes($results, $user, $input['auth']);
                        break;
                    case 'genre':
                    case 'tag':
                        echo Xml6_Data::genres($results, $user);
                        break;
                    case 'user':
                        echo Xml6_Data::users($results);
                        break;
                    case 'video':
                        echo Xml6_Data::videos($results, $user, $input['auth']);
                        break;
                    default:
                        echo Xml6_Data::songs($results, $user, $input['auth']);
                        break;
                }
        }

        return true;
    }
}
