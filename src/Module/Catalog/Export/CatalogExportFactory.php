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

namespace Ampache\Module\Catalog\Export;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;

/**
 * Builds exporter classes
 */
final class CatalogExportFactory implements CatalogExportFactoryInterface
{
    private SongRepositoryInterface $songRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        SongRepositoryInterface $songRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->songRepository = $songRepository;
        $this->modelFactory   = $modelFactory;
    }

    private function createCsvExporter(): CatalogExporterInterface
    {
        return new CsvExporter(
            $this->songRepository,
            $this->modelFactory
        );
    }

    private function createItunesExporter(): CatalogExporterInterface
    {
        return new ItunesExporter(
            $this->songRepository
        );
    }

    /**
     * Returns the exporter class based on the export type
     */
    public function createFromExportType(string $exportType): CatalogExporterInterface
    {
        switch ($exportType) {
            case CatalogExportTypeEnum::ITUNES:
                $exporter = $this->createItunesExporter();
                break;
            default:
                $exporter = $this->createCsvExporter();
        }

        return $exporter;
    }
}
