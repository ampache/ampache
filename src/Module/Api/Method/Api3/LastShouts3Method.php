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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;

/**
 * Class LastShouts3Method
 */
final class LastShouts3Method
{
    public const ACTION = 'last_shouts';

    /**
     * last_shouts
     * This get the latest posted shouts
     */
    public static function last_shouts(array $input, User $user): void
    {
        unset($user);
        $limit = (int)($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold');
        }
        if (AmpConfig::get('sociable')) {
            if (!empty($input['username'])) {
                $username = $input['username'];
            } else {
                $username = null;
            }

            $results = self::getShoutRepository()->getTop($limit, $username);

            ob_end_clean();
            echo Xml3_Data::shouts($results);
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
    }

    /**
     * @todo inject by constructor
     */
    private static function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }
}
