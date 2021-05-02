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
use Ampache\Module\Podcast\Exception\PodcastFeedLoadingException;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use SimpleXMLElement;

/**
 * Syncs and creates new podcast episodes from the feed url
 */
final class PodcastSyncer implements PodcastSyncerInterface
{
    private ConfigContainerInterface $configContainer;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastEpisodeCreatorInterface $podcastEpisodeCreator;

    private PodcastRepositoryInterface $podcastRepository;

    private PodcastEpisodeDeleterInterface $podcastEpisodeDeleter;

    private PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader;

    private PodcastFeedLoaderInterface $podcastFeedLoader;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastEpisodeCreatorInterface $podcastEpisodeCreator,
        PodcastRepositoryInterface $podcastRepository,
        PodcastEpisodeDeleterInterface $podcastEpisodeDeleter,
        PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader,
        PodcastFeedLoaderInterface $podcastFeedLoader
    ) {
        $this->configContainer          = $configContainer;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastEpisodeCreator    = $podcastEpisodeCreator;
        $this->podcastRepository        = $podcastRepository;
        $this->podcastEpisodeDeleter    = $podcastEpisodeDeleter;
        $this->podcastEpisodeDownloader = $podcastEpisodeDownloader;
        $this->podcastFeedLoader        = $podcastFeedLoader;
    }

    public function sync(
        PodcastInterface $podcast,
        bool $gather = false
    ): bool {
        try {
            $xml = $this->podcastFeedLoader->load($podcast->getFeed());
        } catch (PodcastFeedLoadingException $e) {
            return false;
        }

        $this->addEpisodes($podcast, $xml->channel->item, $podcast->getLastSync(), $gather);

        return true;
    }

    public function addEpisodes(
        PodcastInterface $podcast,
        SimpleXMLElement $episodes,
        int $afterdate = 0,
        bool $gather = false
    ): void {
        foreach ($episodes as $episode) {
            $this->podcastEpisodeCreator->create(
                $podcast,
                $episode,
                $afterdate
            );
        }

        // Select episodes to download
        $episodeDownloadAmount = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD);
        if ($episodeDownloadAmount <> 0) {
            $episodes = $this->podcastEpisodeRepository->getDownloadableEpisodes(
                $podcast,
                $episodeDownloadAmount
            );

            foreach ($episodes as $episode) {
                $this->podcastEpisodeRepository->changeState($episode, PodcastStateEnum::PENDING);
                if ($gather) {
                    $this->podcastEpisodeDownloader->download($episode);
                }
            }
        }

        // Remove items outside limit
        $episodeKeepAmount = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_KEEP);
        if ($episodeKeepAmount > 0) {
            $episodes = $this->podcastEpisodeRepository->getDeletableEpisodes(
                $podcast,
                $episodeKeepAmount
            );

            foreach ($episodes as $episode) {
                $this->podcastEpisodeDeleter->delete($episode);
            }
        }

        $this->podcastRepository->updateLastsync($podcast, time());
    }
}
