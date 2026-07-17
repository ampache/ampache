<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

/**
 * Redirect uri of the OpenID Connect login. It is a directory of its own so the uri
 * registered at the identity provider carries no query string; some providers refuse
 * to allow-list one. The provider appends its own `code` and `state` parameters here.
 */

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Login\DefaultAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

define('NO_SESSION', '1');

/**
 * The second step of DefaultAction reads these through RequestParser, which reads $_REQUEST.
 * They have to be set before Init.php runs, because Bootstrap.php builds $_REQUEST from $_GET and $_POST.
 */
$_GET['auth_mod'] = 'oidc';
$_GET['step']     = '2';

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [DefaultAction::REQUEST_KEY => DefaultAction::class],
    DefaultAction::REQUEST_KEY
);
