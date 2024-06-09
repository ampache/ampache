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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

/**
 * Class Playlists4Method
 */
final class Playlists4Method
{
    public const ACTION = 'playlists';

    /**
     * playlists
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * filter      = (string) Alpha-numeric search term (match all if missing) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * show_dupes  = (integer) 0,1, if true ignore 'api_hide_dupe_searches' setting //optional
     * exact       = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * add         = $browse->set_api_filter(date) //optional
     * update      = $browse->set_api_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     */
    public static function playlists(array $input, User $user): void
    {
        $hide       = (array_key_exists('hide_search', $input) && (int)$input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        $show_dupes = (array_key_exists('show_dupes', $input))
            ? (bool)($input['show_dupes'])
            : (bool)Preference::get_by_user($user->getId(), 'api_hide_dupe_searches') === false;

        $browse = Api::getBrowse($user);
        if ($hide === false) {
            $browse->set_type('playlist_search');
        } else {
            $browse->set_type('playlist');
        }

        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), ['name','ASC']);

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_filter('playlist_open', $user->getId());

        if (
            $hide === false &&
            $show_dupes === false
        ) {
            $browse->set_filter('hide_dupe_smartlist', 1);
        }

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = $browse->get_objects();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset((int)($input['offset'] ?? 0));
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::playlists($results, $user);
                break;
            default:
                Xml4_Data::set_offset((int)($input['offset'] ?? 0));
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::playlists($results, $user);
        }
    }
}
