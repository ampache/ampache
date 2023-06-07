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

use Ampache\Module\Application\Admin\Modules\ConfirmUninstallCatalogType;
use Ampache\Module\Application\Admin\Modules\ConfirmUninstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\ConfirmUninstallPluginAction;
use Ampache\Module\Application\Admin\Modules\InstallCatalogTypeAction;
use Ampache\Module\Application\Admin\Modules\InstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\InstallPluginAction;
use Ampache\Module\Application\Admin\Modules\ShowAction;
use Ampache\Module\Application\Admin\Modules\ShowCatalogTypesAction;
use Ampache\Module\Application\Admin\Modules\ShowLocalplayAction;
use Ampache\Module\Application\Admin\Modules\ShowPluginsAction;
use Ampache\Module\Application\Admin\Modules\UninstallCatalogTypeAction;
use Ampache\Module\Application\Admin\Modules\UninstallLocalplayAction;
use Ampache\Module\Application\Admin\Modules\UninstallPluginAction;
use Ampache\Module\Application\Admin\Modules\UpgradePluginAction;
use Ampache\Module\Application\ApplicationRunner;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        InstallLocalplayAction::REQUEST_KEY => InstallLocalplayAction::class,
        ShowAction::REQUEST_KEY => ShowAction::class,
        InstallCatalogTypeAction::REQUEST_KEY => InstallCatalogTypeAction::class,
        ConfirmUninstallLocalplayAction::REQUEST_KEY => ConfirmUninstallLocalplayAction::class,
        ConfirmUninstallCatalogType::REQUEST_KEY => ConfirmUninstallCatalogType::class,
        UninstallLocalplayAction::REQUEST_KEY => UninstallLocalplayAction::class,
        UninstallCatalogTypeAction::REQUEST_KEY => UninstallCatalogTypeAction::class,
        InstallPluginAction::REQUEST_KEY => InstallPluginAction::class,
        ConfirmUninstallPluginAction::REQUEST_KEY => ConfirmUninstallPluginAction::class,
        UninstallPluginAction::REQUEST_KEY => UninstallPluginAction::class,
        UpgradePluginAction::REQUEST_KEY => UpgradePluginAction::class,
        ShowPluginsAction::REQUEST_KEY => ShowPluginsAction::class,
        ShowLocalplayAction::REQUEST_KEY => ShowLocalplayAction::class,
        ShowCatalogTypesAction::REQUEST_KEY => ShowCatalogTypesAction::class,
    ],
    ShowAction::REQUEST_KEY
);
