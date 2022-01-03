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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
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
     * @param array $input
     * id       = (string) $object_id
     * type     = (string) 'song', 'artist', 'album', 'playlist', 'search', 'podcast')
     * fallback = (integer) 0,1, if true return default art ('blankalbum.png') //optional
     * @return boolean
     */
    public static function get_art(array $input): bool
    {
        if (!Api::check_parameter($input, array('id', 'type'), self::ACTION)) {
            http_response_code(400);

            return false;
        }
        $object_id = (int) $input['id'];
        $type      = (string) $input['type'];
        $size      = $input['size'] ?? false;
        $user      = User::get_from_username(Session::username($input['auth']));
        $fallback  = (array_key_exists('fallback', $input) && (int)$input['fallback'] == 1);

        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist', 'search', 'podcast'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

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
            $art       = new Art($item['object_id'], $item['object_type']);
            if (!Art::has_db($object_id, 'song')) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'playlist') {
            if (!Art::has_db($object_id, $type)) {
                $playlist  = new Playlist($object_id);
                $listitems = $playlist->get_items();
                $item      = $listitems[array_rand($listitems)];
                $song      = new Song($item['object_id']);
                $art       = new Art($song->album, 'album');
            }
        }

        if ($art->has_db_info($fallback)) {
            header('Access-Control-Allow-Origin: *');
            if ($size && AmpConfig::get('resize_images')) {
                $dim           = array();
                $dim['width']  = $size;
                $dim['height'] = $size;
                $thumb         = $art->get_thumb($dim);
                if (!empty($thumb)) {
                    header('Content-type: ' . $thumb['thumb_mime']);
                    header('Content-Length: ' . strlen((string) $thumb['thumb']));
                    echo $thumb['thumb'];

                    return true;
                }
            }

            header('Content-type: ' . $art->raw_mime);
            header('Content-Length: ' . strlen((string) $art->raw));
            echo $art->raw;
            Session::extend($input['auth']);

            return true;
        }
        // art not found
        http_response_code(404);

        return false;
    }
}
