<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\User;

/**
 * Class DeletedPodcastEpisodes5Method
 */
final class DeletedPodcastEpisodes5Method
{
    public const ACTION = 'deleted_podcast_episodes';

    /**
     * deleted_podcast_episodes
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast that have been deleted
     *
     * @param array $input
     * @param User $user
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function deleted_podcast_episodes(array $input, User $user): bool
    {
        unset($user);
        if (!AmpConfig::get('podcast')) {
            Api5::error(T_('Enable: podcast'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $items = Podcast_Episode::get_deleted();
        if (empty($items)) {
            Api5::empty('deleted_podcast_episodes', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json5_Data::set_offset($input['offset'] ?? 0);
                Json5_Data::set_limit($input['limit'] ?? 0);
                echo Json5_Data::deleted('podcast_episode', $items);
                break;
            default:
                Xml5_Data::set_offset($input['offset'] ?? 0);
                Xml5_Data::set_limit($input['limit'] ?? 0);
                echo Xml5_Data::deleted('podcast_episode', $items);
        }

        return true;
    }
}
