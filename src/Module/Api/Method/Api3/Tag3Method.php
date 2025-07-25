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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class Tag3Method
 */
final class Tag3Method
{
    public const ACTION = 'tag';

    /**
     * tag
     * This returns a single tag based on UID
     * @param array<string, mixed> $input
     */
    public static function tag(array $input, User $user): void
    {
        unset($user);
        $uid = scrub_in((string) $input['filter']);
        ob_end_clean();
        echo Xml3_Data::tags([(int)$uid]);
    }

    /**
     * genre
     * This returns a single tag based on UID
     * @param array<string, mixed> $input
     */
    public static function genre(array $input, User $user): void
    {
        self::tag($input, $user);
    }
}
