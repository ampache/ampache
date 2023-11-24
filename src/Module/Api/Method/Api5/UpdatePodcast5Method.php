<?php

/**
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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\System\Session;

/**
 * Class UpdatePodcast5Method
 */
final class UpdatePodcast5Method
{
    public const ACTION = 'update_podcast';

    /**
     * update_podcast
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * filter = (string) UID of podcast
     */
    public static function update_podcast(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }

        if (!Api5::check_access('interface', 50, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new Podcast($object_id);
        if ($podcast->id > 0) {
            if ($podcast->sync_episodes(true)) {
                Api5::message('Synced episodes for podcast: ' . (string) $object_id, $input['api_format']);
                Session::extend($input['auth'], 'api');
            } else {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api5::error(sprintf(T_('Bad Request: %s'), $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'podcast', $input['api_format']);
            }
        } else {
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);
        }

        return true;
    }
}
