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

use Access;
use AmpConfig;
use Api;
use Core;
use JSON_Data;
use Session;
use XML_Data;

final class ShareCreateMethod
{
    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) object_id
     * type        = (string) object_type ('song', 'album', 'artist')
     * description = (string) description (will be filled for you if empty) //optional
     * expires     = (integer) days to keep active //optional
     * @return boolean
     */
    public static function share_create($input)
    {
        if (!AmpConfig::get('share')) {
            Api::message('error', T_('Access Denied: sharing features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'filter'), 'share_create')) {
            return false;
        }
        $description = $input['description'];
        $object_id   = $input['filter'];
        $object_type = $input['type'];
        $download    = Access::check_function('download');
        $expire_days = \Share::get_expiry($input['expires']);
        // confirm the correct data
        if (!in_array($object_type, array('song', 'album', 'artist'))) {
            Api::message('error', T_('Wrong object type ' . $object_type), '400', $input['api_format']);

            return false;
        }
        $share = array();
        if (!Core::is_library_item($object_type) || !$object_id) {
            Api::message('error', T_('Wrong library item type'), '400', $input['api_format']);
        } else {
            $item = new $object_type($object_id);
            if (!$item->id) {
                Api::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $share[] = \Share::create_share($object_type, $object_id, true, $download, $expire_days, generate_password(8), 0, $description);
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::shares($share);
                break;
            default:
                echo XML_Data::shares($share);
        }
        Session::extend($input['auth']);

        return true;
    }
}
