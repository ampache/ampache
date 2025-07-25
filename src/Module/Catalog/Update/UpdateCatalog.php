<?php

declare(strict_types=0);

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

namespace Ampache\Module\Catalog\Update;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\System\Dba;
use PDOStatement;

final class UpdateCatalog extends AbstractCatalogUpdater implements UpdateCatalogInterface
{
    private CatalogGarbageCollectorInterface $catalogGarbageCollector;

    public function __construct(
        CatalogGarbageCollectorInterface $catalogGarbageCollector
    ) {
        $this->catalogGarbageCollector = $catalogGarbageCollector;
    }

    public function update(
        Interactor $interactor,
        bool $deactivateMemoryLimit,
        bool $addNew,
        bool $addArt,
        bool $importPlaylists,
        bool $cleanup,
        bool $missing,
        bool $verification,
        bool $updateInfo,
        bool $optimizeDatabase,
        bool $collectGarbage,
        string $catalogType,
        ?string $catalogName,
        ?int $limit
    ): void {
        $start_time = time();
        if ($deactivateMemoryLimit === true) {
            // Temporarily deactivate PHP memory limit
            echo "\033[31m- " . T_("Deactivated PHP memory limit") . " -\033[0m\n";
            ini_set('memory_limit', '-1');
            echo "------------------\n\n";
        }

        $options = [
            'gather_art' => false,
            'parse_playlist' => $importPlaylists,
        ];

        // don't look at catalogs without an action
        if (
            !$addNew &&
            !$addArt &&
            !$importPlaylists &&
            !$cleanup &&
            !$missing &&
            !$verification
        ) {
            $catalogType = '';
            $catalogName = '';
        }
        $db_results   = $this->lookupCatalogs($catalogType, $catalogName);
        $external     = false;
        $changed      = 0;
        $gather_types = [];

        ob_end_clean();
        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            if ($catalog === null) {
                break;
            }
            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Reading Catalog: "%s"'), $catalog->name),
                true
            );
            if (isset($catalog->path) && !Core::is_readable($catalog->path)) {
                $interactor->error(
                    T_('Catalog root unreadable, stopping check'),
                    true
                );
                break;
            }
            $interactor->eol();
            if ($missing === true) {
                ob_start();

                $interactor->info(
                    T_('Look for missing file media entries'),
                    true
                );
                $files = $catalog->check_catalog_proc($interactor);
                foreach ($files as $path) {
                    /* HINT: filename (File path) OR table name (podcast, video, etc) */
                    $interactor->info(
                        sprintf(T_('Missing: %s'), $path),
                        true
                    );
                }

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer((string)$buffer),
                    true
                );
                $interactor->info(
                    '------------------',
                    true
                );
            } else {
                if ($cleanup === true) {
                    ob_start();
                    // Clean out dead files
                    $interactor->info(
                        T_('Start cleaning orphaned media entries'),
                        true
                    );
                    $changed += $catalog->clean_catalog($interactor);

                    $buffer = ob_get_contents();

                    ob_end_clean();

                    $interactor->info(
                        $this->cleanBuffer((string)$buffer),
                        true
                    );
                    $interactor->info(
                        '------------------',
                        true
                    );
                }
                if ($addNew === true || $importPlaylists === true) {
                    ob_start();

                    // Look for new files
                    $interactor->info(
                        T_('Start adding new media'),
                        true
                    );
                    $changed += $catalog->add_to_catalog($options, $interactor);

                    $buffer = ob_get_contents();

                    ob_end_clean();

                    $interactor->info(
                        $this->cleanBuffer((string)$buffer),
                        true
                    );
                    $interactor->info(
                        '------------------',
                        true
                    );
                }
                if ($verification === true) {
                    ob_start();

                    // Verify Existing
                    $interactor->info(
                        T_('Start verifying media related to Catalog entries'),
                        true
                    );
                    $changed += $catalog->verify_catalog_proc($limit, $interactor);

                    $buffer = ob_get_contents();

                    ob_end_clean();

                    $interactor->info(
                        $this->cleanBuffer((string)$buffer),
                        true
                    );
                    $interactor->info(
                        '------------------',
                        true
                    );
                }
            }
            if ($addArt === true) {
                ob_start();

                // Look for media art
                $interactor->info(
                    T_('Start searching new media art'),
                    true
                );
                $catalog->gather_art(null, null, $interactor);

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer((string)$buffer),
                    true
                );
                $interactor->info(
                    '------------------',
                    true
                );
            }
            if ($updateInfo === true && !$external) {
                ob_start();

                // only update from external metadata once.
                $external = true;

                $interactor->info(
                    T_('Update artist information and fetch similar artists from last.fm'),
                    true
                );
                // clean out the bad artists first
                Catalog::clean_duplicate_artists();

                // Look for updated artist information. (1 month since last update MBID IS NOT NULL) LIMIT 500
                $artists = $catalog->get_artist_ids('time');
                $catalog->update_from_external($artists, 'artist');

                // Look for updated recommendations / similar artists LIMIT 500
                $artist_info = $catalog->get_artist_ids('info');
                $catalog->gather_artist_info($artist_info);

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer((string)$buffer),
                    true
                );
                if (AmpConfig::get('label')) {
                    $interactor->info(
                        T_('Update Label information and fetch details using the MusicBrainz plugin'),
                        true
                    );
                    $labels = $catalog->get_label_ids('tag_generated');
                    $catalog->update_from_external($labels, 'label');

                    $buffer = ob_get_contents();

                    ob_end_clean();

                    $interactor->info(
                        $this->cleanBuffer((string)$buffer),
                        true
                    );
                }
                $interactor->info(
                    '------------------',
                    true
                );
            }
            if (!in_array($catalog->gather_types, $gather_types)) {
                $gather_types[] = $catalog->gather_types;
            }
        }
        if ($collectGarbage === true || (($cleanup === true || $verification === true) && $changed > 0)) {
            $interactor->info(
                T_('Garbage Collection'),
                true
            );
            $this->catalogGarbageCollector->collect();
            Catalog::clean_empty_albums();
            Album::update_album_artist();
            Catalog::update_counts();
            $interactor->info(
                '------------------',
                true
            );
        }
        if ($changed > 0 || ($collectGarbage === true && $missing !== true)) {
            $interactor->info(
                T_('Update table mapping, counts and delete garbage data'),
                true
            );
            // clean up after the action
            foreach ($gather_types as $media_type) {
                Catalog::update_catalog_map($media_type);
                switch ($media_type) {
                    case 'podcast':
                        Catalog::garbage_collect_mapping(['podcast_episode', 'podcast']);
                        break;
                    case 'video':
                        Catalog::garbage_collect_mapping(['video']);
                        break;
                    case 'music':
                        Catalog::garbage_collect_mapping(['album', 'artist', 'song']);
                        break;
                }
            }
            Catalog::garbage_collect_filters();
            $interactor->info(
                '------------------',
                true
            );
        }
        if ($optimizeDatabase === true) {
            ob_start();

            // Optimize Database Tables
            $interactor->info(
                T_('Optimizing database tables'),
                true
            );
            Dba::optimize_tables();

            $buffer = ob_get_contents();

            ob_end_clean();

            $interactor->info(
                $this->cleanBuffer((string)$buffer),
                true
            );

            $interactor->info(
                '------------------',
                true
            );
        }
        $time_diff = time() - $start_time;
        $interactor->info(
            T_('Time') . ": " . date('i:s', $time_diff),
            true
        );
    }

    public function updatePath(
        Interactor $interactor,
        string $catalogType,
        ?string $catalogName,
        ?string $newPath
    ): void {
        // argument may be incorrect when not setting a type
        if (is_dir($catalogType) && !$newPath) {
            $newPath     = $catalogType;
            $catalogType = 'local';
        }
        $result = $this->lookupCatalogs(
            $catalogType,
            $catalogName
        );
        // trim everything
        $newPath = rtrim(trim((string)$newPath), "/");

        if (!is_dir((string)$newPath)) {
            $interactor->error(
                T_('The new path is invalid'),
                true
            );

            return;
        }

        while ($row = Dba::fetch_assoc($result)) {
            $catalog = Catalog::create_from_id($row['id']);
            if ($catalog === null) {
                break;
            }
            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Reading Catalog: "%s"'), $catalog->name),
                true
            );
            $interactor->info(
                sprintf('- %s - ', T_('Moving Catalog path')),
                true
            );
            $interactor->eol();
            $interactor->info(
                sprintf('%s -> %s', $catalog->get_path(), $newPath)
            );
            $interactor->eol(2);

            // Migrate a catalog from the current path to a new one.
            if (isset($catalog->path)) {
                if ($catalog->move_catalog_proc($newPath)) {
                    $interactor->info(
                        sprintf('- %s -', T_('The Catalog path has changed')),
                        true
                    );
                } else {
                    $interactor->info(
                        sprintf('- %s -', T_('There Was a Problem')),
                        true
                    );
                }
            } else {
                $interactor->info(
                    sprintf('- %s -', T_('There is an error with your parameters')),
                    true
                );
            }
            $interactor->info(
                '------------------',
                true
            );
        }
    }

    private function lookupCatalogs(
        string $catalogType,
        ?string $catalogName
    ): PDOStatement|false {
        $where = sprintf(
            'catalog_type = \'%s\'',
            Dba::escape($catalogType)
        );
        if ($catalogName !== null) {
            $where = sprintf(
                '%s AND `name` = \'%s\'',
                $where,
                Dba::escape($catalogName)
            );
        }

        return Dba::read(
            sprintf(
                'SELECT `id` FROM `catalog` WHERE %s',
                $where
            )
        );
    }
}
