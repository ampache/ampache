<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\System\Session;
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
     * @param array $input
     */
    public static function advanced_search(array $input)
    {
        ob_end_clean();

        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);

        $user    = User::get_from_username(Session::username($input['auth']));
        $results = Search::run($input, $user);

        $type = 'song';
        if (isset($input['type'])) {
            $type = $input['type'];
        }

        switch ($type) {
            case 'artist':
                echo Xml3_Data::artists($results, array(), $user->id);
                break;
            case 'album':
                echo Xml3_Data::albums($results, array(), $user->id);
                break;
            default:
                echo Xml3_Data::songs($results, $user->id);
                break;
        }
    } // advanced_search
}
