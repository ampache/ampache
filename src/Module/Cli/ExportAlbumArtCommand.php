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

declare(strict_types=1);

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Catalog;
use Ampache\Module\Album\Export\AlbumArtExporterInterface;
use Ampache\Module\Album\Export\Exception\AlbumArtExportException;
use Ampache\Module\Album\Export\Writer\MetadataWriterTypeEnum;
use Ampache\Module\System\LegacyLogger;
use Psr\Container\ContainerInterface;

final class ExportAlbumArtCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private AlbumArtExporterInterface $albumArtExporter;

    private ContainerInterface $dic;

    public function __construct(
        ConfigContainerInterface $configContainer,
        AlbumArtExporterInterface $albumArtExporter,
        ContainerInterface $dic
    ) {
        parent::__construct('export:albumArt', 'Exports the album art');

        $this->configContainer  = $configContainer;
        $this->albumArtExporter = $albumArtExporter;
        $this->dic              = $dic;

        $this
            ->argument('[type]', 'Metadata write mode (`linux` or `windows`)', 'linux')
            ->usage('<bold>  export:albumArt</end> <comment>linux</end> ## Exports album art for linux<eol/>');
    }

    public function execute(
        string $type
    ): void {
        $interactor = $this->app()->io();

        $metadataWriterType = MetadataWriterTypeEnum::MAP[$type] ?? MetadataWriterTypeEnum::EXPORT_DRIVER_LINUX;

        $catalogs = Catalog::get_catalogs();

        $interactor->info(
            T_('Start Album Art Dump'),
            true
        );

        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);

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
