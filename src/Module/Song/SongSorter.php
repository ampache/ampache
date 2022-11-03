<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SongSorter implements SongSorterInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

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
        ?string $catalogName = null
    ): void {
        $various_artist = 'Various Artists';

        if ($various_artist_override !== null) {
            $interactor->info(
                T_(sprintf('Setting Various Artists name to: %s', $various_artist_override)),
                true
            );

            $various_artist = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $various_artist_override));
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
            /* HINT: Catalog Name */
            $interactor->info(
                sprintf(T_('Starting Catalog: %s'), stripslashes($catalog->name)),
                true
            );

            $stats  = Catalog::get_server_counts(0);
            $total  = $stats['song'];
            $chunks = (int)floor($total / 10000);
            foreach (range(0, $chunks) as $chunk) {
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
                    if (!file_exists($song->file)) {

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
                    // sort_find_home will replace the % with the correct values.
                    $directory = ($filesOnly)
                    ? dirname($song->file)
                    : $this->sort_find_home($interactor, $song, $catalog->sort_pattern, $catalog->path, $various_artist, $windowsCompat);
                    $filename  = $this->sort_find_home($interactor, $song, $catalog->rename_pattern, null, $various_artist, $windowsCompat);
                    if ($directory === false || $filename === false) {
                        $fullpath = $song->file;
                    } else {
                        $fullpath = rtrim($directory, "\/") . '/' . ltrim($filename, "\/") . "." . pathinfo($song->file, PATHINFO_EXTENSION);
                    }

                    /* We need to actually do the moving (fake it if we are testing)
                     * Don't try to move it, if it's already the same friggin thing!
                     */
                    if ($song->file != $fullpath && strlen($fullpath)) {
                        $interactor->info(
                        T_("Examine File..."),
                        true
                    );
                        /* HINT: filename (File path) */
                        $interactor->info(
                        sprintf(T_('Source: %s'), $song->file),
                        true
                    );
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
     * Get the directory for this file from the catalog and the song info using the sort_pattern
     * takes into account various artists and the alphabet_prefix
     * @param Interactor $interactor
     * @param Song $song
     * @param $sort_pattern
     * @param $base
     * @param string $various_artist
     * @param bool $windowsCompat
     * @return false|string
     */
    private function sort_find_home(
        Interactor $interactor,
        $song,
        $sort_pattern,
        $base = null,
        $various_artist = "Various Artists",
        $windowsCompat = false
    ) {
        $home = '';
        if ($base) {
            $home = rtrim($base, "\/");
            $home = rtrim($home, "\\");
        }

        // Create the filename that this file should have
        $album  = $this->sort_clean_name($song->f_album_full, '%A', $windowsCompat);
        $artist = $this->sort_clean_name($song->f_artist_full, '%a', $windowsCompat);
        $track  = $this->sort_clean_name($song->track, '%T', $windowsCompat);
        if ((int) $track < 10) {
            $track = '0' . (string) $track;
        }

        $title   = $this->sort_clean_name($song->title, '%t', $windowsCompat);
        $year    = $this->sort_clean_name($song->year, '%y', $windowsCompat);
        $comment = $this->sort_clean_name($song->comment, '%c', $windowsCompat);

        // Do the various check
        $album_object = $this->modelFactory->createAlbum($song->album);
        $album_object->format();
        if ($album_object->get_album_artist_fullname() != "") {
            $artist = $album_object->f_album_artist_name;
        } elseif ($album_object->artist_count != 1) {
            $artist = $various_artist;
        }
        $disk           = $this->sort_clean_name($song->disk, '%d');
        $catalog_number = $this->sort_clean_name($album_object->catalog_number, '%C');
        $barcode        = $this->sort_clean_name($album_object->barcode, '%b');
        $original_year  = $this->sort_clean_name($album_object->original_year, '%Y');
        $release_type   = $this->sort_clean_name($album_object->release_type, '%r');
        $release_status = $this->sort_clean_name($album_object->release_status, '%R');
        $subtitle       = $this->sort_clean_name($album_object->subtitle, '%s');
        $genre          = (!empty($album_object->tags))
            ? Tag::get_display($album_object->tags)
            : '%b';

        // Replace everything we can find
        $replace_array = array('%a', '%A', '%t', '%T', '%y', '%Y', '%c', '%C', '%r', '%R', '%s', '%d', '%g', '%b');
        $content_array = array($artist, $album, $title, $track, $year, $original_year, $comment, $catalog_number, $release_type, $release_status, $subtitle, $disk, $genre, $barcode);
        $sort_pattern  = str_replace($replace_array, $content_array, $sort_pattern);

        // Remove non A-Z0-9 chars
        $sort_pattern = preg_replace("[^\\\/A-Za-z0-9\-\_\ \'\, \(\)]", "_", $sort_pattern);

        // Replace non-critical search patterns
        $post_replace_array = array('%Y', '%c', '%C', '%r', '%R', '%g', '%b', ' []', ' ()');
        $post_content_array = array('', '', '', '', '', '', '', '', '', '');
        $sort_pattern       = str_replace($post_replace_array, $post_content_array, $sort_pattern);

        $home .= "/$sort_pattern";
        // dont send a mismatched file!
        foreach ($replace_array as $replace_string) {
            if (strpos($sort_pattern, $replace_string) !== false) {
                /* HINT: $sort_pattern after replacing %x values */
                $interactor->info(
                    sprintf(T_('The sort_pattern has left over variables. %s'), $sort_pattern),
                    true
                );

                return false;
            }
        }

        return $home;
    }

    /**
     * This is run on every individual element of the search before it is put together
     * It removes / and \ and windows-incompatible characters (if you use -w|--windows)
     * @param string|int $string
     * @param string $return
     * @param bool $windowsCompat
     * @return string
     */
    public function sort_clean_name($string, $return = '', $windowsCompat = false)
    {
        if (empty($string)) {
            return $return;
        }
        $string = ($windowsCompat)
            ? str_replace(['/', '\\', ':', '*', '<', '>', '"', '|', '?'], '_', (string)$string)
            : str_replace(['/', '\\'], '_', (string)$string);

        return (string)$string;
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
     * @return bool
     */
    private function sort_move_file(
        Interactor $interactor,
        Song $song,
        $fullname,
        $test_mode,
        $windowsCompat = false
    ) {
        $old_dir   = dirname($song->file);
        $info      = pathinfo($fullname);
        $directory = $info['dirname'];
        $file      = $info['basename'];
        $data      = preg_split("/[\/\\\]/", $directory);
        $path      = '';

        // We not need the leading /
        unset($data[0]);

        foreach ($data as $dir) {
            $dir = $this->sort_clean_name($dir, '', $windowsCompat);
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
                    $results = mkdir($path);
                    if (!$results) {
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

            $results = copy($song->file, $fullname);
            if (!$results) {
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
                $preferred  = $this->sort_clean_name($this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME) ?? 'folder.jpg', '', $windowsCompat);
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

            // If we've made it this far it should be safe
            $results = unlink($song->file);
            if (!$results) {
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
