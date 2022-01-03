<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Repository\UserFollowerRepositoryInterface;

/**
 * Class Followers4Method
 */
final class Followers4Method
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
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function followers(array $input): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api4::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('username'), 'followers')) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                $users = static::getUserFollowerRepository()->getFollowers($user->id);
                if (!count($users)) {
                    Api4::message('error', 'User `' . $username . '` has no followers.', '400', $input['api_format']);
                } else {
                    ob_end_clean();
                    switch ($input['api_format']) {
                        case 'json':
                            echo Json4_Data::users($users);
                        break;
                        default:
                            echo Xml4_Data::users($users);
                    }
                }
            } else {
                debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
                Api4::message('error', 'User `' . $username . '` cannot be found.', '400', $input['api_format']);
            }
        }

        return true;
    } // followers

    /**
     * @deprecated inject by constructor
     */
    private static function getUserFollowerRepository(): UserFollowerRepositoryInterface
    {
        global $dic;

        return $dic->get(UserFollowerRepositoryInterface::class);
    }
}
