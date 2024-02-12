<?php

declare(strict_types=0);

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

namespace Ampache\Module\Art\Export;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\ImageRepositoryInterface;
use Ampache\Repository\Model\Art;
use Ampache\Module\Art\Export;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

/**
 * This runs through all of the images and tries to
 * export all database art to local_metadata_dir
 */
final class ArtExporter implements ArtExporterInterface
{
    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    private ImageRepositoryInterface $imageRepository;

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        ImageRepositoryInterface $imageRepository
    ) {
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
        $this->imageRepository = $imageRepository;
    }

    public function export(
        Interactor $interactor,
        Writer\MetadataWriterInterface $metadataWriter,
        bool $clearData
    ): void {
        if ($clearData && !$this->configContainer->get('album_art_store_disk')) {
            $clearData = false;
            $interactor->info(
                T_('Set album_art_store_disk to remove art from the database'),
                true
            );
            $this->logger->critical(
                'Not Clearing images from database, set album_art_store_disk to remove art from the database',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }
        // Get all of the art items with an image
        $images = $this->imageRepository->findAllHavingImage();
        $count  = 0;

        // Run through them and get the art!
        foreach ($images as $artRow) {
            $artId   = $artRow['object_id'];
            $artType = $artRow['object_type'];
            $artSize = $artRow['size'];
            $artMime = $artRow['mime'];
            $artKind = ($artType == 'video')
                ? 'preview'
                : 'default';

            $extension = Art::extension($artMime);
            $filename  = 'art-' . $artSize . '.' . $extension;
            $folder    = Art::get_dir_on_disk($artType, $artId, $artKind, true);
            if (!$folder) {
                throw new Export\Exception\ArtExportException(
                    T_('local_metadata_dir setting is required to store art on disk')
                );
            }
            $target_file = $folder . $filename;
            $file_handle = fopen($target_file, 'w');
            $is_file     = is_file($target_file);
            if (!$is_file) {
                if ($file_handle === false) {
                    throw new Export\Exception\ArtExportException(
                        sprintf(T_('Unable to open `%s` for writing'), $target_file)
                    );
                }
                $write_result = fwrite(
                    $file_handle,
                    (string) $this->imageRepository->getRawImage($artId, $artType, $artSize, $artMime)
                );
                fclose($file_handle);

                if ($write_result === false) {
                    throw new Export\Exception\ArtExportException(
                        sprintf(T_('Unable to write to `%s`'), $target_file)
                    );
                }

                $count++;
                if (!($count % 100)) {
                    $interactor->info(
                        sprintf(T_('Art files written: %d'), $count),
                        true
                    );
                }
            }
            // require a really good reason to clear this art
            if ($clearData && $is_file) {
                //The file is out so clear the table as well
                $this->logger->critical(
                    'Clearing database image for ' . $artRow['id'],
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                $this->imageRepository->deleteImage($artRow['id']);
            }
        }
    }
}
