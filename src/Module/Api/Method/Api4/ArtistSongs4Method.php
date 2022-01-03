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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;
use Ampache\Repository\SongRepositoryInterface;

/**
 * Class ArtistSongs4Method
 */
final class ArtistSongs4Method
{
    public const ACTION = 'artist_songs';

    /**
     * artist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of the specified artist
     *
     * @param array $input
     * filter = (string) UID of Artist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function artist_songs(array $input): bool
    {
        if (!Api4::check_parameter($input, array('filter'), 'artist_songs')) {
            return false;
        }
        $artist = new Artist($input['filter']);
        $songs  = static::getSongRepository()->getByArtist($artist->id);
        $user   = User::get_from_username(Session::username($input['auth']));

        if (!empty($songs)) {
            ob_end_clean();
            switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::songs($songs, $user->id);
            break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::songs($songs, $user->id);
            }
        }

        return true;
    } // artist_songs

    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
