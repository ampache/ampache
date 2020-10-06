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
use XML_Data;

final class PodcastEpisodeMethod
{
    /**
     * podcast_episode
     * MINIMUM_API_VERSION=420000
     *
     * Get the podcast_episode from it's id.
     *
     * @param array $input
     * filter  = (integer) podcast_episode ID number
     * @return boolean
     */
    public static function podcast_episode($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), 'podcast_episode')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $episode   = new \Podcast_Episode($object_id);
        if ($episode->id > 0) {
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::podcast_episodes(array($object_id));
                    break;
                default:
                    echo XML_Data::podcast_episodes(array($object_id));
            }
        } else {
            Api::message('error', 'podcast_episode ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
