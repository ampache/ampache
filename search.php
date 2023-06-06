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
use Ampache\Module\Application\Search\DescriptorAction;
use Ampache\Module\Application\Search\SaveAsSmartPlaylistAction;
use Ampache\Module\Application\Search\SaveAsPlaylistAction;
use Ampache\Module\Application\Search\SearchAction;
use Ampache\Module\Application\Search\ShowAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        SearchAction::REQUEST_KEY => SearchAction::class,
        SaveAsSmartPlaylistAction::REQUEST_KEY => SaveAsSmartPlaylistAction::class,
        SaveAsPlaylistAction::REQUEST_KEY => SaveAsPlaylistAction::class,
        DescriptorAction::REQUEST_KEY => DescriptorAction::class,
        ShowAction::REQUEST_KEY => ShowAction::class,
    ],
    ShowAction::REQUEST_KEY
);
