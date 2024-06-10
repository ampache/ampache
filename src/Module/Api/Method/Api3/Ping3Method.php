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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api3;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Session;

/**
 * Class Ping3Method
 */
final class Ping3Method
{
    public const ACTION = 'ping';

    /**
     * ping
     * This can be called without being authenticated, it is useful for determining if what the status
     * of the server is, and what version it is running/compatible with
     * @param array $input
     */
    public static function ping(array $input): void
    {
        $version      = (isset($input['version'])) ? $input['version'] : Api3::$version;
        $data_version = (int)substr($version, 0, 1);
        $results      = array(
            'server' => AmpConfig::get('version'),
            'version' => Api3::$version,
            'compatible' => '350001'
        );

        // Check and see if we should extend the api sessions (done if valid sess is passed)
        if (array_key_exists('auth', $input) && Session::exists(AccessTypeEnum::API->value, $input['auth'])) {
            Session::extend($input['auth'], AccessTypeEnum::API->value);
            // perpetual sessions do not expire
            $perpetual      = (bool)AmpConfig::get('perpetual_api_session', false);
            $session_expire = ($perpetual)
                ? 0
                : date("c", time() + (int)AmpConfig::get('session_length', 3600) - 60);
            if (in_array($data_version, Api::API_VERSIONS)) {
                Session::write($input['auth'], $data_version, $perpetual);
            }
            $results = array_merge(
                array('session_expire' => $session_expire),
                $results
            );
        }

        debug_event(self::class, "Ping$data_version Received from " . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP), 5);

        ob_end_clean();
        echo Xml3_Data::keyed_array($results);
    }
}
