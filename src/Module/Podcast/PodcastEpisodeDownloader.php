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

                $pinfo = pathinfo($episode->source);
                $file  = $path . DIRECTORY_SEPARATOR . $episode->pubdate . '-' . str_replace(array('?', '<', '>', '\\', '/'), '_', (string)$episode->title) . '-' . strtok($pinfo['basename'], '?');
            }

            if (Core::get_filesize(Core::conv_lc_file($file)) == 0) {
                // the file doesn't exist locally so download it

                $this->logger->debug(
                    sprintf('Downloading %s to %s ...', $episode->source, $file),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                $handle = fopen($episode->source, 'r');
                if ($handle && file_put_contents($file, $handle)) {
                    $this->logger->debug(
                        'Download completed',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }

            if ($file !== null && Core::get_filesize(Core::conv_lc_file($file)) > 0) {
                // the file exists so get/update file details in the DB
                $this->logger->debug(
                    sprintf('Updating details %s...', $file),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

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
