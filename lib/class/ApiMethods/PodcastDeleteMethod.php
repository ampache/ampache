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

use AmpConfig;
use Api;
use Session;
use User;

final class PodcastDeleteMethod
{
    /**
     * podcast_delete
     *
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast.
     *
     * @param array $input
     * filter = (string) UID of podcast to delete
     * @return boolean
     */
    public static function podcast_delete($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        if (!Api::check_access('interface', 75, $user->id, 'podcast_delete', $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('filter'), 'podcast_delete')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new \Podcast($object_id);
        if ($podcast->id) {
            if ($podcast->remove()) {
                Api::message('success', 'podcast ' . $object_id . ' deleted', null, $input['api_format']);
            } else {
                Api::message('error', 'podcast ' . $object_id . ' was not deleted', '400', $input['api_format']);
            }
        } else {
            Api::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
