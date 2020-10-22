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
use Podcast_Episode;
use Session;
use XML_Data;

/**
 * Class PodcastEpisodeMethod
 * @package Lib\ApiMethods
 */
final class PodcastEpisodeMethod
{
    private const ACTION = 'podcast_episode';

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
    public static function podcast_episode(array $input)
    {
        if (!AmpConfig::get('podcast')) {
            Api::error(T_('Enable: podcast'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $episode   = new Podcast_Episode($object_id);
        if (!$episode->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::podcast_episodes(array($object_id));
                break;
            default:
                echo XML_Data::podcast_episodes(array($object_id));
        }
        Session::extend($input['auth']);

        return true;
    }
}
