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

use Ampache\Module\Application\Admin\Catalog\AddCatalogAction;
use Ampache\Module\Application\Admin\Catalog\AddToAllCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\AddToCatalogAction;
use Ampache\Module\Application\Admin\Catalog\CleanAllCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\CleanCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ClearNowPlayingAction;
use Ampache\Module\Application\Admin\Catalog\ClearStatsAction;
use Ampache\Module\Application\Admin\Catalog\DeleteCatalogAction;
use Ampache\Module\Application\Admin\Catalog\EnableDisabledAction;
use Ampache\Module\Application\Admin\Catalog\FullServiceAction;
use Ampache\Module\Application\Admin\Catalog\GarbageCollectAction;
use Ampache\Module\Application\Admin\Catalog\GatherMediaArtAction;
use Ampache\Module\Application\Admin\Catalog\ImportToCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowAddCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\ShowCustomizeCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowDeleteCatalogAction;
use Ampache\Module\Application\Admin\Catalog\ShowDisabledAction;
use Ampache\Module\Application\Admin\Catalog\UpdateAllCatalogsAction;
use Ampache\Module\Application\Admin\Catalog\UpdateAllFileTagsActions;
use Ampache\Module\Application\Admin\Catalog\UpdateCatalogAction;
use Ampache\Module\Application\Admin\Catalog\UpdateCatalogSettingsAction;
use Ampache\Module\Application\Admin\Catalog\UpdateFileTagsAction;
use Ampache\Module\Application\Admin\Catalog\UpdateFromAction;
use Ampache\Module\Application\ApplicationRunner;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowCatalogsAction::REQUEST_KEY => ShowCatalogsAction::class,
        ShowCustomizeCatalogAction::REQUEST_KEY => ShowCustomizeCatalogAction::class,
        ShowDisabledAction::REQUEST_KEY => ShowDisabledAction::class,
        ClearNowPlayingAction::REQUEST_KEY => ClearNowPlayingAction::class,
        ShowAddCatalogAction::REQUEST_KEY => ShowAddCatalogAction::class,
        ClearStatsAction::REQUEST_KEY => ClearStatsAction::class,
        DeleteCatalogAction::REQUEST_KEY => DeleteCatalogAction::class,
        ShowDeleteCatalogAction::REQUEST_KEY => ShowDeleteCatalogAction::class,
        AddToAllCatalogsAction::REQUEST_KEY => AddToAllCatalogsAction::class,
        UpdateCatalogAction::REQUEST_KEY => UpdateCatalogAction::class,
        FullServiceAction::REQUEST_KEY => FullServiceAction::class,
        AddToCatalogAction::REQUEST_KEY => AddToCatalogAction::class,
        CleanAllCatalogsAction::REQUEST_KEY => CleanAllCatalogsAction::class,
        CleanCatalogAction::REQUEST_KEY => CleanCatalogAction::class,
        GarbageCollectAction::REQUEST_KEY => GarbageCollectAction::class,
        UpdateFileTagsAction::REQUEST_KEY => UpdateFileTagsAction::class,
        UpdateAllFileTagsActions::REQUEST_KEY => UpdateAllFileTagsActions::class,
        GatherMediaArtAction::REQUEST_KEY => GatherMediaArtAction::class,
        ImportToCatalogAction::REQUEST_KEY => ImportToCatalogAction::class,
        AddCatalogAction::REQUEST_KEY => AddCatalogAction::class,
        UpdateFromAction::REQUEST_KEY => UpdateFromAction::class,
        UpdateAllCatalogsAction::REQUEST_KEY => UpdateAllCatalogsAction::class,
        EnableDisabledAction::REQUEST_KEY => EnableDisabledAction::class,
        UpdateCatalogSettingsAction::REQUEST_KEY => UpdateCatalogSettingsAction::class,
    ],
    ShowCatalogsAction::REQUEST_KEY
);
