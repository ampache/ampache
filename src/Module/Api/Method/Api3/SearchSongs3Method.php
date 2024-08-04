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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Repository\Model\Search;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class SearchSongs3Method
 */
final class SearchSongs3Method
{
    public const ACTION = 'search_songs';

    /**
     * search_songs
     * This searches the songs and returns... songs
     */
    public static function search_songs(array $input, User $user): void
    {
        $data                    = [];
        $data['type']            = 'song';
        $data['rule_1']          = 'anywhere';
        $data['rule_1_input']    = $input['filter'];
        $data['rule_1_operator'] = 0;

        ob_end_clean();

        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);

        $results = Search::run($data, $user);

        echo Xml3_Data::songs($results, $user);
    }
}
