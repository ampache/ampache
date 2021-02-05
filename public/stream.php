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
use Ampache\Module\Application\Stream\AlbumRandomAction;
use Ampache\Module\Application\Stream\ArtistRandomAction;
use Ampache\Module\Application\Stream\BasketAction;
use Ampache\Module\Application\Stream\DemocraticAction;
use Ampache\Module\Application\Stream\DownloadAction;
use Ampache\Module\Application\Stream\PlayFavoriteAction;
use Ampache\Module\Application\Stream\PlayItemAction;
use Ampache\Module\Application\Stream\PlaylistRandomAction;
use Ampache\Module\Application\Stream\TmpPlaylistAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        DownloadAction::REQUEST_KEY => DownloadAction::class,
        DemocraticAction::REQUEST_KEY => DemocraticAction::class,
        PlaylistRandomAction::REQUEST_KEY => PlaylistRandomAction::class,
        AlbumRandomAction::REQUEST_KEY => AlbumRandomAction::class,
        ArtistRandomAction::REQUEST_KEY => ArtistRandomAction::class,
        PlayItemAction::REQUEST_KEY => PlayItemAction::class,
        PlayFavoriteAction::REQUEST_KEY => PlayFavoriteAction::class,
        TmpPlaylistAction::REQUEST_KEY => TmpPlaylistAction::class,
        BasketAction::REQUEST_KEY => BasketAction::class,
    ],
    PlayFavoriteAction::REQUEST_KEY
);
