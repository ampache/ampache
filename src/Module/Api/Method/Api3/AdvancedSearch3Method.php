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

use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;

/**
 * Class AdvancedSearch3Method
 */
final class AdvancedSearch3Method
{
    public const ACTION = 'advanced_search';

    /**
     * advanced_search
     * Perform an advanced search given passed rules
     */
    public static function advanced_search(array $input, User $user): void
    {
        ob_end_clean();

        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = (isset($data['type'])) ? (string) $data['type'] : 'song';
        $results        = Search::run($data, $user);

        $type = 'song';
        if (isset($input['type'])) {
            $type = $input['type'];
        }

        switch ($type) {
            case 'artist':
                echo Xml3_Data::artists($results, array(), $user);
                break;
            case 'album':
                echo Xml3_Data::albums($results, array(), $user);
                break;
            default:
                echo Xml3_Data::songs($results, $user);
                break;
        }
    }
}
