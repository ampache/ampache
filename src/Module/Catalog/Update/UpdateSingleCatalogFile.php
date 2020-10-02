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
use Ampache\Model\Podcast_Episode;
use Ampache\Model\Song;
use Ampache\Model\Video;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

final class UpdateSingleCatalogFile implements UpdateSingleCatalogFileInterface
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
        $catname = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $catname));

        // -------- Options before the File actions loop
        if ($searchArtMode === true) {
            $options['gather_art'] = true;
        } else {
            $options['gather_art'] = false;
        }

        $sql        = "SELECT `id` FROM `catalog` WHERE `name` = '$catname' AND `catalog_type`='local'";
        $db_results = Dba::read($sql);

        ob_start("ob_html_strip",'1024',true);

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            ob_flush();
            if (!$catalog->id) {
                $interactor->error(sprintf(T_('Catalog `%s` not found'), $catname), true);

                return;
            }
            // Identify the catalog and file (if it exists in the DB)
            switch ($catalog->gather_types) {
                case 'podcast':
                    $type    = 'podcast_episode';
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    $media   = new Podcast_Episode(Catalog::get_id_from_file($filePath, $type));
                    break;
                case 'clip':
                case 'tvshow':
                case 'movie':
                case 'personal_video':
                    $type    = 'video';
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    $media   = new Video(Catalog::get_id_from_file($filePath, $type));
                    break;
                case 'music':
                default:
                    $type    = 'song';
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                    $media   = new Song($file_id);
                    break;
            }
            // deleted file
            if (!is_file($filePath) && $cleanupMode == 1) {
                $catalog->clean_file($filePath, $type);
                $interactor->info(
                    sprintf(T_('Removing File: "%s"'), $filePath),
                    true
                );

                return;
            }
            // existing files
            if (is_file($filePath) && Core::is_readable($filePath)) {
                $interactor->info(
                    sprintf(T_('Reading File: "%s"'), $filePath),
                    true
                );
                if ($media->id && $verificationMode == 1) {
                    // Verify Existing files
                    $catalog = $media->catalog;
                    Catalog::update_media_from_tags($media);
                }
                // new files don't have an ID
                if (!$file_id && $addMode == 1) {
                    // Look for new files
                    $catalog->add_file($filePath, array());
                    Catalog::get_id_from_file($filePath, $type);
                    // get the new id after adding it
                    $file_id = Catalog::get_id_from_file($filePath, $type);
                }
                if ($searchArtMode == 1 && $file_id && $verificationMode === false) {
                    // Look for media art after adding new files
                    Catalog::gather_art_item($type, $file_id, true);
                }
            }
        }
    }
}
