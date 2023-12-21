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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Album\Export\AlbumArtExporterInterface;
use Ampache\Module\Album\Export\Exception\AlbumArtExportException;
use Ampache\Module\Album\Export\Writer\MetadataWriterTypeEnum;
use Ampache\Module\System\LegacyLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ExportAlbumArtCommand extends Command
{
    private LoggerInterface $logger;

    private AlbumArtExporterInterface $albumArtExporter;

    private ContainerInterface $dic;

    public function __construct(
        LoggerInterface $logger,
        AlbumArtExporterInterface $albumArtExporter,
        ContainerInterface $dic
    ) {
        parent::__construct('export:albumArt', T_('Export album art'));

        $this->logger           = $logger;
        $this->albumArtExporter = $albumArtExporter;
        $this->dic              = $dic;

        $this
            ->argument('[type]', T_('Metadata write mode (`linux` or `windows`)'), 'linux')
            ->usage('<bold>  export:albumArt</end> <comment>linux</end> ## ' . T_('Export album art for Linux') . '<eol/>');
    }

    public function execute(
        string $type
    ): void {
        $interactor         = $this->app()->io();
        $metadataWriterType = MetadataWriterTypeEnum::MAP[$type] ?? MetadataWriterTypeEnum::EXPORT_DRIVER_LINUX;

        $interactor->info(
            T_('Start Album Art Dump'),
            true
        );

        $catalogs = Catalog::get_catalogs();
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }

            try {
                $this->albumArtExporter->export(
                    $interactor,
                    $catalog,
                    $this->dic->get($metadataWriterType)
                );
            } catch (AlbumArtExportException $e) {
                $this->logger->error(
                    $e->getMessage(),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                $interactor->error(
                    $e->getMessage(),
                    true
                );
            }

            $interactor->info(
                T_('Album Art Dump Complete'),
                true
            );
        }
    }
}
