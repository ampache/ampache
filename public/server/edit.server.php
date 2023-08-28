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
 *  Because this is accessed via Ajax we are going to allow the session_id
 * as part of the get request
 */

use Ampache\Module\Api\Edit\EditObjectAction;
use Ampache\Module\Api\Edit\RefreshUpdatedAction;
use Ampache\Module\Api\Edit\ShowEditObjectAction;
use Ampache\Module\Api\Edit\ShowEditPlaylistAction;
use Ampache\Module\Application\ApplicationRunner;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

// Set that this is an ajax include
define('AJAX_INCLUDE', '1');

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        EditObjectAction::REQUEST_KEY => EditObjectAction::class,
        RefreshUpdatedAction::REQUEST_KEY => RefreshUpdatedAction::class,
        ShowEditObjectAction::REQUEST_KEY => ShowEditObjectAction::class,
        ShowEditPlaylistAction::REQUEST_KEY => ShowEditPlaylistAction::class,
    ],
    ShowEditObjectAction::REQUEST_KEY
);
