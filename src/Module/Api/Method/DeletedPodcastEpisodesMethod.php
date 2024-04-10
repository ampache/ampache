<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\DeletedPodcastEpisodeRepositoryInterface;
use Ampache\Repository\Model\User;

final class DeletedPodcastEpisodesMethod
{
    public const ACTION = 'deleted_podcast_episodes';

    /**
     * deleted_podcast_episodes
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast that have been deleted
     *
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *  api_format: string,
     *  limit?: string,
     *  offset?: string
     * } $input
     */
    public static function deleted_podcast_episodes(array $input, User $user): bool
    {
        unset($user);
        if (!AmpConfig::get('podcast')) {
            Api::error('Enable: podcast', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $items = iterator_to_array(self::getDeletedPodcastEpisodeRepository()->findAll());
        if ($items === []) {
            Api::empty('deleted_podcast_episodes', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int) ($input['offset'] ?? 0));
                Json_Data::set_limit((int) ($input['limit'] ?? 0));
                echo Json_Data::deleted('podcast_episode', $items);
                break;
            default:
                Xml_Data::set_offset((int) ($input['offset'] ?? 0));
                Xml_Data::set_limit((int) ($input['limit'] ?? 0));
                echo Xml_Data::deleted('podcast_episode', $items);
        }

        return true;
    }

    /**
     * @todo inject dependency
     */
    private static function getDeletedPodcastEpisodeRepository(): DeletedPodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(DeletedPodcastEpisodeRepositoryInterface::class);
    }
}
