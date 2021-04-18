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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Cache\ObjectCacheInterface;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\NowPlayingRepositoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\RecommendationRepositoryInterface;
use Ampache\Repository\SessionRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Log\LoggerInterface;

final class CronProcessCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ObjectCacheInterface $objectCache;

    private CatalogGarbageCollectorInterface $catalogGarbageCollector;

    private BookmarkRepositoryInterface $bookmarkRepository;

    private ShareRepositoryInterface $shareRepository;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private LoggerInterface $logger;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private RecommendationRepositoryInterface $recommendationRepository;

    private SessionRepositoryInterface $sessionRepository;

    private NowPlayingRepositoryInterface $nowPlayingRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ObjectCacheInterface $objectCache,
        CatalogGarbageCollectorInterface $catalogGarbageCollector,
        BookmarkRepositoryInterface $bookmarkRepository,
        ShareRepositoryInterface $shareRepository,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        LoggerInterface $logger,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        RecommendationRepositoryInterface $recommendationRepository,
        SessionRepositoryInterface $sessionRepository,
        NowPlayingRepositoryInterface $nowPlayingRepository
    ) {
        parent::__construct('run:cronProcess', T_('Run the cron process'));

        $this->configContainer          = $configContainer;
        $this->objectCache              = $objectCache;
        $this->catalogGarbageCollector  = $catalogGarbageCollector;
        $this->bookmarkRepository       = $bookmarkRepository;
        $this->shareRepository          = $shareRepository;
        $this->updateInfoRepository     = $updateInfoRepository;
        $this->logger                   = $logger;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->recommendationRepository = $recommendationRepository;
        $this->sessionRepository        = $sessionRepository;
        $this->nowPlayingRepository     = $nowPlayingRepository;
    }

    public function execute(): void
    {
        $io = $this->app()->io();

        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CRON_CACHE)) {
            $this->logger->debug(
                'ENABLE \'Cache computed SQL data (eg. media hits stats) using a cron\' * In Admin -> Server Config -> System -> Catalog',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $io->error(
                T_('Cron cache not enabled'),
                true
            );

            return;
        }

        $this->logger->info(
            'started cron process',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        /**
         * Catalog garbage_collection covers these functions
         *
         * Song::garbage_collection();
         * Album::garbage_collection();
         * Artist::garbage_collection();
         * Video::garbage_collection();
         * Movie::garbage_collection();
         * Art::garbage_collection();
         * Stats::garbage_collection();
         * Rating::garbage_collection();
         * AUserflag::garbage_collection();
         * Useractivity::garbage_collection();
         * Playlist::garbage_collection();
         * Tmp_Playlist::garbage_collection(); FIXME Duplicated with Session
         * Shoutbox::garbage_collection();
         * Tag::garbage_collection();
         * Metadata::garbage_collection();
         * MetadataField::garbage_collection();
         */
        $this->catalogGarbageCollector->collect();

        /**
         * Session garbage_collection covers these functions.
         *
         * Query::garbage_collection();
         * Stream_Playlist::garbage_collection();
         * Song_Preview::garbage_collection();
         * Tmp_Playlist::garbage_collection(); FIXME Duplicated with Catalog
         */
        $this->sessionRepository->collectGarbage();

        /**
         * Clean up remaining functions.
         */
        $this->shareRepository->collectGarbage();
        $this->nowPlayingRepository->collectGarbage();
        $this->podcastEpisodeRepository->collectGarbage();
        $this->bookmarkRepository->collectGarbage();
        $this->recommendationRepository->collectGarbage();

        /**
         * Run compute_cache
         */
        $this->objectCache->compute();

        /**
         *mark the date this cron was completed.
         */
        $this->updateInfoRepository->setLastCronDate();

        $this->logger->info(
            'finished cron process',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $io->white(T_('Cron process finished'), true);
    }
}
