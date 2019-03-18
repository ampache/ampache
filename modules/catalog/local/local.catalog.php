<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Local Catalog Class
 *
 * This class handles all actual work in regards to local catalogs.
 *
 */
class Catalog_local extends Catalog
{
    private $version        = '000001';
    private $type           = 'local';
    private $description    = 'Local Catalog';

    private $count;
    private $added_songs_to_gather;
    private $added_videos_to_gather;

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description()
    {
        return $this->description;
    } // get_description

    /**
     * get_version
     * This returns the current version
     */
    public function get_version()
    {
        return $this->version;
    } // get_version

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type()
    {
        return $this->type;
    } // get_type

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help()
    {
        return "";
    } // get_create_help

    /**
     * is_installed
     * This returns true or false if local catalog is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'catalog_local'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the local catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_local` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`path` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db_results = Dba::query($sql);

        return true;
    } // install

    public function catalog_fields()
    {
        $fields['path']      = array('description' => T_('Path'),'type' => 'text');

        return $fields;
    }

    public $path;

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct($catalog_id = null)
    {
        if ($catalog_id) {
            $this->id = intval($catalog_id);
            $info     = $this->get_info($catalog_id);

            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * get_from_path
     *
     * Try to figure out which catalog path most closely resembles this one.
     * This is useful when creating a new catalog to make sure we're not
     * doubling up here.
     */
    public static function get_from_path($path)
    {
        // First pull a list of all of the paths for the different catalogs
        $sql        = "SELECT `catalog_id`,`path` FROM `catalog_local`";
        $db_results = Dba::read($sql);

        $catalog_paths  = array();
        $component_path = $path;

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog_paths[$row['path']] = $row['catalog_id'];
        }

        // Break it down into its component parts and start looking for a catalog
        do {
            if ($catalog_paths[$component_path]) {
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
     */
    public static function create_type($catalog_id, $data)
    {
        // Clean up the path just in case
        $path = rtrim(rtrim(trim($data['path']), '/'), '\\');

        if (!strlen($path)) {
            AmpError::add('general', T_('Error: Path not specified'));

            return false;
        }

        // Make sure that there isn't a catalog with a directory above this one
        if (self::get_from_path($path)) {
            AmpError::add('general', T_('Error: Defined Path is inside an existing catalog'));

            return false;
        }

        // Make sure the path is readable/exists
        if (!Core::is_readable($path)) {
            debug_event('catalog', 'Cannot add catalog at unopenable path ' . $path, 1);
            AmpError::add('general', sprintf(T_('Error: %s is not readable or does not exist'), scrub_out($data['path'])));

            return false;
        }

        // Make sure this path isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_local` WHERE `path` = ?';
        $db_results = Dba::read($sql, array($path));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate path ' . $path, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $path));

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
     */
    public function add_files($path, $options)
    {
        // Profile the memory a bit
        debug_event('Memory', UI::format_bytes(memory_get_usage(true)), 5);

        // See if we want a non-root path for the add
        if (isset($options['subdirectory'])) {
            $path = $options['subdirectory'];
            unset($options['subdirectory']);
        }

        // Correctly detect the slash we need to use here
        if (strpos($path, '/') !== false) {
            $slash_type = '/';
        } else {
            $slash_type = '\\';
        }

        /* Open up the directory */
        $handle = opendir($path);

        if (!is_resource($handle)) {
            debug_event('read', "Unable to open $path", 5);
            AmpError::add('catalog_add', sprintf(T_('Error: Unable to open %s'), $path));

            return false;
        }

        /* Change the dir so is_dir works correctly */
        if (!chdir($path)) {
            debug_event('read', "Unable to chdir to $path", 2);
            AmpError::add('catalog_add', sprintf(T_('Error: Unable to change to directory %s'), $path));

            return false;
        }

        debug_event('Memory', UI::format_bytes(memory_get_usage(true)), 5);

        /* Recurse through this dir and create the files array */
        while (false !== ($file = readdir($handle))) {
            /* Skip to next if we've got . or .. */
            if (substr($file, 0, 1) == '.') {
                continue;
            }

            debug_event('read', "Starting work on $file inside $path", 5);
            debug_event('Memory', UI::format_bytes(memory_get_usage(true)), 5);

            /* Create the new path */
            $full_file = $path . $slash_type . $file;
            $this->add_file($full_file, $options);
        } // end while reading directory

        debug_event('closedir', "Finished reading $path , closing handle", 5);

        // This should only happen on the last run
        if ($path == $this->path) {
            UI::update_text('add_count_' . $this->id, $this->count);
        }

        /* Close the dir handle */
        @closedir($handle);
    } // add_files

    public function add_file($full_file, $options)
    {
        // Ensure that we've got our cache
        $this->_create_filecache();

        /* First thing first, check if file is already in catalog.
         * This check is very quick, so it should be performed before any other checks to save time
         */
        if (isset($this->_filecache[strtolower($full_file)])) {
            return false;
        }

        // Incase this is the second time through clear this variable
        // if it was set the day before
        unset($failed_check);

        if (AmpConfig::get('no_symlinks')) {
            if (is_link($full_file)) {
                debug_event('read', "Skipping symbolic link $full_file", 5);

                return false;
            }
        }

        /* If it's a dir run this function again! */
        if (is_dir($full_file)) {
            $this->add_files($full_file, $options);

            /* Change the dir so is_dir works correctly */
            if (!chdir($full_file)) {
                debug_event('read', "Unable to chdir to $path", 2);
                AmpError::add('catalog_add', sprintf(T_('Error: Unable to change to directory %s'), $path));
            }

            /* Skip to the next file */
            return true;
        } //it's a directory

        $is_audio_file = Catalog::is_audio_file($full_file);
        if (AmpConfig::get('catalog_video_pattern')) {
            $is_video_file = Catalog::is_video_file($full_file);
        }

        if ($options['parse_playlist'] && AmpConfig::get('catalog_playlist_pattern')) {
            $is_playlist = Catalog::is_playlist_file($full_file);
        }

        /* see if this is a valid audio file or playlist file */
        if ($is_audio_file || $is_video_file || $is_playlist) {
            /* Now that we're sure its a file get filesize  */
            $file_size = Core::get_filesize($full_file);

            if (!$file_size) {
                debug_event('read', "Unable to get filesize for $full_file", 2);
                /* HINT: FullFile */
                AmpError::add('catalog_add', sprintf(T_('Error: Unable to get filesize for %s'), $full_file));
            } // file_size check

            if (!Core::is_readable($full_file)) {
                // not readable, warn user
                debug_event('read', "$full_file is not readable by ampache", 2);
                /* HINT: FullFile */
                AmpError::add('catalog_add', sprintf(T_('%s is not readable by ampache'), $full_file));

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
                    debug_event('read', $full_file . ' has non-' . $site_charset . ' characters and can not be indexed, converted filename:' . $enc_full_file, '1');
                    /* HINT: FullFile */
                    AmpError::add('catalog_add', sprintf(T_('%s does not match site charset'), $full_file));

                    return false;
                }
                $full_file = $enc_full_file;

                // Check again with good encoding
                if (isset($this->_filecache[strtolower($full_file)])) {
                    return false;
                }
            } // end if iconv

            if ($is_playlist) {
                debug_event('read', 'Found playlist file to import: ' . $full_file, '5');
                $this->_playlists[] = $full_file;
            } // if it's a playlist

            else {
                if (count($this->get_gather_types('music')) > 0) {
                    if ($is_audio_file) {
                        $this->insert_local_song($full_file, $options);
                    } else {
                        debug_event('read', $full_file . " ignored, bad media type for this music catalog.", 5);

                        return false;
                    }
                } else {
                    if (count($this->get_gather_types('video')) > 0) {
                        if ($is_video_file) {
                            $this->insert_local_video($full_file, $options);
                        } else {
                            debug_event('read', $full_file . " ignored, bad media type for this video catalog.", 5);

                            return false;
                        }
                    }
                }

                $this->count++;
                $file = str_replace(array('(', ')', '\''), '', $full_file);
                if (UI::check_ticker()) {
                    UI::update_text('add_count_' . $this->id, $this->count);
                    UI::update_text('add_dir_' . $this->id, scrub_out($file));
                } // update our current state
            } // if it's not an m3u
            return true;
        } //if it matches the pattern
        else {
            debug_event('read', "$full_file ignored, non-audio file or 0 bytes", 5);

            return false;
        } // else not an audio file
    }

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     */
    public function add_to_catalog($options = null)
    {
        if ($options == null) {
            $options = array(
                'gather_art' => true,
                'parse_playlist' => false
            );
        }

        $this->count                  = 0;
        $this->added_songs_to_gather  = array();
        $this->added_videos_to_gather = array();

        if (!defined('SSE_OUTPUT')) {
            require AmpConfig::get('prefix') . UI::find_template('show_adds_catalog.inc.php');
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
        if ($this->gather_types == "podcast") {
            $this->sync_podcasts();
        } else {
            /* Get the songs and then insert them into the db */
            $this->add_files($this->path, $options);

            if ($options['parse_playlist'] && count($this->_playlists)) {
                // Foreach Playlists we found
                foreach ($this->_playlists as $full_file) {
                    $result = $this->import_playlist($full_file);
                    if ($result['success']) {
                        $file = basename($full_file);
                    } // end if import worked
                } // end foreach playlist files
            }

            /* Do a little stats mojo here */
            $current_time = time();

            if ($options['gather_art']) {
                $catalog_id = $this->id;
                if (!defined('SSE_OUTPUT')) {
                    require AmpConfig::get('prefix') . UI::find_template('show_gather_art.inc.php');
                    flush();
                }
                $this->gather_art($this->added_songs_to_gather, $this->added_videos_to_gather);
            }
        }

        /* Update the Catalog last_update */
        $this->update_last_add();

        $time_diff = ($current_time - $start_time) ?: 0;
        $rate      = number_format(($time_diff > 0) ? $this->count / $time_diff : 0, 2);
        if ($rate <= 0) {
            $rate = T_('N/A');
        }

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top();
        }
        UI::update_text('', sprintf(T_('Catalog Update Finished.  Total Time: [%s] Total Media: [%s] Media Per Second: [%s]'), date('i:s', $time_diff), $this->count, $rate));
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }
    } // add_to_catalog

    /**
     * verify_catalog_proc
     * This function compares the DB's information with the ID3 tags
     */
    public function verify_catalog_proc()
    {
        debug_event('verify', 'Starting on ' . $this->name, 5);
        set_time_limit(0);

        $stats         = self::get_stats($this->id);
        $number        = $stats['videos'] + $stats['songs'];
        $total_updated = 0;
        $this->count   = 0;

        if (!defined('SSE_OUTPUT')) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_verify_catalog.inc.php');
            flush();
        }

        foreach (array('video', 'song') as $media_type) {
            $total = $stats[$media_type . 's']; // UGLY
            if ($total == 0) {
                continue;
            }
            $chunks = floor($total / 10000);
            foreach (range(0, $chunks) as $chunk) {
                // Try to be nice about memory usage
                if ($chunk > 0) {
                    $media_type::clear_cache();
                }
                $total_updated += $this->_verify_chunk($media_type, $chunk, 10000);
            }
        }

        debug_event('verify', "Finished, $total_updated updated in " . $this->name, 5);

        $this->update_last_update();

        return array('total' => $number, 'updated' => $total_updated);
    } // verify_catalog_proc

    /**
     * _verify_chunk
     * This verifies a chunk of the catalog, done to save
     * memory
     */
    private function _verify_chunk($media_type, $chunk, $chunk_size)
    {
        debug_event('verify', "Starting chunk $chunk", 5);
        $count   = $chunk * $chunk_size;
        $changed = 0;

        $sql = "SELECT `id`, `file` FROM `$media_type` " .
            "WHERE `catalog`='$this->id' LIMIT $count,$chunk_size";
        $db_results = Dba::read($sql);

        if (AmpConfig::get('memory_cache')) {
            while ($row = Dba::fetch_assoc($db_results, false)) {
                $media_ids[] = $row['id'];
            }
            $media_type::build_cache($media_ids);
            $db_results = Dba::read($sql);
        }
        while ($row = Dba::fetch_assoc($db_results)) {
            $count++;
            if (UI::check_ticker()) {
                $file = str_replace(array('(', ')', '\''), '', $row['file']);
                UI::update_text('verify_count_' . $this->id, $count);
                UI::update_text('verify_dir_' . $this->id, scrub_out($file));
            }

            if (!Core::is_readable(Core::conv_lc_file($row['file']))) {
                AmpError::add('general', sprintf(T_('%s does not exist or is not readable'), $row['file']));
                debug_event('read', $row['file'] . ' does not exist or is not readable', 5);
                continue;
            }

            $media = new $media_type($row['id']);
            $info  = self::update_media_from_tags($media, $this->get_gather_types(), $this->sort_pattern, $this->rename_pattern);
            if ($info['change']) {
                $changed++;
            }
            unset($info);
        }

        UI::update_text('verify_count_' . $this->id, $count);

        return $changed;
    } // _verify_chunk

    /**
     * clean catalog procedure
     *
     * Removes local songs that no longer exist.
     */
    public function clean_catalog_proc()
    {
        if (!Core::is_readable($this->path)) {
            // First sanity check; no point in proceeding with an unreadable
            // catalog root.
            debug_event('catalog', 'Catalog path:' . $this->path . ' unreadable, clean failed', 1);
            AmpError::add('general', T_('Catalog Root unreadable, stopping clean'));
            AmpError::display('general');

            return 0;
        }

        $dead_total  = 0;
        $stats       = self::get_stats($this->id);
        $this->count = 0;
        foreach (array('video', 'song') as $media_type) {
            $total = $stats[$media_type . 's']; // UGLY
            if ($total == 0) {
                continue;
            }
            $chunks = floor($total / 10000);
            $dead   = array();
            foreach (range(0, $chunks) as $chunk) {
                $dead = array_merge($dead, $this->_clean_chunk($media_type, $chunk, 10000));
            }

            $dead_count = count($dead);
            // The AlmightyOatmeal sanity check
            // Check for unmounted path
            if (!file_exists($this->path)) {
                if ($dead_count >= $total) {
                    debug_event('catalog', 'All files would be removed. Doing nothing.', 1);
                    AmpError::add('general', T_('All files would be removed. Doing nothing'));
                    continue;
                }
            }
            if ($dead_count) {
                $dead_total += $dead_count;
                $sql = "DELETE FROM `$media_type` WHERE `id` IN " .
                    '(' . implode(',', $dead) . ')';
                $db_results = Dba::write($sql);
            }
        }

        \Lib\Metadata\Repository\Metadata::gc();
        \Lib\Metadata\Repository\MetadataField::gc();

        return $dead_total;
    }

    /**
     * _clean_chunk
     * This is the clean function, its broken into
     * said chunks to try to save a little memory
     */
    private function _clean_chunk($media_type, $chunk, $chunk_size)
    {
        debug_event('clean', "Starting chunk $chunk", 5);
        $dead  = array();
        $count = $chunk * $chunk_size;

        $sql = "SELECT `id`, `file` FROM `$media_type` " .
            "WHERE `catalog`='$this->id' LIMIT $count,$chunk_size";
        $db_results = Dba::read($sql);

        while ($results = Dba::fetch_assoc($db_results)) {
            debug_event('clean', 'Starting work on ' . $results['file'] . '(' . $results['id'] . ')', 5);
            $count++;
            if (UI::check_ticker()) {
                $file = str_replace(array('(', ')', '\''), '', $results['file']);
                UI::update_text('clean_count_' . $this->id, $count);
                UI::update_text('clean_dir_' . $this->id, scrub_out($file));
            }
            $file_info = Core::get_filesize(Core::conv_lc_file($results['file']));
            if (!file_exists(Core::conv_lc_file($results['file'])) || $file_info < 1) {
                debug_event('clean', 'File not found or empty: ' . $results['file'], 5);
                AmpError::add('general', sprintf(T_('Error File Not Found or 0 Bytes: %s'), $results['file']));


                // Store it in an array we'll delete it later...
                $dead[] = $results['id'];
            } //if error
            else {
                if (!Core::is_readable(Core::conv_lc_file($results['file']))) {
                    debug_event('clean', $results['file'] . ' is not readable, but does exist', 1);
                }
            }
        }

        return $dead;
    } //_clean_chunk

    /**
     * insert_local_song
     *
     * Insert a song that isn't already in the database.
     */
    private function insert_local_song($file, $options = array())
    {
        $vainfo = new vainfo($file, $this->get_gather_types('music'), '', '', '', $this->sort_pattern, $this->rename_pattern);
        $vainfo->get_info();

        $key = vainfo::get_tag_type($vainfo->tags);

        $results            = vainfo::clean_tag_info($vainfo->tags, $key, $file);
        $results['catalog'] = $this->id;

        if (isset($options['user_upload'])) {
            $results['user_upload'] = $options['user_upload'];
        }

        if (isset($options['license'])) {
            $results['license'] = $options['license'];
        }

        if (isset($options['artist_id'])) {
            $results['artist_id']      = $options['artist_id'];
            $results['albumartist_id'] = $options['artist_id'];
            $artist                    = new Artist($results['artist_id']);
            if ($artist->id) {
                $results['artist'] = $artist->name;
            }
        }

        if (isset($options['album_id'])) {
            $results['album_id'] = $options['album_id'];
            $album               = new Album($results['album_id']);
            if ($album->id) {
                $results['album'] = $album->name;
            }
        }

        if (count($this->get_gather_types('music')) > 0) {
            if (AmpConfig::get('catalog_check_duplicate')) {
                if (Song::find($results)) {
                    debug_event('catalog', 'Song already found, skipped to avoid duplicate', 5);

                    return false;
                }
            }

            if ($options['move_match_pattern']) {
                $patres = vainfo::parse_pattern($file, $this->sort_pattern, $this->rename_pattern);
                if ($patres['artist'] != $results['artist'] || $patres['album'] != $results['album'] || $patres['track'] != $results['track'] || $patres['title'] != $results['title']) {
                    // Remove first left directories from filename to match pattern
                    $cntslash = substr_count($pattern, preg_quote(DIRECTORY_SEPARATOR)) + 1;
                    $filepart = explode(DIRECTORY_SEPARATOR, $file);
                    if (count($filepart) > $cntslash) {
                        $mvfile  = implode(DIRECTORY_SEPARATOR, array_slice($filepart, 0, count($filepart) - $cntslash));
                        $pattern = $this->sort_pattern . DIRECTORY_SEPARATOR . $this->rename_pattern;
                        preg_match_all('/\%\w/', $pattern, $elements);
                        foreach ($elements[0] as $key => $value) {
                            $key     = translate_pattern_code($value);
                            $pattern = str_replace($value, $results[$key], $pattern);
                        }
                        $mvfile .= DIRECTORY_SEPARATOR . $pattern . '.' . pathinfo($file, PATHINFO_EXTENSION);
                        debug_event('catalog', 'Unmatching pattern, moving `' . $file . '` to `' . $mvfile . '`...', 5);

                        $mvdir = pathinfo($mvfile, PATHINFO_DIRNAME);
                        if (!is_dir($mvdir)) {
                            mkdir($mvdir, 0777, true);
                        }
                        if (rename($file, $mvfile)) {
                            $results['file'] = $mvfile;
                        } else {
                            debug_event('catalog', 'File rename failed', 5);
                        }
                    }
                }
            }
        }

        $id = Song::insert($results);
        if ($id) {
            // If song rating tag exists and is well formed (array user=>rating), add it
            if (array_key_exists('rating', $results) && is_array($results['rating'])) {
                // For each user's ratings, call the function
                foreach ($results['rating'] as $user => $rating) {
                    debug_event('Rating', "Setting rating for Song $id to $rating for user $user", 5);
                    $o_rating = new Rating($id, 'song');
                    $o_rating->set_rating($rating, $user);
                }
            }
            // Extended metadata loading is not deferred, retrieve it now
            if (!AmpConfig::get('deferred_ext_metadata')) {
                $song = new Song($id);
                Recommendation::get_artist_info($song->artist);
            }
            if (Song::isCustomMetadataEnabled()) {
                if (!$song) {
                    $song = new Song($id);
                }
                $results = array_diff_key($results, array_flip($song->getDisabledMetadataFields()));
                self::add_metadata($song, $results);
            }
            $this->added_songs_to_gather[] = $id;

            $this->_filecache[strtolower($file)] = $id;
        }

        return $id;
    }

    /**
     * insert_local_video
     * This inserts a video file into the video file table the tag
     * information we can get is super sketchy so it's kind of a crap shoot
     * here
     */
    public function insert_local_video($file, $options = array())
    {
        /* Create the vainfo object and get info */
        $gtypes     = $this->get_gather_types('video');
        $vainfo     = new vainfo($file, $gtypes, '', '', '', $this->sort_pattern, $this->rename_pattern);
        $vainfo->get_info();

        $tag_name           = vainfo::get_tag_type($vainfo->tags, 'metadata_order_video');
        $results            = vainfo::clean_tag_info($vainfo->tags, $tag_name, $file);
        $results['catalog'] = $this->id;

        $id = Video::insert($results, $gtypes, $options);
        if ($results['art']) {
            $art = new Art($id, 'video');
            $art->insert_url($results['art']);

            if (AmpConfig::get('generate_video_preview')) {
                Video::generate_preview($id);
            }
        } else {
            $this->added_videos_to_gather[] = $id;
        }

        $this->_filecache[strtolower($file)] = 'v_' . $id;

        return $id;
    } // insert_local_video

    private function sync_podcasts()
    {
        $podcasts = $this->get_podcasts();
        foreach ($podcasts as $podcast) {
            $podcast->sync_episodes(false);
            $episodes = $podcast->get_episodes('pending');
            foreach ($episodes as $episode_id) {
                $episode = new Podcast_Episode($episode_id);
                $episode->gather();
                $this->count++;
            }
        }
    }

    /**
     * check_local_mp3
     * Checks the song to see if it's there already returns true if found, false if not
     */
    public function check_local_mp3($full_file, $gather_type='')
    {
        $file_date = filemtime($full_file);
        if ($file_date < $this->last_add) {
            debug_event('Check', 'Skipping ' . $full_file . ' File modify time before last add run', '3');

            return true;
        }

        $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
        $db_results = Dba::read($sql, array($full_file));

        //If it's found then return true
        if (Dba::fetch_row($db_results)) {
            return true;
        }

        return false;
    } //check_local_mp3

    public function get_rel_path($file_path)
    {
        $catalog_path = rtrim($this->path, "/");

        return(str_replace($catalog_path . "/", "", $file_path));
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        parent::format();
        $this->f_info      = $this->path;
        $this->f_full_info = $this->path;
    }

    public function prepare_media($media)
    {
        // Do nothing, it's just file...
        return $media;
    }
} // end of local catalog class
