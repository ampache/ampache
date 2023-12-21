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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;

/**
 * Class PlaylistAddSong5Method
 */
final class PlaylistAddSong5Method
{
    public const ACTION = 'playlist_add_song';

    /**
     * playlist_add_song
     * MINIMUM_API_VERSION=380001
     *
     * This adds a song to a playlist
     *
     * filter = (string) UID of playlist
     * song   = (string) UID of song to add to playlist
     * check  = (integer) 0,1 Check for duplicates //optional, default = 0
     */
    public static function playlist_add_song(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('filter', 'song'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        $song     = $input['song'];
        if (!$playlist->has_access($user->id) && $user->access !== 100) {
            Api5::error(T_('Require: 100'), ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);

            return false;
        }
        if ((AmpConfig::get('unique_playlist') || (array_key_exists('check', $input) && (int)$input['check'] == 1)) && in_array($song, $playlist->get_songs())) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $song), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'duplicate', $input['api_format']);

            return false;
        }
        $playlist->add_songs(array($song));
        Api5::message('song added to playlist', $input['api_format']);

        return true;
    }
}
