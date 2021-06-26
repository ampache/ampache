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

declare(strict_types=0);

namespace Ampache\Module\Catalog\Update;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
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
        bool $verification,
        bool $updateInfo,
        bool $optimizeDatabase,
        ?string $catalogName,
        string $catalogType
    ): void {
        if ($deactivateMemoryLimit === true) {
            // Temporarily deactivate PHP memory limit
            echo "\033[31m- " . T_("Deactivated PHP memory limit") . " -\033[0m\n";
            ini_set('memory_limit','-1');
            echo "------------------\n\n";
        }

        $options = [
            'gather_art' => false,
            'parse_playlist' => $importPlaylists
        ];

        $db_results = $this->lookupCatalogs($catalogType, $catalogName);

        ob_end_clean();

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Reading Catalog: "%s"'), $catalog->name),
                true
            );

            if ($cleanup === true) {
                ob_start();

                // Clean out dead files
                $interactor->info(
                    T_('Start cleaning orphaned media entries'),
                    true
                );
                $catalog->clean_catalog();

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer($buffer),
                    true
                );
                $interactor->info('------------------', true);
            }
            if ($addNew === true) {
                ob_start();

                // Look for new files
                $interactor->info(
                    T_('Start adding new media'),
                    true
                );
                $catalog->add_to_catalog($options);

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer($buffer),
                    true
                );
                $interactor->info('------------------', true);
                Album::update_album_artist();
            }
            if ($verification === true) {
                ob_start();

                // Verify Existing
                $interactor->info(
                    T_('Start verifying media related to Catalog entries'),
                    true
                );
                $catalog->verify_catalog_proc();

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer($buffer),
                    true
                );
                $interactor->info('------------------', true);
            }
            if ($addArt === true) {
                ob_start();

                // Look for media art
                $interactor->info(
                    T_('Start searching new media art'),
                    true
                );
                $catalog->gather_art();

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer($buffer),
                    true
                );
                $interactor->info('------------------', true);
            }
            if ($updateInfo === true) {
                ob_start();

                // Look for updated artist information. (missing or < 6 months since last update)
                $interactor->info(
                    T_('Update artist information and fetch similar artists from last.fm'),
                    true
                );
                $artist_info = $catalog->get_artist_ids('info');
                $catalog->gather_artist_info($artist_info);

                $buffer = ob_get_contents();

                ob_end_clean();

                $interactor->info(
                    $this->cleanBuffer($buffer),
                    true
                );
                if (AmpConfig::get('label')) {
                    $interactor->info(
                        T_('Update Label information and fetch details using the MusicBrainz plugin'),
                        true
                    );
                    $labels = $catalog->get_label_ids('tag_generated');
                    $catalog->update_from_external($labels);

                    $buffer = ob_get_contents();

                    ob_end_clean();

                    $interactor->info(
                        $this->cleanBuffer($buffer),
                        true
                    );
                }
                $interactor->info('------------------', true);
            }
        }
        if ($cleanup === true || $verification === true) {
            $this->catalogGarbageCollector->collect();
        }
        Catalog::update_counts();
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
                $this->cleanBuffer($buffer),
                true
            );

            $interactor->info('------------------', true);
        }
    }

    public function updatePath(
        Interactor $interactor,
        string $catalogType,
        ?string $catalogName,
        ?string $newPath
    ): void {
        $result = $this->lookupCatalogs(
            $catalogType,
            $catalogName
        );

        if ($newPath === null || !is_dir($newPath)) {
            $interactor->error('The new path is invalid', true);

            return;
        }

        while ($row = Dba::fetch_assoc($result)) {
            $catalog = Catalog::create_from_id($row['id']);
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
                sprintf('%s -> %s', $catalog->path, $newPath)
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
            $interactor->info('----------------', true);
        }
    }

    private function lookupCatalogs(
        string $catalogType,
        ?string $catalogName
    ): PDOStatement {
        $where = sprintf(
            'catalog_type = \'%s\'',
            Dba::escape($catalogType)
        );
        if ($catalogName !== null) {
            $where = sprintf(
                '%s AND name = \'%s\'',
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
