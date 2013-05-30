<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Catalog Class
 *
 * This class handles all actual work in regards to the catalog,
 * it contains functions for creating/listing/updated the catalogs.
 *
 */
class Catalog extends database_object {

    public $name;
    public $last_update;
    public $last_add;
    public $last_clean;
    public $key;
    public $rename_pattern;
    public $sort_pattern;
    public $catalog_type;
    public $path;

    /* This is a private var that's used during catalog builds */
    private $_playlists = array();

    // Cache all files in catalog for quick lookup during add
    private $_filecache = array();

    // Used in functions
    private static $albums    = array();
    private static $artists    = array();
    private static $tags    = array();

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct($catalog_id = null) {
        if (!$catalog_id) {
            return false;
        }

        $this->id = intval($catalog_id);
        $info = $this->get_info($catalog_id);

        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }
    }

    /**
     * _create_filecache
     *
     * This populates an array which is used to speed up the add process.
     */
    private function _create_filecache() {
        if (count($this->_filecache) == 0) {
            $catalog_id = Dba::escape($this->id);
            // Get _EVERYTHING_
            $sql = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));

            // Populate the filecache
            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower($results['file'])] = $results['id'];
            }

            $sql = 'SELECT `id`,`file` FROM `video` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));

            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower($results['file'])] = 'v_' . $results['id'];
            }
        }

        return true;
    }

    /**
     * get_from_path
     *
     * Try to figure out which catalog path most closely resembles this one.
     * This is useful when creating a new catalog to make sure we're not
     * doubling up here.
     */
    public static function get_from_path($path) {
        // First pull a list of all of the paths for the different catalogs
        $sql = "SELECT `id`,`path` FROM `catalog` WHERE `catalog_type`='local'";
        $db_results = Dba::read($sql);

        $catalog_paths = array();
        $component_path = $path;

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog_paths[$row['path']] = $row['id'];
        }

        // Break it down into its component parts and start looking for a catalog
        do {
            if ($catalog_paths[$component_path]) {
                return $catalog_paths[$component_path];
            }

            // Keep going until the path stops changing
            $old_path = $component_path;
            $component_path = realpath($component_path . '/../');

        } while (strcmp($component_path,$old_path) != 0);

        return false;
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format() {
        $this->f_name = UI::truncate($this->name,
            Config::get('ellipse_threshold_title'));
        $this->f_name_link = '<a href="' . Config::get('web_path') .
            '/admin/catalog.php?action=show_customize_catalog&catalog_id=' .
            $this->id . '" title="' . scrub_out($this->name) . '">' . 
            scrub_out($this->f_name) . '</a>';
        $this->f_path = UI::truncate($this->path, 
            Config::get('ellipse_threshold_title'));
        $this->f_update = $this->last_update 
            ? date('d/m/Y h:i', $this->last_update)
            : T_('Never');
        $this->f_add = $this->last_add 
            ? date('d/m/Y h:i', $this->last_add)
            : T_('Never');
        $this->f_clean = $this->last_clean 
            ? date('d/m/Y h:i', $this->last_clean)
            : T_('Never');
    }

    /**
     * get_catalogs
     *
     * Pull all the current catalogs and return an array of ids
     * of what you find
     */
    public static function get_catalogs() {
        $sql = "SELECT `id` FROM `catalog` ORDER BY `name`";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_stats
     *
     * This returns an hash with the #'s for the different
     * objects that are associated with this catalog. This is used
     * to build the stats box, it also calculates time.
     */
    public static function get_stats($catalog_id = null) {
        $results = self::count_songs($catalog_id);
        $results = array_merge(User::count(), $results);
        $results['tags'] = self::count_tags();
        $results['videos'] = self::count_videos($catalog_id);

        $hours = floor($results['time'] / 3600);

        $results['formatted_size'] = UI::format_bytes($results['size']);

        $days = floor($hours / 24);
        $hours = $hours % 24;

        $time_text = "$days ";
        $time_text .= T_ngettext('day','days',$days);
        $time_text .= ", $hours ";
        $time_text .= T_ngettext('hour','hours',$hours);

        $results['time_text'] = $time_text;

        return $results;
    }

    /**
     * create
     *
     * This creates a new catalog entry and then returns the insert id.
     * It checks to make sure this path is not already used before creating
     * the catalog.
     */
    public static function create($data) {
        // Clean up the path just in case
        $path = rtrim(rtrim(trim($data['path']),'/'),'\\');

        // Make sure the path is readable/exists
        if ($data['type'] == 'local') {
            if (!Core::is_readable($path)) {
                debug_event('catalog', 'Cannot add catalog at unopenable path ' . $path, 1);
                Error::add('general', sprintf(T_('Error: %s is not readable or does not exist'), scrub_out($data['path'])));
                return false;
            }
        }

        // Make sure this path isn't already in use by an existing catalog
        $sql = 'SELECT `id` FROM `catalog` WHERE `path` = ?';
        $db_results = Dba::read($sql, array($path));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate path ' . $path, 1);
            Error::add('general', sprintf(T_('Error: Catalog with %s already exists'), $path));
            return false;
        }

        $name = $data['name'];
        $type = $data['type'];
        $rename_pattern = $data['rename_pattern'];
        $sort_pattern = $data['sort_pattern'];
        $remote_username = $type == 'remote' ? $data['remote_username'] : '';
        $remote_password = $type == 'remote' ? hash('sha256', $data['remote_password']) : '';

        $sql = 'INSERT INTO `catalog` (`name`, `path`, `catalog_type`, ' .
            '`rename_pattern`, `sort_pattern`, `remote_username`, ' .
            '`remote_password`) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $db_results = Dba::write($sql, array(
            $name,
            $path,
            $type,
            $rename_pattern,
            $sort_pattern,
            $remote_username,
            $remote_password
        ));

        $insert_id = Dba::insert_id();

        if (!$insert_id) {
            Error::add('general', T_('Catalog Insert Failed check debug logs'));
            debug_event('catalog', 'Insert failed: ' . json_encode($data), 2);
            return false;
        }

        return $insert_id;
    }

    /**
     * run_add
     *
     * This runs the add to catalog function
     * it includes the javascript refresh stuff and then starts rolling
     * throught the path for this catalog
     */
    public function run_add($options) {
        // Prevent the script from timing out
        set_time_limit(0);

        if ($this->catalog_type == 'remote') {
            UI::show_box_top(T_('Running Remote Sync') . '. . .');
            $this->update_remote_catalog();
            UI::show_box_bottom();
            return true;
        }

        $start_time = time();

        require Config::get('prefix') . '/templates/show_adds_catalog.inc.php';
        flush();

        $this->add_files($this->path, $options);

        // If they have checked the box then go ahead and gather the art
        if ($options['gather_art']) {
            $catalog_id = $this->id;
            require Config::get('prefix') . '/templates/show_gather_art.inc.php';
            flush();
            $this->gather_art();
        }

        // Handle m3u-ness
        if ($options['parse_m3u'] AND count($this->_playlists)) {
            foreach ($this->_playlists as $playlist_file) {
                $result = $this->import_m3u($playlist_file);
            }
        }

        return true;
    }

    /**
     * count_videos
     *
     * This returns the current number of video files in the database.
     */
    public static function count_videos($id = null) {
        $sql = 'SELECT COUNT(`id`) FROM `video` ';
        if ($id) {
            $sql .= 'WHERE `catalog` = ?';
        }
        $db_results = Dba::read($sql, $id ? array($id) : null);

        $row = Dba::fetch_assoc($db_results);
        return $row[0];
    }

    /**
     * count_tags
     *
     * This returns the current number of unique tags in the database.
     */
    public static function count_tags($id = null) {
        // FIXME: Ignores catalog_id
        $sql = "SELECT COUNT(`id`) FROM `tag`";
        $db_results = Dba::read($sql);

        $row = Dba::fetch_row($db_results);
        return $row[0];
    }

    /**
     * count_songs
     *
     * This returns the current number of songs, albums, and artists
     * in this catalog.
     */
    public static function count_songs($id = null) {
        $where_sql = $id ? 'WHERE `catalog` = ?' : '';
        $params = $id ? array($id) : null;

        $sql = 'SELECT COUNT(`id`), SUM(`time`), SUM(`size`) FROM `song` ' .
            $where_sql;

        $db_results = Dba::read($sql, $params);
        $data = Dba::fetch_row($db_results);
        $songs    = $data[0];
        $time    = $data[1];
        $size    = $data[2];

        $sql = 'SELECT COUNT(DISTINCT(`album`)) FROM `song` ' . $where_sql;
        $db_results = Dba::read($sql, $params);
        $data = Dba::fetch_row($db_results);
        $albums = $data[0];

        $sql = 'SELECT COUNT(DISTINCT(`artist`)) FROM `song` ' . $where_sql;
        $db_results = Dba::read($sql, $params);
        $data = Dba::fetch_row($db_results);
        $artists = $data[0];

        $results['songs'] = $songs;
        $results['albums'] = $albums;
        $results['artists'] = $artists;
        $results['size'] = $size;
        $results['time'] = $time;

        return $results;
    }

    /**
     * add_files
     *
     * Recurses through $this->path and pulls out all mp3s and returns the
     * full path in an array. Passes gather_type to determine if we need to
     * check id3 information against the db.
     */
    public function add_files($path, $options) {

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
        }
        else {
            $slash_type = '\\';
        }

        /* Open up the directory */
        $handle = opendir($path);

        if (!is_resource($handle)) {
            debug_event('read', "Unable to open $path", 5);
            Error::add('catalog_add', sprintf(T_('Error: Unable to open %s'), $path));
            return false;
        }

        /* Change the dir so is_dir works correctly */
        if (!chdir($path)) {
            debug_event('read', "Unable to chdir to $path", 2);
            Error::add('catalog_add', sprintf(T_('Error: Unable to change to directory %s'), $path));
            return false;
        }

        // Ensure that we've got our cache
        $this->_create_filecache();

        debug_event('Memory', UI::format_bytes(memory_get_usage(true)), 5);

        /* Recurse through this dir and create the files array */
        while ( false !== ( $file = readdir($handle) ) ) {

            /* Skip to next if we've got . or .. */
            if (substr($file,0,1) == '.') { continue; }

            debug_event('read', "Starting work on $file inside $path", 5);
            debug_event('Memory', UI::format_bytes(memory_get_usage(true)), 5);

            /* Create the new path */
            $full_file = $path.$slash_type.$file;

            /* First thing first, check if file is already in catalog.
             * This check is very quick, so it should be performed before any other checks to save time
             */
            if (isset($this->_filecache[strtolower($full_file)])) {
                continue;
            }

            // Incase this is the second time through clear this variable
            // if it was set the day before
            unset($failed_check);

            if (Config::get('no_symlinks')) {
                if (is_link($full_file)) {
                    debug_event('read', "Skipping symbolic link $path", 5);
                    continue;
                }
            }

            /* If it's a dir run this function again! */
            if (is_dir($full_file)) {
                $this->add_files($full_file,$options);

                /* Change the dir so is_dir works correctly */
                if (!chdir($path)) {
                    debug_event('read', "Unable to chdir to $path", 2);
                    Error::add('catalog_add', sprintf(T_('Error: Unable to change to directory %s'), $path));
                }

                /* Skip to the next file */
                continue;
            } //it's a directory

            /* If it's not a dir let's roll with it
             * next we need to build the pattern that we will use
             * to detect if it's an audio file
             */
            $pattern = "/\.(" . Config::get('catalog_file_pattern');
            if ($options['parse_m3u']) {
                $pattern .= "|m3u)$/i";
            }
            else {
                $pattern .= ")$/i";
            }

            $is_audio_file = preg_match($pattern,$file);

            // Define the Video file pattern
            if (!$is_audio_file AND Config::get('catalog_video_pattern')) {
                $video_pattern = "/\.(" . Config::get('catalog_video_pattern') . ")$/i";
                $is_video_file = preg_match($video_pattern,$file);
            }

            /* see if this is a valid audio file or playlist file */
            if ($is_audio_file OR $is_video_file) {

                /* Now that we're sure its a file get filesize  */
                $file_size = filesize($full_file);

                if (!$file_size) {
                    debug_event('read', "Unable to get filesize for $full_file", 2);
                    /* HINT: FullFile */
                    Error::add('catalog_add', sprintf(T_('Error: Unable to get filesize for %s'), $full_file));
                } // file_size check

                if (!Core::is_readable($full_file)) {
                    // not readable, warn user
                    debug_event('read', "$full_file is not readable by ampache", 2);
                    /* HINT: FullFile */
                    Error::add('catalog_add', sprintf(T_('%s is not readable by ampache'), $full_file));
                    continue;
                }

                // Check to make sure the filename is of the expected charset
                if (function_exists('iconv')) {
                    if (strcmp($full_file,iconv(Config::get('site_charset'),Config::get('site_charset'),$full_file)) != '0') {
                        debug_event('read',$full_file . ' has non-' . Config::get('site_charset') . ' characters and can not be indexed, converted filename:' . iconv(Config::get('site_charset'),Config::get('site_charset'),$full_file),'1');
                        /* HINT: FullFile */
                        Error::add('catalog_add', sprintf(T_('%s does not match site charset'), $full_file));
                        continue;
                    }
                } // end if iconv

                if ($options['parse_m3u'] AND substr($file,-3,3) == 'm3u') {
                    $this->_playlists[] = $full_file;
                } // if it's an m3u

                else {
                    if ($is_audio_file) {
                        $this->_insert_local_song($full_file,$file_size);
                    }
                    else { $this->insert_local_video($full_file,$file_size); }

                    $this->count++;
                    $file = str_replace(array('(',')','\''),'',$full_file);
                    if(UI::check_ticker()) {
                        UI::update_text('add_count_' . $this->id, $this->count);
                        UI::update_text('add_dir_' . $this->id, scrub_out($file));
                    } // update our current state

                } // if it's not an m3u

            } //if it matches the pattern
            else {
                debug_event('read', "$full_file ignored, non-audio file or 0 bytes", 5);
            } // else not an audio file

        } // end while reading directory

        debug_event('closedir', "Finished reading $path , closing handle", 5);

        // This should only happen on the last run
        if ($path == $this->path) {
            UI::update_text('add_count_' . $this->id, $this->count);
            UI::update_text('add_dir_' . $this->id, scrub_out($file));
        }


        /* Close the dir handle */
        @closedir($handle);

    } // add_files

    /**
     * get_album_ids
     *
     * This returns an array of ids of albums that have songs in this
     * catalog
     */
    public function get_album_ids() {
        $results = array();

        $sql = 'SELECT DISTINCT(`song`.`album`) FROM `song` WHERE `song`.`catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['album'];
        }

        return $results;
    }

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and trys
     * to find the art for them from the mp3s
     */
    public function gather_art() {
        // Make sure they've actually got methods
        $art_order = Config::get('art_order');
        if (!count($art_order)) {
            debug_event('gather_art', 'art_order not set, Catalog::gather_art aborting', 3);
            return true;
        }

        // Prevent the script from timing out
        set_time_limit(0);

        $albums = $this->get_album_ids();

        // Run through them and get the art!
        foreach ($albums as $album_id) {
            $art = new Art($album_id, 'album');
            $album = new Album($album_id);
            // We're going to need the name here
            $album->format();

            debug_event('gather_art', 'Gathering art for ' . $album->name, 5);

            $options = array(
                'album_name' => $album->full_name,
                'artist'     => $album->artist_name,
                'keyword'    => $album->artist_name . ' ' . $album->full_name
            );

            $results = $art->gather($options, 1);

            if (count($results)) {
                // Pull the string representation from the source
                $image = Art::get_from_source($results[0], 'album');
                if (strlen($image) > '5') {
                    $art->insert($image, $results[0]['mime']);
                    // If they've enabled resizing of images generate a thumbnail
                    if (Config::get('resize_images')) {
                        $thumb = $art->generate_thumb($image, array(
                                'width' => 275,
                                'height' => 275),
                            $results[0]['mime']);
                        if (is_array($thumb)) {
                            $art->save_thumb($thumb['thumb'], $thumb['thumb_mime'], '275x275');
                        }
                    }

                }
                else {
                    debug_event('gather_art', 'Image less than 5 chars, not inserting', 3);
                }
                $art_found++;
            }

            // Stupid little cutesie thing
            $search_count++;
            if (UI::check_ticker()) {
                UI::update_text('count_art_' . $this->id, $search_count);
                UI::update_text('read_art_' . $this->id, scrub_out($album->name));
            }

            unset($found);
        } // foreach albums

        // One last time for good measure
        UI::update_text('count_art_' . $this->id, $search_count);
        UI::update_text('read_art_' . $this->id, scrub_out($album->name));
    }

    /**
     * get_songs
     *
     * Returns an array of song objects.
     */
    public function get_songs() {
        $results = array();

        $sql = "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled`='1'";
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = new Song($row['id']);
        }

        return $results;
    }

    /**
     * dump_album_art
     *
     * This runs through all of the albums and tries to dump the
     * art for them into the 'folder.jpg' file in the appropriate dir.
     */
    public function dump_album_art($methods = array()) {
        // Get all of the albums in this catalog
        $albums = $this->get_album_ids();

        echo "Starting Dump Album Art...\n";

        // Run through them and get the art!
        foreach ($albums as $album_id) {

            $album = new Album($album_id);
            $art = new Art($album_id, 'album');

            if (!$art->get_db()) {
                continue;
            }

            // Get the first song in the album
            $songs = $album->get_songs(1);
            $song = new Song($songs[0]);
            $dir = dirname($song->file);

            $extension = Art::extension($art->raw_mime);

            // Try the preferred filename, if that fails use folder.???
            $preferred_filename = Config::get('album_art_preferred_filename');
            if (!$preferred_filename ||
                strpos($preferred_filename, '%') !== false) {
                $preferred_filename = "folder.$extension";
            }

            $file = "$dir/$preferred_filename";
            if ($file_handle = fopen($file,"w")) {
                if (fwrite($file_handle, $art->raw)) {

                    // Also check and see if we should write
                    // out some metadata
                    if ($methods['metadata']) {
                        switch ($methods['metadata']) {
                            case 'windows':
                                $meta_file = $dir . '/desktop.ini';
                                $string = "[.ShellClassInfo]\nIconFile=$file\nIconIndex=0\nInfoTip=$album->full_name";
                                break;
                            default:
                            case 'linux':
                                $meta_file = $dir . '/.directory';
                                $string = "Name=$album->full_name\nIcon=$file";
                                break;
                        }

                        $meta_handle = fopen($meta_file,"w");
                        fwrite($meta_handle,$string);
                        fclose($meta_handle);

                    } // end metadata
                    $i++;
                    if (!($i%100)) {
                        echo "Written: $i. . .\n";
                        debug_event('art_write',"$album->name Art written to $file",'5');
                    }
                }
                else {
                    debug_event('art_write',"Unable to open $file for writing", 5);
                    echo "Error: unable to open file for writing [$file]\n";
                }
            }
            fclose($file_handle);
        }

        echo "Album Art Dump Complete\n";
    }

    /**
     * update_last_update
     * updates the last_update of the catalog
     */
    private function update_last_update() {

        $date = time();
        $sql = "UPDATE `catalog` SET `last_update`='$date' WHERE `id`='$this->id'";
        $db_results = Dba::write($sql);

    } // update_last_update

    /**
     * update_last_add
     * updates the last_add of the catalog
     */
    public function update_last_add() {

        $date = time();
        $sql = "UPDATE `catalog` SET `last_add`='$date' WHERE `id`='$this->id'";
        $db_results = Dba::write($sql);

    } // update_last_add

    /**
     * update_last_clean
     * This updates the last clean information
     */
    public function update_last_clean() {

        $date = time();
        $sql = "UPDATE `catalog` SET `last_clean`='$date' WHERE `id`='$this->id'";
        $db_results = Dba::write($sql);

    } // update_last_clean

    /**
     * update_settings
     * This function updates the basic setting of the catalog
     */
    public static function update_settings($data) {

        $id    = Dba::escape($data['catalog_id']);
        $name    = Dba::escape($data['name']);
        $rename    = Dba::escape($data['rename_pattern']);
        $sort    = Dba::escape($data['sort_pattern']);
        $remote_username = Dba::escape($data['remote_username']);
        $remote_password = Dba::escape($data['remote_password']);

        $sql = "UPDATE `catalog` SET `name`='$name', `rename_pattern`='$rename', " .
            "`sort_pattern`='$sort', `remote_username`='$remote_username', `remote_password`='$remote_password' WHERE `id` = '$id'";
        $db_results = Dba::write($sql);

        return true;

    } // update_settings

    /**
     * update_single_item
     * updates a single album,artist,song from the tag data
     * this can be done by 75+
     */
    public static function update_single_item($type,$id) {

        // Because single items are large numbers of things too
        set_time_limit(0);

        $songs = array();

        switch ($type) {
            case 'album':
                $album = new Album($id);
                $songs = $album->get_songs();
                break;
            case 'artist':
                $artist = new Artist($id);
                $songs = $artist->get_songs();
                break;
            case 'song':
                $songs[] = $id;
                break;
        } // end switch type

        foreach($songs as $song_id) {
            $song = new Song($song_id);
            $info = self::update_media_from_tags($song,'','');

            if ($info['change']) {
                $file = scrub_out($song->file);
                echo "<dl>\n\t<dd>";
                echo "<strong>$file " . T_('Updated') . "</strong>\n";
                echo $info['text'];
                echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
                flush();
            } // if change
            else {
                echo"<dl>\n\t<dd>";
                echo "<strong>" . scrub_out($song->file) . "</strong><br />" . T_('No Update Needed') . "\n";
                echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
                flush();
            }
        } // foreach songs

        self::gc();

    } // update_single_item

    /**
     * update_media_from_tags
     * This is a 'wrapper' function calls the update function for the media
     * type in question
     */
    public static function update_media_from_tags($media, $sort_pattern='', $rename_pattern='') {

        // Check for patterns
        if (!$sort_pattern OR !$rename_pattern) {
            $catalog = new Catalog($media->catalog);
            $sort_pattern = $catalog->sort_pattern;
            $rename_pattern = $catalog->rename_pattern;
        }

        debug_event('tag-read', 'Reading tags from ' . $media->file, 5);

        $vainfo = new vainfo($media->file,'','','',$sort_pattern,$rename_pattern);
        $vainfo->get_info();

        $key = vainfo::get_tag_type($vainfo->tags);

        $results = vainfo::clean_tag_info($vainfo->tags,$key,$media->file);

        // Figure out what type of object this is and call the right
        // function, giving it the stuff we've figured out above
        $name = (get_class($media) == 'Song') ? 'song' : 'video';

        $function = 'update_' . $name . '_from_tags';

        $return = call_user_func(array('Catalog',$function),$results,$media);

        return $return;

    } // update_media_from_tags

    /**
     * update_video_from_tags
     * Updates the video info based on tags
     */
    public static function update_video_from_tags($results,$video) {

        // Pretty sweet function here
        return $results;

    } // update_video_from_tags

    /**
     * update_song_from_tags
     * Updates the song info based on tags; this is called from a bunch of
     * different places and passes in a full fledged song object, so it's a
     * static function.
     * FIXME: This is an ugly mess, this really needs to be consolidated and
     * cleaned up.
     */
    public static function update_song_from_tags($results,$song) {

        /* Setup the vars */
        $new_song         = new Song();
        $new_song->file        = $results['file'];
        $new_song->title    = $results['title'];
        $new_song->year        = $results['year'];
        $new_song->comment    = $results['comment'];
        $new_song->language    = $results['language'];
        $new_song->lyrics    = $results['lyrics'];
        $new_song->bitrate    = $results['bitrate'];
        $new_song->rate        = $results['rate'];
        $new_song->mode        = ($results['mode'] == 'cbr') ? 'cbr' : 'vbr';
        $new_song->size        = $results['size'];
        $new_song->time        = $results['time'];
        $new_song->mime        = $results['mime'];
        $new_song->track    = intval($results['track']);
        $new_song->mbid        = $results['mb_trackid'];
        $artist            = $results['artist'];
        $artist_mbid        = $results['mb_artistid'];
        $album            = $results['album'];
        $album_mbid        = $results['mb_albumid'];
        $disk            = $results['disk'];
        $tags            = $results['genre'];    // multiple genre support makes this an array

        /*
        * We have the artist/genre/album name need to check it in the tables
        * If found then add & return id, else return id
        */
        $new_song->artist = Artist::check($artist, $artist_mbid);
        $new_song->f_artist = $artist;
        $new_song->album = Album::check($album, $new_song->year, $disk, $album_mbid);
        $new_song->f_album = $album . " - " . $new_song->year;
        $new_song->title = self::check_title($new_song->title,$new_song->file);

        // Nothing to assign here this is a multi-value doodly
        // multiple genre support
        foreach ($tags as $tag) {
            $tag = trim($tag);
            //self::check_tag($tag,$song->id);
            //self::check_tag($tag,$new_song->album,'album');
            //self::check_tag($tag,$new_song->artist,'artist');
        }

        /* Since we're doing a full compare make sure we fill the extended information */
        $song->fill_ext_info();

        $info = Song::compare_song_information($song,$new_song);

        if ($info['change']) {
            debug_event('update', "$song->file : differences found, updating database", 5);
            $song->update_song($song->id,$new_song);
            // Refine our reference
            $song = $new_song;
        }
        else {
            debug_event('update', "$song->file : no differences found", 5);
        }

        return $info;

    } // update_song_from_tags

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     */
    public function add_to_catalog() {

        if ($this->catalog_type == 'remote') {
            UI::show_box_top(T_('Running Remote Update') . '. . .');
            $this->update_remote_catalog();
            UI::show_box_bottom();
            return true;
        }

        require Config::get('prefix') . '/templates/show_adds_catalog.inc.php';
        flush();

        /* Set the Start time */
        $start_time = time();

        // Make sure the path doesn't end in a / or \
        $this->path = rtrim($this->path,'/');
        $this->path = rtrim($this->path,'\\');

        // Prevent the script from timing out and flush what we've got
        set_time_limit(0);

        /* Get the songs and then insert them into the db */
        $this->add_files($this->path,$type,0,$verbose);

        // Foreach Playlists we found
        foreach ($this->_playlists as $full_file) {
            $result = $this->import_m3u($full_file);
            if ($result['success']) {
                $file = basename($full_file);
                if ($verbose) {
                    echo "&nbsp;&nbsp;&nbsp;" . T_('Added Playlist From') . " $file . . . .<br />\n";
                    flush();
                }
            } // end if import worked
        } // end foreach playlist files

        /* Do a little stats mojo here */
        $current_time = time();

        $catalog_id = $this->id;
        require Config::get('prefix') . '/templates/show_gather_art.inc.php';
        flush();
        $this->gather_art();

        /* Update the Catalog last_update */
        $this->update_last_add();

        $time_diff = ($current_time - $start_time) ?: 0;
        $rate = intval($this->count / $time_diff) ?: T_('N/A');

        UI::show_box_top();
        echo "\n<br />" .
        printf(T_('Catalog Update Finished.  Total Time: [%s] Total Songs: [%s] Songs Per Second: [%s]'),
            date('i:s', $time_diff), $this->count, $rate);
        echo '<br /><br />';
        UI::show_box_bottom();

    } // add_to_catalog

    /**
     * connect
     *
     * Connects to the remote catalog that we are.
     */
    public function connect() {
        try {
            $remote_handle = new AmpacheApi(array(
                'username' => $this->remote_username,
                'password' => $this->remote_password,
                'server' => $this->path,
                'debug_callback' => 'debug_event'
            ));
        } catch (Exception $e) {
            Error::add('general', $e->getMessage());
            Error::display('general');
            flush();
            return false;
        }

        if ($remote_handle->state() != 'CONNECTED') {
            debug_event('catalog', 'API client failed to connect', 1);
            Error::add('general', T_('Error connecting to remote server'));
            Error::display('general');
            return false;
        }

        return $remote_handle; 
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog($type = 0) {
        $remote_handle = $this->connect();
        if (!$remote_handle) {
            return false;
        }

        // Get the song count, etc.
        $remote_catalog_info = $remote_handle->info();

        // Tell 'em what we've found, Johnny!
        printf(T_('%u remote catalog(s) found (%u songs)'), $remote_catalog_info['catalogs'], $remote_catalog_info['songs']);
        flush();

        // Hardcoded for now
        $step = 500;
        $current = 0;
        $total = $remote_catalog_info['songs'];

        while ($total > $current) {
            $start = $current;
            $current += $step;
            try {
                $songs = $remote_handle->send_command('songs', array('offset' => $start, 'limit' => $step));
            }
            catch (Exception $e) {
                Error::add('general',$e->getMessage());
                Error::display('general');
                flush();
            }

            // Iterate over the songs we retrieved and insert them
            foreach ($songs as $data) {
                if ($this->check_remote_song($data['song'])) {
                    debug_event('remote_catalog', 'Skipping existing song ' . $data['song']['url'], 5);
                }
                else {
                    $data['song']['catalog'] = $this->id;
                    $data['song']['file'] = preg_replace('/ssid=.*?&/', '', $data['song']['url']);
                    if (!Song::insert($data['song'])) {
                        debug_event('remote_catalog', 'Insert failed for ' . $data['song']['self']['id'], 1);
                        Error::add('general', T_('Unable to Insert Song - %s'), $data['song']['title']);
                        Error::display('general');
                        flush();
                    }
                }
            }
        } // end while

        echo "<p>" . T_('Completed updating remote catalog(s).') . "</p><hr />\n";
        flush();

        // Update the last update value
        $this->update_last_update();

        return true;

    }

    /**
     * clean_remote_catalog
     * 
     * Removes remote songs that no longer exist.
     */
    private function clean_remote_catalog() {
        $remote_handle = $this->connect();
        if (!$remote_handle) {
            debug_event('remote-clean', 'Remote login failed', 1, 'ampache-catalog');
            return false;
        }

        $dead = 0;

        $sql = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('remote-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
            try {
                $song = $remote_handle->send_command('url_to_song', array('url' => $row['file']));
            }
            catch (Exception $e) {
                // FIXME: What to do, what to do
            }
           
            if (count($song) == 1) {
                debug_event('remote-clean', 'keeping song', 5, 'ampache-catalog');
            }
            else {
                debug_event('remote-clean', 'removing song', 5, 'ampache-catalog');
                $dead++;
                Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
            }
        }

        return $dead;
    }

    /**
     * clean_local_catalog
     *
     * Removes local songs that no longer exist.
     */
     private function clean_local_catalog() {
        if (!Core::is_readable($this->path)) {
            // First sanity check; no point in proceeding with an unreadable
            // catalog root.
            debug_event('catalog', 'Catalog path:' . $this->path . ' unreadable, clean failed', 1);
            Error::add('general', T_('Catalog Root unreadable, stopping clean'));
            Error::display('general');
            return 0;
        }

        $dead_total = 0;
        $stats = self::get_stats($this->id);
        foreach(array('video', 'song') as $media_type) {
            $total = $stats[$media_type . 's']; // UGLY
            if ($total == 0) {
                continue;
            }
            $chunks = floor($total / 10000);
            $dead = array();
            foreach(range(0, $chunks) as $chunk) {
                $dead = array_merge($dead, $this->_clean_chunk($media_type, $chunk, 10000));
            }

            $dead_count = count($dead);
            // The AlmightyOatmeal sanity check
            // Never remove everything; it might be a dead mount
            if ($dead_count >= $total) {
                debug_event('catalog', 'All files would be removed. Doing nothing.', 1);
                Error::add('general', T_('All files would be removed. Doing nothing'));
                continue;
            }
            if ($dead_count) {
                $dead_total += $dead_count;
                $sql = "DELETE FROM `$media_type` WHERE `id` IN " .
                    '(' . implode(',',$dead) . ')';
                $db_results = Dba::write($sql);
            }
        }
        return $dead_total;
    }

    /**
     * clean_catalog
     *
     * Cleans the catalog of files that no longer exist.
     */
    public function clean_catalog() {

        // We don't want to run out of time
        set_time_limit(0);

        debug_event('clean', 'Starting on ' . $this->name, 5);

        require_once Config::get('prefix') . '/templates/show_clean_catalog.inc.php';
        ob_flush();
        flush();

        if ($this->catalog_type == 'remote') {
            $dead_total = $this->clean_remote_catalog();
        }
        else {
            $dead_total = $this->clean_local_catalog();
        }

        debug_event('clean', 'clean finished, ' . $dead_count .
            ' removed from '. $this->name, 5);

        // Remove any orphaned artists/albums/etc.
        self::gc();

        UI::show_box_top();
        echo "<strong>";
        printf (T_ngettext('Catalog Clean Done. %d file removed.', 'Catalog Clean Done. %d files removed.', $dead_total), $dead_total);
        echo "</strong><br />\n";
        UI::show_box_bottom();
        ob_flush();
        flush();

        $this->update_last_clean();
    } // clean_catalog


    /**
     * _clean_chunk
     * This is the clean function, its broken into
     * said chunks to try to save a little memory
     */
    private function _clean_chunk($media_type, $chunk, $chunk_size) {
        debug_event('clean', "Starting chunk $chunk", 5);
        $dead = array();
        $count = $chunk * $chunk_size;

        $sql = "SELECT `id`, `file` FROM `$media_type` " .
            "WHERE `catalog`='$this->id' LIMIT $count,$chunk_size";
        $db_results = Dba::read($sql);

        while ($results = Dba::fetch_assoc($db_results)) {
            debug_event('clean', 'Starting work on ' . $results['file'] . '(' . $results['id'] . ')', 5);
            $count++;
            if (UI::check_ticker()) {
                $file = str_replace(array('(',')', '\''), '', $results['file']);
                UI::update_text('clean_count_' . $this->id, $count);
                UI::update_text('clean_dir_' . $this->id, scrub_out($file));
            }
            $file_info = filesize($results['file']);
            if (!file_exists($results['file']) || $file_info < 1) {
                debug_event('clean', 'File not found or empty: ' . $results['file'], 5);
                Error::add('general', sprintf(T_('Error File Not Found or 0 Bytes: %s'), $results['file']));


                // Store it in an array we'll delete it later...
                $dead[] = $results['id'];

            } //if error
            else if (!Core::is_readable($results['file'])) {
                debug_event('clean', $results['file'] . ' is not readable, but does exist', 1);
            }
        }
        return $dead;

    } //_clean_chunk

    /**
     * verify_catalog
     * This function compares the DB's information with the ID3 tags
     */
    public function verify_catalog() {

        debug_event('verify', 'Starting on ' . $this->name, 5);
        set_time_limit(0);

        $stats = self::get_stats($this->id);
        $number = $stats['videos'] + $stats['songs'];
        $total_updated = 0;

        require_once Config::get('prefix') . '/templates/show_verify_catalog.inc.php';

        foreach(array('video', 'song') as $media_type) {
            $total = $stats[$media_type . 's']; // UGLY
            if ($total == 0) {
                continue;
            }
            $chunks = floor($total / 10000);
            foreach(range(0, $chunks) as $chunk) {
                // Try to be nice about memory usage
                if ($chunk > 0) {
                    $media_type::clear_cache();
                }
                $total_updated += $this->_verify_chunk($media_type, $chunk, 10000);
            }
        }

        debug_event('verify', "Finished, $total_updated updated in " . $this->name, 5);

        self::gc();
        $this->update_last_update();

        UI::show_box_top();
        echo '<strong>';
        printf(T_('Catalog Verify Done. %d of %d files updated.'), $total_updated, $number);
        echo "</strong><br />\n";
        UI::show_box_bottom();
        ob_flush();
        flush();

        return true;

    } // verify_catalog

    /**
     * _verify_chunk
     * This verifies a chunk of the catalog, done to save
     * memory
     */
    private function _verify_chunk($media_type, $chunk, $chunk_size) {
        debug_event('verify', "Starting chunk $chunk", 5);
        $count = $chunk * $chunk_size;
        $changed = 0;

        $sql = "SELECT `id`, `file` FROM `$media_type` " .
            "WHERE `catalog`='$this->id' LIMIT $count,$chunk_size";
        $db_results = Dba::read($sql);

        if (Config::get('memory_cache')) {
            while ($row = Dba::fetch_assoc($db_results, false)) {
                $media_ids[] = $row['id'];
            }
            $media_type::build_cache($media_ids);
            $db_results = Dba::read($sql);
        }

        while ($row = Dba::fetch_assoc($db_results)) {
            $count++;
            if (UI::check_ticker()) {
                $file = str_replace(array('(',')','\''), '', $row['file']);
                UI::update_text('verify_count_' . $this->id, $count);
                UI::update_text('verify_dir_' . $this->id, scrub_out($file));
            }

            if (!Core::is_readable($row['file'])) {
                Error::add('general', sprintf(T_('%s does not exist or is not readable'), $row['file']));
                debug_event('read', $row['file'] . ' does not exist or is not readable', 5);
                continue;
            }

            $media = new $media_type($row['id']);

            if (Flag::has_flag($media->id, $type)) {
                debug_event('verify', "$media->file is flagged, skipping", 5);
                continue;
            }

            $info = self::update_media_from_tags($media, $this->sort_pattern,$this->rename_pattern);
            if ($info['change']) {
                $changed++;
            }
            unset($info);
        }

        UI::update_text('verify_count_' . $this->id, $count);
        return $changed;

    } // _verfiy_chunk

    /**
     * gc
     *
     * This is a wrapper function for all of the different cleaning
     * functions, it runs them in an order that resembles correctness.
     */
    public static function gc() {

        debug_event('catalog', 'Database cleanup started', 5);
        Song::gc();
        Album::gc();
        Artist::gc();
        Art::gc();
        Flag::gc();
        Stats::gc();
        Rating::gc();
        Playlist::gc();
        Tmp_Playlist::gc();
        Shoutbox::gc();
        Tag::gc();
        debug_event('catalog', 'Database cleanup ended', 5);

    }

    /**
     * trim_prefix
     * Splits the prefix from the string
     */
    public static function trim_prefix($string) {
        $prefix_pattern = '/^(' . implode('\\s|',explode('|',Config::get('catalog_prefix_pattern'))) . '\\s)(.*)/i';
        preg_match($prefix_pattern, $string, $matches);

        if (count($matches)) {
            $string = trim($matches[2]);
            $prefix = trim($matches[1]);
        }
        else {
            $prefix = null;
        }

        return array('string' => $string, 'prefix' => $prefix);
    } // trim_prefix

    /**
     * check_title
     * this checks to make sure something is
     * set on the title, if it isn't it looks at the
     * filename and trys to set the title based on that
     */
    public static function check_title($title,$file=0) {

        if (strlen(trim($title)) < 1) {
            $title = Dba::escape($file);
        }

        return $title;

    } // check_title

    /**
     * _insert_local_song
     *
     * Insert a song that isn't already in the database.
     */
    private function _insert_local_song($file, $file_info) {
        $vainfo = new vainfo($file, '', '', '', $this->sort_pattern, $this->rename_pattern);
        $vainfo->get_info();

        $key = vainfo::get_tag_type($vainfo->tags);
        $results = vainfo::clean_tag_info($vainfo->tags, $key, $file);

        $results['catalog'] = $this->id;
        return Song::insert($results);
    }

    /**
     * insert_local_video
     * This inserts a video file into the video file table the tag
     * information we can get is super sketchy so it's kind of a crap shoot
     * here
     */
    public function insert_local_video($file,$filesize) {

        /* Create the vainfo object and get info */
        $vainfo     = new vainfo($file,'','','',$this->sort_pattern,$this->rename_pattern);
        $vainfo->get_info();

        $tag_name = vainfo::get_tag_type($vainfo->tags);
        $results = vainfo::clean_tag_info($vainfo->tags,$tag_name,$file);


        $file         = Dba::escape($file);
        $catalog_id     = Dba::escape($this->id);
        $title         = Dba::escape($results['title']);
        $vcodec     = $results['video_codec'];
        $acodec     = $results['audio_codec'];
        $rezx         = intval($results['resolution_x']);
        $rezy         = intval($results['resolution_y']);
        $filesize     = Dba::escape($filesize);
        $time         = Dba::escape($results['time']);
        $mime        = Dba::escape($results['mime']);
        // UNUSED CURRENTLY
        $comment    = Dba::escape($results['comment']);
        $year        = Dba::escape($results['year']);
        $disk        = Dba::escape($results['disk']);

        $sql = "INSERT INTO `video` (`file`,`catalog`,`title`,`video_codec`,`audio_codec`,`resolution_x`,`resolution_y`,`size`,`time`,`mime`) " .
            " VALUES ('$file','$catalog_id','$title','$vcodec','$acodec','$rezx','$rezy','$filesize','$time','$mime')";
        $db_results = Dba::write($sql);

        return true;

    } // insert_local_video

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it find a song it returns the UID
     */
    public function check_remote_song($song) {
        $url = preg_replace('/ssid=.*&/', '', $song['url']);

        $sql = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['id'];
        }

        return false;
    }

    /**
     * check_local_mp3
     * Checks the song to see if it's there already returns true if found, false if not
     */
    public function check_local_mp3($full_file, $gather_type='') {

        $file_date = filemtime($full_file);
        if ($file_date < $this->last_add) {
            debug_event('Check','Skipping ' . $full_file . ' File modify time before last add run','3');
            return true;
        }

        $full_file = Dba::escape($full_file);

        $sql = "SELECT `id` FROM `song` WHERE `file` = '$full_file'";
        $db_results = Dba::read($sql);

        //If it's found then return true
        if (Dba::fetch_row($db_results)) {
            return true;
        }

        return false;

    } //check_local_mp3

    /**
     * import_m3u
     * this takes m3u filename and then attempts to create a Public Playlist based on the filenames
     * listed in the m3u
     */
    public function import_m3u($filename) {

        $m3u_handle = fopen($filename,'r');

        $data = fread($m3u_handle,filesize($filename));

        $results = explode("\n",$data);

        $pattern = '/\.(' . Config::get('catalog_file_pattern') . ')$/i';

        // Foreach what we're able to pull out from the file
        foreach ($results as $value) {

            // Remove extra whitespace
            $value = trim($value);
            if (preg_match($pattern,$value)) {

                /* Translate from \ to / so basename works */
                $value = str_replace("\\","/",$value);
                $file = basename($value);

                /* Search for this filename, cause it's a audio file */
                $sql = "SELECT `id` FROM `song` WHERE `file` LIKE '%" . Dba::escape($file) . "'";
                $db_results = Dba::read($sql);
                $results = Dba::fetch_assoc($db_results);

                if (isset($results['id'])) { $songs[] = $results['id']; }

            } // if it's a file
            // Check to see if it's a url from this ampache instance
            elseif (substr($value, 0, strlen(Config::get('web_path'))) == Config::get('web_path')) {
                $data = Stream_URL::parse($value);
                $sql = 'SELECT COUNT(*) FROM `song` WHERE `id` = ?';
                $db_results = Dba::read($sql, array($data['id']));

                if (Dba::num_rows($db_results)) {
                    $songs[] = $song_id;
                }

            } // end if it's an http url

        } // end foreach line

        debug_event('m3u_parse', "Parsed $filename, found " . count($songs) . " songs", 5);

        if (count($songs)) {
            $name = "M3U - " . basename($filename,'.m3u');
            $playlist_id = Playlist::create($name,'public');

            if (!$playlist_id) {
                return array(
                    'success' => false,
                    'error' => 'Failed to create playlist.',
                );
            }

            /* Recreate the Playlist */
            $playlist = new Playlist($playlist_id);
            $playlist->add_songs($songs, true);

            return array(
                'success' => true,
                'id' => $playlist_id,
                'count' => count($songs)
            );
        }

        return array(
            'success' => false,
            'error' => 'No valid songs found in M3U.'
        );

    } // import_m3u

    /**
     * delete
     * Deletes the catalog and everything associated with it
     * it takes the catalog id
     */
    public static function delete($catalog_id) {

        $catalog_id = Dba::escape($catalog_id);

        // First remove the songs in this catalog
        $sql = "DELETE FROM `song` WHERE `catalog` = '$catalog_id'";
        $db_results = Dba::write($sql);

        // Only if the previous one works do we go on
        if (!$db_results) { return false; }

        $sql = "DELETE FROM `video` WHERE `catalog` = '$catalog_id'";
        $db_results = Dba::write($sql);

        if (!$db_results) { return false; }

        // Next Remove the Catalog Entry it's self
        $sql = "DELETE FROM `catalog` WHERE `id` = '$catalog_id'";
        $db_results = Dba::write($sql);

        // Run the cleaners...
        self::gc();

    } // delete

    /**
     * exports the catalog
     * it exports all songs in the database to the given export type.
     */
    public function export($type) {

        // Select all songs in catalog
        if($this->id) {
            $sql = 'SELECT `id` FROM `song` ' .
                "WHERE `catalog`='$this->id' " .
                'ORDER BY `album`, `track`';
        }
        else {
            $sql = 'SELECT `id` FROM `song` ORDER BY `album`, `track`';
        }
        $db_results = Dba::read($sql);

        switch ($type) {
            case 'itunes':
                echo xml_get_header('itunes');
                while ($results = Dba::fetch_assoc($db_results)) {
                    $song = new Song($results['id']);
                    $song->format();

                    $xml = array();
                    $xml['key']= $results['id'];
                    $xml['dict']['Track ID']= intval($results['id']);
                    $xml['dict']['Name'] = $song->title;
                    $xml['dict']['Artist'] = $song->f_artist_full;
                    $xml['dict']['Album'] = $song->f_album_full;
                    $xml['dict']['Total Time'] = intval($song->time) * 1000; // iTunes uses milliseconds
                    $xml['dict']['Track Number'] = intval($song->track);
                    $xml['dict']['Year'] = intval($song->year);
                    $xml['dict']['Date Added'] = date("Y-m-d\TH:i:s\Z",$song->addition_time);
                    $xml['dict']['Bit Rate'] = intval($song->bitrate/1000);
                    $xml['dict']['Sample Rate'] = intval($song->rate);
                    $xml['dict']['Play Count'] = intval($song->played);
                    $xml['dict']['Track Type'] = "URL";
                    $xml['dict']['Location'] = Song::play_url($song->id);
                    echo xml_from_array($xml, 1, 'itunes');
                    // flush output buffer
                } // while result
                echo xml_get_footer('itunes');

                break;
            case 'csv':
                echo "ID,Title,Artist,Album,Length,Track,Year,Date Added,Bitrate,Played,File\n";
                while ($results = Dba::fetch_assoc($db_results)) {
                    $song = new Song($results['id']);
                    $song->format();
                    echo '"' . $song->id . '","' .
                        $song->title . '","' .
                        $song->f_artist_full . '","' .
                        $song->f_album_full .'","' .
                        $song->f_time . '","' .
                        $song->f_track . '","' .
                        $song->year .'","' .
                        date("Y-m-d\TH:i:s\Z", $song->addition_time) . '","' .
                        $song->f_bitrate .'","' .
                        $song->played . '","' .
                        $song->file . "\n";
                }
                break;
        } // end switch

    } // export

} // end of catalog class

?>
