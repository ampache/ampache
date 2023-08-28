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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Module\System\Session;

/**
 * Class Ping5Method
 */
final class Ping5Method
{
    public const ACTION = 'ping';

    /**
     * ping
     * MINIMUM_API_VERSION=380001
     *
     * This can be called without being authenticated, it is useful for determining if what the status
     * of the server is, and what version it is running/compatible with
     *
     * @param array $input
     * auth    = (string) //optional
     * version = (string) $version //optional
     */
    public static function ping(array $input)
    {
        $version       = (isset($input['version'])) ? $input['version'] : Api5::$version;
        Api5::$version = ((int)$version >= 350001) ? Api5::$version_numeric : Api5::$version;
        $data_version  = (int)substr($version, 0, 1);
        $results       = array(
            'server' => AmpConfig::get('version'),
            'version' => Api5::$version,
            'compatible' => '350001'
        );

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if (Session::exists('api', $input['auth'])) {
            Session::extend($input['auth']);
            if (in_array($data_version, array(3, 4, 5))) {
                Session::write($input['auth'], $data_version);
            }
            $results = array_merge(array('session_expire' => date("c", time() + (int)AmpConfig::get('session_length', 3600) - 60)), $results, Api5::server_details($input['auth']));
        }

        debug_event(self::class, "Ping$data_version Received from " . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP), 5);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml5_Data::keyed_array($results);
        }
    } // ping
}
