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

/**
 * This is the wrapper for opening music streams from this server.  This script
 * will play the local version or redirect to the remote server if that be
 * the case.  Also this will update local statistics for songs as well.
 * This is also where it decides if you need to be downsampled.
 */

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Playback\Play2Action;
use Ampache\Module\Application\Playback\PlayAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

define('NO_SESSION', '1');
define('OUTDATED_DATABASE_OK', 1);

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        PlayAction::REQUEST_KEY => PlayAction::class,
        Play2Action::REQUEST_KEY => Play2Action::class,
    ],
    PlayAction::REQUEST_KEY
);
