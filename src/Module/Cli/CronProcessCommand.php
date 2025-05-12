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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Cache\ObjectCacheInterface;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;

final class CronProcessCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ObjectCacheInterface $objectCache;

    private CatalogGarbageCollectorInterface $catalogGarbageCollector;

    private BookmarkRepositoryInterface $bookmarkRepository;

    private UserRepositoryInterface $userRepository;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private ShareRepositoryInterface $shareRepository;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn ($exitCode = 0) => exit($exitCode));

        return $this;
    }

    public function __construct(
        ConfigContainerInterface $configContainer,
        ObjectCacheInterface $objectCache,
        CatalogGarbageCollectorInterface $catalogGarbageCollector,
        BookmarkRepositoryInterface $bookmarkRepository,
        UserRepositoryInterface $userRepository,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        ShareRepositoryInterface $shareRepository,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository
    ) {
        parent::__construct('run:cronProcess', T_('Run the cron process'));

        $this->configContainer          = $configContainer;
        $this->objectCache              = $objectCache;
        $this->catalogGarbageCollector  = $catalogGarbageCollector;
        $this->bookmarkRepository       = $bookmarkRepository;
        $this->userRepository           = $userRepository;
        $this->updateInfoRepository     = $updateInfoRepository;
        $this->shareRepository          = $shareRepository;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        if (!$this->configContainer->get('cron_cache')) {
            debug_event(self::class, 'ENABLE \'Cache computed SQL data (eg. media hits stats) using a cron\' * In Admin -> Server Config -> System -> Catalog', 5);

            $interactor->error(
                T_('Cron cache not enabled'),
                true
            );

            return;
        }

        $start_time = time();
        debug_event(self::class, 'started cron process', 3);
        $interactor->info(
            T_('Ampache Cron'),
            true
        );

        /**
         * Catalog garbage_collection covers these functions
         *
         * Song::garbage_collection();
         * Album::garbage_collection();
         * Artist::garbage_collection();
         * Video::garbage_collection();
         * Art::garbage_collection();
         * Stats::garbage_collection();
         * Rating::garbage_collection();
         * Ampache\Model\Userflag::garbage_collection();
         * Ampache\Model\Useractivity::garbage_collection();
         * Playlist::garbage_collection();
         * Shoutbox::garbage_collection();
         * Tag::garbage_collection();
         * Metadata::garbage_collection();
         * MetadataField::garbage_collection();
         */
        $this->catalogGarbageCollector->collect();
        debug_event(self::class, 'finished catalogGarbageCollector->collect()', 5);

        /**
         * Session garbage_collection covers these functions.
         *
         * Query::garbage_collection();
         * Stream_Playlist::garbage_collection();
         * Ampache\Model\Song_Preview::garbage_collection();
         * Ampache\Model\Tmp_Playlist::garbage_collection();
         */
        Session::garbage_collection();
        debug_event(self::class, 'finished Session::garbage_collection()', 5);

        /**
         * Clean up remaining functions.
         */
        $this->shareRepository->collectGarbage();
        debug_event(self::class, 'finished shareRepository->collectGarbage()', 5);
        Stream::garbage_collection();
        debug_event(self::class, 'finished Stream::garbage_collection()', 5);
        $this->podcastEpisodeRepository->collectGarbage();
        debug_event(self::class, 'finished podcastEpisodeRepository->collectGarbage()', 5);
        $this->bookmarkRepository->collectGarbage();
        debug_event(self::class, 'finished bookmarkRepository->collectGarbage()', 5);
        Recommendation::garbage_collection();
        debug_event(self::class, 'finished Recommendation::garbage_collection()', 5);
        $this->userRepository->collectGarbage();
        debug_event(self::class, 'finished userRepository->collectGarbage()', 5);

        /**
         * Run compute_cache
         */
        $this->objectCache->compute();
        debug_event(self::class, 'finished objectCache->compute()', 5);

        $time = time();

        // mark the date this cron was completed.
        $this->updateInfoRepository->setValue(
            UpdateInfoEnum::CRON_DATE,
            (string)$time
        );

        debug_event(self::class, 'finished cron process', 4);

        $interactor->info(
            T_('Cron process finished'),
            true
        );

        $time_diff = time() - $start_time;
        $interactor->info(
            T_('Time') . ": " . date('i:s', $time_diff),
            true
        );
    }
}
