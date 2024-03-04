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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;
use ZipStream\Exception;
use ZipStream\ZipStream;

final class ZipHandler implements ZipHandlerInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    /**
     * Check that an object type is allowed to be zipped.
     */
    public function isZipable(string $object_type): bool
    {
        return in_array(
            $object_type,
            $this->configContainer->getTypesAllowedForZip()
        );
    }

    /**
     * takes array of full paths to medias
     * zips them, adds art and m3u, and sends them
     *
     * @param string $name name of the zip file to be created
     * @param array $media_files array of full paths to medias to zip create w/ call to get_media_files
     * @param bool $flat_path put the files into a single folder
     */
    public function zip(string $name, array $media_files, bool $flat_path): void
    {
        $art      = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME);
        $addart   = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ART_ZIP_ADD);
        $filter   = preg_replace('/[^a-zA-Z0-9. -]/', '', $name);
        $arc      = new ZipStream(
            comment: (string) $this->configContainer->get(ConfigurationKeyEnum::FILE_ZIP_COMMENT),
            outputName: $filter . ".zip"
        );
        $playlist = '';

        foreach ($media_files as $dir => $files) {
            foreach ($files as $file) {
                $dirname = ($flat_path)
                    ? $filter
                    : dirname($file);
                $artpath = $dirname . '/' . $art;
                $folder  = explode('/', $dirname)[substr_count($dirname, "/")];
                $playlist .= $folder . "/" . basename($file) . "\n";
                try {
                    $arc->addFileFromPath($folder . '/' . basename($file), $file);
                } catch (Exception $e) {
                    $this->logger->error(
                        $e->getMessage(),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
            if ($addart === true && !empty($folder) && !empty($artpath)) {
                try {
                    $arc->addFileFromPath($folder . '/' . $art, $artpath);
                } catch (Exception $e) {
                    $this->logger->error(
                        $e->getMessage(),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
        }
        if (!empty($playlist)) {
            $arc->addFile($filter . ".m3u", $playlist);
        }
        $this->logger->debug(
            'Sending Zip ' . $filter,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $arc->finish();
    }
}
