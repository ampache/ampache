<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\UserFollowerRepositoryInterface;

/**
 * Class FollowersMethod
 * @package Lib\ApiMethods
 */
final class FollowersMethod
{
    public const ACTION = 'followers';

    /**
     * followers
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * This gets followers of the user
     * Error when user not found or no followers
     *
     * username = (string) $username //optional
     */
    public static function followers(array $input, User $user): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api::error('Enable: sociable', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $username = (isset($input['username']))
            ? $input['username']
            : $user->username;
        $leadUser = User::get_from_username($username);
        if ($leadUser === null) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $username), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'username', $input['api_format']);

            return false;
        }

        $results = static::getUserFollowerRepository()->getFollowers($leadUser);
        if (empty($results)) {
            Api::empty('user', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::users($results);
                break;
            default:
                echo Xml_Data::users($results);
        }

        return true;
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getUserFollowerRepository(): UserFollowerRepositoryInterface
    {
        global $dic;

        return $dic->get(UserFollowerRepositoryInterface::class);
    }
}
