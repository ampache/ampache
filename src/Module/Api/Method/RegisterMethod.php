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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\User\Registration;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class RegisterMethod
 * @package Lib\ApiMethods
 */
final class RegisterMethod
{
    public const ACTION = 'register';

    /**
     * register
     * MINIMUM_API_VERSION=6.0.0
     *
     * Register a new user.
     * Requires the username, password and email.
     *
     * @param array $input
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password)
     * email    = (string) $email
     */
    public static function register(array $input): bool
    {
        if (!AmpConfig::get('allow_public_registration', false)) {
            Api::error(T_('Enable: allow_public_registration'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('username', 'password', 'email'), self::ACTION)) {
            return false;
        }
        $username             = $input['username'];
        $fullname             = $input['fullname'] ?? $username;
        $email                = urldecode($input['email']);
        $password             = $input['password'];
        $disable              = (bool)AmpConfig::get('admin_enable_required');
        $access               = User::access_name_to_level(AmpConfig::get('auto_user') ?? 'guest');
        $catalog_filter_group = 0;
        $user_id              = User::create($username, $fullname, $email, '', $password, $access, $catalog_filter_group, '', '', $disable, true);

        if ($user_id > 0) {
            if (!AmpConfig::get('user_no_email_confirm', false)) {
                $client     = new User($user_id);
                $validation = md5(uniqid((string) rand(), true));
                $client->update_validation($validation);

                // Notify user and/or admins
                Registration::send_confirmation($username, $fullname, $email, '', $validation);
            }
            $text = 'successfully created: ' . $username;
            if (AmpConfig::get('admin_enable_required')) {
                $text = T_('Please wait for an administrator to activate your account');
            }
            Api::message($text, $input['api_format']);
            Catalog::count_table('user');

            return true;
        }

        $userRepository = static::getUserRepository();

        if ($userRepository->idByUsername($username) > 0) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $username), '4710', self::ACTION, 'username', $input['api_format']);

            return false;
        }
        if ($userRepository->idByEmail($email) > 0) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $email), '4710', self::ACTION, 'email', $input['api_format']);

            return false;
        }
        Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

        return false;
    }

    /**
     * @todo Inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
