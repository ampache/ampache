<?php

declare(strict_types=1);

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

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Update\ClearAction;
use Ampache\Module\Application\Update\ShowAction;
use Ampache\Module\Application\Update\UpdateAction;
use Ampache\Module\Application\Update\UpdatePluginsAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

if (!array_key_exists('type', $_REQUEST) || (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) !== 'sources') {
    // We need this stuff
    define('NO_SESSION', 1);
    define('OUTDATED_DATABASE_OK', 1);
}

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ClearAction::REQUEST_KEY => ClearAction::class,
        ShowAction::REQUEST_KEY => ShowAction::class,
        UpdateAction::REQUEST_KEY => UpdateAction::class,
        UpdatePluginsAction::REQUEST_KEY => UpdatePluginsAction::class,
    ],
    ShowAction::REQUEST_KEY
);
