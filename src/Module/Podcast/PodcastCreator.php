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
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Podcast\Exception\PodcastFeedLoadingException;
use Ampache\Module\System\AmpError;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Log\LoggerInterface;

final class PodcastCreator implements PodcastCreatorInterface
{
    private ModelFactoryInterface $modelFactory;

    private PodcastSyncerInterface $podcastSyncer;

    private CatalogLoaderInterface $catalogLoader;

    private PodcastFeedLoaderInterface $podcastFeedLoader;

    private PodcastRepositoryInterface $podcastRepository;

    private LoggerInterface $logger;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        PodcastSyncerInterface $podcastSyncer,
        CatalogLoaderInterface $catalogLoader,
        PodcastFeedLoaderInterface $podcastFeedLoader,
        PodcastRepositoryInterface $podcastRepository,
        LoggerInterface $logger
    ) {
        $this->modelFactory      = $modelFactory;
        $this->podcastSyncer     = $podcastSyncer;
        $this->catalogLoader     = $catalogLoader;
        $this->podcastFeedLoader = $podcastFeedLoader;
        $this->podcastRepository = $podcastRepository;
        $this->logger            = $logger;
    }

    public function create(
        string $feedUrl,
        int $catalog_id
    ): ?PodcastInterface {
        // Feed must be http/https
        if (strpos($feedUrl, "http://") !== 0 && strpos($feedUrl, "https://") !== 0) {
            AmpError::add('feed', T_('Feed URL is invalid'));
        }

        if ($catalog_id < 1) {
            AmpError::add('catalog', T_('Target Catalog is required'));
        } else {
            $catalog = $this->catalogLoader->byId($catalog_id);
            if ($catalog->gather_types !== "podcast") {
                AmpError::add('catalog', T_('Wrong target Catalog type'));
            }
        }

        if (AmpError::occurred()) {
            return null;
        }

        // don't allow duplicate podcasts
        $podcastId = $this->podcastRepository->findByFeedUrl($feedUrl);
        if ($podcastId !== null) {
            return $this->podcastRepository->findById($podcastId);
        }

        try {
            $xml = $this->podcastFeedLoader->load($feedUrl);
        } catch (PodcastFeedLoadingException $e) {
            AmpError::add('feed', T_('Can not read the feed'));

            return null;
        }

        $title            = html_entity_decode((string)$xml->channel->title);
        $website          = (string)$xml->channel->link;
        $description      = html_entity_decode((string)$xml->channel->description);
        $language         = (string)$xml->channel->language;
        $copyright        = html_entity_decode((string)$xml->channel->copyright);
        $generator        = html_entity_decode((string)$xml->channel->generator);
        $lastbuilddatestr = (string)$xml->channel->lastBuildDate;
        if ($lastbuilddatestr) {
            $lastbuilddate = strtotime($lastbuilddatestr);
        } else {
            $lastbuilddate = 0;
        }

        if ($xml->channel->image) {
            $arturl = (string)$xml->channel->image->url;
        } else {
            $arturl = '';
        }

        $episodes = $xml->channel->item;

        $podcastId = $this->podcastRepository->insert(
            $feedUrl,
            $catalog_id,
            $title,
            $website,
            $description,
            $language,
            $copyright,
            $generator,
            $lastbuilddate
        );

        if ($podcastId === null) {
            return null;
        }

        $podcast = $this->podcastRepository->findById($podcastId);
        if (!empty($arturl)) {
            $art = new Art((int)$podcastId, 'podcast');
            $art->insert_url($arturl);
        }
        if ($episodes) {
            $this->podcastSyncer->addEpisodes($podcast, $episodes);
        }

        return $podcast;
    }
}
