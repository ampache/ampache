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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

/**
 * Class PlaylistDelete6Method
 * @package Lib\Api6Methods
 */
final class PlaylistDelete6Method
{
    public const ACTION = 'playlist_delete';

    public const REST_ACTION = 'playlists_delete';

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
        if (!Api6::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $playlist = new Playlist((int)$input['filter']);
        if (!$playlist->has_access($user)) {
            Api6::error('Require: 100', ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);
        } else {
            $playlist->delete();
            Api6::message('playlist deleted', $input['api_format']);
            Catalog::count_table('playlist');
        }

        return true;
    }

    /**
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function playlists_delete(array $input, User $user): bool
    {
        return self::playlist_delete($input, $user);
    }
}
