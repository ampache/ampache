<?php

declare(strict_types=1);

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

namespace Ampache\Module\Podcast;

use Ampache\Module\Podcast\Exception\PodcastFolderException;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Downloads podcast episode-files and update media information
 */
final class PodcastEpisodeDownloader implements PodcastEpisodeDownloaderInterface
{
    private PodcastFolderProviderInterface $podcastFolderProvider;

    private WebFetcherInterface $webFetcher;

    private PodcastRepositoryInterface $podcastRepository;

    private LoggerInterface $logger;

    public function __construct(
        PodcastFolderProviderInterface $podcastFolderProvider,
        WebFetcherInterface $webFetcher,
        PodcastRepositoryInterface $podcastRepository,
        LoggerInterface $logger
    ) {
        $this->podcastFolderProvider = $podcastFolderProvider;
        $this->webFetcher            = $webFetcher;
        $this->podcastRepository     = $podcastRepository;
        $this->logger                = $logger;
    }

    /**
     * Download the podcast-episodes files and perform media info update
     */
    public function fetch(
        Podcast_Episode $episode
    ): void {
        $source    = $episode->getSource();
        $episodeId = $episode->getId();

        if ($source === '') {
            $this->logger->warning(
                sprintf('Cannot download podcast episode %d, empty source.', $episodeId),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return;
        }

        // existing file (completed)
        $destinationFilePath = $episode->getFile();
        if ($destinationFilePath === '') {
            // new file (pending)
            $podcast = $this->podcastRepository->findById($episode->getPodcastId());
            if ($podcast === null) {
                return;
            }

            try {
                $path = $this->podcastFolderProvider->getBaseFolder($podcast);
            } catch (PodcastFolderException $error) {
                $this->logger->error(
                    sprintf(
                        'Podcast folder error: %s. Check your catalog directory and permissions',
                        $error->getMessage()
                    ),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return;
            }

            $extension = pathinfo($source, PATHINFO_EXTENSION);

            // match any characters (except ?) before the first occurrence of ?
            if (preg_match('/^[^?]+(?=\?)/', $extension, $matches)) {
                $extension = $matches[0];
            }

            $destinationFilePath = sprintf(
                '%s%s%s-%s.%s',
                $path,
                DIRECTORY_SEPARATOR,
                $episode->pubdate,
                $episodeId,
                $extension
            );
        }

        if (Core::get_filesize(Core::conv_lc_file($destinationFilePath)) === 0) {
            // the file doesn't exist locally so download it

            $this->logger->debug(
                sprintf('Downloading %s to %s ...', $source, $destinationFilePath),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            try {
                $this->webFetcher->fetchToFile($source, $destinationFilePath);
            } catch (FetchFailedException $error) {
                $this->logError($error->getMessage());

                return;
            }
        }

        // the file exists now so get/update file details in the DB
        if (Core::get_filesize(Core::conv_lc_file($destinationFilePath)) > 0) {
            $this->logger->debug(
                sprintf('Updating details %s...', $destinationFilePath),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            // file is null until it's downloaded
            if (empty($episode->file)) {
                $episode->file = $destinationFilePath;
                Podcast_Episode::update_file($destinationFilePath, $episodeId);
            }
            Catalog::update_media_from_tags($episode);

            return;
        }

        $this->logError();
    }

    private function logError(?string $previousError = null): void
    {
        $errorMessage = 'Error when downloading podcast episode.';
        if ($previousError !== null) {
            $errorMessage .= sprintf(' %s', $previousError);
        }

        $this->logger->critical(
            $errorMessage,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
    }
}
