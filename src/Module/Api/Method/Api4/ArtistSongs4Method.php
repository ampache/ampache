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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
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
     * filter = (string) UID of Artist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter: string,
     *     top50?: int,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function artist_songs(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $artist  = new Artist((int)$input['filter']);
        $results = self::getSongRepository()->getByArtist($artist->id);

        if (!empty($results)) {
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    Json4_Data::set_offset($input['offset'] ?? 0);
                    Json4_Data::set_limit($input['limit'] ?? 0);
                    echo Json4_Data::songs($results, $user);
                    break;
                default:
                    Xml4_Data::set_offset($input['offset'] ?? 0);
                    Xml4_Data::set_limit($input['limit'] ?? 0);
                    echo Xml4_Data::songs($results, $user);
            }
        }

        return true;
    }

    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
