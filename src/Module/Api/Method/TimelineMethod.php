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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * Class TimelineMethod
 * @package Lib\ApiMethods
 */
final class TimelineMethod
{
    public const ACTION = 'timeline';

    /**
     * timeline
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string)
     * limit    = (integer) //optional
     * since    = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     limit?: int,
     *     since?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function timeline(array $input, User $user): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api::error('Enable: sociable', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $input['username'] = $input['filter'] ?? $input['username'] ?? null;
        if (!Api::check_parameter($input, ['username'], self::ACTION)) {
            return false;
        }

        $username = $input['username'];
        $leadUser = (is_numeric($username))
            ? User::get_from_id((int)$username)
            : User::get_from_username((string)$username);
        if (!empty($leadUser)) {
            $limit = (int)($input['limit'] ?? 0);
            $since = (int)($input['since'] ?? 0);
            if (
                $leadUser->getId() === $user->getId() ||
                Preference::get_by_user($leadUser->getId(), 'allow_personal_info_recent')
            ) {
                $results = self::getUseractivityRepository()->getActivities(
                    $leadUser->getId(),
                    $limit,
                    $since
                );
                ob_end_clean();
                switch ($input['api_format']) {
                    case 'json':
                        echo Json8_Data::timeline($results);
                        break;
                    default:
                        echo Xml8_Data::timeline($results);
                }
            }
        }

        return true;
    }

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
