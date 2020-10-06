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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

final class PodcastCreateMethod
{
    /**
     * podcast_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     * @return boolean
     */
    public static function podcast_create($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'podcast_create', $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('url', 'catalog'), 'podcast_create')) {
            return false;
        }
        $data            = array();
        $data['feed']    = urldecode($input['url']);
        $data['catalog'] = $input['catalog'];
        $podcast         = \Podcast::create($data, true);
        if ($podcast) {
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::podcasts(array($podcast));
                    break;
                default:
                    echo XML_Data::podcasts(array($podcast));
            }
        } else {
            Api::message('error', T_('Failed: podcast was not created.'), '400', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
