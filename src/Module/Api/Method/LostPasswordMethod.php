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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\User\NewPasswordSenderInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\System\Core;
use Ampache\Repository\UserRepositoryInterface;
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
     * auth = (string) (
     *   $username;
     *   $key = hash('sha256', 'email');
     *   auth = hash('sha256', $username . $key);
     * )
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function lost_password(array $input): bool
    {
        if (!Mailer::is_mail_enabled()) {
            Api::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);
        }

        if (AmpConfig::get('simple_user_mode')) {
            Api::error('simple_user_mode', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        if (!Api::check_parameter($input, ['auth'], self::ACTION)) {
            return false;
        }

        // identify the user to modify
        $user_id     = self::getUserRepository()->idByResetToken($input['auth']);
        $update_user = new User($user_id);

        if ($user_id > 0) {
            // no resets for admin users
            if ($update_user->access == 100) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Bad Request: %s', $user_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

                return false;
            }
            if (empty($update_user->email)) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Bad Request: %s', $user_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'email', $input['api_format']);

                return false;
            }

            $current_ip = Core::get_user_ip();

            // Do not acknowledge a password has been sent or failed
            self::getNewPasswordSender()->send($update_user->email, $current_ip);

            Api::message('success', $input['api_format']);

            return true;
        }
        Api::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'input', $input['api_format']);

        return false;
    }

    /**
     * @todo replace by constructor injection
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }

    /**
     * @todo replace by constructor injection
     */
    private static function getNewPasswordSender(): NewPasswordSenderInterface
    {
        global $dic;

        return $dic->get(NewPasswordSenderInterface::class);
    }
}
