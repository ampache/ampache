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

namespace Ampache\Module\Application\Admin\Export;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Catalog\Exception\CatalogLoadingException;
use Ampache\Module\Catalog\Export\CatalogExportFactoryInterface;
use Ampache\Module\Catalog\Export\CatalogExportTypeEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Exports a catalog according to the submitted configuration
 */
final class ExportAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'export';

    private CatalogExportFactoryInterface $catalogExportFactory;

    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        CatalogExportFactoryInterface $catalogExportFactory,
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->catalogExportFactory = $catalogExportFactory;
        $this->catalogLoader        = $catalogLoader;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false) {
            throw new AccessDeniedException();
        }

        // This may take a while
        set_time_limit(0);

        // Clear everything we've done so far
        ob_end_clean();

        // This will disable buffering so contents are sent immediately to browser.
        // This is very useful for large catalogs because it will immediately display the download dialog to user,
        // instead of waiting until contents are generated, which could take a long time.
        ob_implicit_flush();

        $requestData  = (array)$request->getParsedBody();
        $catalogId    = (int) ($requestData['export_catalog'] ?? 0);
        $exportFormat = CatalogExportTypeEnum::tryFrom($requestData['export_format'] ?? '') ?? CatalogExportTypeEnum::CSV;

        try {
            $catalog = $this->catalogLoader->getById($catalogId);
        } catch (CatalogLoadingException $e) {
            $catalog = null;
        }

        $exporter = $this->catalogExportFactory->createFromExportType($exportFormat);
        $exporter->sendHeaders();
        $exporter->export($catalog);

        return null;
    }
}
