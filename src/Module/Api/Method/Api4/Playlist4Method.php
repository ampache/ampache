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
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

/**
 * Class Playlist4Method
 */
final class Playlist4Method
{
    public const ACTION = 'playlist';

    /**
     * playlist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single playlist
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
    public static function playlist(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $list_id = scrub_in((string) $input['filter']);

        if (str_replace('smart_', '', $list_id) === $list_id) {
            // Playlists
            $playlist = new Playlist((int) $list_id);
        } else {
            // Smartlists
            $playlist = new Search((int) str_replace('smart_', '', $list_id), 'song', $user);
        }
        if ($playlist->isNew()) {
            Api4::message('error', T_('Library item not found'), '404', $input['api_format']);

            return false;
        }
        if (
            $playlist->type !== 'public' &&
            !$playlist->has_collaborate($user)
        ) {
            Api4::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::playlists([$list_id], $user, $input['auth']);
                break;
            default:
                echo Xml4_Data::playlists([$list_id], $user, $input['auth']);
        }

        return true;
    }
}
