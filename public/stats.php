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
use Ampache\Module\Application\Stats\GraphAction;
use Ampache\Module\Application\Stats\HighestAction;
use Ampache\Module\Application\Stats\NewestAction;
use Ampache\Module\Application\Stats\PopularAction;
use Ampache\Module\Application\Stats\RecentAction;
use Ampache\Module\Application\Stats\ShareAction;
use Ampache\Module\Application\Stats\ShowAction;
use Ampache\Module\Application\Stats\ShowUserAction;
use Ampache\Module\Application\Stats\UploadAction;
use Ampache\Module\Application\Stats\UserflagAction;
use Ampache\Module\Application\Stats\WantedAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowUserAction::REQUEST_KEY => ShowUserAction::class,
        NewestAction::REQUEST_KEY => NewestAction::class,
        PopularAction::REQUEST_KEY => PopularAction::class,
        HighestAction::REQUEST_KEY => HighestAction::class,
        UserflagAction::REQUEST_KEY => UserflagAction::class,
        RecentAction::REQUEST_KEY => RecentAction::class,
        WantedAction::REQUEST_KEY => WantedAction::class,
        ShareAction::REQUEST_KEY => ShareAction::class,
        UploadAction::REQUEST_KEY => UploadAction::class,
        GraphAction::REQUEST_KEY => GraphAction::class,
        ShowAction::REQUEST_KEY => ShowAction::class
    ],
    ShowAction::REQUEST_KEY
);
