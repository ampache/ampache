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
use Ampache\Module\System\Core;
use Ampache\Module\Api\Upnp_Api;

final class UpnpApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('upnp_backend')) {
            echo T_("Disabled");

            return;
        }

        $htmllang = str_replace("_", "-", AmpConfig::get('lang'));
        if (($_GET['btnSend']) || ($_GET['btnSendAuto'])) {
            $msIP = 1;
            Upnp_Api::sddpSend($msIP);
        } ?>
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
        <head>
            <!-- Propelled by Ampache | ampache.org -->
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <?php
            if (Core::get_get('btnSendAuto') !== '') {
                echo '<meta http-equiv="refresh" content="1">';
            } ?>
            <title><?php echo T_('Ampache') . " " . T_('UPnP'); ?></title>
            <style media="screen">
                body {
                    color:black;
                    background-color:white;
                    background-image:url(images/upnp.jpg);
                    background-repeat:no-repeat;
                    background-position:50% 50%;
                    height: 400px;
                }
            </style>
        </head>

        <body>
        <form method="get" action="">
            <label>Ampache UPnP backend enabled.
            </label>
            <br />
            <br />
            <br />
            <input type="submit" name="btnSend" id="id-btnSend" value="Send SSDP broadcast" />
            <input type="submit" name="btnSendAuto" id="id-btnSendAuto" value="Send SSDP broadcast every second" />
        </form>
        <br />
        <?php
        if (($_GET['btnSend']) || ($_GET['btnSendAuto'])) {
            echo 'SSDP sent at ' . date('H:i:s') . '.';
        } ?>
        </body>
        </html><?php
    }
}
