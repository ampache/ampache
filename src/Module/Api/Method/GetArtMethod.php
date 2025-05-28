<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\System\Session;

/**
 * Class GetArtMethod
 * @package Lib\ApiMethods
 */
final class GetArtMethod
{
    public const ACTION = 'get_art';

    /**
     * get_art
     * MINIMUM_API_VERSION=400001
     *
     * Get an art image.
     *
     * id       = (string) $object_id
     * type     = (string) 'song', 'artist', 'album', 'label', 'live_stream', 'playlist', 'podcast', 'search', 'user', 'video'
     * fallback = (integer) 0,1, if true return default art ('blankalbum.png') //optional
     * size     = (string) width x height ('640x480', 'original') //optional
     *
     * @param array{
     *     id: string,
     *     type: string,
     *     fallback?: int,
     *     size?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function get_art(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['id', 'type'], self::ACTION)) {
            http_response_code(400);

            return false;
        }

        $type = (string) $input['type'];
        if ($type == 'video' && !AmpConfig::get('allow_video')) {
            Api::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        if ($type == 'label' && !AmpConfig::get('label')) {
            Api::error('Enable: label', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        if ($type == 'podcast' && !AmpConfig::get('podcast')) {
            Api::error('Enable: podcast', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $object_id = (int) $input['id'];
        $size      = (string)($input['size'] ?? 'original');
        $fallback  = (array_key_exists('fallback', $input) && (int)$input['fallback'] == 1);

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'artist', 'album', 'label', 'live_stream', 'playlist', 'podcast', 'search', 'user', 'video'])) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $art = new Art($object_id, $type);
        if ($type == 'song') {
            if (!Art::has_db($object_id, $type)) {
                // in most cases the song doesn't have a picture, but the album where it belongs to has
                // if this is the case, we take the album art
                $song = new Song($object_id);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'search') {
            $smartlist = new Search($object_id, 'song', $user);
            $listitems = $smartlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $art       = new Art($item['object_id'], $item['object_type']->value);
            if (!Art::has_db($object_id, 'song')) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'playlist' && !Art::has_db($object_id, $type)) {
            $playlist  = new Playlist($object_id);
            $listitems = $playlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $song      = new Song($item['object_id']);
            $art       = new Art($song->album, 'album');
        }

        Session::extend($input['auth'], AccessTypeEnum::API->value);

        if (
            preg_match('/^[0-9]+x[0-9]+$/', $size) &&
            !$art->has_db_info($size, $fallback)
        ) {
            $size = 'original';
        }

        return $art->show($size, $fallback);
    }
}
