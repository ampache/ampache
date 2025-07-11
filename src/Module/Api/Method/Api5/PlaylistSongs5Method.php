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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class PlaylistSongs5Method
 */
final class PlaylistSongs5Method
{
    public const ACTION = 'playlist_songs';

    /**
     * playlist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs for a playlist
     *
     * filter = (string) UID of playlist
     * random = (integer) 0,1, if true get random songs using limit //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter: string,
     *     random?: int,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function playlist_songs(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $object_id = $input['filter'];
        $random    = (array_key_exists('random', $input) && (int)$input['random'] == 1);
        $playlist  = ((int) $object_id === 0)
            ? new Search((int) str_replace('smart_', '', $object_id), 'song', $user)
            : new Playlist((int) $object_id);

        if ($playlist->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        if (
            $playlist->type !== 'public' &&
            !$playlist->has_collaborate($user)
        ) {
            Api5::error(T_('Require: 100'), ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);

            return false;
        }

        debug_event(self::class, 'User ' . $user->id . ' loading playlist: ' . $object_id, 5);
        $items = ($random)
            ? $playlist->get_random_items()
            : $playlist->get_items();
        if (empty($items)) {
            Api5::empty('song', $input['api_format']);

            return false;
        }
        $results = [];
        foreach ($items as $object) {
            if ($object['object_type'] === LibraryItemEnum::SONG) {
                $results[] = $object['object_id'];
            }
        } // end foreach

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json5_Data::set_offset($input['offset'] ?? 0);
                Json5_Data::set_limit($input['limit'] ?? 0);
                echo Json5_Data::songs($results, $user, $input['auth']);
                break;
            default:
                Xml5_Data::set_offset($input['offset'] ?? 0);
                Xml5_Data::set_limit($input['limit'] ?? 0);
                echo Xml5_Data::songs($results, $user, $input['auth']);
        }

        return true;
    }
}
