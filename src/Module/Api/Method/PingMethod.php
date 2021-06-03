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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;

/**
 * Class PingMethod
 * @package Lib\ApiMethods
 */
final class PingMethod
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
        // set the version to the old string for old api clients
        $version      = (isset($input['version'])) ? $input['version'] : Api::$version;
        Api::$version = ((int) $version >= 350001) ? '500000' : Api::$version;

        $xmldata = array('server' => AmpConfig::get('version'), 'version' => Api::$version, 'compatible' => '350001');

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if (Session::exists('api', $input['auth'])) {
            Session::extend($input['auth']);
            $xmldata = array_merge(array('session_expire' => date("c", time() + (int) AmpConfig::get('session_length') - 60)), $xmldata, Api::server_details($input['auth']));
        }

        debug_event(self::class, 'Ping Received from ' . Core::get_server('REMOTE_ADDR'), 5);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($xmldata, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::keyed_array($xmldata);
        }
    } // ping
}
