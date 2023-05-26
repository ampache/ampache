<?php

declare(strict_types=0);

/*
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

namespace Ampache\Application\Api\Upnp;

use Ampache\Application\ApplicationInterface;
use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Upnp_Api;

final class CmControlReplyApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('upnp_backend')) {
            echo T_("Disabled");

            return;
        }

        set_time_limit(600);

        header("Content-Type: text/html; charset=UTF-8");

        // Parse the request from UPnP player
        $requestRaw = file_get_contents('php://input');
        if ($requestRaw != '') {
            $upnpRequest = Upnp_Api::parseUPnPRequest($requestRaw);
            debug_event('cm-control-reply', 'Request: ' . $requestRaw, 5);
        } else {
            echo T_('Received an empty UPnP request');
            debug_event('cm-control-reply', 'No request', 5);

            return;
        }

        switch ($upnpRequest['action']) {
            case 'getprotocolinfo':
                $responseType = 'u:GetProtocolInfoResponse';
                //$items = \Ampache\Module\Api\Upnp_Api::cm_getProtocolInfo();
                break;
        }
    }
}
