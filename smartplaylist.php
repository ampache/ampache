<?php

declare(strict_types=1);

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

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Search\SaveAsPlaylistAction;
use Ampache\Module\Application\SmartPlaylist\CreatePlaylistAction;
use Ampache\Module\Application\SmartPlaylist\DeletePlaylistAction;
use Ampache\Module\Application\SmartPlaylist\RefreshPlaylistAction;
use Ampache\Module\Application\SmartPlaylist\ShowAction;
use Ampache\Module\Application\SmartPlaylist\UpdatePlaylistAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowAction::REQUEST_KEY => ShowAction::class,
        RefreshPlaylistAction::REQUEST_KEY => RefreshPlaylistAction::class,
        UpdatePlaylistAction::REQUEST_KEY => UpdatePlaylistAction::class,
        SaveAsPlaylistAction::REQUEST_KEY => SaveAsPlaylistAction::class,
        DeletePlaylistAction::REQUEST_KEY => DeletePlaylistAction::class,
        CreatePlaylistAction::REQUEST_KEY => CreatePlaylistAction::class,
    ],
    ShowAction::REQUEST_KEY
);
