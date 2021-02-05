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

declare(strict_types=1);

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\DemocraticPlayback\CreateAction;
use Ampache\Module\Application\DemocraticPlayback\DeleteAction;
use Ampache\Module\Application\DemocraticPlayback\ManageAction;
use Ampache\Module\Application\DemocraticPlayback\ManagePlaylistsAction;
use Ampache\Module\Application\DemocraticPlayback\ShowCreateAction;
use Ampache\Module\Application\DemocraticPlayback\ShowPlaylistAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ManageAction::REQUEST_KEY => ManageAction::class,
        ShowCreateAction::REQUEST_KEY => ShowCreateAction::class,
        DeleteAction::REQUEST_KEY => DeleteAction::class,
        CreateAction::REQUEST_KEY => CreateAction::class,
        ManagePlaylistsAction::REQUEST_KEY => ManagePlaylistsAction::class,
        ShowPlaylistAction::REQUEST_KEY => ShowPlaylistAction::class,
    ],
    ShowPlaylistAction::REQUEST_KEY
);
