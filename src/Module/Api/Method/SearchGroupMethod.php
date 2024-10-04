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
 * Class SearchGroupMethod
 * @package Lib\ApiMethods
 */
final class SearchGroupMethod
{
    public const ACTION = 'search_group';

    /**
     * search_group
     * MINIMUM_API_VERSION=6.3.0
     *
     * Perform a search given passed rules and return matching objects in a group.
     * If the rules to not exist for the object type or would return the entire table they will not return objects
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
     * type            = (string) 'all', 'music', 'song_artist', 'album_artist', 'podcast', 'video' (all by default) //optional
     * random          = (boolean)  0, 1 (random order of results; default to 0) //optional
     * offset          = (integer) //optional
     * limit           = (integer) //optional
     */
    public static function search_group(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['rule_1', 'rule_1_operator', 'rule_1_input'], self::ACTION)) {
            return false;
        }
        $search_groups = [
            'all',
            'music',
            'song_artist',
            'album_artist',
            'podcast',
            'video'
        ];
        $type = (isset($input['type']))
            ? $input['type']
            : 'all';
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), $search_groups)) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $search_types = [
            'song',
            'album',
            'song_artist',
            'album_artist',
            'artist',
            'label',
            'playlist',
            'podcast',
            'podcast_episode',
            'genre',
            'user'
        ];
        switch ($type) {
            case 'all':
                break;
            case 'music':
                $search_types = [
                    'song',
                    'album',
                    'artist',
                ];
                break;
            case 'song_artist':
                $search_types = [
                    'song',
                    'album',
                    'song_artist',
                ];
                break;
            case 'album_artist':
                $search_types = [
                    'song',
                    'album',
                    'album_artist',
                ];
                break;
            case 'podcast':
                $search_types = [
                    'podcast',
                    'podcast_episode',
                ];
                break;
            case 'video':
                $search_types = ['video'];
        }
        $offset         = $input['offset'] ?? 0;
        $limit          = $input['limit'] ?? 0;
        $results        = [];
        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        foreach ($search_types as $type) {
            $data['type']   = $type;
            $results[$type] = Search::run($data, $user, true);
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                $output = ['search' => []];
                Json_Data::set_offset($offset);
                Json_Data::set_limit($limit);
                foreach ($results as $key => $search) {
                    switch ($key) {
                        case 'album':
                            if ((count($search) > $limit || $offset > 0) && $limit) {
                                $search = array_slice($search, $offset, $limit);
                            }
                            $output['search'][$key] = Json_Data::albums($search, [], $user, false);
                            break;
                        case 'song_artist':
                        case 'album_artist':
                        case 'artist':
                            if ((count($search) > $limit || $offset > 0) && $limit) {
                                $search = array_slice($search, $offset, $limit);
                            }
                            $output['search'][$key] = Json_Data::artists($search, [], $user, false);
                            break;
                        case 'label':
                            $output['search'][$key] = Json_Data::labels($search, false);
                            break;
                        case 'playlist':
                            $output['search'][$key] = Json_Data::playlists($search, $user, false, false);
                            break;
                        case 'podcast':
                            $output['search'][$key] = Json_Data::podcasts($search, $user, false, false);
                            break;
                        case 'podcast_episode':
                            if ((count($search) > $limit || $offset > 0) && $limit) {
                                $search = array_slice($search, $offset, $limit);
                            }
                            $output['search'][$key] = Json_Data::podcast_episodes($search, $user, false);
                            break;
                        case 'genre':
                        case 'tag':
                            $output['search'][$key] = Json_Data::genres($search, false);
                            break;
                        case 'user':
                            $output['search'][$key] = Json_Data::users($search, false);
                            break;
                        case 'video':
                            $output['search'][$key] = Json_Data::videos($search, $user, false);
                            break;
                        case 'song':
                            if ((count($search) > $limit || $offset > 0) && $limit) {
                                $search = array_slice($search, $offset, $limit);
                            }
                            $output['search'][$key] = Json_Data::songs($search, $user, false);
                            break;
                    }
                }
                echo json_encode($output, JSON_PRETTY_PRINT);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::searches($results, $user);
        }

        return true;
    }
}
