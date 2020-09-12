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

namespace Ampache\Module\Album;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Model\Album;
use Ampache\Model\Art;
use Ampache\Model\Catalog;
use Ampache\Model\Song;

final class AlbumArtExporter implements AlbumArtExporterInterface
{
    /**
     * This runs through all of the albums and tries to dump the
     * art for them into the 'folder.jpg' file in the appropriate dir.
     */
    public function export(
        Interactor $interactor,
        Catalog $catalog,
        array $methods = []
    ): void {

        // Get all of the albums in this catalog
        $albums = $catalog->get_album_ids();

        $interactor->info(
            T_('Starting Album Art Dump'),
            true
        );
        $count = 0;

        // Run through them and get the art!
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $art   = new Art($album_id, 'album');

            if (!$art->has_db_info()) {
                continue;
            }

            // Get the first song in the album
            $songs = $album->get_songs(1);
            $song  = new Song($songs[0]);
            $dir   = dirname($song->file);

            $extension = Art::extension($art->raw_mime);

            // Try the preferred filename, if that fails use folder.???
            $preferred_filename = AmpConfig::get('album_art_preferred_filename');
            if (!$preferred_filename || strpos($preferred_filename, '%') !== false) {
                $preferred_filename = "folder.$extension";
            }

            $file = $dir . DIRECTORY_SEPARATOR . $preferred_filename;
            if ($file_handle = fopen($file, "w")) {
                if (fwrite($file_handle, $art->raw)) {
                    // Also check and see if we should write
                    // out some metadata
                    if ($methods['metadata']) {
                        switch ($methods['metadata']) {
                            case 'windows':
                                $meta_file = $dir . '/desktop.ini';
                                $string    = "[.ShellClassInfo]\nIconFile=$file\nIconIndex=0\nInfoTip=$album->full_name";
                                break;
                            case 'linux':
                            default:
                                $meta_file = $dir . '/.directory';
                                $string    = "Name=$album->full_name\nIcon=$file";
                                break;
                        }

                        $meta_handle = fopen($meta_file, "w");
                        fwrite($meta_handle, $string);
                        fclose($meta_handle);
                    } // end metadata
                    $count++;
                    if (!($count % 100)) {
                        debug_event('catalog.class', "$album->name Art written to $file", 5);

                        $interactor->info(
                            sprintf(T_("Art files written: %s"), $count),
                            true
                        );
                    }
                } else {
                    debug_event('catalog.class', "Unable to open $file for writing", 3);

                    $interactor->error(
                        sprintf(T_("Couldn't get write to create art file: %s"), $file),
                        true
                    );
                }
            }
            fclose($file_handle);
        }

        $interactor->info(
            T_('Album Art Dump Complete'),
            true
        );
    }
}
