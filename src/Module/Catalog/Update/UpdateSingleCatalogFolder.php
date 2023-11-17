<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

define('API', 1);
final class UpdateSingleCatalogFolder extends AbstractCatalogUpdater implements UpdateSingleCatalogFolderInterface
{
    public function update(
        Interactor $interactor,
        string $catname,
        string $folderPath,
        bool $verificationMode,
        bool $addMode,
        bool $cleanupMode,
        bool $searchArtMode
    ): void {
        $sql        = "SELECT `id` FROM `catalog` WHERE `name` = ? AND `catalog_type`='local'";
        $db_results = Dba::read($sql, array($catname));

        ob_end_clean();
        ob_start();

        $changed = 0;
        while ($row = Dba::fetch_assoc($db_results)) {
            /** @var Catalog_local $catalog */
            $catalog = Catalog::create_from_id($row['id']);
            if (!$catalog instanceof Catalog) {
                $interactor->error(
                    sprintf(T_('Catalog `%s` not found'), $catname),
                    true
                );

                return;
            }
            ob_flush();
            // Identify the catalog and file (if it exists in the DB)
            switch ($catalog->gather_types) {
                case 'podcast':
                    $type      = 'podcast_episode';
                    $file_ids  = Catalog::get_ids_from_folder($folderPath, $type);
                    $className = Podcast_Episode::class;
                    break;
                case 'clip':
                case 'tvshow':
                case 'movie':
                case 'personal_video':
                    $type      = 'video';
                    $file_ids  = Catalog::get_ids_from_folder($folderPath, $type);
                    $className = Video::class;
                    break;
                case 'music':
                default:
                    $type      = 'song';
                    $file_ids  = Catalog::get_ids_from_folder($folderPath, $type);
                    $className = Song::class;
                    break;
            }
            foreach ($file_ids as $file_id) {
                /** @var Song|Podcast_Episode|Video $className */
                $media     = new $className($file_id);
                $file_path = $media->file;
                $file_test = is_file($file_path);
                // deleted file
                if (!$file_test && $cleanupMode == 1) {
                    if ($catalog->clean_file($file_path, $type)) {
                        $changed++;
                    }
                    $interactor->info(
                        sprintf(T_('Removing File: "%s"'), $file_path),
                        true
                    );
                }
                // existing files
                if ($file_test && Core::is_readable($file_path)) {
                    $interactor->info(
                        sprintf(T_('Reading File: "%s"'), $file_path),
                        true
                    );
                    if ($media->id && $verificationMode == 1) {
                        // Verify Existing files
                        Catalog::update_media_from_tags($media);
                    }
                    if ($searchArtMode == 1 && $file_id) {
                        // Look for media art after adding new files
                        $gather_song_art = (AmpConfig::get('gather_song_art', false));
                        if ($type == 'song') {
                            $media    = new Song($file_id);
                            $art      = ($gather_song_art) ? new Art($file_id, $type) : new Art($media->album, $type);
                            $art_id   = ($gather_song_art) ? $file_id : $media->album;
                            $art_type = ($gather_song_art) ? 'song' : 'album';
                            $artist   = new Art($media->artist, $type);
                            if (!$art->has_db_info()) {
                                Catalog::gather_art_item($art_type, $art_id, true);
                            }
                            if (!$artist->has_db_info()) {
                                Catalog::gather_art_item('artist', $media->artist, true);
                            }
                        }
                        if ($type == 'video') {
                            $art = new Art($file_id, $type);
                            if (!$art->has_db_info()) {
                                Catalog::gather_art_item($type, $file_id, true);
                            }
                        }
                    }
                }
            }
            // new files don't have an ID
            if ($addMode == 1) {
                $options = array(
                    'gather_art' => ($searchArtMode == 1)
                );
                // Look for new files
                $changed += $catalog->add_files($folderPath, $options);
            }
            if (($verificationMode == 1 && !empty($file_ids)) || $changed > 0) {
                $interactor->info(
                    T_('Update table mapping, counts and delete garbage data'),
                    true
                );
                // update counts after adding/verifying
                if ($type == 'song') {
                    Catalog::clean_empty_albums();
                    Album::update_album_artist();
                    Album::update_table_counts();
                    Artist::update_table_counts();
                }
                // clean up after the action
                Catalog::update_catalog_map($catalog->gather_types);
                Catalog::garbage_collect_mapping();
                Catalog::garbage_collect_filters();
            }
        }

        $buffer = ob_get_contents();

        ob_end_clean();

        $interactor->info(
            $this->cleanBuffer($buffer),
            true
        );
    }
}
