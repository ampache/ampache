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

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class PlaylistDelete4Method
 */
final class PlaylistDelete4Method
{
    public const ACTION = 'playlist_delete';

    /**
     * playlist_delete
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a playlist
     *
     * filter = (string) UID of playlist
     *
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function playlist_delete(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $playlist = new Playlist((int)$input['filter']);
        if (!$playlist->has_access($user)) {
            Api4::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);
        } else {
            $playlist->delete();
            Api4::message('success', 'playlist deleted', null, $input['api_format']);
        }

        return true;
    }
}
