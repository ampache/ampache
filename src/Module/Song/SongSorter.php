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

namespace Ampache\Module\Song;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Song;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SongSorter implements SongSorterInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function sort(
        Interactor $interactor,
        bool $dryRun = true,
        bool $filesOnly = false,
        int $limit = 0,
        bool $windowsCompat = false,
        ?string $various_artist_override = null,
        ?string $catalogName = null
    ): void {
        $various_artist = T_('Various Artists');

        if ($various_artist_override !== null) {
            $interactor->info(
                T_(sprintf('Setting Various Artists name to: %s', $various_artist_override)),
                true
            );

            $various_artist = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $various_artist_override)) ?? $various_artist;
        }
        $move_count = 0;

        if (!empty($catalogName)) {
            $sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local' AND `name` = ?;";
            $db_results = Dba::read($sql, array($catalogName));
        } else {
            $sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local';";
            $db_results = Dba::read($sql);
        }

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            if (!$catalog instanceof Catalog) {
                break;
            }
            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Starting Catalog: %s'), stripslashes((string)$catalog->name)),
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
                $songs = $catalog->get_songs($chunk, 1000);
                // Foreach through each file and find it a home!
                foreach ($songs as $song) {
                    if ($limit > 0 && $move_count == $limit) {
                        /* HINT: filename (File path) */
                        $interactor->info(
                            sprintf(nT_('%d file updated.', '%d files updated.', $move_count), $move_count),
                            true
                        );

                        return;
                    }
                    // Check for file existence
                    if (empty($song->file) || !file_exists($song->file)) {
                        $this->logger->critical(
                            sprintf('Missing: %s', $song->file),
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                        /* HINT: filename (File path) OR table name (podcast, clip, etc) */
                        $interactor->info(
                            sprintf(T_('Missing: %s'), $song->file),
                            true
                        );
                        continue;
                    }
                    $song->format();

                    $interactor->info(
                        T_("Examine File..."),
                        true
                    );
                    /* HINT: filename (File path) */
                    $interactor->info(
                        sprintf(T_('Source: %s'), $song->file),
                        true
                    );

                    // sort_find_home will replace the % with the correct values.
                    $directory = ($filesOnly)
                        ? dirname((string)$song->file)
                        : $catalog->sort_find_home($song, $catalog->sort_pattern, $catalog->get_path(), $various_artist, $windowsCompat);
                    if ($directory === null) {
                        /* HINT: $sort_pattern after replacing %x values */
                        $interactor->info(
                            sprintf(T_('The sort_pattern has left over variables. %s'), $catalog->sort_pattern),
                            true
                        );
                    }
                    $filename = $catalog->sort_find_home($song, $catalog->rename_pattern, null, $various_artist, $windowsCompat);
                    if ($filename === null) {
                        /* HINT: $sort_pattern after replacing %x values */
                        $interactor->info(
                            sprintf(T_('The sort_pattern has left over variables. %s'), $catalog->rename_pattern),
                            true
                        );
                    }
                    if ($directory === null || $filename === null) {
                        $fullpath = (string)$song->file;
                    } else {
                        $fullpath = rtrim($directory, "\/") . '/' . ltrim($filename, "\/") . "." . (pathinfo((string)$song->file, PATHINFO_EXTENSION));
                    }

                    /* We need to actually do the moving (fake it if we are testing)
                     * Don't try to move it, if it's already the same friggin thing!
                     */
                    if ($song->file != $fullpath && strlen($fullpath)) {
                        /* HINT: filename (File path) */
                        $interactor->info(
                            sprintf(T_('Destin: %s'), $fullpath),
                            true
                        );
                        flush();
                        if ($this->sort_move_file($interactor, $song, $fullpath, $dryRun, $windowsCompat)) {
                            $move_count++;
                        }
                    }
                }
            }
            /* HINT: filename (File path) */
            $interactor->info(
                sprintf(nT_('%d file updated.', '%d files updated.', $move_count), $move_count),
                true
            );
        }
    }

    /**
     * All this function does is, move the friggin file and then update the database
     * We can't use the rename() function of PHP because it's functionality depends on the
     * current phase of the moon, the alignment of the planets and my current BAL
     * Instead we cheeseball it and walk through the new dir structure and make
     * sure that the directories exist, once the dirs exist then we do a copy
     * and unlink.. This is a little unsafe, and as such it verifies the copy
     * worked by doing a filesize() before unlinking.
     * @param Interactor $interactor
     * @param Song $song
     * @param $fullname
     * @param $test_mode
     * @param bool $windowsCompat
     * @return bool
     */
    private function sort_move_file(
        Interactor $interactor,
        Song $song,
        $fullname,
        $test_mode,
        $windowsCompat = false
    ): bool {
        $old_dir   = dirname((string)$song->file);
        $info      = pathinfo($fullname);
        $directory = $info['dirname'];
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
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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
            $sql = "UPDATE `song` SET `file` = " . Dba::escape($fullname) . " WHERE `id` = " . Dba::escape($song->id) . ";";
            $interactor->info(
                sprintf('SQL: %s', $sql),
                true
            );
            flush();
        } else {
            if (file_exists($fullname)) {
                $this->logger->critical(
                    sprintf('Error: %s already exists', $fullname),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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

            if (empty($song->file) || !copy($song->file, $fullname)) {
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('There was an error trying to copy file to "%s"'), $fullname),
                    true
                );

                return false;
            }
            $this->logger->critical(
                'Copied ' . $song->file . ' to ' . $fullname,
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
            // Check the filesize
            $new_sum = Core::get_filesize($fullname);
            $old_sum = Core::get_filesize($song->file);

            if ($new_sum != $old_sum || $new_sum == 0) {
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('Size comparison failed. Not deleting "%s"'), $song->file),
                    true
                );
                unlink($fullname); // delete the copied file on failure

                return false;
            } // end if sum's don't match

            if (!unlink($song->file)) {
                /* HINT: filename (File path) */
                $interactor->info(
                    sprintf(T_('There was an error trying to delete "%s"'), $song->file),
                    true
                );
            }

            // Update the catalog
            $sql = "UPDATE `song` SET `file` = ? WHERE `id` = ?;";
            Dba::write($sql, array($fullname, $song->id));
        } // end else

        return true;
    }
}
