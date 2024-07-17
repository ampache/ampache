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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;

/**
 * Class PlaylistSongsMethod
 * @package Lib\ApiMethods
 */
final class PlaylistHashMethod
{
    public const ACTION = 'playlist_check_hash';

    /**
     * playlist_check_hash
     * MINIMUM_API_VERSION=6.6.0
     *
     * This returns the md5 hash for the songs in a playlist
     *
     * filter = (string) UID of playlist
     */
    public static function playlist_songs(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        $object_id = $input['filter'];
        $playlist  = ((int) $object_id === 0)
            ? new Search((int) str_replace('smart_', '', $object_id), 'song', $user)
            : new Playlist((int) $object_id);

        if ($playlist->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        if (
            $playlist->type !== 'public' &&
            !$playlist->has_collaborate($user)
        ) {
            Api::error('Require: 100', ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);

            return false;
        }

        debug_event(self::class, 'User ' . $user->id . ' loading playlist: ' . $object_id, 5);
        $items = $playlist->get_items();
        if (empty($items)) {
            $results = [
                'md5' => null
            ];
        } else {
            $results = [
                'md5' => md5(serialize($items))
            ];
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::keyed_array($results);
        }

        return true;
    }
}
