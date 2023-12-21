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
use Ampache\Module\Application\Art\ClearArtAction;
use Ampache\Module\Application\Art\FindArtAction;
use Ampache\Module\Application\Art\SelectArtAction;
use Ampache\Module\Application\Art\ShowArtDlgAction;
use Ampache\Module\Application\Art\UploadArtAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ClearArtAction::REQUEST_KEY => ClearArtAction::class,
        ShowArtDlgAction::REQUEST_KEY => ShowArtDlgAction::class,
        FindArtAction::REQUEST_KEY => FindArtAction::class,
        UploadArtAction::REQUEST_KEY => UploadArtAction::class,
        SelectArtAction::REQUEST_KEY => SelectArtAction::class,
    ],
    ShowArtDlgAction::REQUEST_KEY
);
