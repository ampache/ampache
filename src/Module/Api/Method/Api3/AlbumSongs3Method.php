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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\System\Session;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class AlbumSongs3Method
 */
class AlbumSongs3Method
{
    public const ACTION = 'album_songs';

    /**
     * album_songs
     * This returns the songs of a specified album
     * @param array $input
     */
    public static function album_songs(array $input)
    {
        $album = new Album($input['filter']);
        $songs = static::getAlbumRepository()->getSongs($album->id);
        $user  = User::get_from_username(Session::username($input['auth']));

        // Set the offset
        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);

        ob_end_clean();
        echo Xml3_Data::songs($songs, $user->id);
    } // album_songs

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }
}
