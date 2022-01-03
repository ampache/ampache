<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Podcast;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * Class PodcastEpisodes4Method
 */
final class PodcastEpisodes4Method
{
    public const ACTION = 'podcast_episodes';

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
    public static function podcast_episodes(array $input): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('filter'), 'podcast_episodes')) {
            return false;
        }
        $user       = User::get_from_username(Session::username($input['auth']));
        $user_id    = $user->id;
        $podcast_id = (int) scrub_in($input['filter']);
        debug_event(self::class, 'User ' . $user->id . ' loading podcast: ' . $podcast_id, 5);
        $podcast = new Podcast($podcast_id);
        $items   = $podcast->get_episodes();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::podcast_episodes($items, $user_id);
                break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::podcast_episodes($items, $user_id);
        }

        return true;
    } // podcast_episodes
}
