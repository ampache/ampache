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
abstract class Catalog extends database_object
{
    public $name;
    public $last_update;
    public $last_add;
    public $last_clean;
    public $key;
    public $rename_pattern;
    public $sort_pattern;
    public $catalog_type;

    /* This is a private var that's used during catalog builds */
    protected $_playlists = array();

    // Cache all files in catalog for quick lookup during add
    protected $_filecache = array();

    // Used in functions
    protected static $albums    = array();
    protected static $artists    = array();
    protected static $tags    = array();

    abstract public function get_type();
    abstract public function get_description();
    abstract public function get_version();
    abstract public function get_create_help();
    abstract public function is_installed();
    abstract public function install();
    abstract public function add_to_catalog($options = null);
    abstract public function verify_catalog_proc();
    abstract public function clean_catalog_proc();
    abstract public function catalog_fields();
    abstract public function get_rel_path($file_path);
    abstract public function prepare_media($media);

        /**
     * uninstall
     * This removes the remote catalog
     */
    public function uninstall()
    {
        $sql = "DELETE FROM `catalog` WHERE `catalog_type` = ?";
        $db_results = Dba::query($sql, array($this->get_type()));

        $sql = "DROP TABLE `catalog_" . $this->get_type() ."`";
        $db_results = Dba::query($sql);

        return true;

    } // uninstall

    public static function create_from_id($id)
    {
        $sql = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($id));
        if ($results = Dba::fetch_assoc($db_results)) {
            return self::create_catalog_type($results['catalog_type'], $id);
        }

        return null;
    }

    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     * all Catalog modules should be located in /modules/catalog/<name>.class.php
     */
    public static function create_catalog_type($type, $id=0)
    {
        if (!$type) { return false; }

        $filename = AmpConfig::get('prefix') . '/modules/catalog/' . $type . '.catalog.php';
        $include = require_once $filename;

        if (!$include) {
            /* Throw Error Here */
            debug_event('catalog', 'Unable to load ' . $type . ' catalog type', '2');
            return false;
        } // include
        else {
            $class_name = "Catalog_" . $type;
            if ($id > 0) {
                $catalog = new $class_name($id);
            } else {
                $catalog = new $class_name();
            }
            if (!($catalog instanceof Catalog)) {
                debug_event('catalog', $type . ' not an instance of Catalog abstract, unable to load', '1');
                return false;
            }
            return $catalog;
        }

    } // create_catalog_type

    public static function show_catalog_types($divback = 'catalog_type_fields')
    {
        echo "<script language=\"javascript\" type=\"text/javascript\">" .
            "var type_fields = new Array();" .
            "type_fields['none'] = '';";
        $seltypes = '<option value="none">[Select]</option>';
        $types = self::get_catalog_types();
        foreach ($types as $type) {
            $catalog = self::create_catalog_type($type);
            if ($catalog->is_installed()) {
                $seltypes .= '<option value="' . $type . '">' . $type . '</option>';
                echo "type_fields['" . $type . "'] = \"";
                $fields = $catalog->catalog_fields();
                $help = $catalog->get_create_help();
                if (!empty($help)) {
                    echo "<tr><td></td><td>" . $help . "</td></tr>";
                }
                foreach ($fields as $key=>$field) {
                    echo "<tr><td style='width: 25%;'>" . $field['description'] . ":</td><td>";

                    switch ($field['type']) {
                        case 'checkbox':
                            echo "<input type='checkbox' name='" . $key . "' value='1' " . (($field['value']) ? 'checked' : '') . "/>";
                            break;
                        case 'password':
                            echo "<input type='password' name='" . $key . "' value='" . $field['value'] . "' />";
                            break;
                        default:
                            echo "<input type='text' name='" . $key . "' value='" . $field['value'] . "' />";
                            break;
                    }
                    echo "</td></tr>";
                }
                echo "\";";
            }
        }

        echo "function catalogTypeChanged() {" .
            "var sel = document.getElementById('catalog_type');" .
            "var seltype = sel.options[sel.selectedIndex].value;" .
            "var ftbl = document.getElementById('" . $divback . "');" .
            "ftbl.innerHTML = '<table class=\"tabledata\" cellpadding=\"0\" cellspacing=\"0\">' + type_fields[seltype] + '</table>';" .
            "} </script>" .
            "<select name=\"type\" id=\"catalog_type\" onChange=\"catalogTypeChanged();\">" . $seltypes . "</select>";
    }

    /**
     * get_catalog_types
     * This returns the catalog types that are available
     */
    public static function get_catalog_types()
    {
        /* First open the dir */
        $handle = opendir(AmpConfig::get('prefix') . '/modules/catalog');

        if (!is_resource($handle)) {
            debug_event('catalog', 'Error: Unable to read catalog types directory', '1');
            return array();
        }

        $results = array();

        while ($file = readdir($handle)) {

            if (substr($file, -11, 11) != 'catalog.php') { continue; }

            /* Make sure it isn't a dir */
            if (!is_dir($file)) {
                /* Get the basename and then everything before catalog */
                $filename = basename($file, '.catalog.php');
                $results[] = $filename;
            }
        } // end while

        return $results;

    } // get_catalog_types

    public static function is_audio_file($file)
    {
        $pattern = "/\.(" . AmpConfig::get('catalog_file_pattern') . ")$/i";
        $match = preg_match($pattern, $file);
        
        return $match;
    }

    public static function is_video_file($file)
    {
        $video_pattern = "/\.(" . AmpConfig::get('catalog_video_pattern') . ")$/i";
        return preg_match($video_pattern, $file);
    }
    
    public static function is_playlist_file($file)
    {
        $playlist_pattern = "/\.(" . AmpConfig::get('catalog_playlist_pattern') . ")$/i";
        return preg_match($playlist_pattern, $file);
    }

    public function get_info($id, $table = 'catalog')
    {
        $info = parent::get_info($id, $table);

        $table = 'catalog_' . $this->get_type();
        $sql = "SELECT `id` FROM $table WHERE `catalog_id` = ?";
        $db_results = Dba::read($sql, array($id));

        if ($results = Dba::fetch_assoc($db_results)) {

            $info_type = parent::get_info($results['id'], $table);
            foreach ($info_type as $key => $value) {
                if (!$info[$key]) {
                    $info[$key] = $value;
                }
            }
        }

        return $info;
    }

    public static function get_enable_filter($type, $id)
    {
        if ($type == "song" || $type == "album" || $type == "artist") {
            if ($type == "song") $type = "id";
            $sql = "(SELECT COUNT(`song_dis`.`id`) FROM `song` AS `song_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `song_dis`.`catalog` " .
                "WHERE `song_dis`.`" . $type . "`=" . $id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `song_dis`.`" . $type . "`) > 0";
        } else if ($type == "video") {
            $sql = "(SELECT COUNT(`video_dis`.`id`) FROM `video` AS `video_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `video_dis`.`catalog` " .
                "WHERE `video_dis`.`id`=" . $id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `video_dis`.`id`) > 0";
        }

        return $sql;
    }

    /**
     * _create_filecache
     *
     * This populates an array which is used to speed up the add process.
     */
    protected function _create_filecache()
    {
        if (count($this->_filecache) == 0) {
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
     * update_enabled
     * sets the enabled flag
     */
    public static function update_enabled($new_enabled, $catalog_id)
    {
        self::_update_item('enabled', $new_enabled, $catalog_id, '75');

    } // update_enabled

    /**
     * _update_item
     * This is a private function that should only be called from within the catalog class.
     * It takes a field, value, catalog id and level. first and foremost it checks the level
     * against $GLOBALS['user'] to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     */
    private static function _update_item($field, $value, $catalog_id, $level)
    {
        /* Check them Rights! */
        if (!Access::check('interface', $level)) { return false; }

        /* Can't update to blank */
        if (!strlen(trim($value))) { return false; }

        $value = Dba::escape($value);

        $sql = "UPDATE `catalog` SET `$field`='$value' WHERE `id`='$catalog_id'";
        $db_results = Dba::write($sql);

        return true;

    } // _update_item

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        $this->f_name = $this->name;
        $this->f_name_link = '<a href="' . AmpConfig::get('web_path') .
            '/admin/catalog.php?action=show_customize_catalog&catalog_id=' .
            $this->id . '" title="' . scrub_out($this->name) . '">' .
            scrub_out($this->f_name) . '</a>';
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
    public static function get_catalogs()
    {
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
    public static function get_stats($catalog_id = null)
    {
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
     * This creates a new catalog entry and associate it to current instance
     */
    public static function create($data)
    {
        $name = $data['name'];
        $type = $data['type'];
        $rename_pattern = $data['rename_pattern'];
        $sort_pattern = $data['sort_pattern'];

        $filename = AmpConfig::get('prefix') . '/modules/catalog/' . $type . '.catalog.php';
        $include = require_once $filename;

        if ($include) {
            $sql = 'INSERT INTO `catalog` (`name`, `catalog_type`, ' .
                '`rename_pattern`, `sort_pattern`) VALUES (?, ?, ?, ?)';
            $db_results = Dba::write($sql, array(
                $name,
                $type,
                $rename_pattern,
                $sort_pattern
            ));

            $insert_id = Dba::insert_id();

            if (!$insert_id) {
                Error::add('general', T_('Catalog Insert Failed check debug logs'));
                debug_event('catalog', 'Insert failed: ' . json_encode($data), 2);
                return false;
            }

            $classname = 'Catalog_' . $type;
            if (!$classname::create_type($insert_id, $data)) {
                $sql = 'DELETE FROM `catalog` WHERE `id` = ?';
                Dba::write($sql, array($insert_id));
                $insert_id = 0;
            }
        }

        return $insert_id;
    }

    /**
     * count_videos
     *
     * This returns the current number of video files in the database.
     */
    public static function count_videos($id = null)
    {
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
    public static function count_tags($id = null)
    {
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
    public static function count_songs($id = null)
    {
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
     * get_album_ids
     *
     * This returns an array of ids of albums that have songs in this
     * catalog
     */
    public function get_album_ids()
    {
        $results = array();

        $sql = 'SELECT DISTINCT(`song`.`album`) FROM `song` WHERE `song`.`catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['album'];
        }

        return $results;
    }

    /**
    * get_artist
    *
    * This returns an array of ids of artists that have songs in the catalogs parameter
    */
    public static function get_artists($catalogs = null)
    {
        if (is_array($catalogs) && count($catalogs)) {
            $catlist = '(' . implode(',', $catalogs) . ')';
            $sql_where = "WHERE `song`.`catalog` IN $catlist";
        }

        $sql = "SELECT `artist`.id, `artist`.`name`, `artist`.`summary` FROM `song` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` $sql_where GROUP BY `song`.artist ORDER BY `artist`.`name`";

        $results = array();
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = Artist::construct_from_array($r);
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
    public function gather_art()
    {
        // Make sure they've actually got methods
        $art_order = AmpConfig::get('art_order');
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
                    if (AmpConfig::get('resize_images')) {
                        $thumb = $art->generate_thumb($image, array(
                                'width' => 275,
                                'height' => 275),
                            $results[0]['mime']);
                        if (is_array($thumb)) {
                            $art->save_thumb($thumb['thumb'], $thumb['thumb_mime'], '275x275');
                        }
                    }

                } else {
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
    public function get_songs()
    {
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
    public function dump_album_art($methods = array())
    {
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
            $preferred_filename = AmpConfig::get('album_art_preferred_filename');
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
                } else {
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
    protected function update_last_update()
    {
        $date = time();
        $sql = "UPDATE `catalog` SET `last_update` = ? WHERE `id` = ?";
        $db_results = Dba::write($sql, array($date, $this->id));

    } // update_last_update

    /**
     * update_last_add
     * updates the last_add of the catalog
     */
    public function update_last_add()
    {
        $date = time();
        $sql = "UPDATE `catalog` SET `last_add` = ? WHERE `id` = ?";
        $db_results = Dba::write($sql, array($date, $this->id));

    } // update_last_add

    /**
     * update_last_clean
     * This updates the last clean information
     */
    public function update_last_clean()
    {
        $date = time();
        $sql = "UPDATE `catalog` SET `last_clean` = ? WHERE `id` = ?";
        $db_results = Dba::write($sql, array($date, $this->id));

    } // update_last_clean

    /**
     * update_settings
     * This function updates the basic setting of the catalog
     */
    public static function update_settings($data)
    {
        $sql = "UPDATE `catalog` SET `name` = ?, `rename_pattern` = ?, `sort_pattern` = ? WHERE `id` = ?";
        $params = array($data['name'], $data['rename_pattern'], $data['sort_pattern'], $data['catalog_id']);
        $db_results = Dba::write($sql, $params);

        return true;

    } // update_settings

    /**
     * update_single_item
     * updates a single album,artist,song from the tag data
     * this can be done by 75+
     */
    public static function update_single_item($type,$id)
    {
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

        foreach ($songs as $song_id) {
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
    public static function update_media_from_tags($media, $sort_pattern='', $rename_pattern='')
    {
        // Check for patterns
        if (!$sort_pattern OR !$rename_pattern) {
            $catalog = Catalog::create_from_id($media->catalog);
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
    public static function update_video_from_tags($results,$video)
    {
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
    public static function update_song_from_tags($results,$song)
    {
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
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim($tag);
                //self::check_tag($tag,$song->id);
                //self::check_tag($tag,$new_song->album,'album');
                //self::check_tag($tag,$new_song->artist,'artist');
            }
        }

        /* Since we're doing a full compare make sure we fill the extended information */
        $song->fill_ext_info();

        $info = Song::compare_song_information($song,$new_song);

        if ($info['change']) {
            debug_event('update', "$song->file : differences found, updating database", 5);
            $song->update_song($song->id,$new_song);
            // Refine our reference
            $song = $new_song;
        } else {
            debug_event('update', "$song->file : no differences found", 5);
        }

        return $info;

    } // update_song_from_tags

    /**
     * clean_catalog
     *
     * Cleans the catalog of files that no longer exist.
     */
    public function clean_catalog()
    {
        // We don't want to run out of time
        set_time_limit(0);

        debug_event('clean', 'Starting on ' . $this->name, 5);

        require_once AmpConfig::get('prefix') . '/templates/show_clean_catalog.inc.php';
        ob_flush();
        flush();

        $dead_total = $this->clean_catalog_proc();

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
     * verify_catalog
     * This function verify the catalog
     */
    public function verify_catalog()
    {
        $verified = $this->verify_catalog_proc();+

        UI::show_box_top();
        echo '<strong>';
        printf(T_('Catalog Verify Done. %d of %d files updated.'), $verified['updated'], $verified['total']);
        echo "</strong><br />\n";
        UI::show_box_bottom();
        ob_flush();
        flush();

        return true;
    } // verify_catalog

    /**
     * gc
     *
     * This is a wrapper function for all of the different cleaning
     * functions, it runs them in an order that resembles correctness.
     */
    public static function gc()
    {
        debug_event('catalog', 'Database cleanup started', 5);
        Song::gc();
        Album::gc();
        Artist::gc();
        Art::gc();
        Stats::gc();
        Rating::gc();
        Userflag::gc();
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
    public static function trim_prefix($string)
    {
        $prefix_pattern = '/^(' . implode('\\s|',explode('|',AmpConfig::get('catalog_prefix_pattern'))) . '\\s)(.*)/i';
        preg_match($prefix_pattern, $string, $matches);

        if (count($matches)) {
            $string = trim($matches[2]);
            $prefix = trim($matches[1]);
        } else {
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
    public static function check_title($title,$file=0)
    {
        if (strlen(trim($title)) < 1) {
            $title = Dba::escape($file);
        }

        return $title;

    } // check_title
    
    /**
     * playlist_import
     * Attempts to create a Public Playlist based on the playlist file
     */
    public static function import_playlist($playlist)
    {
        $data = file_get_contents($playlist);
        if (substr($playlist, -3,3) == 'm3u') {
            $files = self::parse_m3u($data);
        } elseif (substr($playlist, -3,3) == 'pls') {
            $files = self::parse_pls($data);
        } elseif (substr($playlist, -3,3) == 'asx') {
            $files = self::parse_asx($data);
        } elseif (substr($playlist, -4,4) == 'xspf') {
            $files = self::parse_xspf($data);
        }
        
        $songs = array();
        $pinfo = pathinfo($playlist);
        if ($files) {
            foreach ($files as $file) {
                $file = trim($file);
                // Check to see if it's a url from this ampache instance
                if (substr($value, 0, strlen(AmpConfig::get('web_path'))) == AmpConfig::get('web_path')) {
                    $data = Stream_URL::parse($value);
                    $sql = 'SELECT COUNT(*) FROM `song` WHERE `id` = ?';
                    $db_results = Dba::read($sql, array($data['id']));

                    if (Dba::num_rows($db_results)) {
                        $songs[] = $song_id;
                    }
                } // end if it's an http url
                else {
                    // Remove file:// prefix if any
                    if (strpos($file, "file://") !== false) {
                        $file = urldecode(substr($file, 7));
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            // Removing starting / on Windows OS.
                            if (substr($file, 0, 1) == '/') {
                                $file = substr($file, 1);
                            }
                            // Restore real directory separator
                            $file = str_replace("/", DIRECTORY_SEPARATOR, $file);
                        }
                    }
                    debug_event('catalog', 'Add file ' . $file . ' to playlist.', '5');
                
                    // First, try to found the file as absolute path
                    $sql = "SELECT `id` FROM `song` WHERE `file` = ?";
                    $db_results = Dba::read($sql, array($file));
                    $results = Dba::fetch_assoc($db_results);

                    if (isset($results['id'])) {
                        $songs[] = $results['id'];
                    } else {
                        // Not found in absolute path, create it from relative path
                        $finfo = pathinfo($file);
                        $file = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $file;
                        // Normalize the file path. realpath requires the files to exists.
                        $file = realpath($file);
                        if ($file) {
                            $sql = "SELECT `id` FROM `song` WHERE `file` = ?";
                            $db_results = Dba::read($sql, array($file));
                            $results = Dba::fetch_assoc($db_results);

                            if (isset($results['id'])) {
                                $songs[] = $results['id'];
                            }
                        }
                    }
                } // if it's a file
            }
        }
        
        debug_event('import_playlist', "Parsed " . $filename . ", found " . count($songs) . " songs", 5);
        
        if (count($songs)) {
            $name = $pinfo['extension'] . " - " . $pinfo['filename'];
            $playlist_id = Playlist::create($name, 'public');

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
            'error' => 'No valid songs found in playlist file.'
        );
    }

    /**
     * parse_m3u
     * this takes m3u filename and then attempts to found song filenames listed in the m3u
     */
    public static function parse_m3u($data)
    {
        $files = array();
        $results = explode("\n", $data);
        
        foreach ($results as $value) {
            $value = trim($value);
            if (!empty($value) && substr($value, 0, 1) != '#') {
                $files[] = $value;
            }
        }

        return $files;
    } // parse_m3u
    
    /**
     * parse_pls
     * this takes pls filename and then attempts to found song filenames listed in the pls
     */
    public static function parse_pls($data)
    {
        $files = array();
        $results = explode("\n", $data);
        
        foreach ($results as $value) {
            $value = trim($value);
            if (preg_match("/file[0-9]+[\s]*\=(.*)/i", $value, $matches)) {
                $file = trim($matches[1]);
                if (!empty($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    } // parse_pls
    
    /**
     * parse_asx
     * this takes asx filename and then attempts to found song filenames listed in the asx
     */
    public static function parse_asx($data)
    {
        $files = array();
        $xml = simplexml_load_string($data);
        
        if ($xml) {
            foreach ($xml->entry as $entry) {
                $file = trim($entry->ref['href']);
                if (!empty($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    } // parse_asx
    
    /**
     * parse_xspf
     * this takes xspf filename and then attempts to found song filenames listed in the xspf
     */
    public static function parse_xspf($data)
    {
        $files = array();
        $xml = simplexml_load_string($data);
        if ($xml) {
            foreach ($xml->trackList->track as $track) {
                $file = trim($track->location);
                if (!empty($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    } // parse_xspf

    /**
     * delete
     * Deletes the catalog and everything associated with it
     * it takes the catalog id
     */
    public static function delete($catalog_id)
    {
        // Large catalog deletion can take time
        set_time_limit(0);

        // First remove the songs in this catalog
        $sql = "DELETE FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, array($catalog_id));

        // Only if the previous one works do we go on
        if (!$db_results) { return false; }

        $sql = "DELETE FROM `video` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, array($catalog_id));

        if (!$db_results) { return false; }

        $catalog = self::create_from_id($catalog_id);

        $sql = 'DELETE FROM `catalog_' . $catalog->get_type() . '` WHERE catalog_id = ?';
        $db_results = Dba::write($sql, array($catalog_id));

        if (!$db_results) { return false; }

        // Next Remove the Catalog Entry it's self
        $sql = "DELETE FROM `catalog` WHERE `id` = ?";
        $db_results = Dba::write($sql, array($catalog_id));

        // Run the cleaners...
        self::gc();

    } // delete

    /**
     * exports the catalog
     * it exports all songs in the database to the given export type.
     */
    public static function export($type, $catalog_id = '')
    {
        // Select all songs in catalog
        $params = array();
        if ($catalog_id) {
            $sql = 'SELECT `id` FROM `song` ' .
                "WHERE `catalog`= ? " .
                'ORDER BY `album`, `track`';
            $params[] = $catalog_id;
        } else {
            $sql = 'SELECT `id` FROM `song` ORDER BY `album`, `track`';
        }
        $db_results = Dba::read($sql, $params);

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
                    echo xoutput_from_array($xml, 1, 'itunes');
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
