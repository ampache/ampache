<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use JSON_Data;
use Search;
use Session;
use User;
use XML_Data;

/**
 * Class AdvancedSearchMethod
 * @package Lib\ApiMethods
 */
final class AdvancedSearchMethod
{
    private const ACTION = 'advanced_search';

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
     * http://ampache.org/api/api-xml-methods
     * http://ampache.org/api/api-json-methods
     *
     * @param array $input
     * operator        = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0,1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'song', 'album', 'artist', 'playlist', 'label', 'user', 'video' (song by default) //optional
     * random          = (boolean)  0, 1 (random order of results; default to 0) //optional
     * offset          = (integer) //optional
     * limit           = (integer) //optional
     * @return boolean
     */
    public static function advanced_search(array $input)
    {
        if (!Api::check_parameter($input, array('rule_1', 'rule_1_operator', 'rule_1_input'), self::ACTION)) {
            return false;
        }

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist', 'label', 'user', 'video'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $results = Search::run($input, $user);
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                switch ($type) {
                    case 'artist':
                        echo JSON_Data::artists($results, array(), $user->id);
                        break;
                    case 'album':
                        echo JSON_Data::albums($results, array(), $user->id);
                        break;
                    case 'playlist':
                        echo JSON_Data::playlists($results);
                        break;
                    case 'label':
                        echo JSON_Data::labels($results);
                        break;
                    case 'user':
                        echo JSON_Data::users($results);
                        break;
                    case 'video':
                        echo JSON_Data::videos($results, $user->id);
                        break;
                    default:
                        echo JSON_Data::songs($results, $user->id);
                        break;
                }
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                switch ($type) {
                    case 'artist':
                        echo XML_Data::artists($results, array(), $user->id);
                        break;
                    case 'album':
                        echo XML_Data::albums($results, array(), $user->id);
                        break;
                    case 'playlist':
                        echo XML_Data::playlists($results);
                        break;
                    case 'label':
                        echo XML_Data::labels($results);
                        break;
                    case 'user':
                        echo XML_Data::users($results);
                        break;
                    case 'video':
                        echo XML_Data::videos($results, $user->id);
                        break;
                    default:
                        echo XML_Data::songs($results, $user->id);
                        break;
                }
        }
        Session::extend($input['auth']);

        return true;
    }
}
