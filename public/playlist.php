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
use Ampache\Module\Application\Playlist\AddSongAction;
use Ampache\Module\Application\Playlist\CreatePlaylistAction;
use Ampache\Module\Application\Playlist\DeletePlaylistAction;
use Ampache\Module\Application\Playlist\ImportPlaylistAction;
use Ampache\Module\Application\Playlist\RemoveDuplicatesAction;
use Ampache\Module\Application\Playlist\SetTrackNumbersAction;
use Ampache\Module\Application\Playlist\ShowAction;
use Ampache\Module\Application\Playlist\ShowImportPlaylistAction;
use Ampache\Module\Application\Playlist\ShowPlaylistAction;
use Ampache\Module\Application\Playlist\SortTrackAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowAction::REQUEST_KEY => ShowAction::class,
        SortTrackAction::REQUEST_KEY => SortTrackAction::class,
        RemoveDuplicatesAction::REQUEST_KEY => RemoveDuplicatesAction::class,
        AddSongAction::REQUEST_KEY => AddSongAction::class,
        SetTrackNumbersAction::REQUEST_KEY => SetTrackNumbersAction::class,
        ImportPlaylistAction::REQUEST_KEY => ImportPlaylistAction::class,
        ShowImportPlaylistAction::REQUEST_KEY => ShowImportPlaylistAction::class,
        ShowPlaylistAction::REQUEST_KEY => ShowPlaylistAction::class,
        DeletePlaylistAction::REQUEST_KEY => DeletePlaylistAction::class,
        CreatePlaylistAction::REQUEST_KEY => CreatePlaylistAction::class,
    ],
    ShowAction::REQUEST_KEY
);
