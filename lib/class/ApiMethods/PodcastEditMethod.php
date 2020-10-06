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

final class PodcastEditMethod
{
    /**
     * podcast_edit
     * MINIMUM_API_VERSION=420000
     * CHANGED_IN_API_VERSION=430000
     * Update the description and/or expiration date for an existing podcast.
     * Takes the podcast id to update with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term
     * feed        = (string) feed url (xml!) //optional
     * title       = (string) title string //optional
     * website     = (string) source website url //optional
     * description = (string) //optional
     * generator   = (string) //optional
     * copyright   = (string) //optional
     * @return boolean
     */
    public static function podcast_edit($input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::message('error', T_('Access Denied: podcast features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        if (!Api::check_access('interface', 50, $user->id, 'podcast_edit', $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('filter'), 'podcast_edit')) {
            return false;
        }
        $podcast_id = $input['filter'];
        $podcast    = new \Podcast($podcast_id);
        if ($podcast->id) {
            $feed           = filter_var($input['feed'], FILTER_VALIDATE_URL) ? $input['feed'] : $podcast->feed;
            $title          = isset($input['title']) ? scrub_in($input['title']) : $podcast->title;
            $website        = filter_var($input['website'], FILTER_VALIDATE_URL) ? scrub_in($input['website']) : $podcast->website;
            $description    = isset($input['description']) ? scrub_in($input['description']) : $podcast->description;
            $generator      = isset($input['generator']) ? scrub_in($input['generator']) : $podcast->generator;
            $copyright      = isset($input['copyright']) ? scrub_in($input['copyright']) : $podcast->copyright;

            $data = array(
                'feed' => $feed,
                'title' => $title,
                'website' => $website,
                'description' => $description,
                'generator' => $generator,
                'copyright' => $copyright
            );
            if ($podcast->update($data)) {
                Api::message('success', 'podcast ' . $podcast_id . ' updated', null, $input['api_format']);
            } else {
                Api::message('error', 'podcast ' . $podcast_id . ' was not updated', '400', $input['api_format']);
            }
        } else {
            Api::message('error', 'podcast ' . $podcast_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
