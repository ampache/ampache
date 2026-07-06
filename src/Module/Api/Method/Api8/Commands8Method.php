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
     * action = 'catalog_action', 'command', 'flag', 'localplay', 'player', 'playlist_add', 'playlist_remove', 'playlist_remove_song', 'podcast_update', 'rate', 'record_play', 'share', 'toggle_follow', 'update_art', 'update_from_tags'
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
        $object_type = $input['type'];

        if (!AmpConfig::get('podcast') && ($object_type == 'podcast' || $object_type == 'podcast_episode')) {
            Api::error(ErrorCodeEnum::ACCESS_DENIED, 'Enable: podcast', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        // confirm the correct data
        if (!in_array(strtolower($object_type), ['album', 'artist', 'catalog', 'localplay', 'playlist', 'podcast_episode', 'podcast', 'song', 'user', 'video'])) {
            Api::error(ErrorCodeEnum::BAD_REQUEST, sprintf('Bad Request: %s', $object_type), self::ACTION, 'type', $input['api_format']);

            return false;
        }

        switch ($action) {
            case 'catalog_action':
                return CatalogAction8Method::action($input, $user);
            //case 'command':
            //    return Command8Method::command($input, $user);
            case 'flag':
                return Flag8Method::flag($input, $user);
            case 'localplay':
                return LocalPlay8Method::localplay($input, $user);
            case 'player':
                return Player8Method::player($input, $user);
            case 'playlist_add':
                return PlaylistAdd8Method::playlist_add($input, $user);
            case 'playlist_remove':
                return PlaylistRemove8Method::playlist_remove($input, $user);
            case 'playlist_remove_song':
                return PlaylistRemoveSong8Method::playlist_remove_song($input, $user);
            case 'podcast_update':
                return PodcastUpdate8Method::podcast_update($input, $user);
            case 'rate':
                return Rate8Method::rate($input, $user);
            case 'record_play':
                return RecordPlay8Method::record_play($input, $user);
            case 'share':
                return Share8Method::share($input, $user);
            case 'toggle_follow':
                return ToggleFollow8Method::toggle_follow($input, $user);
            case 'update_art':
                return UpdateArt8Method::update_art($input, $user);
            case 'update_from_tags':
                return UpdateFromTags8Method::update_from_tags($input, $user);
            default:
                Api::error(ErrorCodeEnum::GENERIC_ERROR, sprintf('Bad Request: %s', $object_type), self::ACTION, 'type', $input['api_format']);

                return false;
        }
    }
}
