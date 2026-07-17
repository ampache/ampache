<?php

declare(strict_types=1);

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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;

/**
 * Class Album3Method
 */
final class Album3Method
{
    public const ACTION = 'album';

    /**
     * album
     * This returns a single album based on the UID provided
     *
     * @param array{
     *     filter: string,
     *     include?: string|string[],
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function album(array $input, User $user): void
    {
        if (!array_key_exists('filter', $input) || (string) $input['filter'] === '') {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            echo Xml3_Data::error(400, sprintf(T_('Bad Request: %s'), 'filter'));

            return;
        }
        $uid   = (int) scrub_in((string) $input['filter']);
        $album = new Album($uid);
        if ($album->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            echo Xml3_Data::error(404, sprintf(T_('Not Found: %s'), $uid));

            return;
        }
        $include = [];
        if (array_key_exists('include', $input)) {
            if (is_array($input['include'])) {
                foreach ($input['include'] as $item) {
                    if ($item === 'songs' || $item == '1') {
                        $include[] = 'songs';
                    }
                }
            } elseif ($input['include'] === 'songs' || $input['include'] == '1') {
                $include[] = 'songs';
            }
        }
        echo Xml3_Data::albums([$uid], $include, $user, $input['auth']);
    }
}
