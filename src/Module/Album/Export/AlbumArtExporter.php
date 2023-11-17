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
 */

declare(strict_types=0);

namespace Ampache\Module\Album\Export;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Album\Export;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * This runs through all of the albums and tries to dump the
 * art for them into the 'folder.jpg' file in the appropriate dir.
 */
final class AlbumArtExporter implements AlbumArtExporterInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory,
        SongRepositoryInterface $songRepository
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->modelFactory    = $modelFactory;
        $this->songRepository  = $songRepository;
    }

    public function export(
        Interactor $interactor,
        Catalog $catalog,
        Writer\MetadataWriterInterface $metadataWriter
    ): void {

        // Get all of the albums in this catalog
        $albums = $catalog->get_album_ids();

        $count = 0;

        // Run through them and get the art!
        foreach ($albums as $albumId) {
            $albumId = (int) $albumId;
            $art     = $this->modelFactory->createArt($albumId);

            if (!$art->has_db_info()) {
                continue;
            }

            $album = $this->modelFactory->createAlbum($albumId);

            // Get the first song in the album
            $songs = $this->songRepository->getByAlbum($albumId, 1);
            $song  = $this->modelFactory->createSong((int) $songs[0]);
            $dir   = dirname($song->file);

            $extension = Art::extension($art->raw_mime);

            // Try the preferred filename, if that fails use folder.???
            $preferred_filename = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME);
            if (!$preferred_filename || strpos($preferred_filename, '%') !== false) {
                $preferred_filename = sprintf('folder.%s', $extension);
            }

            $file = $dir . DIRECTORY_SEPARATOR . $preferred_filename;

            $file_handle = @fopen($file, 'w');

            if ($file_handle === false) {
                throw new Export\Exception\AlbumArtExportException(
                    sprintf(T_('Unable to open `%s` for writing'), $file)
                );
            }
            $write_result = @fwrite($file_handle, $art->raw);

            if ($write_result === false) {
                throw new Export\Exception\AlbumArtExportException(
                    sprintf(T_('Unable to write to `%s`'), $file)
                );
            }

            fclose($file_handle);

            $fileName = $album->get_fullname(true);

            $metadataWriter->write(
                $fileName,
                $dir,
                $file
            );

            $count++;
            if (!($count % 100)) {
                $interactor->info(
                    sprintf(T_('Art files written: %d'), $count),
                    true
                );
            }
        }
    }
}
