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

final class PodcastMethod
{
    /**
     * podcast
     * MINIMUM_API_VERSION=420000
     *
     * Get the podcast from it's id.
     *
     * @param array $input
     * filter  = (integer) Podcast ID number
     * include = (string) 'episodes' (include episodes in the response) //optional
     * @return boolean
     */
    public static function podcast($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), 'podcast')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new \Podcast($object_id);
        if ($podcast->id) {
            $episodes = $input['include'] == 'episodes';

            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::podcasts(array($object_id), $episodes);
                    break;
                default:
                    echo XML_Data::podcasts(array($object_id), $episodes);
            }
        } else {
            Api::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
