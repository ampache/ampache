<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class LastShouts3Method
 */
final class LastShouts3Method
{
    public const ACTION = 'last_shouts';

    /**
     * last_shouts
     * This get the latest posted shouts
     * @param array $input
     * @param User $user
     */
    public static function last_shouts(array $input, User $user)
    {
        unset($user);
        $limit = (int)($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold');
        }
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $results = Shoutbox::get_top($limit, $username);
            } else {
                $results = Shoutbox::get_top($limit);
            }

            ob_end_clean();
            echo Xml3_Data::shouts($results);
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
    } // last_shouts
}
