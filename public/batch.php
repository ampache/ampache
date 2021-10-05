<?php
/**
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

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Batch\DefaultAction;
use Ampache\Module\System\Session;
use Ampache\Module\Util\UiInterface;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;

if (array_key_exists('ssid', $_REQUEST)) {
    define('NO_SESSION', 1);
    /** @var \Psr\Container\ContainerInterface $dic */
    $dic = require_once __DIR__ . '/../src/Config/Init.php';
    if (!Session::exists('stream', $_REQUEST['ssid'])) {
        $dic->get(UiInterface::class)->accessDenied();

        return false;
    }
} else {
    $dic = require_once __DIR__ . '/../src/Config/Init.php';
}
$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        DefaultAction::REQUEST_KEY => DefaultAction::class,
    ],
    DefaultAction::REQUEST_KEY
);
