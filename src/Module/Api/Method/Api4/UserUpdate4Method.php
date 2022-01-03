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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Session;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\Mailer;

/**
 * Class UserUpdate4Method
 */
final class UserUpdate4Method
{
    public const ACTION = 'user_update';

    /**
     * user_update
     * MINIMUM_API_VERSION=400001
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * @param array $input
     * username   = (string) $username
     * password   = (string) hash('sha256', $password)) //optional
     * fullname   = (string) $fullname //optional
     * email      = (string) $email //optional
     * website    = (string) $website //optional
     * state      = (string) $state //optional
     * city       = (string) $city //optional
     * disable    = (integer) 0,1 true to disable, false to enable //optional
     * maxbitrate = (integer) $maxbitrate //optional
     * @return boolean
     */
    public static function user_update(array $input): bool
    {
        if (!Api4::check_access('interface', 100, User::get_from_username(Session::username($input['auth']))->id, 'user_update', $input['api_format'])) {
            return false;
        }
        if (!Api4::check_parameter($input, array('username'), 'user_update')) {
            return false;
        }
        $username   = $input['username'];
        $password   = $input['password'] ?? null;
        $fullname   = $input['fullname'] ?? null;
        $email      = $input['email'] ?? null;
        $website    = $input['website'] ?? null;
        $state      = $input['state'] ?? null;
        $city       = $input['city'] ?? null;
        $disable    = $input['disable'] ?? null;
        $maxbitrate = $input['maxbitrate'] ?? null;

        // identify the user to modify
        $user    = User::get_from_username($username);
        $user_id = $user->id;

        if ($password && Access::check('interface', 100, $user_id)) {
            Api4::message('error', 'Do not update passwords for admin users! ' . $username, '400', $input['api_format']);

            return false;
        }

        $userStateToggler = static::getUserStateToggler();

        if ($user_id > 0) {
            if ($password && !AmpConfig::get('simple_user_mode')) {
                $user->update_password('', $password);
            }
            if ($fullname) {
                $user->update_fullname($fullname);
            }
            if (Mailer::validate_address($email)) {
                $user->update_email($email);
            }
            if ($website) {
                $user->update_website($website);
            }
            if ($state) {
                $user->update_state($state);
            }
            if ($city) {
                $user->update_city($city);
            }
            if ($disable === '1') {
                $userStateToggler->disable($user);
            } elseif ($disable === '0') {
                $userStateToggler->enable($user);
            }
            if ((int) $maxbitrate > 0) {
                Preference::update('transcode_bitrate', $user_id, $maxbitrate);
            }
            Api4::message('success', 'successfully updated: ' . $username, null, $input['api_format']);

            return true;
        }
        Api4::message('error', 'failed to update: ' . $username, '400', $input['api_format']);

        return false;
    } // user_update

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserStateToggler(): UserStateTogglerInterface
    {
        global $dic;

        return $dic->get(UserStateTogglerInterface::class);
    }
}
