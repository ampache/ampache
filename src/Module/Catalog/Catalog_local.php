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

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Metadata\Repository\Metadata;
use Ampache\Repository\Model\Metadata\Repository\MetadataField;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Recommendation;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\VaInfo;
use Exception;

/**
 * This class handles all actual work in regards to local catalogs.
 */
class Catalog_local extends Catalog
{
    private string $version     = '000001';
    private string $type        = 'local';
    private string $description = 'Local Catalog';

    private int $catalog_id;
    private $count;
    private $songs_to_gather;
    private $videos_to_gather;

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
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    }

    /**
     * @return array
     */
    public function catalog_fields()
    {
        $fields = array();

        $fields['path'] = array('description' => T_('Path'), 'type' => 'text');

        return $fields;
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     * @param int $catalog_id
     */
    public function __construct($catalog_id = null)
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
     * @param $path
     * @return int|bool
     */
    public static function get_from_path($path)
    {
        // First pull a list of all of the paths for the different catalogs
        $sql        = "SELECT `catalog_id`, `path` FROM `catalog_local`";
        $db_results = Dba::read($sql);

        $catalog_paths  = array();
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
            $component_path = realpath($component_path . '/../');
        } while (strcmp($component_path, $old_path) != 0);

        return false;
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param $catalog_id
     * @param array $data
     */
    public static function create_type($catalog_id, $data): bool
    {
        // Clean up the path just in case
        $path = rtrim(rtrim(trim($data['path']), '/'), '\\');

        if (!self::check_path($path)) {
            AmpError::add('general', T_('Path was not specified'));

            return false;
        }

        // Make sure this path isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_local` WHERE `path` = ?';
        $db_results = Dba::read($sql, array($path));

        if (Dba::num_rows($db_results)) {
            debug_event('local.catalog', 'Cannot add catalog with duplicate path ' . $path, 1);
            /* HINT: directory (file path) */
            AmpError::add('general', sprintf(T_('This path belongs to an existing local Catalog: %s'), $path));

            return false;
        }

        $sql = 'INSERT INTO `catalog_local` (`path`, `catalog_id`) VALUES (?, ?)';
        Dba::write($sql, array($path, $catalog_id));

        return true;
    }

    /**
     * add_files
     *
     * Recurses through $this->path and pulls out all mp3s and returns the
     * full path in an array. Passes gather_type to determine if we need to
     * check id3 information against the db.
     * @param string $path
     * @param array $options
     * @param int $counter
     */
    public function add_files($path, $options, $counter = 0): int
    {
        // See if we want a non-root path for the add
        if (isset($options['subdirectory'])) {
            $path = $options['subdirectory'];
            unset($options['subdirectory']);
        }
        // Make sure the path doesn't end in a / or \
        $path = rtrim($path, '/');
        $path = rtrim($path, '\\');

        // Correctly detect the slash we need to use here
        if (strpos($path, '/') !== false) {
            $slash_type = '/';
        } else {
            $slash_type = '\\';
        }

        /* Open up the directory */
        $handle = opendir($path);

        if (!is_resource($handle)) {
            debug_event('local.catalog', "Unable to open $path", 3);
            /* HINT: directory (file path) */
            AmpError::add('catalog_add', sprintf(T_('Unable to open: %s'), $path));

            return 0;
        }

        /* Change the dir so is_dir works correctly */
        if (!chdir($path)) {
            debug_event('local.catalog', "Unable to chdir to $path", 2);
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
            if ($counter % 1000 == 0) {
                debug_event('local.catalog', "Reading $file inside $path", 5);
                debug_event('local.catalog', "Memory usage: " . (string) UI::format_bytes(memory_get_usage(true)), 5);
            }
            $counter++;

            /* Create the new path */
            $full_file = $path . $slash_type . $file;
            if ($this->add_file($full_file, $options, $counter)) {
                $songsadded++;
            }
        } // end while reading directory

        if ($counter % 1000 == 0) {
            debug_event('local.catalog', "Finished reading $path, closing handle", 5);
        }

        // This should only happen on the last run
        if ($path == $this->path) {
            Ui::update_text('add_count_' . $this->catalog_id, $this->count);
        }

        /* Close the dir handle */
        closedir($handle);

        return $songsadded;
    }

    /**
     * add_file
     *
     * @param $full_file
     * @param array $options
     * @param int $counter
     * @return bool
     * @throws Exception
     */
    public function add_file($full_file, $options, $counter = 0): bool
    {
        // Ensure that we've got our cache
        $this->_create_filecache();

        /* First thing first, check if file is already in catalog.
         * This check is very quick, so it should be performed before any other checks to save time
         */
        if (isset($this->_filecache[strtolower($full_file)])) {
            return false;
        }

        if (AmpConfig::get('no_symlinks')) {
            if (is_link($full_file)) {
                debug_event('local.catalog', "Skipping symbolic link $full_file", 5);

                return false;
            }
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
                debug_event('local.catalog', "Unable to chdir to $full_file", 2);
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

            if (!$file_size) {
                debug_event('local.catalog', "Unable to get filesize for $full_file", 2);
                /* HINT: FullFile */
                AmpError::add('catalog_add', sprintf(T_('Unable to get the filesize for "%s"'), $full_file));
            } // file_size check

            if (!Core::is_readable($full_file)) {
                // not readable, warn user
                debug_event('local.catalog', "$full_file is not readable by Ampache", 2);
                /* HINT: filename (file path) */
                AmpError::add('catalog_add', sprintf(T_("The file couldn't be read. Does it exist? %s"), $full_file));

                return false;
            }

            // Check to make sure the filename is of the expected charset
            if (function_exists('iconv')) {
                $convok       = false;
                $site_charset = AmpConfig::get('site_charset');
                $lc_charset   = $site_charset;
                if (AmpConfig::get('lc_charset')) {
                    $lc_charset = AmpConfig::get('lc_charset');
                }

                $enc_full_file = iconv($lc_charset, $site_charset, $full_file);
                if ($lc_charset != $site_charset) {
                    $convok = (strcmp($full_file, iconv($site_charset, $lc_charset, $enc_full_file)) == 0);
                } else {
                    $convok = (strcmp($enc_full_file, $full_file) == 0);
                }
                if (!$convok) {
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
            } // end if iconv

            if ($is_playlist) {
                // if it's a playlist
                debug_event('local.catalog', 'Found playlist file to import: ' . $full_file, 5);
                $this->_playlists[] = $full_file;
            } else {
                if (count($this->get_gather_types('music')) > 0) {
                    if ($is_audio_file && $this->insert_local_song($full_file, $options)) {
                        debug_event('local.catalog', 'Imported song file: ' . $full_file, 5);
                    } else {
                        debug_event('local.catalog', 'Skipped song file: ' . $full_file, 5);

                        return false;
                    }
                } else {
                    if (count($this->get_gather_types('video')) > 0) {
                        if ($is_video_file && $this->insert_local_video($full_file, $options)) {
                            debug_event('local.catalog', 'Imported video file: ' . $full_file, 5);
                        } else {
                            debug_event('local.catalog', 'Skipped video file: ' . $full_file, 5);

                            return false;
                        }
                    }
                }

                $this->count++;
                $file = str_replace(array('(', ')', '\''), '', $full_file);
                if (Ui::check_ticker()) {
                    Ui::update_text('add_count_' . $this->catalog_id, $this->count);
                    Ui::update_text('add_dir_' . $this->catalog_id, scrub_out($file));
                } // update our current state
            } // if it's not an m3u

            return true;
        } else {
            // if it matches the pattern
            if ($counter % 1000 == 0) {
                debug_event('local.catalog', "$full_file ignored, non-audio file or 0 bytes", 5);
            }

            return false;
        } // else not an audio file
    }

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     * @param array $options
     */
    public function add_to_catalog($options = null): int
    {
        if (empty($options)) {
            $options = array(
                'gather_art' => true,
                'parse_playlist' => false
            );
        }
        // make double sure that options are set
        if (!array_key_exists('gather_art', $options)) {
            $options['gather_art'] = true;
        }
        if (!array_key_exists('parse_playlist', $options)) {
            $options['parse_playlist'] = false;
        }

        $this->count            = 0;
        $this->songs_to_gather  = array();
        $this->videos_to_gather = array();

        if (!defined('SSE_OUTPUT') && !defined('API')) {
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
            $this->count += $this->add_files($this->path, $options);
            if ($options['parse_playlist'] && count($this->_playlists)) {
                // Foreach Playlists we found
                foreach ($this->_playlists as $full_file) {
                    debug_event('local.catalog', 'Processing playlist: ' . $full_file, 5);
                    $result = Catalog::import_playlist($full_file, -1, 'public');
                    if ($result['success']) {
                        $file = basename($full_file);
                        echo "\n$full_file\n";
                        if (!empty($result['results'])) {
                            foreach ($result['results'] as $file) {
                                if ($file['found']) {
                                    echo scrub_out($file['track']) . ": " . T_('Success') . ":\t" . scrub_out($file['file']) . "\n";
                                } else {
                                    echo "-: " . T_('Failure') . ":\t" . scrub_out($file['file']) . "\n";
                                }
                                flush();
                            } // foreach songs
                            echo "\n";
                        }
                    } // end if import worked
                } // end foreach playlist files
            }
            // only gather art if you've added new stuff
            if (($this->count) > 0 && $options['gather_art']) {
                debug_event(__CLASS__, 'gather_art after adding', 4);
                $catalog_id = $this->catalog_id;
                if (!defined('SSE_OUTPUT') && !defined('API')) {
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
        $rate      = number_format(($time_diff > 0)
            ? $this->count / $time_diff
            : 0, 2);
        if ($rate < 1) {
            $rate = T_('N/A');
        }

        if (!defined('SSE_OUTPUT') && !defined('API')) {
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
     * verify_catalog_proc
     */
    public function verify_catalog_proc(): int
    {
        debug_event('local.catalog', 'Verify starting on ' . $this->name, 5);
        set_time_limit(0);

        $total_updated = 0;
        $this->count   = 0;

        $catalog_media_type = $this->gather_types;
        if ($catalog_media_type == 'music') {
            $media_type  = 'album';
            $media_class = Album::class;
            $total       = self::count_table($media_type, $this->catalog_id);
        } elseif ($catalog_media_type == 'podcast') {
            $media_type  = 'podcast_episode';
            $media_class = Podcast_Episode::class;
            $total       = self::count_table($media_type, $this->catalog_id);
        } elseif (in_array($catalog_media_type, array('clip', 'tvshow', 'movie', 'personal_video'))) {
            $media_type  = 'video';
            $media_class = Video::class;
            $total       = self::count_table($media_type, $this->catalog_id);
        } else {
            return $total_updated;
        }
        $count  = 1;
        $chunks = 1;
        $chunk  = 0;
        if ($total > 1000) {
            $chunks = floor($total / 1000) + 1;
        }
        $media_class::clear_cache();
        while ($chunk < $chunks) {
            debug_event('local.catalog', "catalog " . $this->name . " starting verify " . $media_type . " on chunk $count/$chunks", 5);
            $total_updated += $this->_verify_chunk($media_type, $chunk, 1000);
            $chunk++;
            $count++;
        }
        debug_event('local.catalog', "Verify finished, $total_updated updated in " . $this->name, 5);
        $this->update_last_update();

        return $total_updated;
    }

    /**
     * _verify_chunk
     * This verifies a chunk of the catalog, done to save
     * memory
     * @param string $tableName
     * @param int $chunk
     * @param int $chunk_size
     */
    private function _verify_chunk($tableName, $chunk, $chunk_size): int
    {
        $count   = $chunk * $chunk_size;
        $changed = 0;

        switch ($tableName) {
            case 'album':
                $sql = "SELECT `song`.`album` AS `id`, MIN(`song`.`file`) AS `file`, MIN(`song`.`update_time`) AS `min_update_time` FROM `song` WHERE `song`.`album` IN (SELECT `song`.`album` FROM `song` LEFT JOIN `catalog` ON `song`.`catalog` = `catalog`.`id` WHERE `song`.`catalog` = " . $this->catalog_id . ") GROUP BY `song`.`album` ORDER BY `min_update_time` LIMIT $count, $chunk_size";
                break;
            case 'podcast_episode':
                $sql = "SELECT `podcast_episode`.`id`, `podcast_episode`.`file` FROM `podcast_episode` LEFT JOIN `catalog` ON `podcast_episode`.`catalog` = `catalog`.`id` WHERE `podcast_episode`.`catalog` = " . $this->catalog_id . " AND `podcast_episode`.`file` IS NOT NULL ORDER BY `podcast_episode`.`pubdate` DESC, `podcast_episode`.`file` LIMIT $count, $chunk_size";
                break;
            case 'video':
            default:
                $sql = "SELECT `$tableName`.`id`, `$tableName`.`file`, `$tableName`.`update_time` AS `min_update_time` FROM `$tableName` LEFT JOIN `catalog` ON `$tableName`.`catalog` = `catalog`.`id` WHERE `$tableName`.`catalog` = " . $this->catalog_id . " AND `$tableName`.`update_time` < `catalog`.`last_update` ORDER BY `min_update_time` DESC, `$tableName`.`file` LIMIT $count, $chunk_size";
                break;
        }
        $db_results = Dba::read($sql);
        $className  = ObjectTypeToClassNameMapper::map($tableName);

        if (AmpConfig::get('memory_cache') && $tableName !== 'podcast_episode') {
            $media_ids = array();
            while ($row = Dba::fetch_assoc($db_results, false)) {
                $media_ids[] = $row['id'];
            }
            /** @var Song|Album|Video $className */
            $className::build_cache($media_ids);
            $db_results = Dba::read($sql);
        }
        $verify_by_time = AmpConfig::get('catalog_verify_by_time', false) && $tableName !== 'podcast_episode';
        while ($row = Dba::fetch_assoc($db_results)) {
            $count++;
            if (Ui::check_ticker()) {
                $file = str_replace(array('(', ')', '\''), '', $row['file']);
                Ui::update_text('verify_count_' . $this->catalog_id, $count);
                Ui::update_text('verify_dir_' . $this->catalog_id, scrub_out($file));
            }

            if (!Core::is_readable(Core::conv_lc_file((string)$row['file']))) {
                /* HINT: filename (file path) */
                AmpError::add('general', sprintf(T_("The file couldn't be read. Does it exist? %s"), $row['file']));
                debug_event('local.catalog', $row['file'] . ' does not exist or is not readable', 5);
                continue;
            }
            $file_time = filemtime($row['file']);
            // check the modification time on the file to see if it's worth checking the tags.
            if ($verify_by_time && ($this->last_update > $file_time || (array_key_exists('min_update_time', $row) && (int)$row['min_update_time'] > $file_time))) {
                continue;
            }

            if (self::update_single_item($tableName, $row['id'], true)['change']) {
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
    public function clean_catalog_proc(): int
    {
        if (!Core::is_readable($this->path)) {
            // First sanity check; no point in proceeding with an unreadable catalog root.
            debug_event('local.catalog', 'Catalog path:' . $this->path . ' unreadable, clean failed', 1);
            AmpError::add('general', T_('Catalog root unreadable, stopping clean'));
            echo AmpError::display('general');

            return 0;
        }
        $dead_total  = 0;
        $this->count = 0;

        $catalog_media_type = $this->gather_types;
        $media_type         = 'song';
        if ($catalog_media_type == 'podcast') {
            $media_type = 'podcast_episode';
        } elseif (in_array($catalog_media_type, array('clip', 'tvshow', 'movie', 'personal_video'))) {
            $media_type = 'video';
        }
        $total = self::count_table($media_type, $this->catalog_id);
        if ($total == 0) {
            return $dead_total;
        }
        $dead   = array();
        $count  = 1;
        $chunks = 1;
        $chunk  = 0;
        if ($total > 10000) {
            $chunks = floor($total / 10000) + 1;
        }
        while ($chunk < $chunks) {
            debug_event('local.catalog', "catalog " . $this->name . " Starting clean " . $media_type . " on chunk $count/$chunks", 5);
            $dead = array_merge($dead, $this->_clean_chunk($media_type, $chunk, 10000));
            $chunk++;
            $count++;
        }
        debug_event('local.catalog', "Clean finished, $total files checked in " . $this->name, 5);

        $dead_count = count($dead);
        // Check for unmounted path
        if (!file_exists($this->path)) {
            if ($dead_count >= $total) {
                debug_event('local.catalog', 'All files would be removed. Doing nothing.', 1);
                AmpError::add('general', T_('All files would be removed. Doing nothing'));

                return $dead_total;
            }
        }
        if ($dead_count) {
            $dead_total += $dead_count;
            $sql = "DELETE FROM `$media_type` WHERE `id` IN (" . implode(',', $dead) . ")";
            Dba::write($sql);
        }

        Metadata::garbage_collection();
        MetadataField::garbage_collection();

        return (int)$dead_total;
    }

    /**
     * _clean_chunk
     * This is the clean function and is broken into chunks to try to save a little memory
     * @param $media_type
     * @param $chunk
     * @param $chunk_size
     * @return array
     */
    private function _clean_chunk($media_type, $chunk, $chunk_size)
    {
        $dead  = array();
        $count = $chunk * $chunk_size;

        $tableName = ObjectTypeToClassNameMapper::reverseMap($media_type);

        $sql        = "SELECT `id`, `file` FROM `$tableName` WHERE `catalog` = ? AND `file` IS NOT NULL LIMIT $count, $chunk_size;";
        $db_results = Dba::read($sql, array($this->catalog_id));
        while ($results = Dba::fetch_assoc($db_results)) {
            //debug_event('local.catalog', 'Cleaning check on ' . $results['file'] . ' (' . $results['id'] . ')', 5);
            $count++;
            if (Ui::check_ticker()) {
                $file = str_replace(array('(', ')', '\''), '', $results['file']);
                Ui::update_text('clean_count_' . $this->catalog_id, $count);
                Ui::update_text('clean_dir_' . $this->catalog_id, scrub_out($file));
            }
            if ($this->clean_file($results['file'], $media_type)) {
                $dead[] = $results['id'];
            }
        }

        return $dead;
    }

    /**
     * _check_chunk
     * This is the check function and is broken into chunks to try to save a little memory
     * @param $media_type
     * @param $chunk
     * @param $chunk_size
     * @return array
     */
    private function _check_chunk($media_type, $chunk, $chunk_size)
    {
        $missing = array();
        $count   = $chunk * $chunk_size;

        $tableName = ObjectTypeToClassNameMapper::reverseMap($media_type);

        $sql        = "SELECT `id`, `file` FROM `$tableName` WHERE `catalog` = ? LIMIT $count, $chunk_size;";
        $db_results = Dba::read($sql, array($this->catalog_id));

        while ($results = Dba::fetch_assoc($db_results)) {
            $file_info = Core::get_filesize(Core::conv_lc_file($results['file']));
            if ($file_info < 1) {
                debug_event('local.catalog', '_clean_chunk: {' . $results['id'] . '} File not found or empty ' . $results['file'], 5);
                $missing[] = $results['file'];
            } elseif (!Core::is_readable(Core::conv_lc_file((string)$results['file']))) {
                debug_event('local.catalog', "_clean_chunk: " . $results['file'] . ' is not readable, but does exist', 1);
            }
        }

        return $missing;
    }

    /**
     * clean_file
     *
     * Clean up a single file checking that it's missing or just unreadable.
     * Return true on delete. false on failures
     *
     * @param string $file
     * @param string $media_type
     */
    public function clean_file($file, $media_type = 'song'): bool
    {
        $file_info = Core::get_filesize(Core::conv_lc_file($file));
        if ($file_info < 1) {
            $object_id = Catalog::get_id_from_file($file, $media_type);
            debug_event('local.catalog', 'clean_file: {' . $object_id . '} File not found or empty ' . $file, 5);
            /* HINT: filename (file path) */
            AmpError::add('general', sprintf(T_('File was not found or is 0 Bytes: %s'), $file));
            $params = array($object_id);
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
            $sql = "DELETE FROM `$media_type` WHERE `id` = ?";
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
     * @param $file
     * @param array $options
     * @return bool|int
     * @throws Exception
     * @throws Exception
     */
    private function insert_local_song($file, $options = array())
    {
        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $file,
            $this->get_gather_types('music'),
            '',
            '',
            $this->sort_pattern,
            $this->rename_pattern
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
            if (isset($album->id)) {
                $results['album'] = $album->name;
            }
        }

        $song_id = Song::insert($results);
        if ($song_id) {
            $is_duplicate = false;
            if (count($this->get_gather_types('music')) > 0) {
                if (AmpConfig::get('catalog_check_duplicate')) {
                    if (Song::find($results)) {
                        debug_event('local.catalog', 'disable_duplicate ' . $file, 5);
                        $is_duplicate = true;
                    }
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
                            mkdir($root);
                        }
                    }
                    // sort_find_home will replace the % with the correct values.
                    $directory = $this->sort_find_home($song, $this->sort_pattern, $root);
                    $filename  = $this->sort_find_home($song, $this->rename_pattern);
                    if ($directory === null || $filename === null) {
                        $fullpath = (string)$song->file;
                    } else {
                        $fullpath = rtrim($directory, "\/") . '/' . ltrim($filename, "\/") . "." . (pathinfo((string)$song->file, PATHINFO_EXTENSION));
                    }

                    // don't move over existing files
                    if (!empty($song->file) && !is_file($fullpath) && $song->file != $fullpath && strlen($fullpath)) {
                        debug_event(self::class, 'Destin: ' . $fullpath, 5);
                        $info      = pathinfo($fullpath);
                        $directory = $info['dirname'] ?? '';
                        $file      = $info['basename'];

                        if (!Core::is_readable($directory)) {
                            debug_event(self::class, 'mkdir: ' . $directory, 5);
                            mkdir($directory, 0755, true);
                        }

                        // Now that we've got the correct directory structure let's try to copy it
                        copy($song->file, $fullpath);

                        // Check the filesize
                        $new_sum = Core::get_filesize($fullpath);
                        $old_sum = Core::get_filesize($song->file);

                        if ($new_sum != $old_sum || $new_sum == 0) {
                            unlink($fullpath); // delete the copied file on failure
                        } else {
                            debug_event(self::class, 'song path updated: ' . $fullpath, 5);
                            unlink($song->file); // delete the original on success
                            // Update the catalog
                            $sql = "UPDATE `song` SET `file` = ? WHERE `id` = ?;";
                            Dba::write($sql, array($fullpath, $song->id));
                        }
                    }
                }
            }
            // If song rating tag exists and is well formed (array user=>rating), add it
            if (array_key_exists('rating', $results) && is_array($results['rating'])) {
                // For each user's ratings, call the function
                foreach ($results['rating'] as $user => $rating) {
                    debug_event('local.catalog', "Setting rating for Song $song_id to $rating for user $user", 5);
                    $o_rating = new Rating($song_id, 'song');
                    $o_rating->set_rating((int)$rating, $user);
                }
            }
            // Extended metadata loading is not deferred, retrieve it now
            if (!AmpConfig::get('deferred_ext_metadata')) {
                $song = new Song($song_id);
                Recommendation::get_artist_info($song->artist);
            }
            if (Song::isCustomMetadataEnabled()) {
                $song    = new Song($song_id);
                $results = array_diff_key($results, array_flip($song->getDisabledMetadataFields()));
                self::add_metadata($song, $results);
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
     * @param $file
     * @param array $options
     * @return int
     * @throws Exception
     * @throws Exception
     */
    public function insert_local_video($file, $options = array()): int
    {
        /* Create the vainfo object and get info */
        $gtypes = $this->get_gather_types('video');

        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $file,
            $gtypes,
            '',
            '',
            $this->sort_pattern,
            $this->rename_pattern
        );
        $vainfo->gather_tags();

        $tag_name           = VaInfo::get_tag_type($vainfo->tags, 'metadata_order_video');
        $results            = VaInfo::clean_tag_info($vainfo->tags, $tag_name, $file);
        $results['catalog'] = $this->catalog_id;

        $video_id = Video::insert($results, $gtypes, $options);
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
     * @param string $file_path
     */
    public function get_rel_path($file_path): string
    {
        $catalog_path = rtrim($this->path, "/");

        return (str_replace($catalog_path . "/", "", $file_path));
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format(): void
    {
        parent::format();
        $this->f_info      = $this->path;
        $this->f_full_info = $this->path;
    }

    /**
     * @param Song|Podcast_Episode|Video $media
     * @return array{
     *  file_path: string,
     *  file_name: string,
     *  file_size: int,
     *  file_type: string
     * }
     */
    public function prepare_media($media): array
    {
        return [
            'file_path' => (string) $media->file,
            'file_name' => $media->f_file,
            'file_size' => $media->size,
            'file_type' => $media->type
        ];
    }

    /**
     * check_path
     * Checks the path to see if it's there or conflicting with an existing catalog
     * @param string $path
     */
    public static function check_path($path): bool
    {
        if (!strlen($path)) {
            AmpError::add('general', T_('Path was not specified'));

            return false;
        }

        // Make sure that there isn't a catalog with a directory above this one
        if (self::get_from_path($path)) {
            AmpError::add('general', T_('Specified path is inside an existing catalog'));

            return false;
        }

        // Make sure the path is readable/exists
        if (!Core::is_readable($path)) {
            debug_event('local.catalog', 'Cannot add catalog at unopenable path ' . $path, 1);
            /* HINT: directory (file path) */
            AmpError::add('general', sprintf(T_("The folder couldn't be read. Does it exist? %s"), scrub_out($path)));

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function check_catalog_proc()
    {
        if (!Core::is_readable($this->path)) {
            // First sanity check; no point in proceeding with an unreadable catalog root.
            AmpError::add('general', T_('Catalog root unreadable, stopping check'));
            echo AmpError::display('general');

            return array();
        }
        $missing     = array();
        $this->count = 0;

        $catalog_media_type = $this->gather_types;
        $media_type         = 'song';
        if ($catalog_media_type == 'podcast') {
            $media_type = 'podcast_episode';
        } elseif (in_array($catalog_media_type, array('clip', 'tvshow', 'movie', 'personal_video'))) {
            $media_type = 'video';
        }
        $total = self::count_table($media_type, $this->catalog_id);
        if ($total == 0) {
            return $missing;
        }
        $chunks = (int)floor($total / 10000) + 1;
        foreach (range(1, $chunks) as $chunk) {
            debug_event('local.catalog', "catalog " . $this->name . " Starting check " . $media_type . " on chunk $chunk/$chunks", 5);
            $missing = array_merge($missing, $this->_check_chunk($media_type, $chunk, 10000));
        }

        return $missing;
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location
     * @param string $new_path
     */
    public function move_catalog_proc($new_path): bool
    {
        if (!self::check_path($new_path)) {
            return false;
        }
        if ($this->path == $new_path) {
            debug_event('local.catalog', 'The new path equals the old path: ' . $new_path, 5);

            return false;
        }
        $sql    = "UPDATE `catalog_local` SET `path` = ? WHERE `catalog_id` = ?";
        $params = array($new_path, $this->catalog_id);
        Dba::write($sql, $params);

        $sql    = "UPDATE `song` SET `file` = REPLACE(`file`, '" . Dba::escape($this->path) . "', '" . Dba::escape($new_path) . "') WHERE `catalog` = ?";
        $params = array($this->catalog_id);
        Dba::write($sql, $params);

        return true;
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        $m4a    = AmpConfig::get('cache_m4a');
        $flac   = AmpConfig::get('cache_flac');
        $mpc    = AmpConfig::get('cache_mpc');
        $ogg    = AmpConfig::get('cache_ogg');
        $oga    = AmpConfig::get('cache_oga');
        $opus   = AmpConfig::get('cache_opus');
        $wav    = AmpConfig::get('cache_wav');
        $wma    = AmpConfig::get('cache_wma');
        $aif    = AmpConfig::get('cache_aif');
        $aiff   = AmpConfig::get('cache_aiff');
        $ape    = AmpConfig::get('cache_ape');
        $shn    = AmpConfig::get('cache_shn');
        $mp3    = AmpConfig::get('cache_mp3');
        $path   = (string)AmpConfig::get('cache_path', '');
        $target = (string)AmpConfig::get('cache_target', '');
        // need a destination and target filetype
        if (!is_dir($path) || empty($target)) {
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
        $params = array($this->catalog_id);
        $join   = 'AND (';
        if ($m4a) {
            $sql .= "$join `file` LIKE '%.m4a' ";
            $join = 'OR';
        }
        if ($flac) {
            $sql .= "$join `file` LIKE '%.flac' ";
            $join = 'OR';
        }
        if ($mpc) {
            $sql .= "$join `file` LIKE '%.mpc' ";
            $join = 'OR';
        }
        if ($ogg) {
            $sql .= "$join `file` LIKE '%.ogg' ";
            $join = 'OR';
        }
        if ($oga) {
            $sql .= "$join `file` LIKE '%.oga' ";
            $join = 'OR';
        }
        if ($opus) {
            $sql .= "$join `file` LIKE '%.opus' ";
            $join = 'OR';
        }
        if ($wav) {
            $sql .= "$join `file` LIKE '%.wav' ";
            $join = 'OR';
        }
        if ($wma) {
            $sql .= "$join `file` LIKE '%.wma' ";
            $join = 'OR';
        }
        if ($aif) {
            $sql .= "$join `file` LIKE '%.aif' ";
            $join = 'OR';
        }
        if ($aiff) {
            $sql .= "$join `file` LIKE '%.aiff' ";
            $join = 'OR';
        }
        if ($ape) {
            $sql .= "$join `file` LIKE '%.ape' ";
            $join = 'OR';
        }
        if ($shn) {
            $sql .= "$join `file` LIKE '%.shn' ";
        }
        if ($mp3) {
            $sql .= "$join `file` LIKE '%.mp3' ";
        }
        if ($sql == "SELECT `id` FROM `song` WHERE `catalog` = ? ") {
            return false;
        }
        $sql .= ');';
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }
        foreach ($results as $song_id) {
            $target_file     = Catalog::get_cache_path($song_id, $this->catalog_id, $path, $target);
            $old_target_file = rtrim(trim($path), '/') . '/' . $this->catalog_id . '/' . $song_id . '.' . $target;
            if (is_file($old_target_file)) {
                // check for the old path first
                rename($old_target_file, $target_file);
                debug_event('local.catalog', 'Moved: ' . $song_id . ' from: {' . $old_target_file . '}' . ' to: {' . $target_file . '}', 5);
            }
            $file_exists = ($target_file !== false && is_file($target_file));
            $media       = new Song($song_id);
            // check the old path too
            if ($file_exists) {
                // get the time for the cached file and compare
                $vainfo = $this->getUtilityFactory()->createVaInfo(
                    $target_file,
                    $this->get_gather_types('music'),
                    '',
                    '',
                    $this->sort_pattern,
                    $this->rename_pattern
                );
                if ($media->time > 0 && !$vainfo->check_time($media->time)) {
                    debug_event('local.catalog', 'check_time FAILED for: ' . $media->id, 5);
                    unlink($target_file);
                    $file_exists = false;
                }
            }
            if (!$file_exists) {
                // transcode to the new path
                $transcode_settings = $media->get_transcode_settings($target);
                Stream::start_transcode($media, $transcode_settings, (string)$target_file);
                debug_event('local.catalog', 'Saved: ' . $song_id . ' to: {' . $target_file . '}', 5);
            }
        }

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getUtilityFactory(): UtilityFactoryInterface
    {
        global $dic;

        return $dic->get(UtilityFactoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastSyncer(): PodcastSyncerInterface
    {
        global $dic;

        return $dic->get(PodcastSyncerInterface::class);
    }
}
