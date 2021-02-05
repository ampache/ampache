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
use Ampache\Model\Bookmark;
use Ampache\Model\Podcast_Episode;
use Ampache\Model\Share;
use Ampache\Module\Cache\ObjectCacheInterface;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Cron;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\BookmarkRepositoryInterface;

final class CronProcessCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ObjectCacheInterface $objectCache;

    private CatalogGarbageCollectorInterface $catalogGarbageCollector;

    private BookmarkRepositoryInterface $bookmarkRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ObjectCacheInterface $objectCache,
        CatalogGarbageCollectorInterface $catalogGarbageCollector,
        BookmarkRepositoryInterface $bookmarkRepository
    ) {
        parent::__construct('run:cronProcess', 'Runs the cron process');

        $this->configContainer         = $configContainer;
        $this->objectCache             = $objectCache;
        $this->catalogGarbageCollector = $catalogGarbageCollector;
        $this->bookmarkRepository      = $bookmarkRepository;
    }

    public function execute(): void
    {
        $io = $this->app()->io();

        if (!$this->configContainer->get('cron_cache')) {
            debug_event('cron.inc', 'ENABLE \'Cache computed SQL data (eg. media hits stats) using a cron\' * In Admin -> Server Config -> System -> Catalog', 5);

            $io->error(
                T_('Cron cache not enabled'),
                true
            );

            return;
        }
        debug_event('cron', 'started cron process', 3);

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
         * Ampache\Model\Userflag::garbage_collection();
         * Ampache\Model\Useractivity::garbage_collection();
         * Playlist::garbage_collection();
         * Ampache\Model\Tmp_Playlist::garbage_collection(); FIXME Duplicated with Session
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
         * Ampache\Model\Song_Preview::garbage_collection();
         * Ampache\Model\Tmp_Playlist::garbage_collection(); FIXME Duplicated with Catalog
         */
        Session::garbage_collection();

        /**
         * Clean up remaining functions.
         */
        Share::garbage_collection();
        Stream::garbage_collection();
        Podcast_Episode::garbage_collection();
        $this->bookmarkRepository->collectGarbage();
        Recommendation::garbage_collection();

        /**
         * Run compute_cache
         */
        $this->objectCache->compute();

        // mark the date this cron was completed.
        Cron::set_cron_date();

        debug_event('cron', 'finished cron process', 4);

        $io->white(T_('Cron process finished'), true);
    }
}
