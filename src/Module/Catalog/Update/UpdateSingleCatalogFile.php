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

final class UpdateSingleCatalogFile extends AbstractCatalogUpdater implements UpdateSingleCatalogFileInterface
{
    public function update(
        Interactor $interactor,
        string $catname,
        string $filePath,
        bool $verificationMode,
        bool $addMode,
        bool $cleanupMode,
        bool $searchArtMode
    ): void {
        $sql        = "SELECT `id` FROM `catalog` WHERE `name` = ? AND `catalog_type`='local'";
        $db_results = Dba::read($sql, array($catname));

        ob_end_clean();
        ob_start();

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            if ($catalog === null) {
                $interactor->error(
                    sprintf(T_('Catalog `%s` not found'), $catname),
                    true
                );

                return;
            }
            if (isset($catalog->path) && !Core::is_readable($catalog->path)) {
                $interactor->error(
                    T_('Catalog root unreadable, stopping check'),
                    true
                );

                return;
            }
            ob_flush();
            // Identify the catalog and file (if it exists in the DB)
            /** @var Catalog_local $catalog */
            switch ($catalog->gather_types) {
                case 'podcast':
                    $type    = 'podcast_episode';
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    $media   = new Podcast_Episode($file_id);
                    break;
                case 'clip':
                case 'tvshow':
                case 'movie':
                case 'personal_video':
                    $type    = 'video';
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    $media   = new Video($file_id);
                    break;
                case 'music':
                default:
                    $type    = 'song';
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    $media   = new Song($file_id);
                    break;
            }
            $file_test = is_file($filePath);
            // deleted file but it was valid media in the database
            if (
                $media->isNew() === false &&
                !$file_test &&
                $cleanupMode == 1
            ) {
                $catalog->clean_file($filePath, $type);
                $interactor->info(
                    sprintf(T_('Removing File: "%s"'), $filePath),
                    true
                );
                // update counts after cleaning a missing file
                Album::update_table_counts();
                Artist::update_table_counts();

                return;
            }
            // existing files
            if ($file_test && Core::is_readable($filePath)) {
                $interactor->info(
                    sprintf(T_('Reading File: "%s"'), $filePath),
                    true
                );
                if ($media->id && $verificationMode == 1) {
                    // Verify Existing files
                    Catalog::update_media_from_tags($media);
                }
                // new files don't have an ID
                if (!$file_id && $addMode == 1) {
                    // Look for new files
                    $catalog->add_file($filePath, array());
                    Catalog::get_id_from_file($filePath, $type);
                    // get the new id after adding it
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    // update counts after adding/verifying
                    Album::update_table_counts();
                    Artist::update_table_counts();
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
                        if ($media->artist && !$artist->has_db_info()) {
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

        $buffer = ob_get_contents();

        ob_end_clean();

        $interactor->info(
            $this->cleanBuffer($buffer),
            true
        );
    }
}
