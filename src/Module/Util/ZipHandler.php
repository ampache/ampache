<?php

declare(strict_types=0);

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
     * @param array{total_size: int, files: iterable<iterable<string>>} $files array of full paths to medias to zip create w/ call to get_media_files
     * @param bool $flat_path put the files into a single folder
     */
    public function zip(
        ResponseInterface $response,
        string $name,
        array $files,
        bool $flat_path
    ): ResponseInterface {
        $art_name    = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME);
        $addart      = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ART_ZIP_ADD);
        $archiveName = (string)preg_replace('/[^a-zA-Z0-9. -]/', '', $name);
        $comment     = (string)$this->configContainer->get(ConfigurationKeyEnum::FILE_ZIP_COMMENT);

        $this->zipFile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . uniqid('ampache-zip-');

        ob_end_clean();

        /* Drop the normal Time limit constraints, this can take a while */
        set_time_limit(0);

        // Write/close session data to release session lock for this script.
        // This to allow other pages from the same session to be processed
        // Do NOT change any session variable after this call
        session_write_close();

        // Take whatever we've got and send the zip
        set_memory_limit($files['total_size'] + 32);

        $arc = new ZipArchive();
        $arc->open($this->zipFile, ZipArchive::CREATE);
        if (!empty($comment)) {
            $arc->setArchiveComment($comment);
        }

        $playlist = '';
        $folder   = '';
        $artpath  = '';
        foreach ($files['files'] as $file_list) {
            foreach ($file_list as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $dirname = ($flat_path)
                    ? $archiveName
                    : dirname($file);
                $artpath = $dirname . DIRECTORY_SEPARATOR . $art_name;
                $folder  = explode(DIRECTORY_SEPARATOR, $dirname)[substr_count($dirname, DIRECTORY_SEPARATOR)];
                $playlist .= $folder . DIRECTORY_SEPARATOR . basename($file) . "\n";

                $arc->addEmptyDir($folder, ZipArchive::CREATE);
                $arc->addFile($file, $folder . DIRECTORY_SEPARATOR . basename($file));
            }
        }
        if (
            $addart === true &&
            !empty($folder) &&
            is_file($artpath)
        ) {
            $arc->addFile($artpath, $folder . DIRECTORY_SEPARATOR . $art_name);
        }
        if (!empty($playlist) && !empty($folder)) {
            $arc->addEmptyDir($folder, ZipArchive::CREATE);
            $arc->addFromString($archiveName . ".m3u", $playlist);
        }

        $arc->close();

        $this->logger->debug(
            'Sending Zip ' . $archiveName,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        // Various different browsers dislike various characters here. Strip them all for safety.
        $normalizedArchiveName = trim(str_replace(['"', "'", '\\', ';', "\n", "\r"], '', $archiveName . '.zip'));

        return $response
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', sprintf('attachment; filename*=UTF-8\'\'%s', rawurlencode($normalizedArchiveName)))
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
