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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\Mailer;

/**
 * Class UserUpdate5Method
 */
final class UserUpdate5Method
{
    public const ACTION = 'user_update';

    /**
     * user_update
     * MINIMUM_API_VERSION=400001
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * username   = (string) $username
     * password   = (string) hash('sha256', $password)) //optional
     * fullname   = (string) $fullname //optional
     * email      = (string) $email //optional
     * website    = (string) $website //optional
     * state      = (string) $state //optional
     * city       = (string) $city //optional
     * disable    = (integer) 0,1 true to disable, false to enable //optional
     * maxbitrate = (integer) $maxbitrate //optional
     */
    public static function user_update(array $input, User $user): bool
    {
        if (!Api5::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api5::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username   = $input['username'];
        $password   = $input['password'] ?? null;
        $fullname   = $input['fullname'] ?? null;
        $email      = (array_key_exists('email', $input)) ? urldecode($input['email']) : null;
        $website    = $input['website'] ?? null;
        $state      = $input['state'] ?? null;
        $city       = $input['city'] ?? null;
        $disable    = $input['disable'] ?? null;
        $maxbitrate = (int)($input['maxBitRate'] ?? 0);

        // identify the user to modify
        $update_user = User::get_from_username($username);
        if ($update_user === null) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $username), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'username', $input['api_format']);

            return false;
        }

        if ($password && $update_user->access == 100) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $username), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $user_id = $update_user->getId();
        if ($user_id > 0) {
            if ($password && !AmpConfig::get('simple_user_mode')) {
                $update_user->update_password('', $password);
            }
            if ($fullname) {
                $update_user->update_fullname($fullname);
            }
            if ($email && Mailer::validate_address($email)) {
                $update_user->update_email($email);
            }
            if ($website) {
                $update_user->update_website($website);
            }
            if ($state) {
                $update_user->update_state($state);
            }
            if ($city) {
                $update_user->update_city($city);
            }
            $userStateToggler = static::getUserStateToggler();
            if ($disable === '1') {
                $userStateToggler->disable($update_user);
            } elseif ($disable === '0') {
                $userStateToggler->enable($update_user);
            }
            if ($maxbitrate > 0) {
                Preference::update('transcode_bitrate', $user_id, $maxbitrate);
            }
            Api5::message('successfully updated: ' . $username, $input['api_format']);

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api5::error(sprintf(T_('Bad Request: %s'), $username), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

        return false;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserStateToggler(): UserStateTogglerInterface
    {
        global $dic;

        return $dic->get(UserStateTogglerInterface::class);
    }
}
