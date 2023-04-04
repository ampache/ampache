<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api5;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class AdvancedSearch5Method
 */
final class AdvancedSearch5Method
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
     * @param array $input
     * @param User $user
     * operator        = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0,1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'song', 'album', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'genre', 'user', 'video' (song by default) //optional
     * random          = (boolean)  0, 1 (random order of results; default to 0) //optional
     * offset          = (integer) //optional
     * limit           = (integer) //optional
     * @return boolean
     */
    public static function advanced_search(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('rule_1', 'rule_1_operator', 'rule_1_input'), self::ACTION)) {
            return false;
        }

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api5::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
            Api5::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $results        = Search::run($data, $user);
        if (empty($results)) {
            Api5::empty($type, $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json5_Data::set_offset($input['offset'] ?? 0);
                Json5_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'album':
                        echo Json5_Data::albums($results, array(), $user);
                        break;
                    case 'song_artist':
                    case 'album_artist':
                    case 'artist':
                        echo Json5_Data::artists($results, array(), $user);
                        break;
                    case 'label':
                        echo Json5_Data::labels($results);
                        break;
                    case 'playlist':
                        echo Json5_Data::playlists($results, $user);
                        break;
                    case 'podcast':
                        echo Json5_Data::podcasts($results, $user);
                        break;
                    case 'podcast_episode':
                        echo Json5_Data::podcast_episodes($results, $user);
                        break;
                    case 'genre':
                    case 'tag':
                        echo Json5_Data::genres($results);
                        break;
                    case 'user':
                        echo Json5_Data::users($results);
                        break;
                    case 'video':
                        echo Json5_Data::videos($results, $user);
                        break;
                    default:
                        echo Json5_Data::songs($results, $user);
                        break;
                }
                break;
            default:
                Xml5_Data::set_offset($input['offset'] ?? 0);
                Xml5_Data::set_limit($input['limit'] ?? 0);
                switch ($type) {
                    case 'album':
                        echo Xml5_Data::albums($results, array(), $user);
                        break;
                    case 'artist':
                        echo Xml5_Data::artists($results, array(), $user);
                        break;
                    case 'label':
                        echo Xml5_Data::labels($results, $user);
                        break;
                    case 'playlist':
                        echo Xml5_Data::playlists($results, $user);
                        break;
                    case 'podcast':
                        echo Xml5_Data::podcasts($results, $user);
                        break;
                    case 'podcast_episode':
                        echo Xml5_Data::podcast_episodes($results, $user);
                        break;
                    case 'genre':
                    case 'tag':
                        echo Xml5_Data::genres($results, $user);
                        break;
                    case 'user':
                        echo Xml5_Data::users($results);
                        break;
                    case 'video':
                        echo Xml5_Data::videos($results, $user);
                        break;
                    default:
                        echo Xml5_Data::songs($results, $user);
                        break;
                }
        }

        return true;
    }
}
