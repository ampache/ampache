<?php

/*
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

declare(strict_types=0);

namespace Ampache\Module\Application\Admin\Export;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\CatalogExporterInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ExportAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'export';

    private CatalogExporterInterface $catalogExporter;

    private Psr17Factory $psr17Factory;

    public function __construct(
        CatalogExporterInterface $catalogExporter,
        Psr17Factory $psr17Factory
    ) {
        $this->catalogExporter = $catalogExporter;
        $this->psr17Factory    = $psr17Factory;
    }

    /**
     * @todo implement a memory-friendly way to output the stream content
     */
    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        /** @var array<string, mixed> $body */
        $body = $request->getParsedBody();

        $catalogId = (int) ($body['export_catalog'] ?? 0);

        // This may take a while
        set_time_limit(0);

        // This will disable buffering so contents are sent immediately to browser.
        // This is very useful for large catalogs because it will immediately display the download dialog to user,
        // instead of waiting until contents are generated, which could take a long time.
        ob_implicit_flush(1);

        $response = $this->psr17Factory->createResponse()
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Cache-control', 'public');

        $date = get_datetime(time(), 'short', 'none', 'y-MM-dd');

        switch ($body['export_format'] ?? '') {
            case 'itunes':
                return $response
                    ->withHeader(
                        'Content-Type',
                        'application/itunes+xml; charset=utf-8'
                    )
                    ->withHeader(
                        'Content-Disposition',
                        sprintf('attachment; filename="ampache-itunes-%s.xml"', $date)
                    )
                    ->withBody(
                        $this->catalogExporter->export('itunes', $catalogId)
                    );
            case 'csv':
            default:
                return $response
                    ->withHeader(
                        'Content-Type',
                        'application/vnd.ms-excel'
                    )
                    ->withHeader(
                        'Content-Disposition',
                        sprintf('filename="ampache-export-%s.csv"', $date)
                    )
                    ->withBody(
                        $this->catalogExporter->export('csv', $catalogId)
                    );
        }
    }
}
