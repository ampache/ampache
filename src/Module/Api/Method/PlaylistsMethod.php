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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class PlaylistsMethod
 * @package Lib\ApiMethods
 */
final class PlaylistsMethod
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
     * include     = (integer) 0,1, if true include playlist contents //optional
     * exact       = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * add         = $browse->set_api_filter(date) //optional
     * update      = $browse->set_api_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * cond        = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort        = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     *
     * @param array{
     *     filter?: string,
     *     hide_search?: int,
     *     show_dupes?: int,
     *     include?: int,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function playlists(array $input, User $user): bool
    {
        $include    = (bool)($input['include'] ?? false);
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

        // hide playlists starting with the user string (if enabled)
        $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string)Preference::get_by_user($user->id, 'api_hidden_playlists')));
        if (!empty($hide_string)) {
            $browse->set_filter('not_starts_with', $hide_string);
        }

        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), ['name', 'ASC']);

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
        if (empty($results)) {
            Api::empty('playlist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                Json_Data::set_count($browse->get_total());
                echo Json_Data::playlists($results, $user, $include);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                Xml_Data::set_count($browse->get_total());
                echo Xml_Data::playlists($results, $user, $include);
        }

        return true;
    }
}
