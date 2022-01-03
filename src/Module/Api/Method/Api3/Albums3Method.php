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
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api3;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * Class Albums3Method
 */
final class Albums3Method
{
    public const ACTION = 'albums';

    /**
     * albums
     * This returns albums based on the provided search filters
     * @param array $input
     */
    public static function albums(array $input)
    {
        $browse = Api3::getBrowse();
        $browse->reset_filters();
        $browse->set_type('album');
        $browse->set_sort('name', 'ASC');
        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $user   = User::get_from_username(Session::username($input['auth']));

        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);

        $albums  = $browse->get_objects();
        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string)$input['include']);
        }
        // Set the offset
        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);
        ob_end_clean();
        echo Xml3_Data::albums($albums, $include, $user->id);
    } // albums
}
