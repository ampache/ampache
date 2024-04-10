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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class PodcastEpisodeDeleteMethod
 * @package Lib\ApiMethods
 */
final class PodcastEpisodeDeleteMethod
{
    public const ACTION = 'podcast_episode_delete';

    /**
     * podcast_episode_delete
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast_episode.
     *
     * filter = (string) UID of podcast_episode to delete
     */
    public static function podcast_episode_delete(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api::error('Enable: podcast', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $episode   = new Podcast_Episode($object_id);
        if ($episode->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }

        if ($episode->remove()) {
            Api::message('podcast_episode ' . $object_id . ' deleted', $input['api_format']);
            Catalog::count_table('podcast_episode');
        } else {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);
        }

        return true;
    }
}
