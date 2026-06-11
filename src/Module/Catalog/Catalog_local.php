<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Catalog;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Recommendation;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Exception;

/**
 * This class handles all actual work in regards to local catalogs.
 */
class Catalog_local extends Catalog
{
    private string $version = '000001';

    private string $type = 'local';

    private string $description = 'Local Catalog';

    private int $catalog_id;

    private int $count = 0;

    /** @var int[] $songs_to_gather */
    private array $songs_to_gather = [];

    /** @var int[] $videos_to_gather */
    private array $videos_to_gather = [];

    public string $path = '';

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * get_path
     * This returns the current catalog path/uri
     */
    public function get_path(): string
    {
        return $this->path;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        return "";
    }

    /**
     * is_installed
     * This returns true or false if local catalog is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'catalog_local'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the local catalog
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = sprintf('CREATE TABLE `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` VARCHAR(255) COLLATE %s NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = %s DEFAULT CHARSET=%s COLLATE=%s', $collation, $engine, $charset, $collation);
        Dba::query($sql);

        return true;
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    public function catalog_fields(): array
    {
        return ['path' => ['description' => T_('Path'), 'type' => 'text']];
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct(?int $catalog_id = null)
    {
        if ($catalog_id) {
            $info = $this->get_info($catalog_id, static::DB_TABLENAME);
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }

            $this->catalog_id = (int)$catalog_id;
        }
    }

    /**
     * get_from_path
     *
     * Try to figure out which catalog path most closely resembles this one.
     * This is useful when creating a new catalog to make sure we're not
     * doubling up here.
     */
    public static function get_from_path(string $path): ?int
    {
        // First pull a list of all of the paths for the different catalogs
        $sql        = "SELECT `catalog_id`, `path` FROM `catalog_local`";
        $db_results = Dba::read($sql);

        $catalog_paths  = [];
        $component_path = $path;

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog_paths[$row['path']] = (int)$row['catalog_id'];
        }

        // Break it down into its component parts and start looking for a catalog
        do {
            if (array_key_exists($component_path, $catalog_paths)) {
                return $catalog_paths[$component_path];
            }

            // Keep going until the path stops changing
            $old_path       = $component_path;
            $parent_path    = realpath($component_path . '/../');
            $component_path = ($parent_path === false)
                ? $component_path
                : $parent_path;
        } while (strcmp($component_path, $old_path) !== 0);

        return null;
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param array{
     *     path?: string,
     * } $data
     */
    public static function create_type(string $catalog_id, array $data): bool
    {
        // Clean up the path just in case
        $path = rtrim(rtrim(trim($data['path'] ?? ''), '/'), '\\');

        if (!self::check_path($path)) {
            AmpError::add('general', T_('Path was not specified'));

            return false;
        }

        // Make sure this path isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_local` WHERE `path` = ?';
        $db_results = Dba::read($sql, [$path]);

        if (Dba::num_rows($db_results) !== 0) {
            debug_event('local.catalog', 'Cannot add catalog with duplicate path ' . $path, 1);
            /* HINT: directory (file path) */
            AmpError::add('general', sprintf(T_('This path belongs to an existing local Catalog: %s'), $path));

            return false;
        }

        $sql = 'INSERT INTO `catalog_local` (`path`, `catalog_id`) VALUES (?, ?)';
        Dba::write($sql, [$path, $catalog_id]);

        return true;
    }

    /**
     * add_files
     *
     * Recurses through $this->path and pulls out all mp3s and returns the
     * full path in an array. Passes gather_type to determine if we need to
     * check id3 information against the db.
     * @param array<string, mixed> $options
     */
    public function add_files(string $path, array $options, int $counter = 0, ?Interactor $interactor = null): int
    {
        // See if we want a non-root path for the add
        if (isset($options['subdirectory'])) {
            $path = $options['subdirectory'];
            unset($options['subdirectory']);
        }

        // Make sure the path doesn't end in a / or \
        $path = rtrim((string) $path, '/');
        $path = rtrim($path, '\\');

        // Correctly detect the slash we need to use here
        $slash_type = str_contains($path, '/') ? '/' : '\\';

        /* Open up the directory */
        $handle = opendir($path);

        if (!is_resource($handle)) {
            $interactor?->info(
                'Unable to open ' . $path,
                true
            );
            debug_event('local.catalog', 'Unable to open ' . $path, 3);
            /* HINT: directory (file path) */
            AmpError::add('catalog_add', sprintf(T_('Unable to open: %s'), $path));

            return 0;
        }

        /* Change the dir so is_dir works correctly */
        if (!chdir($path)) {
            $interactor?->info(
                'Unable to chdir to ' . $path,
                true
            );
            debug_event('local.catalog', 'Unable to chdir to ' . $path, 2);
            /* HINT: directory (file path) */
            AmpError::add('catalog_add', sprintf(T_('Unable to change to directory: %s'), $path));

            return 0;
        }

        $songsadded = 0;
        /* Recurse through this dir and create the files array */
        while (false !== ($file = readdir($handle))) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            // reduce the crazy log info
            if ($counter % 1000 === 0) {
                $interactor?->info(
                    sprintf('Reading %s inside %s', $file, $path),
                    true
                );
                debug_event('local.catalog', sprintf('Reading %s inside %s', $file, $path), 5);
                debug_event('local.catalog', "Memory usage: " . Ui::format_bytes(memory_get_usage(true)), 5);
            }

            $counter++;

            /* Create the new path */
            $full_file = $path . $slash_type . $file;
            try {
                if ($this->add_file($full_file, $options, $counter, $interactor)) {
                    $songsadded++;
                }
            } catch (Exception $error) {
                $interactor?->info(
                    T_('Error') . ' ' . $error->getMessage(),
                    true
                );
                debug_event('local.catalog', 'add_file error: ' . $error->getMessage(), 1);
            }
        } // end while reading directory

        $interactor?->info(
            sprintf('Finished reading %s, closing handle', $path),
            true
        );
        debug_event('local.catalog', sprintf('Finished reading %s, closing handle', $path), 5);

        // This should only happen on the last run
        if ($path === $this->path) {
            Ui::update_text('add_count_' . $this->catalog_id, $this->count);
        }

        /* Close the dir handle */
        closedir($handle);

        return $songsadded;
    }

    public function add_folder(string $folderName, string $folderPath, string $parentPath): ?Folder
    {
        $folder = self::getFolderRepository()->getByPathName($folderPath, $this->getId(), $parentPath);
        if (!$folder || $folder->isNew()) {
            $parent = ($parentPath === $this->path)
                ? self::getFolderRepository()->lookup((string)$this->get_fullname(), $this->getId())
                : self::getFolderRepository()->lookup($parentPath, $this->getId());
            $folder = self::getFolderRepository()->create($folderName, $this->getId(), $folderPath, $parent);
        }

        if (!$folder || $folder->isNew()) {
            return null;
        }

        return $folder;
    }

    /**
     * add_file
     * @throws Exception
     */
    public function add_file(string $full_file, array $options, int $counter = 0, ?Interactor $interactor = null): bool
    {
        // Ensure that we've got our cache
        $this->_create_filecache();

        /* First thing first, check if file is already in catalog.
         * This check is very quick, so it should be performed before any other checks to save time
         */
        if (isset($this->_filecache[strtolower($full_file)])) {
            return false;
        }

        if (AmpConfig::get('no_symlinks') && is_link($full_file)) {
            $interactor?->info(
                'Skipping symbolic link ' . $full_file,
                true
            );
            debug_event('local.catalog', 'Skipping symbolic link ' . $full_file, 5);

            return false;
        }

        if (!array_key_exists('gather_art', $options)) {
            $options['gather_art'] = false;
        }

        if (!array_key_exists('parse_playlist', $options)) {
            $options['parse_playlist'] = false;
        }

        /* If it's a dir run this function again! */
        if (is_dir($full_file)) {
            $this->add_files($full_file, $options, $counter);

            /* Change the dir so is_dir works correctly */
            if (!chdir($full_file)) {
                $interactor?->info(
                    'Unable to chdir to ' . $full_file,
                    true
                );
                debug_event('local.catalog', 'Unable to chdir to ' . $full_file, 2);
                /* HINT: directory (file path) */
                AmpError::add('catalog_add', sprintf(T_('Unable to change to directory: %s'), $full_file));
            }

            /* Skip to the next file */
            return true;
        } // it's a directory

        $is_audio_file = Catalog::is_audio_file($full_file);
        $is_video_file = false;
        if (AmpConfig::get('catalog_video_pattern')) {
            $is_video_file = Catalog::is_video_file($full_file);
        }

        $is_playlist = false;
        if ($options['parse_playlist'] && AmpConfig::get('catalog_playlist_pattern')) {
            $is_playlist = Catalog::is_playlist_file($full_file);
        }

        /* see if this is a valid audio file or playlist file */
        if ($is_audio_file || $is_video_file || $is_playlist) {
            /* Now that we're sure its a file get filesize  */
            $file_size = Core::get_filesize($full_file);

            if ($file_size === 0) {
                $interactor?->info(
                    'Unable to get filesize for ' . $full_file,
                    true
                );
                debug_event('local.catalog', 'Unable to get filesize for ' . $full_file, 2);
                /* HINT: FullFile */
                AmpError::add('catalog_add', sprintf(T_('Unable to get the filesize for "%s"'), $full_file));

                return false;
            } // file_size check

            // not readable, warn user
            if (!Core::is_readable($full_file)) {
                $interactor?->info(
                    $full_file . ' is not readable by Ampache',
                    true
                );
                debug_event('local.catalog', $full_file . ' is not readable by Ampache', 2);
                /* HINT: filename (file path) */
                AmpError::add('catalog_add', sprintf(T_("The file couldn't be read. Does it exist? %s"), $full_file));

                return false;
            }

            // Check to make sure the filename is of the expected charset
            if (function_exists('iconv')) {
                $site_charset = AmpConfig::get('site_charset', 'UTF-8');
                $lc_charset   = $site_charset;
                if (AmpConfig::get('lc_charset')) {
                    $lc_charset = AmpConfig::get('lc_charset');
                }

                $enc_full_file = iconv((string) $lc_charset, (string) $site_charset, $full_file);
                if ($enc_full_file !== false) {
                    if ($lc_charset != $site_charset) {
                        $convok = (iconv((string) $site_charset, (string) $lc_charset, $enc_full_file) && strcmp($full_file, iconv((string) $site_charset, (string) $lc_charset, $enc_full_file)) === 0);
                    } else {
                        $convok = (strcmp($enc_full_file, $full_file) === 0);
                    }

                    if (!$convok) {
                        $interactor?->info(
                            $full_file . ' has non-' . $site_charset . ' characters and can not be indexed, converted filename:' . $enc_full_file,
                            true
                        );
                        debug_event('local.catalog', $full_file . ' has non-' . $site_charset . ' characters and can not be indexed, converted filename:' . $enc_full_file, 1);
                        /* HINT: FullFile */
                        AmpError::add('catalog_add', sprintf(T_('"%s" does not match site charset'), $full_file));

                        return false;
                    }

                    $full_file = $enc_full_file;

                    // Check again with good encoding
                    if (isset($this->_filecache[strtolower($full_file)])) {
                        return false;
                    }
                }
            } // end if iconv

            if ($is_playlist) {
                // if it's a playlist
                $interactor?->info(
                    'Found playlist file to import: ' . $full_file,
                    true
                );
                debug_event('local.catalog', 'Found playlist file to import: ' . $full_file, 5);
                $this->_playlists[] = $full_file;
            } else {
                if ($this->get_gather_types('music') !== []) {
                    if ($is_audio_file && $this->_insert_local_song($full_file, $options)) {
                        $interactor?->info(
                            T_('Added') . ' ' . $full_file,
                            true
                        );
                        debug_event('local.catalog', 'Imported song file: ' . $full_file, 5);
                    } else {
                        $interactor?->info(
                            'Skipped song file: ' . $full_file,
                            true
                        );
                        debug_event('local.catalog', 'Skipped song file: ' . $full_file, 5);

                        return false;
                    }
                } elseif ($this->get_gather_types('video') !== []) {
                    if ($is_video_file && $this->_insert_local_video($full_file, $options)) {
                        $interactor?->info(
                            T_('Added') . ' ' . $full_file,
                            true
                        );
                        debug_event('local.catalog', 'Imported video file: ' . $full_file, 5);
                    } else {
                        $interactor?->info(
                            'Skipped video file: ' . $full_file,
                            true
                        );
                        debug_event('local.catalog', 'Skipped video file: ' . $full_file, 5);

                        return false;
                    }
                }

                $this->count++;
                $file = str_replace(['(', ')', "'"], '', $full_file);
                if (Ui::check_ticker()) {
                    Ui::update_text('add_count_' . $this->catalog_id, $this->count);
                    Ui::update_text('add_dir_' . $this->catalog_id, scrub_out($file));
                } // update our current state
            } // if it's not an m3u

            return true;
        }

        // if it matches the pattern
        if ($counter % 1000 === 0) {
            debug_event('local.catalog', $full_file . ' ignored, non-audio file or 0 bytes', 5);
        }

        return false;
        // else not an audio file
    }

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        if ($options === null || $options === []) {
            $options = [
                'gather_art' => true,
                'parse_playlist' => false
            ];
        }

        // make double sure that options are set
        if (!array_key_exists('gather_art', $options)) {
            $options['gather_art'] = true;
        }

        if (!array_key_exists('parse_playlist', $options)) {
            $options['parse_playlist'] = false;
        }

        $this->count            = 0;
        $this->songs_to_gather  = [];
        $this->videos_to_gather = [];

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            require Ui::find_template('show_adds_catalog.inc.php');
            flush();
        }

        /* Set the Start time */
        $start_time = time();

        // Make sure the path doesn't end in a / or \
        $this->path = rtrim($this->path, '/');
        $this->path = rtrim($this->path, '\\');

        // Prevent the script from timing out and flush what we've got
        set_time_limit(0);

        // If podcast catalog, we don't want to analyze files for now
        if ($this->gather_types == 'podcast') {
            $this->count += $this->getPodcastSyncer()->syncForCatalogs([$this]);
        } else {
            /* Get the songs and then insert them into the db */
            $this->count += $this->add_files($this->path, $options, 0, $interactor);
            if ($options['parse_playlist'] && count($this->_playlists)) {
                // Foreach Playlists we found
                foreach ($this->_playlists as $full_file) {
                    $interactor?->info(
                        'Processing playlist: ' . $full_file,
                        true
                    );
                    debug_event('local.catalog', 'Processing playlist: ' . $full_file, 5);
                    $result = PlaylistImporter::import_playlist($full_file, -1, 'public');
                    if ($result !== null) {
                        $file = basename($full_file);
                        echo PHP_EOL . $full_file . PHP_EOL;
                        if (!empty($result['results'])) {
                            foreach ($result['results'] as $file) {
                                if ($file['found']) {
                                    echo $file['track'] . ": " . T_('Success') . ":\t" . scrub_out($file['file']) . "\n";
                                } else {
                                    echo "-: " . T_('Failure') . ":\t" . scrub_out($file['file']) . "\n";
                                }

                                flush();
                            }

                            // foreach songs
                            echo "\n";
                        }
                    } // end if import worked
                } // end foreach playlist files
            }

            // only gather art if you've added new stuff
            if (($this->count) > 0 && $options['gather_art']) {
                $interactor?->info(
                    'gather_art after adding',
                    true
                );
                debug_event(self::class, 'gather_art after adding', 4);
                $catalog_id = $this->catalog_id;
                if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                    require Ui::find_template('show_gather_art.inc.php');
                    flush();
                }

                $this->gather_art($this->songs_to_gather, $this->videos_to_gather);
            }
        }

        if ($this->count > 0) {
            // update the counts too
            if ($this->gather_types == 'music') {
                Album::update_table_counts();
                Artist::update_table_counts();
            }

            /* Update the Catalog last_update */
            $this->update_last_add();
        }

        $time_diff = time() - $start_time;
        $rate      = ($time_diff > 0)
            ? number_format($this->count / $time_diff)
            : '0';
        if (((float)$rate) < 1) {
            $rate = T_('N/A');
        }

        $interactor?->info(
            T_('Catalog Updated') . "\n" . sprintf(T_('Total Time: [%s] Total Media: [%s] Media Per Second: [%s]'), date('i:s', $time_diff), $this->count, $rate),
            true
        );
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top();
            Ui::update_text(
                T_('Catalog Updated'),
                sprintf(T_('Total Time: [%s] Total Media: [%s] Media Per Second: [%s]'), date('i:s', $time_diff), $this->count, $rate)
            );
            Ui::show_box_bottom();
        }

        return $this->count;
    }

    /**
     * scan_catalog_folders
     */
    public function scan_catalog_folders(?Interactor $interactor = null): int
    {
        set_time_limit(0);

        $interactor?->info(
            'Scan starting on ' . $this->name,
            true
        );
        debug_event('local.catalog', 'Scan starting on ' . $this->name . ' (' . time() . ')', 5);
        sleep(1);

        $this->count = $this->_scan_catalog_folder($interactor);

        $interactor?->info(
            sprintf('Scan finished, %d updated in ', $this->count) . $this->name,
            true
        );
        debug_event('local.catalog', sprintf('Scan finished, %d updated in ', $this->count) . $this->name, 5);
        sleep(1);

        return $this->count;
    }

    /**
     * verify_catalog_proc
     */
    public function verify_catalog_proc(?int $limit = 0, ?Interactor $interactor = null): int
    {
        $interactor?->info(
            'Verify starting on ' . $this->name,
            true
        );
        set_time_limit(0);

        $this->count = 0;
        $chunk_size  = 10000;

        debug_event('local.catalog', 'Verify starting on ' . $this->name . ' (' . time() . ')', 5);
        sleep(1);

        $last_update     = true;
        $gather_type     = $this->gather_types;
        $verify_by_album = AmpConfig::get('catalog_verify_by_album', false);
        $verify_by_time  = ($gather_type !== 'album' && AmpConfig::get('catalog_verify_by_time', false));
        $update_time     = ($verify_by_time)
            ? $this->last_update
            : 0;
        if (!$verify_by_album && $gather_type == 'music') {
            Song::clear_cache();
            $media_type = 'song';
            $total      = self::count_table($media_type, $this->catalog_id, $update_time, $limit);
        } elseif ($verify_by_album && $gather_type == 'music') {
            $chunk_size = 1000;
            Album::clear_cache();
            $media_type = 'album';
            $total      = self::count_table($media_type, $this->catalog_id, $update_time, $limit);
        } elseif ($gather_type == 'podcast') {
            Podcast_Episode::clear_cache();
            $media_type = 'podcast_episode';
            $total      = self::count_table($media_type, $this->catalog_id, $update_time, $limit);
        } elseif ($gather_type == 'video') {
            Video::clear_cache();
            $media_type = 'video';
            $total      = self::count_table($media_type, $this->catalog_id, $update_time, $limit);
        } else {
            return $this->count;
        }

        // count with no limit after 0
        if ($total === 0 && ($update_time > 0 || $limit > 0)) {
            $last_update = false;
            $total       = self::count_table($media_type, $this->catalog_id);
        }

        $count  = 1;
        $chunks = 1;
        $chunk  = 0;

        // how many loops through the catalog
        if ($total > $chunk_size) {
            $chunks = (int)ceil($total / $chunk_size);
        }

        // one loop
        if ($total < $chunk_size) {
            $chunk = 1;
        }

        // only do the requested size
        if ($limit > 0 && $total < $chunk_size) {
            $chunk      = 1;
            $chunks     = 1;
            $chunk_size = $total;
        }

        $interactor?->info(
            sprintf(T_('File count: %d'), $total) . ' (last_update: ' . $this->last_update . ')',
            true
        );
        debug_event('local.catalog', sprintf(T_('File count: %d'), $total) . ' (last_update: ' . $this->last_update . ')', 5);
        while ($count <= $chunks) {
            $interactor?->info(
                "catalog " . $this->name . " starting verify " . $media_type . sprintf(' on chunk %d/%d', $count, $chunks),
                true
            );
            debug_event('local.catalog', "catalog " . $this->name . " starting verify " . $media_type . sprintf(' on chunk %d/%d', $count, $chunks), 5);
            $this->count += $this->_verify_chunk($media_type, ($chunks - $chunk), $chunk_size, $verify_by_time, $last_update);
            $chunk++;
            $count++;
            if ($media_type === 'song') {
                Catalog::clean_empty_albums();
            }
        }

        $interactor?->info(
            sprintf('Verify finished, %d updated in ', $this->count) . $this->name,
            true
        );
        debug_event('local.catalog', sprintf('Verify finished, %d updated in ', $this->count) . $this->name, 5);
        if ($interactor == null && $gather_type == 'music') {
            Album::update_table_counts();
            Artist::update_table_counts();

            $this->getArtistRepository()->collectGarbage();
            $this->getAlbumRepository()->collectGarbage();
        }

        sleep(1);
        // No limit set OR we set a limit and we didn't find anything so update the last_update time
        if ($limit === 0 || $last_update === false) {
            $this->update_last_update(time());
        }

        return $this->count;
    }

    /**
     * _verify_chunk
     * This verifies a chunk of the catalog, done to save
     * memory
     */
    private function _verify_chunk(string $tableName, int $chunk, int $chunk_size, bool $verify_by_time, bool $last_update): int
    {
        $count = $chunk * $chunk_size;
        $sql   = match ($tableName) {
            'album' => ($last_update)
                ? "SELECT `album`.`id`, MIN(`song`.`file`) AS `file`, MIN(`song`.`update_time`) AS `min_update_time` FROM `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` WHERE `album`.`catalog` = ? AND `song`.`update_time` < " . $this->last_update . (' GROUP BY `album`.`id` ORDER BY MIN(`song`.`file`) DESC LIMIT ' . $chunk_size)
                : 'SELECT `album`.`id`, MIN(`song`.`file`) AS `file`, MIN(`song`.`update_time`) AS `min_update_time` FROM `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` WHERE `album`.`catalog` = ? GROUP BY `album`.`id` ORDER BY MIN(`song`.`file`) DESC LIMIT ' . $chunk_size,
            default => ($last_update)
                ? sprintf('SELECT `%s`.`id`, `%s`.`file`, `%s`.`update_time` AS `min_update_time` FROM `%s` LEFT JOIN `catalog` ON `%s`.`catalog` = `catalog`.`id` WHERE `%s`.`catalog` = ? AND `%s`.`update_time` < `catalog`.`last_update` ORDER BY `%s`.`file` DESC LIMIT %d', $tableName, $tableName, $tableName, $tableName, $tableName, $tableName, $tableName, $tableName, $chunk_size)
                : sprintf('SELECT `%s`.`id`, `%s`.`file`, `%s`.`update_time` AS `min_update_time` FROM `%s` LEFT JOIN `catalog` ON `%s`.`catalog` = `catalog`.`id` WHERE `%s`.`catalog` = ? ORDER BY `%s`.`file` DESC LIMIT %d', $tableName, $tableName, $tableName, $tableName, $tableName, $tableName, $tableName, $chunk_size),
        };

        //debug_event(self::class, '_verify_chunk (' . $chunk . ') ' . $sql. ' ' . print_r($params, true), 5);
        if ($tableName !== 'podcast_episode' && AmpConfig::get('memory_cache', false)) {
            $media_ids  = [];
            $db_results = Dba::read($sql, [$this->catalog_id]);
            $className  = ObjectTypeToClassNameMapper::map($tableName);
            while ($row = Dba::fetch_assoc($db_results, false)) {
                $media_ids[] = $row['id'];
            }

            $className::build_cache($media_ids);
        }

        $changed    = 0;
        $db_results = Dba::read($sql, [$this->catalog_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $count++;
            if (Ui::check_ticker()) {
                $file = str_replace(['(', ')', "'"], '', $row['file']);
                Ui::update_text('verify_count_' . $this->catalog_id, $count);
                Ui::update_text('verify_dir_' . $this->catalog_id, scrub_out($file));
            }

            if ($tableName !== 'album') {
                if (!Core::is_readable(Core::conv_lc_file((string)$row['file']))) {
                    /* HINT: filename (file path) */
                    AmpError::add('general', sprintf(T_("The file couldn't be read. Does it exist? %s"), $row['file']));
                    debug_event('local.catalog', $row['file'] . ' does not exist or is not readable', 5);
                    switch ($tableName) {
                        case 'song':
                            Song::update_utime($row['id']);
                            break;
                        case 'video':
                            Video::update_utime($row['id']);
                            break;
                        case 'podcast_episode':
                            Podcast_Episode::update_utime($row['id']);
                            break;
                    }

                    continue;
                }

                if ($verify_by_time) {
                    $file_time = filemtime($row['file']);
                    if ($file_time === false) {
                        debug_event('local.catalog', 'Unable to get file modification time for ' . $row['file'], 3);
                        switch ($tableName) {
                            case 'song':
                                Song::update_utime($row['id']);
                                break;
                            case 'video':
                                Video::update_utime($row['id']);
                                break;
                            case 'podcast_episode':
                                Podcast_Episode::update_utime($row['id']);
                                break;
                        }

                        continue;
                    }

                    // check the modification time on the file to see if it's worth checking the tags.
                    if ((int)($row['min_update_time'] ?? 0) > $file_time) {
                        //debug_event('local.catalog', 'verify_by_time: skipping ' . $row['file'], 5);
                        switch ($tableName) {
                            case 'song':
                                Song::update_utime($row['id']);
                                break;
                            case 'video':
                                Video::update_utime($row['id']);
                                break;
                            case 'podcast_episode':
                                Podcast_Episode::update_utime($row['id']);
                                break;
                        }

                        continue;
                    }
                }
            }

            if (self::update_single_item($tableName, $row['id'], true, true)['change']) {
                $changed++;
            }
        }

        Ui::update_text('verify_count_' . $this->catalog_id, $count);

        return $changed;
    }

    /**
     * clean catalog procedure
     *
     * Removes local songs that no longer exist.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        // First sanity check; no point in proceeding with an unreadable catalog root.
        if (!Core::is_readable($this->path)) {
            $interactor?->info(
                'Catalog path:' . $this->path . ' unreadable, clean failed',
                true
            );
            debug_event('local.catalog', 'Catalog path:' . $this->path . ' unreadable, clean failed', 1);
            if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                AmpError::add('general', T_('Catalog root unreadable, stopping clean'));
                echo AmpError::display('general');
            }

            return 0;
        }

        $this->count = 0;

        $gather_type = $this->gather_types;
        $media_type  = 'song';
        if ($gather_type == 'podcast') {
            $media_type = 'podcast_episode';
        } elseif ($gather_type == 'video') {
            $media_type = 'video';
        }

        $total = self::count_table($media_type, $this->catalog_id);
        if ($total === 0) {
            return $this->count;
        }

        $dead   = [];
        $count  = 1;
        $chunks = 1;
        $chunk  = 0;
        if ($total > 10000) {
            $chunks = (int)ceil($total / 10000);
        }

        while ($chunk < $chunks) {
            $interactor?->info(
                "catalog " . $this->name . " Starting clean " . $media_type . sprintf(' on chunk %d/%d', $count, $chunks),
                true
            );
            debug_event('local.catalog', "catalog " . $this->name . " Starting clean " . $media_type . sprintf(' on chunk %d/%d', $count, $chunks), 5);
            $dead = array_merge($dead, $this->_clean_chunk($media_type, $chunk, 10000, $interactor));
            $chunk++;
            $count++;
        }

        $interactor?->info(
            sprintf('Clean finished, %s files checked in ', $total) . $this->name,
            true
        );
        debug_event('local.catalog', sprintf('Clean finished, %s files checked in ', $total) . $this->name, 5);

        $dead_count = count($dead);
        // Check for unmounted path
        if (!file_exists($this->path) && $dead_count >= $total) {
            $interactor?->info(
                'All files would be removed. Doing nothing.',
                true
            );
            debug_event('local.catalog', 'All files would be removed. Doing nothing.', 1);
            AmpError::add('general', T_('All files would be removed. Doing nothing'));

            return $this->count;
        }

        if ($dead_count !== 0) {
            $this->count += $dead_count;
            $sql = sprintf('DELETE FROM `%s` WHERE `id` IN (', $media_type) . implode(',', $dead) . ")";
            Dba::write($sql);
        }

        $this->getMetadataManager()->collectGarbage();

        return $this->count;
    }

    /**
     * _scan_catalog_folder
     * This is the clean function and is broken into chunks to try to save a little memory
     */
    private function _scan_catalog_folder(?Interactor $interactor = null): int
    {
        $interactor?->info(
            'Scanning check on: ' . $this->path,
            true
        );
        debug_event('local.catalog', 'Scanning check on: ' . $this->path, 5);

        if (!$this->get_fullname()) {
            return 0;
        }

        $folder = self::getFolderRepository()->getByPathName($this->path, $this->getId());
        if (!$folder || $folder->isNew()) {
            $folderId = Folder::create([
                'name' => $this->get_fullname(),
                'catalog' => $this->getId(),
                'path_name' => $this->path,
            ]);

            $folder = ($folderId)
                ? new Folder($folderId)
                : null;
        }

        if (!$folder) {
            $interactor?->error(
                'Failed to open folder: ' . $this->path,
                true
            );
            debug_event('local.catalog', 'Failed to open folder: ' . $this->path, 5);

            return 0;
        }

        // Make sure the path doesn't end in a / or \
        $path = rtrim($this->path, '/');
        $path = rtrim($path, '\\');

        // Correctly detect the slash we need to use here
        $slash_type = str_contains($path, '/') ? '/' : '\\';

        /* Open up the directory */
        $handle = opendir($path);

        if (!is_resource($handle)) {
            $interactor?->info(
                'Unable to open ' . $path,
                true
            );
            debug_event('local.catalog', 'Unable to open ' . $path, 3);
            /* HINT: directory (file path) */
            AmpError::add('catalog_scan', sprintf(T_('Unable to open: %s'), $path));

            return 0;
        }

        /* Change the dir so is_dir works correctly */
        if (!chdir($path)) {
            $interactor?->info(
                'Unable to chdir to ' . $path,
                true
            );
            debug_event('local.catalog', 'Unable to chdir to ' . $path, 2);
            /* HINT: directory (file path) */
            AmpError::add('catalog_scan', sprintf(T_('Unable to change to directory: %s'), $path));

            return 0;
        }

        $counter      = 0;
        $foldersadded = 0;
        /* Recurse through this dir and create the files array */
        while (false !== ($file = readdir())) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            // reduce the crazy log info
            if ($counter % 1000 === 0) {
                $interactor?->info(
                    sprintf('Reading %s inside %s', $file, $path),
                    true
                );
                debug_event('local.catalog', sprintf('Reading %s inside %s', $file, $path), 5);
                debug_event('local.catalog', "Memory usage: " . Ui::format_bytes(memory_get_usage(true)), 5);
            }

            $counter++;

            /* Create the new path */
            $full_file = $path . $slash_type . $file;

            try {
                if (
                    is_dir($full_file) &&
                    $this->add_folder($file, $full_file, $path)
                ) {
                    $foldersadded++;
                }
            } catch (Exception $error) {
                $interactor?->info(
                    T_('Error') . ' ' . $error->getMessage(),
                    true
                );
                debug_event('local.catalog', 'add_file error: ' . $error->getMessage(), 1);
            }
        } // end while reading directory

        $interactor?->info(
            sprintf('Finished reading %s, closing handle', $path),
            true
        );
        debug_event('local.catalog', sprintf('Finished reading %s, closing handle', $path), 5);

        // This should only happen on the last run
        if ($path === $this->path) {
            Ui::update_text('scan_count_' . $this->catalog_id, $this->count);
        }

        /* Close the dir handle */
        closedir($handle);

        return $foldersadded;
    }

    /**
     * _clean_chunk
     * This is the clean function and is broken into chunks to try to save a little memory
     * @return int[]
     */
    private function _clean_chunk(string $media_type, int $chunk, int $chunk_size, ?Interactor $interactor = null): array
    {
        $dead  = [];
        $count = $chunk * $chunk_size;

        $sql        = sprintf('SELECT `id`, `file` FROM `%s` WHERE `catalog` = ? AND `file` IS NOT NULL LIMIT %d, %d;', $media_type, $count, $chunk_size);
        $db_results = Dba::read($sql, [$this->catalog_id]);
        while ($results = Dba::fetch_assoc($db_results)) {
            //debug_event('local.catalog', 'Cleaning check on ' . $results['file'] . ' (' . $results['id'] . ')', 5);
            $count++;
            if (Ui::check_ticker()) {
                $file = str_replace(['(', ')', "'"], '', $results['file']);
                Ui::update_text('clean_count_' . $this->catalog_id, $count);
                Ui::update_text('clean_dir_' . $this->catalog_id, scrub_out($file));
            }

            if ($this->clean_file($results['file'], $media_type)) {
                $interactor?->info(
                    'File removed: ' . $results['file'],
                    true
                );
                $dead[] = $results['id'];
            }
        }

        return $dead;
    }

    /**
     * _check_chunk
     * This is the check function and is broken into chunks to try to save a little memory
     * @return list<string>
     */
    private function _check_chunk(string $media_type, int $chunk, int $chunk_size, ?Interactor $interactor = null): array
    {
        $missing = [];
        $count   = $chunk * $chunk_size;

        $sql        = sprintf('SELECT `id`, `file` FROM `%s` WHERE `catalog` = ? LIMIT %d, %d;', $media_type, $count, $chunk_size);
        $db_results = Dba::read($sql, [$this->catalog_id]);

        while ($results = Dba::fetch_assoc($db_results)) {
            $file_info = Core::get_filesize(Core::conv_lc_file($results['file']));
            if ($file_info < 1) {
                $interactor?->info(
                    'File not found or empty: ' . $results['file'],
                    true
                );
                debug_event('local.catalog', '_clean_chunk: {' . $results['id'] . '} File not found or empty ' . $results['file'], 5);
                $missing[] = $results['file'];
            } elseif (!Core::is_readable(Core::conv_lc_file((string)$results['file']))) {
                $interactor?->info(
                    $results['file'] . ' is not readable, but does exist',
                    true
                );
                debug_event('local.catalog', "_clean_chunk: " . $results['file'] . ' is not readable, but does exist', 1);
            }
        }

        return $missing;
    }

    private function _move_file(Song|Podcast_Episode|Video $media, string $new_file, int $newCatalogId, ?Interactor $interactor = null): bool
    {
        if (file_exists($new_file) && is_file($new_file)) {
            debug_event('local.catalog', 'Error: ' . $new_file . ' already exists', 2);
            $interactor?->info(
                T_('Error') . ': ' . T_('File already exists') . ' ' . $new_file,
                true
            );

            return false;
        }

        // HINT: %1$s: file, %2$s: directory
        $interactor?->info(
            sprintf(T_('Copying "%1$s" to "%2$s"'), $media->file, $new_file),
            true
        );

        $info      = pathinfo($new_file);
        $directory = ($info['dirname'] ?? '');
        if (!Core::is_readable($directory) || !is_dir($directory)) {
            debug_event(self::class, 'mkdir: ' . $directory, 5);
            if (!mkdir($directory, 0775, true)) {
                debug_event('local.catalog', T_('Error') . ': ' . sprintf(T_('Create directory "%s"'), $directory), 2);
                $interactor?->info(
                    T_('Error') . ': ' . sprintf(T_('Create directory "%s"'), $directory),
                    true
                );

                return false;
            }
        }

        if (in_array($media->file, [null, '', '0'], true) || !copy($media->file, $new_file)) {
            if (is_file($new_file)) {
                unlink($new_file);
            }

            /* HINT: filename (File path) */
            $interactor?->info(
                sprintf(T_('There was an error trying to copy file to "%s"'), $new_file),
                true
            );

            return false;
        }

        debug_event('local.catalog', 'Copied ' . $media->file . ' to ' . $new_file, 5);

        // Check the filesize
        $new_sum = Core::get_filesize($new_file);
        $old_sum = Core::get_filesize($media->file);

        if ($new_sum !== $old_sum || $new_sum === 0) {
            if (is_file($new_file)) {
                unlink($new_file);
            }

            /* HINT: filename (File path) */
            $interactor?->info(
                sprintf(T_('Size comparison failed. Not deleting "%s"'), $media->file),
                true
            );

            return false;
        } // end if sum's don't match

        if (!unlink($media->file)) {
            /* HINT: filename (File path) */
            $interactor?->info(
                sprintf(T_('There was an error trying to delete "%s"'), $media->file),
                true
            );
        }

        // Update the catalog
        $sql = "UPDATE `song` SET `file` = ?, catalog = ? WHERE `id` = ?;";

        return (Dba::write($sql, [$new_file, $newCatalogId, $media->id]) !== null);
    }

    /**
     * get_catalog_id_from_file
     *
     * Get catalog id from the file path.
     */
    private function _get_catalog_id_from_file(string $file_path): int
    {
        $sql        = "SELECT `catalog_id` FROM `catalog_local` WHERE ? LIKE CONCAT(`path`, '%')";
        $db_results = Dba::read($sql, [$file_path]);

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['catalog_id'];
        }

        return 0;
    }

    /**
     * move_file
     *
     * Move the file to a new location
     * New path MUST be within an existing catalog
     */
    public function move_file(Song|Podcast_Episode|Video $object, string $new_file, ?string $media_type = null, ?Interactor $interactor = null): bool
    {
        if ($this->get_type() !== 'local') {
            return false;
        }

        switch ($media_type) {
            case 'song':
            case 'video':
            case 'podcast_episode':
                $newCatalogId = $this->_get_catalog_id_from_file($new_file);
                $newCatalog   = self::create_from_id($newCatalogId);
                if ($newCatalog?->get_type() !== 'local') {
                    debug_event('local.catalog', sprintf('move_file: %s is not part of a local catalog', $new_file), 1);

                    return false;
                }

                if (self::_move_file($object, $new_file, $newCatalogId, $interactor)) {
                    if ($object->catalog !== $newCatalog->id) {
                        // update mapping for new catalogs
                        $sql = "UPDATE `catalog_map` SET `catalog_id` = ? WHERE `object_type` = ? AND `object_id` = ?;";

                        if (Dba::write($sql, [$newCatalogId, $media_type, $object->getId()]) !== null) {
                            if ($object instanceof Song) {
                                $sql        = "SELECT `id` FROM `song` WHERE `album` = ? AND `catalog` = ?;";
                                $db_results = Dba::read($sql);
                                // you have moved all the songs so update the album catalog too
                                if (Dba::num_rows($db_results) === 0) {
                                    Dba::write("UPDATE `album` SET `catalog` = ? WHERE `id` = ?;", [$newCatalogId, $object->album]);
                                }

                                $sql = "UPDATE `catalog_map` SET `catalog_id` = ? WHERE `object_type` = ? AND `object_id` = ?;";

                                return (Dba::write($sql, [$newCatalogId, $media_type, $object->album]) !== null);
                            }

                            if ($object instanceof Podcast_Episode) {
                                $sql        = "SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? AND `catalog` = ?;";
                                $db_results = Dba::read($sql);
                                // you have moved all the episodes so update the podcast catalog too
                                if (Dba::num_rows($db_results) === 0) {
                                    Dba::write("UPDATE `album` SET `catalog` = ? WHERE `id` = ?;", [$newCatalogId, $object->podcast]);
                                }

                                $sql = "UPDATE `catalog_map` SET `catalog_id` = ? WHERE `object_type` = ? AND `object_id` = ?;";

                                return (Dba::write($sql, [$newCatalogId, $media_type, $object->getPodcastId()]) !== null);
                            }

                            return true;
                        }
                    }

                    return true;
                }

                return false;
            default:
                return false;
        }
    }

    /**
     * set_file
     *
     * Update file path
     * Return true on rename. false on failures
     */
    public function set_file(Song|Podcast_Episode|Video $object, string $new_file, ?string $media_type = null): bool
    {
        switch ($media_type) {
            case 'song':
            case 'video':
            case 'podcast_episode':
                $newCatalogId = self::get_id_from_file($new_file, (string)$media_type);
                $newCatalog   = self::create_from_id($newCatalogId);
                if ($newCatalog === null) {
                    return false;
                }

                if ($object->catalog !== $newCatalog->id) {
                    // update mapping for new catalogs
                    $sql = "UPDATE `catalog_map` SET `catalog_id` = ? WHERE `object_type` = ? AND `object_id` = ?;";

                    return (Dba::write($sql, [$newCatalogId, $media_type, $object->getId()]) !== null);
                }

                $sql = sprintf('UPDATE `%s` SET `file` = ?, `catalog` = ? WHERE `id` = ?;', $media_type);

                return (Dba::write($sql, [$new_file, $newCatalogId, $object->getId()]) !== null);
            default:
                return false;
        }
    }

    /**
     * clean_file
     *
     * Clean up a single file checking that it's missing or just unreadable.
     * Return true on delete. false on failures
     */
    public function clean_file(string $file, string $media_type = 'song'): bool
    {
        $file_info = Core::get_filesize(Core::conv_lc_file($file));
        if ($file_info < 1) {
            $object_id = Catalog::get_id_from_file($file, $media_type);
            debug_event('local.catalog', 'clean_file: {' . $object_id . '} File not found or empty ' . $file, 5);
            /* HINT: filename (file path) */
            AmpError::add('general', sprintf(T_('File was not found or is 0 Bytes: %s'), $file));
            $params = [$object_id];
            switch ($media_type) {
                case 'song':
                    $sql = "REPLACE INTO `deleted_song` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist` FROM `song` WHERE `id` = ?;";
                    Dba::write($sql, $params);
                    break;
                case 'video':
                    $sql = "REPLACE INTO `deleted_video` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip` FROM `video` WHERE `id` = ?;";
                    Dba::write($sql, $params);
                    break;
                case 'podcast_episode':
                    $sql = "REPLACE INTO `deleted_podcast_episode` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast` FROM `podcast_episode` WHERE `id` = ?;";
                    Dba::write($sql, $params);
                    break;
            }

            $sql = sprintf('DELETE FROM `%s` WHERE `id` = ?', $media_type);
            Dba::write($sql, $params);

            return true;
        } elseif (!Core::is_readable(Core::conv_lc_file((string)$file))) {
            debug_event('local.catalog', "clean_file: " . $file . ' is not readable, but does exist', 1);
        }

        return false;
    }

    /**
     * insert_local_song
     *
     * Insert a song that isn't already in the database.
     * @param array<string, mixed> $options
     * @throws Exception
     * @throws Exception
     */
    private function _insert_local_song(string $file, array $options = []): ?int
    {
        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $file,
            $this->get_gather_types('music'),
            '',
            '',
            (string) $this->sort_pattern,
            (string) $this->rename_pattern
        );
        $vainfo->gather_tags();

        $key = VaInfo::get_tag_type($vainfo->tags);

        $results            = VaInfo::clean_tag_info($vainfo->tags, $key, $file);
        $results['catalog'] = $this->catalog_id;

        if (array_key_exists('user_upload', $options)) {
            $results['user_upload'] = $options['user_upload'];
        }

        if (array_key_exists('license', $options)) {
            $results['license'] = $options['license'];
        }

        if (array_key_exists('artist_id', $options) && (int)$options['artist_id'] > 0) {
            $results['artist_id']      = $options['artist_id'];
            $results['albumartist_id'] = $options['artist_id'];
            $artist                    = new Artist($results['artist_id']);
            if ($artist->isNew() === false) {
                $results['artist'] = $artist->name;
            }
        }

        if (array_key_exists('album_id', $options) && (int)$options['album_id'] > 0) {
            $results['album_id'] = $options['album_id'];
            $album               = new Album($results['album_id']);
            if ($album->isNew() === false) {
                $results['album'] = $album->name;
            }
        }

        $song_id = Song::insert($results);
        if (!$song_id) {
            debug_event('local.catalog', 'Failed to insert song ' . $file, 5);

            return null;
        }

        $is_duplicate = false;
        if ($this->get_gather_types('music') !== []) {
            if (AmpConfig::get('catalog_check_duplicate') && Song::find($results)) {
                debug_event('local.catalog', 'disable_duplicate ' . $file, 5);
                $is_duplicate = true;
            }

            if (array_key_exists('move_match_pattern', $options)) {
                debug_event(self::class, 'Move uploaded file ' . $song_id . ' according to pattern', 5);
                $song = new Song($song_id);
                $root = $this->path;
                debug_event(self::class, 'Source: ' . $song->file, 5);
                if (AmpConfig::get('upload_subdir') && $song->user_upload) {
                    $root .= DIRECTORY_SEPARATOR . User::get_username($song->user_upload);
                    if (!Core::is_readable($root)) {
                        debug_event(self::class, 'Target user directory `' . $root . "` doesn't exist. Creating it...", 5);
                        mkdir($root, 0775);
                    }
                }

                // sort_find_home will replace the % with the correct values.
                $directory = $this->sort_find_home($song, (string) $this->sort_pattern, $root);
                $filename  = $this->sort_find_home($song, (string) $this->rename_pattern);
                if ($directory === null || $filename === null) {
                    $fullpath = (string)$song->file;
                } else {
                    $fullpath = rtrim($directory, "\/") . '/' . ltrim($filename, "\/") . "." . (pathinfo((string)$song->file, PATHINFO_EXTENSION));
                }

                // don't move over existing files
                if (!in_array($song->file, [null, '', '0'], true) && !is_file($fullpath) && $song->file != $fullpath && strlen($fullpath)) {
                    debug_event(self::class, 'Destin: ' . $fullpath, 5);
                    $info      = pathinfo($fullpath);
                    $directory = $info['dirname'];
                    $file      = $info['basename'];

                    if (!Core::is_readable($directory)) {
                        debug_event(self::class, 'mkdir: ' . $directory, 5);
                        if (!mkdir($directory, 0775, true)) {
                            debug_event('local.catalog', T_('Error') . ': ' . sprintf(T_('Create directory "%s"'), $directory), 2);

                            return null;
                        }
                    }

                    // Now that we've got the correct directory structure let's try to copy it
                    copy($song->file, $fullpath);

                    // Check the filesize
                    $new_sum = Core::get_filesize($fullpath);
                    $old_sum = Core::get_filesize($song->file);

                    if ($new_sum !== $old_sum || $new_sum === 0) {
                        unlink($fullpath); // delete the copied file on failure
                    } else {
                        debug_event(self::class, 'song path updated: ' . $fullpath, 5);
                        unlink($song->file); // delete the original on success
                        // Update the catalog
                        $sql = "UPDATE `song` SET `file` = ? WHERE `id` = ?;";
                        Dba::write($sql, [$fullpath, $song->id]);
                    }
                }
            }

            // If song rating tag exists and is well formed (array user=>rating), add it
            if (array_key_exists('rating', $results) && is_array($results['rating'])) {
                // For each user's ratings, call the function
                foreach ($results['rating'] as $user => $rating) {
                    debug_event('local.catalog', sprintf('Setting rating for Song %s to %s for user %s', $song_id, $rating, $user), 5);
                    $o_rating = new Rating($song_id, 'song');
                    $o_rating->set_rating((int)$rating, $user, false);
                }
            }

            // Extended metadata loading is not deferred, retrieve it now
            if (!AmpConfig::get('deferred_ext_metadata')) {
                $song = new Song($song_id);
                if ($song->artist) {
                    Recommendation::get_artist_info($song->artist);
                }
            }

            if ($this->getMetadataManager()->isCustomMetadataEnabled()) {
                $song = new Song($song_id);
                $this->addMetadata($song, $results);
            }

            // disable dupes if catalog_check_duplicate is enabled
            if ($is_duplicate) {
                Song::update_enabled(false, $song_id);
            }

            $this->songs_to_gather[] = $song_id;

            $this->_filecache[strtolower($file)] = $song_id;
        }

        return $song_id;
    }

    /**
     * insert_local_video
     * This inserts a video file into the video file table the tag
     * information we can get is super sketchy so it's kind of a crap shoot
     * here
     * @param string $file
     * @param array<string, mixed> $options
     * @throws Exception
     * @throws Exception
     */
    private function _insert_local_video($file, array $options = []): int
    {
        /* Create the vainfo object and get info */
        $gtypes = $this->get_gather_types('video');

        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $file,
            $gtypes,
            '',
            '',
            (string) $this->sort_pattern,
            (string) $this->rename_pattern
        );
        $vainfo->gather_tags();

        $tag_name           = VaInfo::get_tag_type($vainfo->tags, 'metadata_order_video');
        $results            = VaInfo::clean_tag_info($vainfo->tags, $tag_name, $file);
        $results['catalog'] = $this->catalog_id;

        $video_id = Video::insert($results, $options);
        if ($results['art']) {
            $art = new Art($video_id, 'video');
            $art->insert_url($results['art']);

            if (AmpConfig::get('generate_video_preview')) {
                Video::generate_preview($video_id);
            }
        } else {
            $this->videos_to_gather[] = $video_id;
        }

        $this->_filecache[strtolower($file)] = 'v_' . $video_id;

        return $video_id;
    }

    /**
     * get_rel_path
     */
    public function get_rel_path(string $file_path): string
    {
        $catalog_path = rtrim($this->path, "/");

        return (str_replace($catalog_path . "/", "", $file_path));
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->path;
    }

    /**
     * @return array{
     *     file_path: string,
     *     file_name: string,
     *     file_size: int,
     *     file_type: string
     * }
     */
    public function prepare_media(Podcast_Episode|Video|Song $media): array
    {
        return [
            'file_path' => (string) $media->file,
            'file_name' => $media->getFileName(),
            'file_size' => $media->size,
            'file_type' => $media->type,
        ];
    }

    /**
     * check_path
     * Checks the path to see if it's there or conflicting with an existing catalog
     */
    public static function check_path(string $path): bool
    {
        if ((string) $path === '') {
            debug_event('local.catalog', 'Path was not specified', 1);
            AmpError::add('general', T_('Path was not specified'));

            return false;
        }

        // Make sure that there isn't a catalog with a directory above this one
        if (is_int(self::get_from_path($path))) {
            debug_event('local.catalog', 'Specified path is inside an existing catalog', 1);
            AmpError::add('general', T_('Specified path is inside an existing catalog'));

            return false;
        }

        // Make sure the path is readable/exists
        if (!Core::is_readable($path)) {
            debug_event('local.catalog', "The folder couldn't be read. Does it exist? " . $path, 1);
            /* HINT: directory (file path) */
            AmpError::add('general', sprintf(T_("The folder couldn't be read. Does it exist? %s"), scrub_out($path)));

            return false;
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function check_catalog_proc(?Interactor $interactor = null): array
    {
        if (!Core::is_readable($this->path)) {
            // First sanity check; no point in proceeding with an unreadable catalog root.
            if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                AmpError::add('general', T_('Catalog root unreadable, stopping check'));
                echo AmpError::display('general');
            }

            return [];
        }

        $missing     = [];
        $this->count = 0;

        $gather_type = $this->gather_types;
        $media_type  = 'song';
        if ($gather_type == 'podcast') {
            $media_type = 'podcast_episode';
        } elseif ($gather_type == 'video') {
            $media_type = 'video';
        }

        $total = self::count_table($media_type, $this->catalog_id);
        if ($total === 0) {
            return $missing;
        }

        $chunks = (int)ceil($total / 10000);
        foreach (range(1, $chunks) as $chunk) {
            debug_event('local.catalog', "catalog " . $this->name . " Starting check " . $media_type . sprintf(' on chunk %d/%d', $chunk, $chunks), 5);
            $missing = array_merge($missing, $this->_check_chunk($media_type, (int)$chunk, 10000, $interactor));
        }

        return $missing;
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location
     */
    public function move_catalog_proc(string $new_path): bool
    {
        if (!self::check_path($new_path)) {
            return false;
        }

        if ($this->path === $new_path) {
            debug_event('local.catalog', 'The new path equals the old path: ' . $new_path, 5);

            return false;
        }

        $sql    = "UPDATE `catalog_local` SET `path` = ? WHERE `catalog_id` = ?";
        $params = [$new_path, $this->catalog_id];
        Dba::write($sql, $params);

        $sql    = "UPDATE `song` SET `file` = REPLACE(`file`, '" . Dba::escape($this->path) . "', '" . Dba::escape($new_path) . "') WHERE `catalog` = ?";
        $params = [$this->catalog_id];
        Dba::write($sql, $params);

        return true;
    }

    public function cache_catalog_file(string $target_file, Podcast_Episode|Song|Video $media, string $cache_target): void
    {
        $transcode_settings = $media->get_transcode_settings($cache_target);
        Stream::start_transcode($media, $transcode_settings, $target_file);
        debug_event('local.catalog', 'Saved: ' . $media->getId() . ' to: {' . $target_file . '}', 5);
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        $m4a  = AmpConfig::get('cache_m4a');
        $flac = AmpConfig::get('cache_flac');
        $mpc  = AmpConfig::get('cache_mpc');
        $ogg  = AmpConfig::get('cache_ogg');
        $oga  = AmpConfig::get('cache_oga');
        $opus = AmpConfig::get('cache_opus');
        $wav  = AmpConfig::get('cache_wav');
        $wma  = AmpConfig::get('cache_wma');
        $aif  = AmpConfig::get('cache_aif');
        $aiff = AmpConfig::get('cache_aiff');
        $ape  = AmpConfig::get('cache_ape');
        $shn  = AmpConfig::get('cache_shn');
        $mp3  = AmpConfig::get('cache_mp3');

        $cache_path   = (string)AmpConfig::get('cache_path', '');
        $cache_target = (string)AmpConfig::get('cache_target', '');
        // need a destination and target filetype
        if (!is_dir($cache_path) || ($cache_target === '' || $cache_target === '0')) {
            debug_event('local.catalog', 'Check your cache_path and cache_target settings', 5);

            return false;
        }

        // need at least one type to transcode
        if (
            !$m4a &&
            !$flac &&
            !$mpc &&
            !$ogg &&
            !$oga &&
            !$opus &&
            !$wav &&
            !$wma &&
            !$aif &&
            !$aiff &&
            !$ape &&
            !$shn &&
            !$mp3
        ) {
            debug_event('local.catalog', 'You need to pick at least 1 file format to cache', 5);

            return false;
        }

        $sql    = "SELECT `id` FROM `song` WHERE `catalog` = ? ";
        $params = [$this->catalog_id];
        $join   = 'AND (';
        if ($m4a) {
            $sql .= $join . " `file` LIKE '%.m4a' ";
            $join = 'OR';
        }

        if ($flac) {
            $sql .= $join . " `file` LIKE '%.flac' ";
            $join = 'OR';
        }

        if ($mpc) {
            $sql .= $join . " `file` LIKE '%.mpc' ";
            $join = 'OR';
        }

        if ($ogg) {
            $sql .= $join . " `file` LIKE '%.ogg' ";
            $join = 'OR';
        }

        if ($oga) {
            $sql .= $join . " `file` LIKE '%.oga' ";
            $join = 'OR';
        }

        if ($opus) {
            $sql .= $join . " `file` LIKE '%.opus' ";
            $join = 'OR';
        }

        if ($wav) {
            $sql .= $join . " `file` LIKE '%.wav' ";
            $join = 'OR';
        }

        if ($wma) {
            $sql .= $join . " `file` LIKE '%.wma' ";
            $join = 'OR';
        }

        if ($aif) {
            $sql .= $join . " `file` LIKE '%.aif' ";
            $join = 'OR';
        }

        if ($aiff) {
            $sql .= $join . " `file` LIKE '%.aiff' ";
            $join = 'OR';
        }

        if ($ape) {
            $sql .= $join . " `file` LIKE '%.ape' ";
            $join = 'OR';
        }

        if ($shn) {
            $sql .= $join . " `file` LIKE '%.shn' ";
        }

        if ($mp3) {
            $sql .= $join . " `file` LIKE '%.mp3' ";
        }

        if ($sql === "SELECT `id` FROM `song` WHERE `catalog` = ? ") {
            return false;
        }

        $sql .= ');';
        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        foreach ($results as $song_id) {
            $target_file     = Catalog::get_cache_path($song_id, $this->catalog_id, $cache_path, $cache_target);
            $old_target_file = rtrim(trim($cache_path), '/') . '/' . $this->catalog_id . '/' . $song_id . '.' . $cache_target;
            if ($target_file !== null && is_file($old_target_file)) {
                // check for the old path first
                rename($old_target_file, $target_file);
                debug_event('local.catalog', 'Moved: ' . $song_id . ' from: {' . $old_target_file . '}' . ' to: {' . $target_file . '}', 5);
            }

            $file_exists = ($target_file !== null && is_file($target_file));
            $media       = new Song($song_id);

            if (
                $media->isNew() ||
                !$media->file ||
                !is_file($media->file)
            ) {
                debug_event('local.catalog', sprintf('Not Found: %s', $media->file), 3);

                return false;
            }

            // check the old path too
            if ($file_exists) {
                // get the time for the cached file and compare
                $vainfo = $this->getUtilityFactory()->createVaInfo(
                    $target_file,
                    $this->get_gather_types('music'),
                    '',
                    '',
                    (string) $this->sort_pattern,
                    (string) $this->rename_pattern
                );
                if ($media->time > 0 && !$vainfo->check_time($media->time)) {
                    debug_event('local.catalog', 'check_time FAILED for: ' . $media->id, 5);
                    unlink($target_file);
                    $file_exists = false;
                }
            }

            if (!$file_exists) {
                // transcode to the new path
                $transcode_settings = $media->get_transcode_settings($cache_target);
                Stream::start_transcode($media, $transcode_settings, (string)$target_file);
                debug_event('local.catalog', 'Saved: ' . $song_id . ' to: {' . $target_file . '}', 5);
            }
        }

        return true;
    }

    private function getUtilityFactory(): UtilityFactoryInterface
    {
        global $dic;

        return $dic->get(UtilityFactoryInterface::class);
    }

    private function getPodcastSyncer(): PodcastSyncerInterface
    {
        global $dic;

        return $dic->get(PodcastSyncerInterface::class);
    }

    private function getMetadataManager(): MetadataManagerInterface
    {
        global $dic;

        return $dic->get(MetadataManagerInterface::class);
    }

    private function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }
}
