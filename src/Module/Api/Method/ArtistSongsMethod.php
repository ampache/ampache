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

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\SongRepositoryInterface;

/**
 * Class ArtistSongsMethod
 * @package Lib\ApiMethods
 */
final class ArtistSongsMethod
{
    public const ACTION = 'artist_songs';

    /**
     * artist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of the specified artist
     *
     * filter = (string) UID of Artist
     * top50  = (integer) 0,1, if true filter to the artist top 50 //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function artist_songs(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $artist    = new Artist($object_id);
        if ($artist->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $results = (array_key_exists('top50', $input) && (int)$input['top50'] == 1)
            ? static::getSongRepository()->getTopSongsByArtist($artist)
            : static::getSongRepository()->getByArtist($object_id);
        if (empty($results)) {
            Api::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::songs($results, $user);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::songs($results, $user);
        }

        return true;
    }

    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
