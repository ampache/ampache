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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class SearchSongs5Method
 */
final class SearchSongs5Method
{
    public const ACTION = 'search_songs';

    /**
     * search_songs
     * MINIMUM_API_VERSION=380001
     *
     * This searches the songs and returns... songs
     *
     * filter = (string) Alpha-numeric search term
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function search_songs(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, ['filter'], self::ACTION)) {
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

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json5_Data::set_offset($input['offset'] ?? 0);
                Json5_Data::set_limit($input['limit'] ?? 0);
                echo Json5_Data::songs($results, $user);
                break;
            default:
                Xml5_Data::set_offset($input['offset'] ?? 0);
                Xml5_Data::set_limit($input['limit'] ?? 0);
                echo Xml5_Data::songs($results, $user);
        }

        return true;
    }
}
