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
use Podcast;
use Session;
use User;

/**
 * Class UpdatePodcastMethod
 * @package Lib\ApiMethods
 */
final class UpdatePodcastMethod
{
    private const ACTION = 'update_podcast';

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
    public static function update_podcast(array $input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 50, User::get_from_username(Session::username($input['auth']))->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new Podcast($object_id);
        if ($podcast->id > 0) {
            if ($podcast->sync_episodes(true)) {
                Api::message('Synced episodes for podcast: ' . (string) $object_id, $input['api_format']);
                Session::extend($input['auth']);
            } else {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Bad Request: %s'), $object_id), '4710', self::ACTION, 'podcast', $input['api_format']);
            }
        } else {
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
