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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
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
     * zips them and sends them
     *
     * @param string $name name of the zip file to be created
     * @param array $media_files array of full paths to medias to zip create w/ call to get_media_files
     */
    public function zip(string $name, array $media_files): void
    {
        $filter  = preg_replace('/[^a-zA-Z0-9. -]/', '', $name);
        $arc     = new ZipStream($filter . ".zip");
        $options = [
            'comment' => $this->configContainer->get('file_zip_comment'),
        ];

        foreach ($media_files as $dir => $files) {
            foreach ($files as $file) {
                try {
                    $arc->addFileFromPath($dir . "/" . basename($file), $file, $options);
                } catch (Exception $e) {
                }
            }
        }
        $this->logger->debug(
            'Sending Zip ' . $name,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $arc->finish();
    }
}
