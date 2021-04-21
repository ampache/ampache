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
 * along with podcastEpisode program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Psr\Log\LoggerInterface;

final class PodcastEpisodeDownloader implements PodcastEpisodeDownloaderInterface
{
    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private LoggerInterface $logger;

    private UtilityFactoryInterface $utilityFactory;

    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        LoggerInterface $logger,
        UtilityFactoryInterface $utilityFactory,
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->logger                   = $logger;
        $this->utilityFactory           = $utilityFactory;
        $this->catalogLoader            = $catalogLoader;
    }

    /**
     * Downloads the podcast episode to the catalog
     */
    public function download(PodcastEpisodeInterface $podcastEpisode): void
    {
        $source = $podcastEpisode->getSource();

        if (!empty($source)) {
            $podcast  = $podcastEpisode->getPodcast();
            $rootPath = $this->getRootPath($podcast);
            if (!empty($rootPath)) {
                $pinfo = pathinfo($source);

                $file = sprintf(
                    '%s%s%s-%s',
                    $rootPath,
                    DIRECTORY_SEPARATOR,
                    $podcastEpisode->getPublicationDate(),
                    strtok($pinfo['basename'], '?')
                );

                $this->logger->info(
                    sprintf('Downloading %s to %s ...', $source, $file),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                if (file_put_contents($file, fopen($source, 'r')) !== false) {
                    $this->logger->info(
                        'Download completed.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    $vainfo = $this->utilityFactory->createVaInfo($file);
                    $vainfo->get_info();

                    $infos = $vainfo->cleanTagInfo(
                        $vainfo->tags,
                        $vainfo->getTagType($vainfo->tags),
                        $file
                    );

                    // No time information, get it from file
                    if ($podcastEpisode->time < 1) {
                        $time = $infos['time'];
                    } else {
                        $time = $podcastEpisode->time;
                    }

                    $this->podcastEpisodeRepository->updateDownloadState(
                        $podcastEpisode,
                        $file,
                        (int) $infos['size'],
                        (int) $time,
                        (int) ($infos['bitrate'] ?? 0),
                        (int) ($infos['rate'] ?? 0),
                        (string) ($infos['mode'] ?? 'CBR')
                    );
                } else {
                    $this->logger->critical(
                        'Error when downloading podcast episode.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
        } else {
            $this->logger->error(
                sprintf('Cannot download podcast episode %d, empty source.', $podcastEpisode->getId()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }
    }

    private function getRootPath(
        PodcastInterface $podcast
    ): string {
        $catalog = $this->catalogLoader->byId($podcast->getCatalog());
        if (!$catalog->get_type() == 'local') {
            $this->logger->critical(
                'Bad catalog type.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return '';
        }

        $path = $catalog->path . DIRECTORY_SEPARATOR . $podcast->getTitle();

        // create path if it doesn't exist
        if (!is_dir($path)) {
            if (@mkdir($path) === false) {
                $this->logger->error(
                    sprintf('Podcast directory is not writable: %s', $path),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }

        return $path;
    }
}
