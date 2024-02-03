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
 *
 */

namespace Ampache\Module\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Generator;
use Psr\Log\LoggerInterface;

/**
 * Provides functionality to delete podcasts and -episodes
 */
final class PodcastDeleter implements PodcastDeleterInterface
{
    private PodcastRepositoryInterface $podcastRepository;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private ConfigContainerInterface $config;

    public function __construct(
        PodcastRepositoryInterface $podcastRepository,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $config,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        LoggerInterface $logger
    ) {
        $this->podcastRepository        = $podcastRepository;
        $this->modelFactory             = $modelFactory;
        $this->config                   = $config;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->logger                   = $logger;
    }

    /**
     * Deletes a podcast including its episodes
     */
    public function delete(
        Podcast $podcast
    ): void {
        $this->logger->debug(
            sprintf('Removing podcast %s', $podcast->getId()),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        $episodeIterable = function (Podcast $podcast): Generator {
            $episodes = $this->podcastEpisodeRepository->getEpisodes($podcast);

            foreach ($episodes as $episodeId) {
                yield $this->modelFactory->createPodcastEpisode($episodeId);
            }
        };

        $this->deleteEpisode($episodeIterable($podcast));

        $this->podcastRepository->delete($podcast);

        Catalog::count_table('podcast');
    }

    /**
     * Delete the provided podcast-episodes
     *
     * @param iterable<Podcast_Episode> $episodes
     */
    public function deleteEpisode(iterable $episodes): void
    {
        foreach ($episodes as $episode) {
            $this->logger->debug(
                sprintf('Removing podcast episode %s', $episode->getId()),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $filePath = (string) $episode->file;

            if ($this->config->get(ConfigurationKeyEnum::DELETE_FROM_DISK) && Core::is_readable($filePath)) {
                @unlink($filePath);
            }

            $this->podcastEpisodeRepository->deleteEpisode($episode);
        }

        Catalog::count_table('podcast_episode');
    }
}
