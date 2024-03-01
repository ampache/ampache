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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Repository\Model\User;

/**
 * Class SearchMethod
 */
final class SearchMethod
{
    public const ACTION = 'search';

    /**
     * search
     * MINIMUM_API_VERSION=6.3.0
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
    public static function search(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }

        return AdvancedSearchMethod::advanced_search($input, $user);
    }
}
