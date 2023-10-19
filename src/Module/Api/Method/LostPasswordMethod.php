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

use Ampache\Module\User\NewPasswordSenderInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\System\Core;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class LostPasswordMethod
 * @package Lib\ApiMethods
 */
final class LostPasswordMethod
{
    public const ACTION = 'lost_password';

    /**
     * lost_password
     * MINIMUM_API_VERSION=6.1.0
     *
     * Allows a non-admin user to reset their password without web access to the main site.
     * It requires a reset token hash using your username and email
     *
     * @param array $input
     * token = (string) (
     *   $username;
     *   $key = hash('sha256', 'email');
     *   token = hash('sha256', $username . $key);
     * )
     * @return boolean
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function handshake(array $input): bool
    {
        if (!Mailer::is_mail_enabled()) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);
        }
        if (!Api::check_parameter($input, array('token'), self::ACTION)) {
            return false;
        }
        // identify the user to modify
        $user_id     = User::id_from_token($input['token']);
        $update_user = new User($user_id);

        if ($user_id > 0) {
            // no resets for admin users
            if ($update_user->access == 100) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Bad Request: %s'), $user_id), '4710', self::ACTION, 'system', $input['api_format']);

                return false;
            }
            // @todo replace by constructor injection
            global $dic;
            /* @var NewPasswordSenderInterface $newPasswordSender */
            $newPasswordSender = $dic->get(NewPasswordSenderInterface::class);
            $current_ip        = Core::get_user_ip();

            // Do not acknowledge a password has been sent or failed
            $newPasswordSender->send($update_user->email, $current_ip);

            Api::message('success', $input['api_format']);

            return true;
        }
        Api::error(T_('Bad Request'), '4710', self::ACTION, 'input', $input['api_format']);

        return false;
    }
}
