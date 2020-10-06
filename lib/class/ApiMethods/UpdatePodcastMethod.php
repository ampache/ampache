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

use Api;

final class UpdatePodcastMethod
{
    /**
     * update_podcast
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * @param array $input
     * filter = (string) UID of podcast
     * @return boolean
     */
    public static function update_podcast($input)
    {
        if (!Api::check_parameter($input, array('filter'), 'update_podcast')) {
            return false;
        }
        if (!Api::check_access('interface', 50, \User::get_from_username(\Session::username($input['auth']))->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        $object_id = (int) scrub_in($input['filter']);
        $podcast   = new \Podcast($object_id);
        if ($podcast->id > 0) {
            if ($podcast->sync_episodes(true)) {
                Api::message('success', 'Synced episodes for podcast: ' . (string) $object_id, null, $input['api_format']);
                \Session::extend($input['auth']);
            } else {
                Api::message('error', T_('Failed to sync episodes for podcast: ' . (string) $object_id), '400', $input['api_format']);
            }
        } else {
            Api::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        \Session::extend($input['auth']);

        return true;
    }
}
