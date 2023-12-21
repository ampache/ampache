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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * Class Goodbye5Method
 */
final class Goodbye5Method
{
    public const ACTION = 'goodbye';

    /**
     * goodbye
     * MINIMUM_API_VERSION=400001
     *
     * Destroy session for auth key.
     *
     * auth = (string)
     */
    public static function goodbye(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('auth'), self::ACTION)) {
            return false;
        }
        debug_event(self::class, 'Goodbye Received from ' . $user->id . ' ' . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) . ' :: ' . $input['auth'], 5);

        // Check and see if we should destroy the api session (done if valid session is passed)
        if (Session::exists('api', $input['auth'])) {
            $sql = "DELETE FROM `session` WHERE `id` = ? AND `type` = 'api';";
            Dba::write($sql, array($input['auth']));

            ob_end_clean();
            Api5::message($input['auth'], $input['api_format']);

            return true;
        }
        ob_end_clean();
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api5::error(sprintf(T_('Bad Request: %s'), $input['auth']), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'account', $input['api_format']);

        return false;
    }
}
