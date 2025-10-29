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

namespace Ampache\Module\Song;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SongSorter implements SongSorterInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    private ?Catalog_local $catalog= null;

    private int $move_count = 0;

    private int $limit = 0;

    private string $various_artist = '';

    private bool $dryRun = true;

    private bool $filesOnly = false;

    private bool $windowsCompat = false;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->modelFactory    = $modelFactory;
    }

    public function sort(
        Interactor $interactor,
        bool $dryRun = true,
        bool $filesOnly = false,
        int $limit = 0,
        bool $windowsCompat = false,
        ?string $various_artist_override = null,
        ?string $customPath = null,
        ?string $catalogName = null
    ): void {
        $this->dryRun         = $dryRun;
        $this->filesOnly      = $filesOnly;
        $this->limit          = $limit;
        $this->windowsCompat  = $windowsCompat;
        $this->various_artist = T_('Various Artists');

        if ($various_artist_override !== null) {
            $interactor->info(
                T_(sprintf('Setting Various Artists name to: %s', $various_artist_override)),
                true
            );

            $this->various_artist = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $various_artist_override)) ?? $this->various_artist;
        }

        if (!empty($catalogName)) {
            $sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local' AND `name` = ?;";
            $db_results = Dba::read($sql, [$catalogName]);
        } else {
            $sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local';";
            $db_results = Dba::read($sql);
        }

        while ($row = Dba::fetch_assoc($db_results)) {
            $this->catalog = Catalog::create_from_id($row['id']);
            if ($this->catalog === null) {
                break;
            }

            if ($customPath !== null) {
                // when you've set a sort path do not continue with full catalog sort
                $this->processPath($customPath, $interactor);
                continue;
            }

            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Starting Catalog: %s'), stripslashes((string)$this->catalog->name)),
                true
            );

            $stats  = Catalog::get_server_counts(0);
            $total  = $stats['song'];
            $chunks = (int)floor($total / 10000) + 1;
            foreach (range(1, $chunks) as $chunk) {
                /* HINT: Catalog Block: 4/120 */
                $interactor->info(
                    sprintf(T_('Catalog Block: %s'), $chunk . '/' . $chunks),
                    true
                );
                $songs = $this->catalog->get_songs($chunk, 1000);
                // Foreach through each file and find it a home!
                foreach ($songs as $song) {
                    $this->processMedia($song, $interactor);
                }
            }
        }

        /* HINT: filename (File path) */
        $interactor->info(
            sprintf(nT_('%d file updated.', '%d files updated.', $this->move_count), $this->move_count),
            true
        );
    }

    private function processMedia(
        Song $media,
        Interactor $interactor
    ): void {
        if ($this->limit > 0 && $this->move_count == $this->limit) {
            /* HINT: filename (File path) */
            $interactor->info(
                sprintf(nT_('%d file updated.', '%d files updated.', $this->move_count), $this->move_count),
                true
            );

            return;
        }
        // Check for file existence
        if (empty($media->file) || !file_exists($media->file)) {
            $this->logger->critical(
                sprintf('Missing: %s', $media->file),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            /* HINT: filename (File path) OR table name (podcast, video, etc) */
            $interactor->info(
                sprintf(T_('Missing: %s'), $media->file),
                true
            );

            return;
        }

        $interactor->info(
            T_("Examine File..."),
            true
        );
        /* HINT: filename (File path) */
        $interactor->info(
            sprintf(T_('Source: %s'), $media->file),
            true
        );

        // sort_find_home will replace the % with the correct values.
        $directory = ($this->filesOnly)
            ? dirname((string)$media->file)
            : $this->catalog->sort_find_home(
                $media,
                (string) $this->catalog->sort_pattern,
                $this->catalog->get_path(),
                $this->various_artist,
                $this->windowsCompat
            );
        if ($directory === null) {
            /* HINT: $sort_pattern after replacing %x values */
            $interactor->info(
                sprintf(T_('The sort_pattern has left over variables. %s'), $this->catalog->sort_pattern),
                true
            );
        }
        $filename = $this->catalog->sort_find_home(
            $media,
            (string) $this->catalog->rename_pattern,
            null,
            $this->various_artist,
            $this->windowsCompat
        );
        if ($filename === null) {
            /* HINT: $sort_pattern after replacing %x values */
            $interactor->info(
                sprintf(T_('The sort_pattern has left over variables. %s'), $this->catalog->rename_pattern),
                true
            );
        }
        if ($directory === null || $filename === null) {
            $fullpath = (string)$media->file;
        } else {
            $fullpath = rtrim($directory, "\/") . '/' . ltrim($filename, "\/") . "." . (pathinfo((string)$media->file, PATHINFO_EXTENSION));
        }

        /* We need to actually do the moving (fake it if we are testing)
         * Don't try to move it, if it's already the same friggin thing!
         */
        if ($media->file != $fullpath && strlen($fullpath) !== 0 && $fullpath !== '/.') {
            /* HINT: filename (File path) */
            $interactor->info(
                sprintf(T_('Destin: %s'), $fullpath),
                true
            );
            flush();
            if ($this->sort_move_file($media, $interactor, $fullpath, $this->dryRun, $this->windowsCompat)) {
                $this->move_count++;
            }
        }
    }

    private function processPath(
        string $path,
        Interactor $interactor
    ): void {
        if (is_dir($path)) {
            $file_ids = Catalog::get_ids_from_folder($path, $this->catalog->gather_types);
            $interactor->info(
                T_(sprintf('Sorting media in folder: %s', $path)),
                true
            );
        } else {
            switch ($this->catalog->gather_types) {
                case 'podcast':
                    $file_ids = [Catalog::get_id_from_file($path, 'podcast_episode')];
                    break;
                case 'video':
                    $file_ids = [Catalog::get_id_from_file($path, 'video')];
                    break;
                case 'music':
                    $file_ids = [Catalog::get_id_from_file($path, 'song')];
                    break;
                default:
                    $file_ids = [];
                    break;
            }
            $interactor->info(
                T_(sprintf('Sorting single file: %s', $path)),
                true
            );
        }

        foreach ($file_ids as $file_id) {
            switch ($this->catalog->gather_types) {
                case 'music':
                    $media = $this->modelFactory->createSong($file_id);
                    break;
                case 'podcast':
                case 'video':
                default:
                    $media = null;
                    break;
            }
            if ($media !== null) {
                $this->processMedia($media, $interactor);
            }
        }
    }

    /**
     * All this function does is, move the friggin file and then update the database
     * We can't use the rename() function of PHP because it's functionality depends on the
     * current phase of the moon, the alignment of the planets and my current BAL
     * Instead we cheeseball it and walk through the new dir structure and make
     * sure that the directories exist, once the dirs exist then we do a copy
     * and unlink. This is a little unsafe, and as such it verifies the copy
     * worked by doing a filesize() before unlinking.
     */
    private function sort_move_file(
        Song $media,
        Interactor $interactor,
        string $fullname,
        bool $test_mode,
        ?bool $windowsCompat = false
    ): bool {
        $old_dir   = dirname((string)$media->file);
        $info      = pathinfo($fullname);
        $directory = ($info['dirname'] ?? '');
        $file      = $info['basename'];
        $data      = preg_split("/[\/\\\]/", $directory);
        $path      = '';

        // We not need the leading /
        unset($data[0]);

        foreach ($data as $dir) {
            $dir = Catalog::sort_clean_name($dir, '', $windowsCompat);
            $path .= '/' . $dir;

            // We need to check for the existence of this directory
            if (!is_dir($path)) {
                if ($test_mode) {
                    /* HINT: Directory (File path) */
                    $interactor->info(
                        sprintf(T_('Create directory "%s"'), $path),
                        true
                    );
                } else {
                    $this->logger->notice(
                        sprintf('Creating %s directory', $path),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    if (!mkdir($path)) {
                        /* HINT: Directory (File path) */
                        $interactor->info(
                            sprintf(T_("There was a problem creating this directory: %s"), $path),
                            true
                        );

                        return false;
                    }
                } // else we aren't in test mode
            } // if it's not a dir
        } // foreach dir

        // Now that we've got the correct directory structure let's try to copy it
        if ($test_mode) {
            $sql = "UPDATE `song` SET `file` = '" . Dba::escape($fullname) . "' WHERE `id` = " . Dba::escape($media->id) . ";";
            $interactor->info(
                sprintf('SQL: %s', $sql),
                true
            );
            flush();
        } else {
            if (file_exists($fullname)) {
                $this->logger->critical(
                    sprintf('Error: %s already exists', $fullname),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('Don\'t overwrite an existing file: "%s"'), $fullname),
                    true
                );

                return false;
            }
            // HINT: %1$s: file, %2$s: directory
            $interactor->info(
                sprintf(T_('Copying "%1$s" to "%2$s"'), $file, $directory),
                true
            );

            if (empty($media->file) || !copy($media->file, $fullname)) {
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('There was an error trying to copy file to "%s"'), $fullname),
                    true
                );

                return false;
            }
            $this->logger->critical(
                'Copied ' . $media->file . ' to ' . $fullname,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            // Look for the folder art and copy that as well
            if ($old_dir != $directory) {
                // don't move things into the same dir
                $preferred  = Catalog::sort_clean_name($this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME) ?? 'folder.jpg', '', $windowsCompat);
                $folder_art = $directory . DIRECTORY_SEPARATOR . $preferred;
                $old_art    = $old_dir . DIRECTORY_SEPARATOR . $preferred;
                // copy art that exists
                if (file_exists($old_art)) {
                    if (copy($old_art, $folder_art) === false) {
                        unlink($fullname); // delete the copied file on failure

                        throw new RuntimeException('Unable to copy ' . $old_art . ' to ' . $folder_art);
                    }
                    $this->logger->critical(
                        'Copied ' . $old_art . ' to ' . $folder_art,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }
            // Check the filesize
            $new_sum = Core::get_filesize($fullname);
            $old_sum = Core::get_filesize($media->file);

            if ($new_sum != $old_sum || $new_sum == 0) {
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('Size comparison failed. Not deleting "%s"'), $media->file),
                    true
                );
                unlink($fullname); // delete the copied file on failure

                return false;
            } // end if sum's don't match

            if (!unlink($media->file)) {
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('There was an error trying to delete "%s"'), $media->file),
                    true
                );
            }

            // Update the catalog
            $sql = "UPDATE `song` SET `file` = ? WHERE `id` = ?;";
            Dba::write($sql, [$fullname, $media->id]);
        } // end else

        return true;
    }
}
