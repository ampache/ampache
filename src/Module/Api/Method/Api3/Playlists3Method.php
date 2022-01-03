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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api3;
use Ampache\Module\Api\Xml3_Data;

/**
 * Class Playlists3Method
 */
final class Playlists3Method
{
    const ACTION = 'playlists';

    /**
     * playlists
     * This returns playlists based on the specified filter
     * @param array $input
     */
    public static function playlists(array $input)
    {
        $browse = Api3::getBrowse();
        $browse->reset_filters();
        $browse->set_type('playlist');
        $browse->set_sort('name', 'ASC');

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        $browse->set_filter('playlist_type', '1');

        $playlists = $browse->get_objects();
        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);

        ob_end_clean();
        echo Xml3_Data::playlists($playlists);
    } // playlists
}
