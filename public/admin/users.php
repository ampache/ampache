<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Module\Application\Admin\User\AddUserAction;
use Ampache\Module\Application\Admin\User\ConfirmDeleteAction;
use Ampache\Module\Application\Admin\User\ConfirmDisableAction;
use Ampache\Module\Application\Admin\User\ConfirmEnableAction;
use Ampache\Module\Application\Admin\User\DeleteAction;
use Ampache\Module\Application\Admin\User\DeleteApikeyAction;
use Ampache\Module\Application\Admin\User\DeleteAvatarAction;
use Ampache\Module\Application\Admin\User\DeleteRsstokenAction;
use Ampache\Module\Application\Admin\User\DeleteStreamtokenAction;
use Ampache\Module\Application\Admin\User\DisableAction;
use Ampache\Module\Application\Admin\User\EnableAction;
use Ampache\Module\Application\Admin\User\GenerateApikeyAction;
use Ampache\Module\Application\Admin\User\GenerateRsstokenAction;
use Ampache\Module\Application\Admin\User\GenerateStreamtokenAction;
use Ampache\Module\Application\Admin\User\ShowAction;
use Ampache\Module\Application\Admin\User\ShowAddUserAction;
use Ampache\Module\Application\Admin\User\ShowDeleteApikeyAction;
use Ampache\Module\Application\Admin\User\ShowDeleteAvatarAction;
use Ampache\Module\Application\Admin\User\ShowDeleteRsstokenAction;
use Ampache\Module\Application\Admin\User\ShowDeleteStreamtokenAction;
use Ampache\Module\Application\Admin\User\ShowEditAction;
use Ampache\Module\Application\Admin\User\ShowGenerateApikeyAction;
use Ampache\Module\Application\Admin\User\ShowGenerateRsstokenAction;
use Ampache\Module\Application\Admin\User\ShowGenerateStreamtokenAction;
use Ampache\Module\Application\Admin\User\ShowIpHistoryAction;
use Ampache\Module\Application\Admin\User\ShowPreferencesAction;
use Ampache\Module\Application\Admin\User\UpdateUserAction;
use Ampache\Module\Application\ApplicationRunner;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowAction::REQUEST_KEY => ShowAction::class,
        ShowPreferencesAction::REQUEST_KEY => ShowPreferencesAction::class,
        ShowAddUserAction::REQUEST_KEY => ShowAddUserAction::class,
        ShowIpHistoryAction::REQUEST_KEY => ShowIpHistoryAction::class,
        GenerateRsstokenAction::REQUEST_KEY => GenerateRsstokenAction::class,
        GenerateStreamtokenAction::REQUEST_KEY => GenerateStreamtokenAction::class,
        ShowGenerateRsstokenAction::REQUEST_KEY => ShowGenerateRsstokenAction::class,
        ShowGenerateStreamtokenAction::REQUEST_KEY => ShowGenerateStreamtokenAction::class,
        DeleteRsstokenAction::REQUEST_KEY => DeleteRsstokenAction::class,
        ShowDeleteRsstokenAction::REQUEST_KEY => ShowDeleteRsstokenAction::class,
        DeleteStreamtokenAction::REQUEST_KEY => DeleteStreamtokenAction::class,
        ShowDeleteStreamtokenAction::REQUEST_KEY => ShowDeleteStreamtokenAction::class,
        GenerateApikeyAction::REQUEST_KEY => GenerateApikeyAction::class,
        ShowGenerateApikeyAction::REQUEST_KEY => ShowGenerateApikeyAction::class,
        DeleteApikeyAction::REQUEST_KEY => DeleteApikeyAction::class,
        ShowDeleteApikeyAction::REQUEST_KEY => ShowDeleteApikeyAction::class,
        DeleteAvatarAction::REQUEST_KEY => DeleteAvatarAction::class,
        ShowDeleteAvatarAction::REQUEST_KEY => ShowDeleteAvatarAction::class,
        DeleteAction::REQUEST_KEY => DeleteAction::class,
        ConfirmDeleteAction::REQUEST_KEY => ConfirmDeleteAction::class,
        ShowEditAction::REQUEST_KEY => ShowEditAction::class,
        DisableAction::REQUEST_KEY => DisableAction::class,
        ConfirmDisableAction::REQUEST_KEY => ConfirmDisableAction::class,
        EnableAction::REQUEST_KEY => EnableAction::class,
        ConfirmEnableAction::REQUEST_KEY => ConfirmEnableAction::class,
        AddUserAction::REQUEST_KEY => AddUserAction::class,
        UpdateUserAction::REQUEST_KEY => UpdateUserAction::class,
    ],
    ShowAction::REQUEST_KEY
);
