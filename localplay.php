<?php

/**
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

declare(strict_types=1);

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\LocalPlay\AddInstanceAction;
use Ampache\Module\Application\LocalPlay\EditInstanceAction;
use Ampache\Module\Application\LocalPlay\ShowAddInstanceAction;
use Ampache\Module\Application\LocalPlay\ShowInstancesAction;
use Ampache\Module\Application\LocalPlay\ShowPlaylistAction;
use Ampache\Module\Application\LocalPlay\UpdateInstanceAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowAddInstanceAction::REQUEST_KEY => ShowAddInstanceAction::class,
        ShowPlaylistAction::REQUEST_KEY => ShowPlaylistAction::class,
        AddInstanceAction::REQUEST_KEY => AddInstanceAction::class,
        UpdateInstanceAction::REQUEST_KEY => UpdateInstanceAction::class,
        EditInstanceAction::REQUEST_KEY => EditInstanceAction::class,
        ShowInstancesAction::REQUEST_KEY => ShowInstancesAction::class,
    ],
    ShowPlaylistAction::REQUEST_KEY
);
