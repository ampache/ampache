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
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use ZipArchive;

final class ZipHandler implements ZipHandlerInterface
{
    private ?string $zipFile = null;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly LoggerInterface $logger
    ) {
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
    public function zip(
        ResponseInterface $response,
        string $name,
        array $media_files,
        bool $flat_path
    ): ResponseInterface {
        $art      = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME);
        $addart   = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ART_ZIP_ADD);
        $filter   = (string)preg_replace('/[^a-zA-Z0-9. -]/', '', $name);

        $this->zipFile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . uniqid('ampache-zip-');

        $arc = new ZipArchive();
        $arc->open($this->zipFile, ZipArchive::CREATE);
        $arc->setArchiveComment((string) $this->configContainer->get(ConfigurationKeyEnum::FILE_ZIP_COMMENT));

        $playlist = '';
        $folder   = '';
        $artpath  = '';
        foreach ($media_files as $files) {
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $dirname = ($flat_path)
                    ? $filter
                    : dirname($file);
                $artpath = $dirname . DIRECTORY_SEPARATOR . $art;
                $folder  = explode(DIRECTORY_SEPARATOR, $dirname)[substr_count($dirname, DIRECTORY_SEPARATOR)];
                $playlist .= $folder . DIRECTORY_SEPARATOR . basename($file) . "\n";
                $arc->addEmptyDir($folder, ZipArchive::CREATE);
                $arc->addFile($file, $folder . DIRECTORY_SEPARATOR . basename($file));
            }
            if ($addart === true && !empty($folder) && is_file($artpath)) {
                $arc->addFile($artpath, $folder . DIRECTORY_SEPARATOR . $art);
            }
        }
        if (!empty($playlist) && !empty($folder)) {
            $arc->addEmptyDir($folder, ZipArchive::CREATE);
            $arc->addFromString($filter . ".m3u", $playlist);
        }
        $this->logger->debug(
            'Sending Zip ' . $filter,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $arc->close();

        // Various different browsers dislike various characters here. Strip them all for safety.
        $safeOutput = trim(str_replace(['"', "'", '\\', ';', "\n", "\r"], '', $filter . '.zip'));

        // Check if we need to UTF-8 encode the filename
        $urlencoded = rawurlencode($safeOutput);

        return $response
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', sprintf('attachment; filename*=UTF-8\'\'%s', $urlencoded))
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'public, must-revalidate')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withBody(
                $this->streamFactory->createStreamFromFile($this->zipFile)
            );
    }

    public function __destruct()
    {
        // cleanup the generated file
        if ($this->zipFile) {
            @unlink($this->zipFile);
        }
        $this->zipFile = null;
    }
}
