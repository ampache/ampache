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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Log\LoggerInterface;

final class FolderCollectorModule implements CollectorModuleInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        SongRepositoryInterface $songRepository
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->songRepository  = $songRepository;
    }

    /**
     * This returns the art from the folder of the files
     * If a limit is passed or the preferred filename is found the current
     * results set is returned
     *
     * @param Art $art
     * @param int $limit
     * @param array{
     *     mb_albumid?: string,
     *     artist?: string,
     *     album?: string,
     *     cover?: ?string,
     *     file?: string,
     *     year_filter?: string,
     *     search_limit?: int,
     * } $data
     * @return array<int, array{file: string, mime: string, title: string}>
     */
    public function collectArt(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        if (!$limit) {
            $limit = 5;
        }

        $results   = [];
        $preferred = [];
        // For storing which directories we've already done
        $processed = [];

        /* See if we are looking for a specific filename */
        $preferred_filename = ($this->configContainer->get('album_art_preferred_filename')) ?? 'folder.jpg';
        $artist_filename    = $this->configContainer->get('artist_art_preferred_filename');
        $artist_art_folder  = $this->configContainer->get('artist_art_folder');

        $dirs = [];
        if ($art->object_type == 'album') {
            $media = new Album($art->object_id);
            $songs = $this->songRepository->getByAlbum((int) $media->id);
            foreach ($songs as $song_id) {
                $song   = new Song($song_id);
                $dirs[] = Core::conv_lc_file(dirname((string)$song->file));
            }
        } elseif ($art->object_type == 'video') {
            $media  = new Video($art->object_id);
            $dirs[] = Core::conv_lc_file(dirname($media->file));
        } elseif ($art->object_type == 'artist') {
            $media              = new Artist($art->object_id);
            $preferred_filename = str_replace(['<', '>', '\\', '/'], '_', (string)$media->get_fullname());
            if ($artist_art_folder) {
                $dirs[] = Core::conv_lc_file($artist_art_folder);
            }
            // get the folders from songs as well
            $songs = $this->songRepository->getByArtist((int) $media->id);
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                // look in the directory name of the files (e.g. /mnt/Music/%artistName%/%album%)
                $dirs[] = Core::conv_lc_file(dirname((string)$song->file));
                // look one level up (e.g. /mnt/Music/%artistName%)
                $dirs[] = Core::conv_lc_file(dirname((string)$song->file, 2));
            }
        }

        foreach ($dirs as $dir) {
            if (isset($processed[$dir])) {
                continue;
            }

            if (filter_var($dir, FILTER_VALIDATE_URL)) {
                $processed[$dir] = true;

                $this->logger->debug(
                    "gather_folder: Skipping URL path $dir",
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                continue;
            }

            $this->logger->notice(
                "gather_folder: Opening $dir and checking for " . $art->object_type . " Art",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            /* Open up the directory */
            $handle = @opendir($dir);

            if (!$handle) {
                AmpError::add('general', T_('Unable to open') . ' ' . $dir);

                $this->logger->warning(
                    "gather_folder: Opening $dir and checking for " . $art->object_type . " Art",
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
                continue;
            }

            $processed[$dir] = true;

            // Recurse through this dir and create the files array
            while (false !== ($file = readdir($handle))) {
                $extension = pathinfo($file);
                $extension = $extension['extension'] ?? '';

                // Make sure it looks like an image file
                if (!in_array($extension, Art::VALID_TYPES)) {
                    continue;
                }

                $full_filename = $dir . '/' . $file;

                // Make sure it's got something in it
                if (!Core::get_filesize($full_filename)) {
                    $this->logger->debug(
                        "gather_folder: Opening $dir and checking for " . $art->object_type . " Art",
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    continue;
                }

                // Regularize for mime type
                if ($extension == 'jpg') {
                    $extension = 'jpeg';
                }

                // Take an md5sum so we don't show duplicate files.
                $index = md5($full_filename);

                if (
                    (
                        $file == $preferred_filename ||
                        pathinfo($file, PATHINFO_FILENAME) == $preferred_filename
                    ) ||
                        (
                            $file == $artist_filename ||
                            pathinfo($file, PATHINFO_FILENAME) == $artist_filename
                        )
                ) {
                    // We found the preferred filename and so we're done.
                    $this->logger->debug(
                        "gather_folder: Found preferred image file: $file",
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    $preferred[$index] = [
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    ];
                    break;
                }
                if ($art->object_type !== 'artist') {
                    $this->logger->debug(
                        "gather_folder: Found image file: $file",
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    $results[$index] = [
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    ];
                }
            } // end while reading dir
            closedir($handle);
        } // end foreach dirs

        if (!empty($preferred)) {
            // We found our favorite filename somewhere, so we need
            // to dump the other, less sexy ones.
            $results = $preferred;
        }

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return array_values($results);
    }
}
