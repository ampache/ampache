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

use Ampache\Module\Podcast\Exception\FeedNotLoadableException;
use Ampache\Module\Podcast\Exception\InvalidCatalogException;
use Ampache\Module\Podcast\Exception\InvalidFeedUrlException;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\Exception\PodcastFolderException;
use Ampache\Module\Podcast\Feed\Exception\FeedLoadingException;
use Ampache\Module\Podcast\Feed\FeedLoaderInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles the creation of new podcasts
 */
final class PodcastCreator implements PodcastCreatorInterface
{
    private FeedLoaderInterface $feedLoader;

    private PodcastRepositoryInterface $podcastRepository;

    private LoggerInterface $logger;

    private PodcastSyncerInterface $podcastSyncer;

    private PodcastFolderProviderInterface $podcastFolderProvider;

    public function __construct(
        FeedLoaderInterface $feedLoader,
        PodcastRepositoryInterface $podcastRepository,
        PodcastSyncerInterface $podcastSyncer,
        PodcastFolderProviderInterface $podcastFolderProvider,
        LoggerInterface $logger
    ) {
        $this->feedLoader            = $feedLoader;
        $this->podcastRepository     = $podcastRepository;
        $this->podcastSyncer         = $podcastSyncer;
        $this->podcastFolderProvider = $podcastFolderProvider;
        $this->logger                = $logger;
    }

    /**
     * Creates a new podcast object
     *
     * Loads the feed-url, creates and returns a new podcast object
     *
     * @throws PodcastCreationException
     */
    public function create(
        string $feedUrl,
        Catalog $catalog
    ): Podcast {
        // Feed must be http/https
        if (
            strpos($feedUrl, 'http://') !== 0 &&
            strpos($feedUrl, 'https://') !== 0
        ) {
            throw new InvalidFeedUrlException();
        }

        if ($catalog->supportsType('podcast') === false) {
            throw new InvalidCatalogException();
        }

        // don't allow duplicate podcasts
        $podcast = $this->podcastRepository->findByFeedUrl($feedUrl);
        if ($podcast !== null) {
            Catalog::update_map($catalog->getId(), 'podcast', $podcast->getId());

            return $podcast;
        }

        try {
            $feed = $this->feedLoader->load($feedUrl);
        } catch (FeedLoadingException $e) {
            throw new FeedNotLoadableException();
        }

        $podcast = $this->podcastRepository->prototype()
            ->setCatalog($catalog)
            ->setFeedUrl($feedUrl)
            ->setTitle($feed['title'])
            ->setWebsite($feed['website'])
            ->setDescription($feed['description'])
            ->setLanguage($feed['language'])
            ->setCopyright($feed['copyright'])
            ->setGenerator($feed['generator'])
            ->setLastBuildDate($feed['lastBuildDate']);

        $podcast->save();

        try {
            $this->podcastFolderProvider->getBaseFolder($podcast);
        } catch (PodcastFolderException $e) {
            $this->logger->critical(
                $e->getMessage(),
                [
                    LegacyLogger::CONTEXT_TYPE => self::class
                ]
            );
        }

        $artUrl = (string) $feed['artUrl'];

        if ($artUrl !== '') {
            $art = new Art($podcast->getId(), 'podcast');
            $art->insert_url($artUrl);
        }

        Catalog::update_map($catalog->getId(), 'podcast', $podcast->getId());
        Catalog::count_table('user');

        if ($feed['episodes']) {
            $this->podcastSyncer->addEpisodes($podcast, $feed['episodes']);
        }

        return $podcast;
    }
}
