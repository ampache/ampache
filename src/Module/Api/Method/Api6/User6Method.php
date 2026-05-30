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
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class User6Method
 * @package Lib\Api6Methods
 */
final class User6Method
{
    public const string ACTION = 'user';

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string) $username
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function user(array $input, User $user): bool
    {
        $username = $input['filter'] ?? $input['username'] ?? null;

        // if the username is omitted, use the current users context to retrieve its own data
        if ($username === null) {
            $check_user = $user;

            $fullinfo = true;
        } else {
            $userRepository = self::getUserRepository();
            $check_user     = (is_numeric($username))
                ? $userRepository->findById((int)$username)
                : $userRepository->findByUsername((string) $username);
            if (
                $check_user === null ||
                !in_array($check_user->getId(), $userRepository->getValid(true))
            ) {
                /* HINT: Requested object string/id/type */
                Api6::error(sprintf('Not Found: %s', $username), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'username', $input['api_format']);

                return false;
            }

            // get full info when you're an admin or searching for yourself
            $fullinfo = $check_user->getId() === $user->getId() || $user->access === AccessLevelEnum::ADMIN->value;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json6_Data::user($check_user, $fullinfo, $input['auth'], false);
                break;
            default:
                echo Xml6_Data::user($check_user, $fullinfo, $input['auth']);
        }

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
