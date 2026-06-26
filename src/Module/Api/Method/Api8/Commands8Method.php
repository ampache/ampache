<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;

/**
 * Class Commands8Method
 * @package Lib\Api8Methods
 */
final class Commands8Method
{
    public const string ACTION = 'commands';

    /**
     * browse
     * MINIMUM_API_VERSION=8.0.0
     *
     * Run commands on restful path objects.
     *
     * action = 'catalog_action', 'command', 'flag', 'localplay', 'player', 'playlist_add', 'playlist_remove', 'playlist_remove_song', 'podcast_update', 'rate', 'record_play', 'share', 'toggle_follow', 'update_art', 'update_from_tags'', '     
     * filter = (string) object_id
     * type = (string) 'album', 'artist', 'catalog', 'localplay', 'playlist', 'podcast_episode', 'podcast', 'song', 'user', 'video'
     *
     * @param array{
     *     action: string,
     *     filter: string,
     *     type: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function commands(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['action', 'filter', 'type'], self::ACTION)) {
            return false;
        }

        $action      = $input['action'];
        $object_id   = (int) $input['filter'];
        $object_type = $input['type'];

        if (!AmpConfig::get('podcast') && ($object_type == 'podcast' || $object_type = 'podcast_episode')) {
            Api::error(ErrorCodeEnum::ACCESS_DENIED, 'Enable: podcast', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        // confirm the correct data
        if (!in_array(strtolower($object_type), ['album', 'artist', 'catalog', 'localplay', 'playlist', 'podcast_episode', 'podcast', 'song', 'user', 'video'])) {
            Api::error(ErrorCodeEnum::BAD_REQUEST, sprintf('Bad Request: %s', $object_type), self::ACTION, 'type', $input['api_format']);

            return false;
        }

        // todo fill in commands and output

        return true;
    }
}
