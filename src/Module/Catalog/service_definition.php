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

namespace Ampache\Module\Catalog;

use Ampache\Module\Catalog\Update\AddCatalog;
use Ampache\Module\Catalog\Update\AddCatalogInterface;
use Ampache\Module\Catalog\Update\UpdateCatalog;
use Ampache\Module\Catalog\Update\UpdateCatalogInterface;
use Ampache\Module\Catalog\Update\UpdateSingleCatalogFile;
use Ampache\Module\Catalog\Update\UpdateSingleCatalogFileInterface;
use Ampache\Module\Catalog\Update\UpdateSingleCatalogFolder;
use Ampache\Module\Catalog\Update\UpdateSingleCatalogFolderInterface;

use function DI\autowire;

return [
    AddCatalogInterface::class => autowire(AddCatalog::class),
    UpdateSingleCatalogFileInterface::class => autowire(UpdateSingleCatalogFile::class),
    UpdateSingleCatalogFolderInterface::class => autowire(UpdateSingleCatalogFolder::class),
    UpdateCatalogInterface::class => autowire(UpdateCatalog::class),
    GarbageCollector\CatalogGarbageCollectorInterface::class => autowire(GarbageCollector\CatalogGarbageCollector::class),
    Export\CatalogExportFactoryInterface::class => autowire(Export\CatalogExportFactory::class),
    CatalogLoaderInterface::class => autowire(CatalogLoader::class),
];
