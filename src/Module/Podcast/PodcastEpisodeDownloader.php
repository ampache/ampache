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
 */

namespace Ampache\Module\Podcast;

use Ampache\Config\AmpConfig;
use Ampache\Module\Podcast\Exception\PodcastFolderException;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Psr\Log\LoggerInterface;

final class PodcastEpisodeDownloader implements PodcastEpisodeDownloaderInterface
{
    private PodcastFolderProviderInterface $podcastFolderProvider;

    private LoggerInterface $logger;

    public function __construct(
        PodcastFolderProviderInterface $podcastFolderProvider,
        LoggerInterface $logger
    ) {
        $this->podcastFolderProvider = $podcastFolderProvider;
        $this->logger                = $logger;
    }

    /**
     * gather
     * download the podcast episode to your catalog
     */
    public function fetch(
        Podcast_Episode $episode
    ): void {
        if (!empty($episode->source)) {
            // existing file (completed)
            $file = $episode->file;
            if (empty($file)) {
                // new file (pending)
                $podcast = new Podcast($episode->podcast);
                try {
                    $path = $this->podcastFolderProvider->getBaseFolder($podcast);
                } catch (PodcastFolderException $e) {
                    $this->logger->critical(
                        sprintf(
                            'Podcast folder error: %s. Check your catalog directory and permissions',
                            $e->getMessage()
                        ),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );

                    return;
                }

                $extension = pathinfo($episode->source, PATHINFO_EXTENSION);
                // match any characters (except ?) before the first occurrence of ?
                if (preg_match('/^[^?]+(?=\?)/', $extension, $matches)) {
                    $extension = $matches[0];
                }
                $file = $path . DIRECTORY_SEPARATOR . $episode->pubdate . '-' . (string)$episode->id . '.' . $extension;
            }

            if (Core::get_filesize(Core::conv_lc_file($file)) == 0) {
                // the file doesn't exist locally so download it

                $this->logger->debug(
                    sprintf('Downloading %s to %s ...', $episode->source, $file),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                // try to use curl for feeds that redirect a lot or have other checks
                $curl       = curl_init($episode->source);
                $filehandle = fopen($file, 'w');
                if ($curl && $filehandle) {
                    $options    = array(
                        CURLOPT_FILE => $filehandle,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_USERAGENT => 'Ampache/' . AmpConfig::get('version'),
                        CURLOPT_REFERER => $episode->source,
                    );
                    curl_setopt_array(
                        $curl,
                        $options
                    );

                    $result = curl_exec($curl);
                    if ($result === false) {
                        $this->logger->debug(
                            'Download error: ' . curl_error($curl),
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    } else {
                        // Download completed successfully
                        $this->logger->debug(
                            'Download completed',
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    }

                    curl_close($curl);
                    fclose($filehandle);
                } else {
                    // fall back to fopen
                    $handle = fopen($episode->source, 'r');
                    if ($handle && file_put_contents($file, $handle)) {
                        $this->logger->debug(
                            'Download completed',
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    }
                }
            }
            // the file exists now so get/update file details in the DB
            if ($file !== null && Core::get_filesize(Core::conv_lc_file($file)) > 0) {
                $this->logger->debug(
                    sprintf('Updating details %s...', $file),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                // file is null until it's downloaded
                if (empty($episode->file)) {
                    $episode->file = $file;
                    Podcast_Episode::update_file($file, $episode->id);
                }
                Catalog::update_media_from_tags($episode);

                return;
            }

            $this->logger->critical(
                'Error when downloading podcast episode.',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return;
        }

        $this->logger->warning(
            sprintf('Cannot download podcast episode %d, empty source.', $episode->id),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
    }
}
