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

declare(strict_types=0);

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\Album\AlbumArtistUpdaterInterface;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Song\Tag\SongId3TagWriterInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Song;
use Psr\Log\LoggerInterface;

final class CatalogProcessor implements CatalogProcessorInterface
{
    private CatalogRepositoryInterface $catalogRepository;

    private CatalogLoaderInterface $catalogLoader;

    private SingleItemUpdaterInterface $singleItemUpdater;

    private SongId3TagWriterInterface $songId3TagWriter;

    private CatalogGarbageCollectorInterface $catalogGarbageCollector;

    private AlbumRepositoryInterface $albumRepository;

    private AlbumArtistUpdaterInterface $albumArtistUpdater;

    private CatalogStatisticUpdaterInterface $catalogStatisticUpdater;

    private LoggerInterface $logger;

    public function __construct(
        CatalogRepositoryInterface $catalogRepository,
        CatalogLoaderInterface $catalogLoader,
        SingleItemUpdaterInterface $singleItemUpdater,
        SongId3TagWriterInterface $songId3TagWriter,
        CatalogGarbageCollectorInterface $catalogGarbageCollector,
        AlbumRepositoryInterface $albumRepository,
        AlbumArtistUpdaterInterface $albumArtistUpdater,
        CatalogStatisticUpdaterInterface $catalogStatisticUpdater,
        LoggerInterface $logger
    ) {
        $this->catalogRepository       = $catalogRepository;
        $this->catalogLoader           = $catalogLoader;
        $this->singleItemUpdater       = $singleItemUpdater;
        $this->songId3TagWriter        = $songId3TagWriter;
        $this->catalogGarbageCollector = $catalogGarbageCollector;
        $this->albumRepository         = $albumRepository;
        $this->albumArtistUpdater      = $albumArtistUpdater;
        $this->catalogStatisticUpdater = $catalogStatisticUpdater;
        $this->logger                  = $logger;
    }

    /**
     * @param null|array<int> $catalogs
     * @param null|array<string, mixed> $options
     */
    public function process(
        string $action,
        ?array $catalogs,
        ?array $options = null
    ): void {
        if (!$options || !is_array($options)) {
            $options = array();
        }

        switch ($action) {
            case 'add_to_all_catalogs':
                $catalogs = $this->catalogRepository->getList();
            // Intentional break fall-through
            case 'add_to_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = $this->catalogLoader->byId($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog($options);
                        }
                    }

                    if (!defined('SSE_OUTPUT') && !defined('CLI')) {
                        echo AmpError::display('catalog_add');
                    }
                }
                break;
            case 'update_all_catalogs':
                $catalogs = $this->catalogRepository->getList();
            // Intentional break fall-through
            case 'update_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = $this->catalogLoader->byId($catalog_id);
                        if ($catalog !== null) {
                            $catalog->verify_catalog();
                        }
                    }
                }
                break;
            case 'full_service':
                if (!$catalogs) {
                    $catalogs = $this->catalogRepository->getList();
                }

                /* This runs the clean/verify/add in that order */
                foreach ($catalogs as $catalog_id) {
                    $catalog = $this->catalogLoader->byId($catalog_id);
                    if ($catalog !== null) {
                        $catalog->clean_catalog();
                        $catalog->verify_catalog();
                        $catalog->add_to_catalog();
                    }
                }
                Dba::optimize_tables();
                break;
            case 'clean_all_catalogs':
                $catalogs = $this->catalogRepository->getList();
            // Intentional break fall-through
            case 'clean_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = $this->catalogLoader->byId($catalog_id);
                        if ($catalog !== null) {
                            $catalog->clean_catalog();
                        }
                    } // end foreach catalogs
                    Dba::optimize_tables();
                }
                break;
            case 'update_from':
                $catalog_id = 0;
                // First see if we need to do an add
                if ($options['add_path'] != '/' && strlen((string)$options['add_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($options['add_path'])) {
                        $catalog = $this->catalogLoader->byId($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog(array('subdirectory' => $options['add_path']));
                        }
                    }
                } // end if add

                // Now check for an update
                if ($options['update_path'] != '/' && strlen((string)$options['update_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($options['update_path'])) {
                        $songs = Song::get_from_path($options['update_path']);
                        foreach ($songs as $song_id) {
                            $this->singleItemUpdater->update('song', (int) $song_id);
                        }
                    }
                } // end if update

                if ($catalog_id < 1) {
                    AmpError::add('general',
                        T_("This subdirectory is not inside an existing Catalog. The update can not be processed."));
                }
                break;
            case 'gather_media_art':
                if (!$catalogs) {
                    $catalogs = $this->catalogRepository->getList();
                }

                // Iterate throughout the catalogs and gather as needed
                foreach ($catalogs as $catalog_id) {
                    $catalog = $this->catalogLoader->byId($catalog_id);
                    if ($catalog !== null) {
                        require Ui::find_template('show_gather_art.inc.php');
                        flush();
                        $catalog->gather_art();
                    }
                }
                break;
            case 'update_all_file_tags':
                $catalogs = $this->catalogRepository->getList();
            // Intentional break fall-through
            case 'update_file_tags':
                $write_id3     = AmpConfig::get('write_id3', false);
                $write_id3_art = AmpConfig::get('write_id3_art', false);
                AmpConfig::set_by_array(['write_id3' => 'true'], true);
                AmpConfig::set_by_array(['write_id3_art' => 'true'], true);

                set_time_limit(0);

                if ($catalogs !== null) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = $this->catalogLoader->byId($catalog_id);
                        if ($catalog !== null) {
                            $song_ids = $catalog->get_song_ids();
                            foreach ($song_ids as $song_id) {
                                $song = new Song($song_id);
                                $song->format();

                                $this->songId3TagWriter->write($song);
                            }
                        }
                    }
                }
                AmpConfig::set_by_array(['write_id3' => $write_id3], true);
                AmpConfig::set_by_array(['write_id3' => $write_id3_art], true);
        }

        // Remove any orphaned artists/albums/etc.
        $this->logger->debug(
            'Run Garbage collection',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $this->catalogGarbageCollector->collect();
        $this->albumRepository->cleanEmptyAlbums();
        $this->albumArtistUpdater->update();
        $this->catalogStatisticUpdater->update();
    }
}
