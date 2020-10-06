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
use JSON_Data;
use Session;
use User;
use XML_Data;

final class PodcastEpisodesMethod
{
    /**
     * podcast_episodes
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast
     *
     * @param array $input
     * filter = (string) UID of podcast
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function podcast_episodes($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), 'podcast_episodes')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        $uid  = (int) scrub_in($input['filter']);
        debug_event('api.class', 'User ' . $user->id . ' loading podcast: ' . $input['filter'], 5);
        $podcast = new \Podcast($uid);
        $items   = $podcast->get_episodes();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::podcast_episodes($items);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::podcast_episodes($items);
        }
        Session::extend($input['auth']);

        return true;
    }
}
