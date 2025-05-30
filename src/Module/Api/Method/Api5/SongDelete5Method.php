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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Song\Deletion\SongDeleterInterface;

/**
 * Class SongDelete5Method
 */
final class SongDelete5Method
{
    public const ACTION = 'song_delete';

    /**
     * song_delete
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete an existing song.
     *
     * filter = (string) UID of song to delete
     *
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function song_delete(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        $object_id = (int) $input['filter'];
        $song      = new Song($object_id);
        if ($song->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        if (!Catalog::can_remove($song, $user->id)) {
            Api5::error(T_('Require: 75'), ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);

            return false;
        }
        if (self::getSongDeleter()->delete($song)) {
            Api5::message('song ' . $object_id . ' deleted', $input['api_format']);
            Catalog::count_table('song');
        } else {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);
        }

        return true;
    }

    /**
     * @deprecated
     */
    public static function getSongDeleter(): SongDeleterInterface
    {
        global $dic;

        return $dic->get(SongDeleterInterface::class);
    }
}
