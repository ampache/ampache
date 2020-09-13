<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!AmpConfig::get('webdav_backend')) {
    echo T_("Disabled");

    return false;
}

use Sabre\DAV;

$rootDir = new WebDAV_Catalog();
$server  = new DAV\Server($rootDir);

$baseUri = ((AmpConfig::get('raw_web_path') !== "/") ? AmpConfig::get('raw_web_path') : "") . '/webdav/index.php';
$server->setBaseUri($baseUri);
if (AmpConfig::get('use_auth')) {
    $authBackend = new WebDAV_Auth();
    $authBackend->setRealm('Ampache');
    $authPlugin  = new DAV\Auth\Plugin($authBackend);
    $server->addPlugin($authPlugin);
}

$server->exec();
