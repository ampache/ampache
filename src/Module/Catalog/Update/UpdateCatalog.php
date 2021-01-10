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
use Ampache\Model\Catalog;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\System\Dba;

final class UpdateCatalog implements UpdateCatalogInterface
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
            'gather_art' => $addArt,
            'parse_playlist' => $importPlaylists
        ];

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

        $sql = sprintf(
            'SELECT `id` FROM `catalog` WHERE %s',
            $where
        );
        $db_results = Dba::read($sql);

        ob_end_clean();
        ob_start();

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Reading Catalog: "%s"'), $catalog->name),
                true
            );

            if ($cleanup === true) {
                // Clean out dead files
                $interactor->info(
                    T_('Start cleaning orphaned media entries'),
                    true
                );
                $catalog->clean_catalog();
                $interactor->info('------------------', true);
            }
            if ($verification === true) {
                // Verify Existing
                $interactor->info(
                    T_('Start verifying media related to Catalog entries'),
                    true
                );
                $catalog->verify_catalog_proc();
                $interactor->info('------------------', true);
            }
            if ($addNew === true) {
                // Look for new files
                $interactor->info(
                    T_('Start adding new media'),
                    true
                );
                $catalog->add_to_catalog($options);
                $interactor->info('------------------', true);
            } elseif ($addArt === true) {
                // Look for media art
                $interactor->info(
                    T_('Start searching new media art'),
                    true
                );
                $catalog->gather_art();
                $interactor->info('------------------', true);
            }
            if ($updateInfo === true) {
                // Look for updated artist information. (missing or < 6 months since last update)
                $interactor->info(
                    T_('Update artist information and fetch similar artists from last.fm'),
                    true
                );
                $artist_info = $catalog->get_artist_ids('info');
                $catalog->gather_artist_info($artist_info);
                $interactor->info('------------------', true);
            }
        }
        if ($cleanup === true || $verification === true) {
            $this->catalogGarbageCollector->collect();
        }
        if ($optimizeDatabase === true) {
            // Optimize Database Tables
            $interactor->info(
                T_('Optimizing database tables'),
                true
            );
            Dba::optimize_tables();
            $interactor->info('------------------', true);
        }

        $buffer = ob_get_contents();

        ob_end_clean();

        $interactor->info(
            $this->cleanBuffer($buffer),
            true
        );
    }

    private function cleanBuffer(string $string): string
    {
        $string = str_replace('<br />', "\n", $string);
        $string = strip_tags($string);
        $string = html_entity_decode($string);
        $string = preg_replace("/[\r\n]+[\s\t]*[\r\n]+/","\n",$string);
        $string = trim($string);

        return $string;
    }
}
