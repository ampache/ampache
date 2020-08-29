<?php

declare(strict_types=0);

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Application\Api;

use Ampache\Application\ApplicationInterface;
use AmpConfig;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Server;
use Ampache\Module\WebDav\WebDav_Auth;
use Ampache\Module\WebDav\WebDav_Catalog;

final class WebDavApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('webdav_backend')) {
            echo T_("Disabled");

            return;
        }

        $rootDir = new WebDav_Catalog();
        $server  = new Server($rootDir);

        $baseUri = ((AmpConfig::get('raw_web_path') !== "/") ? AmpConfig::get('raw_web_path') : "") . '/webdav/index.php';
        $server->setBaseUri($baseUri);
        if (AmpConfig::get('use_auth')) {
            $authBackend = new WebDav_Auth();
            $authBackend->setRealm('Ampache');
            $authPlugin  = new Plugin($authBackend);
            $server->addPlugin($authPlugin);
        }

        $server->exec();
    }
}
