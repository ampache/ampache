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

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes a podcast episode
 */
final class PodcastEpisodeDeleter implements PodcastEpisodeDeleterInterface
{
    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->logger                   = $logger;
        $this->configContainer          = $configContainer;
    }

    public function delete(
        PodcastEpisodeInterface $podcastEpisode
    ): bool {
        $this->logger->debug(
            sprintf('Removing podcast episode %d', $podcastEpisode->getId()),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DELETE_FROM_DISK) &&
            !empty($podcastEpisode->file)
        ) {
            if (!@unlink($podcastEpisode->file)) {
                $this->logger->error(
                    sprintf('Cannot delete file %s', $podcastEpisode->file),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }

        return $this->podcastEpisodeRepository->remove($podcastEpisode);
    }
}
