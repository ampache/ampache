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
use Ampache\Module\Application\Podcast\ConfirmDeleteAction;
use Ampache\Module\Application\Podcast\CreateAction;
use Ampache\Module\Application\Podcast\DeleteAction;
use Ampache\Module\Application\Podcast\ExportPodcastsAction;
use Ampache\Module\Application\Podcast\ImportPodcastsAction;
use Ampache\Module\Application\Podcast\ShowAction;
use Ampache\Module\Application\Podcast\ShowCreateAction;
use Ampache\Module\Application\Podcast\ShowImportPodcastsAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowCreateAction::REQUEST_KEY => ShowCreateAction::class,
        CreateAction::REQUEST_KEY => CreateAction::class,
        DeleteAction::REQUEST_KEY => DeleteAction::class,
        ConfirmDeleteAction::REQUEST_KEY => ConfirmDeleteAction::class,
        ShowAction::REQUEST_KEY => ShowAction::class,
        ExportPodcastsAction::REQUEST_KEY => ExportPodcastsAction::class,
        ShowImportPodcastsAction::REQUEST_KEY => ShowImportPodcastsAction::class,
        ImportPodcastsAction::REQUEST_KEY => ImportPodcastsAction::class,
    ],
    ShowAction::REQUEST_KEY
);
