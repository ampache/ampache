<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Podcast;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\User;

/**
 * Class PodcastEpisodesMethod
 * @package Lib\ApiMethods
 */
final class PodcastEpisodesMethod
{
    public const ACTION = 'podcast_episodes';

    /**
     * podcast_episodes
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast
     *
     * filter = (string) UID of podcast
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function podcast_episodes(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api::error(T_('Enable: podcast'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new Podcast($object_id);
        if (!$podcast->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $results = $podcast->get_episodes();
        if (empty($results)) {
            Api::empty('podcast_episode', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::podcast_episodes($results, $user);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::podcast_episodes($results, $user);
        }

        return true;
    }
}
