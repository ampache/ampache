<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

use Lib\Metadata\Repository\Metadata;
use Lib\Metadata\Repository\MetadataField;

/**
 * Catalog Class
 *
 * This class handles all actual work in regards to the catalog,
 * it contains functions for creating/listing/updated the catalogs.
 *
 */
abstract class Catalog extends database_object
{
    /**
     * @var integer $id
     */
    public $id;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var integer $last_update
     */
    public $last_update;
    /**
     * @var integer $last_add
     */
    public $last_add;
    /**
     * @var integer $last_clean
     */
    public $last_clean;
    /**
     * @var string $key
     */
    public $key;
    /**
     * @var string $rename_pattern
     */
    public $rename_pattern;
    /**
     * @var string $sort_pattern
     */
    public $sort_pattern;
    /**
     * @var string $catalog_type
     */
    public $catalog_type;
    /**
     * @var string $gather_types
     */
    public $gather_types;

    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var string $f_update
     */
    public $f_update;
    /**
     * @var string $f_add
     */
    public $f_add;
    /**
     * @var string $f_clean
     */
    public $f_clean;
    /**
     * alias for catalog paths, urls, etc etc
     * @var string $f_full_info
     */
    public $f_full_info;
    /**
     * alias for catalog paths, urls, etc etc
     * @var string $f_info
     */
    public $f_info;
    /**
     * @var integer $enabled
     */
    public $enabled;

    /**
     * This is a private var that's used during catalog builds
     * @var array $_playlists
     */
    protected $_playlists = array();

    /**
     * Cache all files in catalog for quick lookup during add
     * @var array $_filecache
     */
    protected $_filecache = array();

    // Used in functions
    /**
     * @var array $albums
     */
    protected static $albums    = array();
    /**
     * @var array $artists
     */
    protected static $artists    = array();
    /**
     * @var array $tags
     */
    protected static $tags    = array();

    /**
     * @return string
     */
    abstract public function get_type();
    /**
     * @return string
     */
    abstract public function get_description();
    /**
     * @return string
     */
    abstract public function get_version();
    /**
     * @return string
     */
    abstract public function get_create_help();
    /**
     * @return boolean
     */
    abstract public function is_installed();
    /**
     * @return boolean
     */
    abstract public function install();

    /**
     * @param array $options
     * @return mixed
     */
    abstract public function add_to_catalog($options = null);

    /**
     * @return mixed
     */
    abstract public function verify_catalog_proc();

    /**
     * @return mixed
     */
    abstract public function clean_catalog_proc();

    /**
     * @param string $new_path
     * @return boolean
     */
    abstract public function move_catalog_proc($new_path);

    /**
     * @return array
     */
    abstract public function catalog_fields();

    /**
     * @param string $file_path
     * @return string
     */
    abstract public function get_rel_path($file_path);

    /**
     * @param Song|Podcast_Episode|Song_Preview|Video $media
     * @return media|null
     */
    abstract public function prepare_media($media);

    /**
     * Check if the catalog is ready to perform actions (configuration completed, ...)
     * @return boolean
     */
    public function isReady()
    {
        return true;
    }

    /**
     * Show a message to make the catalog ready.
     */
    public function show_ready_process()
    {
        // Do nothing.
    }

    /**
     * Perform the last step process to make the catalog ready.
     */
    public function perform_ready()
    {
        // Do nothing.
    }

    /**
     * uninstall
     * This removes the remote catalog
     * @return boolean
     */
    public function uninstall()
    {
        $sql = "DELETE FROM `catalog` WHERE `catalog_type` = ?";
        Dba::query($sql, array($this->get_type()));

        $sql = "DROP TABLE `catalog_" . $this->get_type() . "`";
        Dba::query($sql);

        return true;
    } // uninstall

    /**
     * Create a catalog from its id.
     * @param integer $catalog_id
     * @return Catalog|null
     */
    public static function create_from_id($catalog_id)
    {
        $sql        = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($catalog_id));
        $results    = Dba::fetch_assoc($db_results);

        return self::create_catalog_type($results['catalog_type'], $catalog_id);
    }

    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     * all Catalog modules should be located in /modules/catalog/<name>/<name>.class.php
     * @param string $type
     * @param integer $catalog_id
     * @return Catalog|null
     */
    public static function create_catalog_type($type, $catalog_id = 0)
    {
        if (!$type) {
            return null;
        }

        $filename = AmpConfig::get('prefix') . '/modules/catalog/' . $type . '/' . $type . '.catalog.php';
        $include  = require_once $filename;

        if (!$include) {
            /* Throw Error Here */
            debug_event(self::class, 'Unable to load ' . $type . ' catalog type', 2);

            return null;
        } // include
        else {
            $class_name = "Catalog_" . $type;
            if ($catalog_id > 0) {
                $catalog = new $class_name($catalog_id);
            } else {
                $catalog = new $class_name();
            }
            if (!($catalog instanceof Catalog)) {
                debug_event(self::class, $type . ' not an instance of Catalog abstract, unable to load', 1);

                return null;
            }
            // identify if it's actually enabled
            $sql        = 'SELECT `enabled` FROM `catalog` WHERE `id` = ?';
            $db_results = Dba::read($sql, array($catalog->id));

            while ($results = Dba::fetch_assoc($db_results)) {
                $catalog->enabled = $results['enabled'];
            }

            return $catalog;
        }
    } // create_catalog_type

    /**
     * Show dropdown catalog types.
     * @param string $divback
     */
    public static function show_catalog_types($divback = 'catalog_type_fields')
    {
        echo '<script>' .
            "var type_fields = new Array();" .
            "type_fields['none'] = '';";
        $seltypes = '<option value="none">[' . T_("Select") . ']</option>';
        $types    = self::get_catalog_types();
        foreach ($types as $type) {
            $catalog = self::create_catalog_type($type);
            if ($catalog->is_installed()) {
                $seltypes .= '<option value="' . $type . '">' . $type . '</option>';
                echo "type_fields['" . $type . "'] = \"";
                $fields = $catalog->catalog_fields();
                $help   = $catalog->get_create_help();
                if (!empty($help)) {
                    echo "<tr><td></td><td>" . $help . "</td></tr>";
                }
                foreach ($fields as $key => $field) {
                    echo "<tr><td style='width: 25%;'>" . $field['description'] . ":</td><td>";

                    switch ($field['type']) {
                        case 'checkbox':
                            echo "<input type='checkbox' name='" . $key . "' value='1' " . (($field['value']) ? 'checked' : '') . "/>";
                            break;
                        default:
                            echo "<input type='" . $field['type'] . "' name='" . $key . "' value='" . $field['value'] . "' />";
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
            "ftbl.innerHTML = '<table class=\"tabledata\">' + type_fields[seltype] + '</table>';" .
            "} </script>" .
            "<select name=\"type\" id=\"catalog_type\" onChange=\"catalogTypeChanged();\">" . $seltypes . "</select>";
    }

    /**
     * create_catalog_path
     * This returns the catalog types that are available
     * @param string $path
     * @return boolean
     */
    public static function create_catalog_path($path)
    {
        if (!is_dir($path)) {
            if (mkdir($path) === false) {
                debug_event(self::class, 'Cannot create directory ' . $path, 2);

                return false;
            }
        }

        return true;
    }

    /**
     * get_catalog_types
     * This returns the catalog types that are available
     * @return string[]
     */
    public static function get_catalog_types()
    {
        /* First open the dir */
        $basedir = AmpConfig::get('prefix') . '/modules/catalog';
        $handle  = opendir($basedir);

        if (!is_resource($handle)) {
            debug_event(self::class, 'Error: Unable to read catalog types directory', 1);

            return array();
        }

        $results = array();

        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            /* Make sure it is a dir */
            if (! is_dir($basedir . '/' . $file)) {
                debug_event(self::class, $file . ' is not a directory.', 3);
                continue;
            }

            // Make sure the plugin base file exists inside the plugin directory
            if (! file_exists($basedir . '/' . $file . '/' . $file . '.catalog.php')) {
                debug_event(self::class, 'Missing class for ' . $file, 3);
                continue;
            }

            $results[] = $file;
        } // end while

        return $results;
    } // get_catalog_types

    /**
     * Check if a file is an audio.
     * @param string $file
     * @return boolean
     */
    public static function is_audio_file($file)
    {
        $pattern = "/\.(" . AmpConfig::get('catalog_file_pattern') . ")$/i";

        return (preg_match($pattern, $file) === 1);
    }

    /**
     * Check if a file is a video.
     * @param string $file
     * @return boolean
     */
    public static function is_video_file($file)
    {
        $video_pattern = "/\.(" . AmpConfig::get('catalog_video_pattern') . ")$/i";

        return (preg_match($video_pattern, $file) === 1);
    }

    /**
     * Check if a file is a playlist.
     * @param string $file
     * @return integer
     */
    public static function is_playlist_file($file)
    {
        $playlist_pattern = "/\.(" . AmpConfig::get('catalog_playlist_pattern') . ")$/i";

        return preg_match($playlist_pattern, $file);
    }

    /**
     * Get catalog info from table.
     * @param integer $object_id
     * @param string $table_name
     * @return array
     */
    public function get_info($object_id, $table_name = 'catalog')
    {
        $info = parent::get_info($object_id, $table_name);

        $table      = 'catalog_' . $this->get_type();
        $sql        = "SELECT `id` FROM $table WHERE `catalog_id` = ?";
        $db_results = Dba::read($sql, array($object_id));

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

    /**
     * Get enable sql filter;
     * @param string $type
     * @param string $catalog_id
     * @return string
     */
    public static function get_enable_filter($type, $catalog_id)
    {
        $sql = "";
        if ($type == "song" || $type == "album" || $type == "artist") {
            if ($type == "song") {
                $type = "id";
            }
            $sql = "(SELECT COUNT(`song_dis`.`id`) FROM `song` AS `song_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `song_dis`.`catalog` " .
                "WHERE `song_dis`.`" . $type . "`=" . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `song_dis`.`" . $type . "`) > 0";
        } elseif ($type == "video") {
            $sql = "(SELECT COUNT(`video_dis`.`id`) FROM `video` AS `video_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `video_dis`.`catalog` " .
                "WHERE `video_dis`.`id`=" . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `video_dis`.`id`) > 0";
        }

        return $sql;
    }

    /**
     * _create_filecache
     *
     * This populates an array which is used to speed up the add process.
     * @return boolean
     */
    protected function _create_filecache()
    {
        if (count($this->_filecache) == 0) {
            // Get _EVERYTHING_
            $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));

            // Populate the filecache
            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower((string) $results['file'])] = $results['id'];
            }

            $sql        = 'SELECT `id`, `file` FROM `video` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));

            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower((string) $results['file'])] = 'v_' . $results['id'];
            }
        }

        return true;
    }

    /**
     * get_count
     *
     * return the counts from update info to speed up responses
     * @param string $table
     * @return integer
     */
    public static function get_count(string $table)
    {
        if ($table == 'playlist' || $table == 'search') {
            $sql        = "SELECT 'playlist' AS `key`, SUM(value) AS `value` FROM `update_info`" .
                "WHERE `key` IN ('playlist', 'search')";
            $db_results = Dba::read($sql);
        } else {
            $sql        = "SELECT * FROM `update_info` WHERE `key` = ?";
            $db_results = Dba::read($sql, array($table));
        }
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['value'];
    } // get_count

    /**
     * set_count
     *
     * write the total_counts to update_info
     * @param string $table
     * @param string $value
     */
    public static function set_count(string $table, string $value)
    {
        Dba::write("REPLACE INTO `update_info` SET `key`= '" . $table . "', `value`=" . $value);
    } // set_count

    /**
     * update_enabled
     * sets the enabled flag
     * @param string $new_enabled
     * @param integer $catalog_id
     */
    public static function update_enabled($new_enabled, $catalog_id)
    {
        self::_update_item('enabled', make_bool($new_enabled), $catalog_id, '75');
    } // update_enabled

    /**
     * _update_item
     * This is a private function that should only be called from within the catalog class.
     * It takes a field, value, catalog id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param boolean $value
     * @param integer $catalog_id
     * @param integer $level
     * @return PDOStatement|boolean
     */
    private static function _update_item($field, $value, $catalog_id, $level)
    {
        /* Check them Rights! */
        if (!Access::check('interface', $level)) {
            return false;
        }

        /* Can't update to blank */
        if (!strlen(trim((string) $value))) {
            return false;
        }

        $value = Dba::escape($value);

        $sql = "UPDATE `catalog` SET `$field`='$value' WHERE `id`='$catalog_id'";

        return Dba::write($sql);
    } // _update_item

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        $time_format    = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        $this->f_name   = $this->name;
        $this->link     = AmpConfig::get('web_path') . '/admin/catalog.php?action=show_customize_catalog&catalog_id=' . $this->id;
        $this->f_link   = '<a href="' . $this->link . '" title="' . scrub_out($this->name) . '">' . scrub_out($this->f_name) . '</a>';
        $this->f_update = $this->last_update ? get_datetime($time_format, (int) $this->last_update) : T_('Never');
        $this->f_add    = $this->last_add ? get_datetime($time_format, (int) $this->last_add) : T_('Never');
        $this->f_clean  = $this->last_clean ? get_datetime($time_format, (int) $this->last_clean) : T_('Never');
    }

    /**
     * get_catalogs
     *
     * Pull all the current catalogs and return an array of ids
     * of what you find
     * @param string $filter_type
     * @return integer[]
     */
    public static function get_catalogs($filter_type = '')
    {
        $params     = array();
        $sql        = "SELECT `id` FROM `catalog` ";
        if (!empty($filter_type)) {
            $sql .= "WHERE `gather_types` = ? ";
            $params[] = $filter_type;
        }
        $sql .= "ORDER BY `name`";
        $db_results = Dba::read($sql, $params);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * Get last catalogs update.
     * @param integer[]|null $catalogs
     * @return integer
     */
    public static function getLastUpdate($catalogs = null)
    {
        $last_update = 0;
        if ($catalogs == null || !is_array($catalogs)) {
            $catalogs = self::get_catalogs();
        }
        foreach ($catalogs as $catalogid) {
            $catalog = self::create_from_id($catalogid);
            if ($catalog->last_add > $last_update) {
                $last_update = $catalog->last_add;
            }
            if ($catalog->last_update > $last_update) {
                $last_update = $catalog->last_update;
            }
            if ($catalog->last_clean > $last_update) {
                $last_update = $catalog->last_clean;
            }
        }

        return $last_update;
    }

    /**
     * get_stats
     *
     * This returns an hash with the #'s for the different
     * objects that are associated with this catalog. This is used
     * to build the stats box, it also calculates time.
     * @param integer|null $catalog_id
     * @return array
     */
    public static function get_stats($catalog_id = null)
    {
        $counts         = ($catalog_id) ? self::count_catalog($catalog_id) : self::count_server();
        $counts         = array_merge(User::count(), $counts);
        $counts['tags'] = self::count_tags();

        $counts['formatted_size'] = UI::format_bytes($counts['size']);

        $hours = floor($counts['time'] / 3600);
        $days  = floor($hours / 24);
        $hours = $hours % 24;

        $time_text = "$days ";
        $time_text .= nT_('day', 'days', $days);
        $time_text .= ", $hours ";
        $time_text .= nT_('hour', 'hours', $hours);

        $counts['time_text'] = $time_text;

        return $counts;
    }

    /**
     * create
     *
     * This creates a new catalog entry and associate it to current instance
     * @param array $data
     * @return integer
     */
    public static function create($data)
    {
        $name           = $data['name'];
        $type           = $data['type'];
        $rename_pattern = $data['rename_pattern'];
        $sort_pattern   = $data['sort_pattern'];
        $gather_types   = $data['gather_media'];

        // Should it be an array? Not now.
        if (!in_array($gather_types, array('music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast'))) {
            return 0;
        }

        $insert_id = 0;
        $filename  = AmpConfig::get('prefix') . '/modules/catalog/' . $type . '/' . $type . '.catalog.php';
        $include   = require_once $filename;

        if ($include) {
            $sql = 'INSERT INTO `catalog` (`name`, `catalog_type`, ' .
                '`rename_pattern`, `sort_pattern`, `gather_types`) VALUES (?, ?, ?, ?, ?)';
            Dba::write($sql, array(
                $name,
                $type,
                $rename_pattern,
                $sort_pattern,
                $gather_types
            ));

            $insert_id = Dba::insert_id();

            if (!$insert_id) {
                AmpError::add('general', T_('Failed to create the catalog, check the debug logs'));
                debug_event(self::class, 'Insert failed: ' . json_encode($data), 2);

                return 0;
            }

            $classname = 'Catalog_' . $type;
            if (!$classname::create_type($insert_id, $data)) {
                $sql = 'DELETE FROM `catalog` WHERE `id` = ?';
                Dba::write($sql, array($insert_id));
                $insert_id = 0;
            }
        }

        return (int) $insert_id;
    }

    /**
     * count_tags
     *
     * This returns the current number of unique tags in the database.
     * @return integer
     */
    public static function count_tags()
    {
        // FIXME: Ignores catalog_id
        $sql        = "SELECT COUNT(`id`) FROM `tag`";
        $db_results = Dba::read($sql);

        $row = Dba::fetch_row($db_results);

        return $row[0];
    }

    /**
     * count_server
     *
     * This returns the current number of songs, videos, albums, and artists
     * across all catalogs on the server
     * @param boolean $enabled
     * @param string $table
     * @return array
     */
    public static function count_server($enabled = false, $table = '')
    {
        // tables with media items to count, song-related tables and the rest
        $media_tables = array('song', 'video', 'podcast_episode');
        $song_tables  = array('artist', 'album');
        $list_tables  = array('search', 'playlist', 'live_stream', 'podcast', 'user', 'catalog', 'label', 'tag', 'share', 'license');
        if (!empty($table)) {
            if (in_array($table, $media_tables)) {
                $media_tables = array($table);
                $song_tables  = array();
                $list_tables  = array();
            }
            if (in_array($table, $song_tables)) {
                $media_tables = array();
                $song_tables  = array($table);
                $list_tables  = array();
            }
            if (in_array($table, $list_tables)) {
                $media_tables = array();
                $song_tables  = array();
                $list_tables  = array($table);
            }
        }

        $results = array();
        $items   = '0';
        $time    = '0';
        $size    = '0';
        foreach ($media_tables as $table) {
            $enabled_sql = ($enabled && $table !== 'podcast_episode') ? " WHERE `$table`.`enabled`='1'" : '';
            $sql         = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`), 0) FROM `$table`" . $enabled_sql;
            $db_results  = Dba::read($sql);
            $data        = Dba::fetch_row($db_results);
            // save the object and add to the current size
            $results[$table] = $data[0];
            $items += $data[0];
            $time += $data[1];
            $size += $data[2];
            // write the total_counts as well
            self::set_count($table, $data[0]);
        }
        // return the totals for all media tables
        $results['items'] = $items;
        $results['size']  = $size;
        $results['time']  = $time;

        foreach ($song_tables as $table) {
            $sql        = "SELECT COUNT(DISTINCT(`$table`)) FROM `song`";
            $db_results = Dba::read($sql);
            $data       = Dba::fetch_row($db_results);
            // save the object count
            $results[$table] = $data[0];
            // write the total_counts as well
            self::set_count($table, $data[0]);
        }

        foreach ($list_tables as $table) {
            $data = self::count_table($table);
            // save the object count
            $results[$table] = $data[0];
        }

        return $results;
    } // count_server

    /**
     * count_table
     *
     * Update a specific table count when adding/removing from the server
     * @param string $table
     * @return array
     */
    public static function count_table($table)
    {
        $sql        = "SELECT COUNT(`id`) FROM `$table`";
        $db_results = Dba::read($sql);
        $data       = Dba::fetch_row($db_results);

        self::set_count($table, $data[0]);

        return $data;
    } // count_table

    /**
     * count_catalog
     *
     * This returns the current number of songs, videos, podcast_episodes in this catalog.
     * @param integer $catalog_id
     * @return array
     */
    public static function count_catalog($catalog_id)
    {
        $where_sql = $catalog_id ? 'WHERE `catalog` = ?' : '';
        $params    = $catalog_id ? array($catalog_id) : array();
        $results   = array();
        $catalog   = self::create_from_id($catalog_id);

        if ($catalog->id) {
            $table = self::get_table_from_type($catalog->gather_types);
            if ($table == 'podcast_episode' && $catalog_id) {
                $where_sql = "WHERE `podcast` IN ( SELECT `id` FROM `podcast` WHERE `catalog` = ?)";
            }
            $sql              = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`), 0) FROM `" . $table . "` " . $where_sql;
            $db_results       = Dba::read($sql, $params);
            $data             = Dba::fetch_row($db_results);
            $results['items'] = $data[0];
            $results['time']  = $data[1];
            $results['size']  = $data[2];
        }

        return $results;
    } // count_catalog

    /**
     * get_uploads_sql
     *
     * @param string $type
     * @param integer|null $user_id
     * @return string
     */
    public static function get_uploads_sql($type, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        $user_id = (int) ($user_id);

        switch ($type) {
            case 'song':
                $sql = "SELECT `song`.`id` as `id` FROM `song` WHERE `song`.`user_upload` = '" . $user_id . "'";
                break;
            case 'album':
                $sql = "SELECT `album`.`id` as `id` FROM `album` JOIN `song` ON `song`.`album` = `album`.`id` WHERE `song`.`user_upload` = '" . $user_id . "' GROUP BY `album`.`id`";
                break;
            case 'artist':
            default:
                $sql = "SELECT `artist`.`id` as `id` FROM `artist` JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `song`.`user_upload` = '" . $user_id . "' GROUP BY `artist`.`id`";
                break;
        }

        return $sql;
    } // get_uploads_sql

    /**
     * get_album_ids
     *
     * This returns an array of ids of albums that have songs in this
     * catalog's
     * @param string $filter
     * @return integer[]
     */
    public function get_album_ids($filter = '')
    {
        $results = array();

        $sql = 'SELECT DISTINCT(`song`.`album`) AS `album` FROM `song` WHERE `song`.`catalog` = ?';
        if ($filter === 'art') {
            $sql = "SELECT DISTINCT(`song`.`album`) AS `album` FROM `song`" .
                   "LEFT JOIN `image` ON `song`.`album` = `image`.`object_id` AND `object_type` = 'album'" .
                   "WHERE `song`.`catalog` = ? AND `image`.`object_id` IS NULL";
        }
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['album'];
        }

        return array_reverse($results);
    }

    /**
     * get_video_ids
     *
     * This returns an array of ids of videos in this catalog
     * @param string $type
     * @return integer[]
     */
    public function get_video_ids($type = '')
    {
        $results = array();

        $sql = 'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` ';
        if (!empty($type)) {
            $sql .= 'JOIN `' . $type . '` ON `' . $type . '`.`id` = `video`.`id`';
        }
        $sql .= 'WHERE `video`.`catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     *
     * @param integer[]|null $catalogs
     * @param string $type
     * @return Video[]
     */
    public static function get_videos($catalogs = null, $type = '')
    {
        if (!$catalogs) {
            $catalogs = self::get_catalogs();
        }

        $results = array();
        foreach ($catalogs as $catalog_id) {
            $catalog   = self::create_from_id($catalog_id);
            $video_ids = $catalog->get_video_ids($type);
            foreach ($video_ids as $video_id) {
                $results[] = Video::create_from_id($video_id);
            }
        }

        return $results;
    }

    /**
     *
     * @param integer|null $catalog_id
     * @param string $type
     * @return integer
     */
    public static function get_videos_count($catalog_id = null, $type = '')
    {
        $sql = "SELECT COUNT(`video`.`id`) AS `video_cnt` FROM `video` ";
        if (!empty($type)) {
            $sql .= "JOIN `" . $type . "` ON `" . $type . "`.`id` = `video`.`id` ";
        }
        if ($catalog_id) {
            $sql .= "WHERE `video`.`catalog` = `" . (string) ($catalog_id) . "`";
        }
        $db_results = Dba::read($sql);
        $video_cnt  = 0;
        if ($row = Dba::fetch_row($db_results)) {
            $video_cnt = $row[0];
        }

        return $video_cnt;
    }

    /**
     * get_tvshow_ids
     *
     * This returns an array of ids of tvshows in this catalog
     * @return integer[]
     */
    public function get_tvshow_ids()
    {
        $results = array();

        $sql = 'SELECT DISTINCT(`tvshow`.`id`) AS `id` FROM `tvshow` ';
        $sql .= 'JOIN `tvshow_season` ON `tvshow_season`.`tvshow` = `tvshow`.`id` ';
        $sql .= 'JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` ';
        $sql .= 'JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` ';
        $sql .= 'WHERE `video`.`catalog` = ?';

        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     *
     * @param integer[]|null $catalogs
     * @return TVShow[]
     */
    public static function get_tvshows($catalogs = null)
    {
        if (!$catalogs) {
            $catalogs = self::get_catalogs();
        }

        $results = array();
        foreach ($catalogs as $catalog_id) {
            $catalog    = self::create_from_id($catalog_id);
            $tvshow_ids = $catalog->get_tvshow_ids();
            foreach ($tvshow_ids as $tvshow_id) {
                $results[] = new TVShow($tvshow_id);
            }
        }

        return $results;
    }

    /**
     * get_artist_ids
     *
     * This returns an array of ids of artist that have songs in this
     * catalog
     * @param string $filter
     * @return integer[]
     */
    public function get_artist_ids($filter = '')
    {
        $results = array();

        $sql        = 'SELECT DISTINCT(`song`.`artist`) AS `artist` FROM `song` WHERE `song`.`catalog` = ?';
        if ($filter === 'art') {
            $sql = "SELECT DISTINCT(`song`.`artist`) AS `artist` FROM `song`" .
                "LEFT JOIN `image` ON `song`.`artist` = `image`.`object_id` AND `object_type` = 'artist'" .
                "WHERE `song`.`catalog` = ? AND `image`.`object_id` IS NULL";
        }
        if ($filter === 'info') {
            // only update info when you haven't done it for 6 months
            $sql = "SELECT DISTINCT(`artist`.`id`) AS `artist` FROM `artist`" .
                "WHERE `artist`.`last_update` > (UNIX_TIMESTAMP() - 15768000) ";
        }
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist'];
        }

        return array_reverse($results);
    }

    /**
     * get_artists
     *
     * This returns an array of artists that have songs in the catalogs parameter
     * @param array|null $catalogs
     * @param integer $size
     * @param integer $offset
     * @return Artist[]
     */
    public static function get_artists($catalogs = null, $size = 0, $offset = 0)
    {
        $sql_where = "";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql_where = "WHERE `song`.`catalog` IN $catlist ";
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = "LIMIT " . $offset . ", " . $size;
        } elseif ($size > 0) {
            $sql_limit = "LIMIT " . $size;
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html  // TODO mysql8 test
            $sql_limit = "LIMIT " . $offset . ", 18446744073709551615";
        }

        $sql = "SELECT `artist`.`id`, `artist`.`name`, `artist`.`prefix`, `artist`.`summary`, (SELECT COUNT(DISTINCT album) from `song` as `inner_song` WHERE `inner_song`.`artist` = `song`.`artist`) AS `albums`" .
                "FROM `song` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` " .
                $sql_where .
                "GROUP BY `artist`.`id`, `artist`.`name`, `artist`.`prefix`, `artist`.`summary`, `song`.`artist` ORDER BY `artist`.`name` " .
                $sql_limit;

        $results    = array();
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = Artist::construct_from_array($row);
        }

        return $results;
    }

    /**
     * get_id_from_file
     *
     * Get media id from the file path.
     *
     * @param string $file_path
     * @param string $media_type
     * @return integer
     */
    public static function get_id_from_file($file_path, $media_type)
    {
        $sql        = "SELECT `id` FROM $media_type WHERE `file` = ?";
        $db_results = Dba::read($sql, array($file_path));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int) $results['id'];
        }

        return 0;
    }

    /**
     * @param string $name
     * @param integer $catalog_id
     * @return array
     */
    public static function search_childrens($name, $catalog_id = 0)
    {
        $search                    = array();
        $search['type']            = "artist";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "name";
        if ($catalog_id > 0) {
            $search['rule_1_input']    = $catalog_id;
            $search['rule_1_operator'] = 0;
            $search['rule_1']          = "catalog";
        }
        $artists = Search::run($search);

        $childrens = array();
        foreach ($artists as $artist_id) {
            $childrens[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return $childrens;
    }

    /**
     * get_albums
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter
     * @param integer $size
     * @param integer $offset
     * @param integer[]|null $catalogs
     * @return integer[]
    */
    public static function get_albums($size = 0, $offset = 0, $catalogs = null)
    {
        $sql_where = "";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql_where = "WHERE `song`.`catalog` IN $catlist";
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = "LIMIT $offset, $size";
        } elseif ($size > 0) {
            $sql_limit = "LIMIT $size";
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html
            $sql_limit = "LIMIT $offset, 18446744073709551615";
        }

        $sql = "SELECT `album`.`id` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` $sql_where GROUP BY `album`.`id` ORDER BY `album`.`name` $sql_limit";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_albums_by_artist
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter, grouped by artist
     * @param integer $size
     * @param integer $offset
     * @param integer[]|null $catalogs
     * @return integer[]
     * @oaram int $offset
     */
    public static function get_albums_by_artist($size = 0, $offset = 0, $catalogs = null)
    {
        $sql_where = "";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql_where = "WHERE `song`.`catalog` IN $catlist";
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = "LIMIT $offset, $size";
        } elseif ($size > 0) {
            $sql_limit = "LIMIT $size";
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html  // TODO mysql8 test
            $sql_limit = "LIMIT $offset, 18446744073709551615";
        }

        $sql = "SELECT `song`.`album` as 'id' FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` " .
            "LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` $sql_where " .
        "GROUP BY `song`.`album`, `artist`.`name`, `artist`.`id`, `album`.`name` ORDER BY `artist`.`name`, `artist`.`id`, `album`.`name` $sql_limit";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_podcast_ids
     *
     * This returns an array of ids of podcasts in this catalog
     * @return integer[]
     */
    public function get_podcast_ids()
    {
        $results = array();

        $sql = 'SELECT `podcast`.`id` FROM `podcast` ';
        $sql .= 'WHERE `podcast`.`catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     *
     * @param integer[]|null $catalogs
     * @return Podcast[]
     */
    public static function get_podcasts($catalogs = null)
    {
        if (!$catalogs) {
            $catalogs = self::get_catalogs('podcast');
        }

        $results = array();
        foreach ($catalogs as $catalog_id) {
            $catalog     = self::create_from_id($catalog_id);
            $podcast_ids = $catalog->get_podcast_ids();
            foreach ($podcast_ids as $podcast_id) {
                $results[] = new Podcast($podcast_id);
            }
        }

        return $results;
    }

    /**
     * get_newest_podcasts_ids
     *
     * This returns an array of ids of latest podcast episodes in this catalog
     * @param integer $count
     * @return integer[]
     */
    public function get_newest_podcasts_ids($count)
    {
        $results = array();

        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ' .
                'INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` ' .
                'WHERE `podcast`.`catalog` = ? ' .
                'ORDER BY `podcast_episode`.`pubdate` DESC';
        if ($count > 0) {
            $sql .= ' LIMIT ' . (string) $count;
        }
        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     *
     * @param integer $count
     * @return Podcast_Episode[]
     */
    public static function get_newest_podcasts($count)
    {
        $catalogs = self::get_catalogs('podcast');
        $results  = array();

        foreach ($catalogs as $catalog_id) {
            $catalog     = self::create_from_id($catalog_id);
            $episode_ids = $catalog->get_newest_podcasts_ids($count);
            foreach ($episode_ids as $episode_id) {
                $results[] = new Podcast_Episode($episode_id);
            }
        }

        return $results;
    }

    /**
     * gather_art_item
     * @param string $type
     * @param integer $object_id
     * @param boolean $db_art_first
     * @param boolean $api
     * @return boolean
     */
    public static function gather_art_item($type, $object_id, $db_art_first = false, $api = false)
    {
        // Should be more generic !
        if ($type == 'video') {
            $libitem = Video::create_from_id($object_id);
        } else {
            $libitem = new $type($object_id);
        }
        $inserted = false;
        $options  = array();
        $libitem->format();
        if ($libitem->id) {
            if (count($options) == 0) {
                // Only search on items with default art kind as `default`.
                if ($libitem->get_default_art_kind() == 'default') {
                    $keywords = $libitem->get_keywords();
                    $keyword  = '';
                    foreach ($keywords as $key => $word) {
                        $options[$key] = $word['value'];
                        if ($word['important'] && !empty($word['value'])) {
                            $keyword .= ' ' . $word['value'];
                        }
                    }
                    $options['keyword'] = $keyword;
                }

                $parent = $libitem->get_parent();
                if (!empty($parent)) {
                    self::gather_art_item($parent['object_type'], $parent['object_id'], $db_art_first, $api);
                }
            }
        }

        $art = new Art($object_id, $type);
        // don't search for art when you already have it
        if ($art->has_db_info() && $db_art_first) {
            debug_event(self::class, 'Blocking art search for ' . $type . '/' . $object_id . ' DB item exists', 5);
            $results = array();
        } else {
            debug_event(self::class, 'Gathering art for ' . $type . '/' . $object_id . '...', 4);
            $results = $art->gather($options);
        }

        foreach ($results as $result) {
            // Pull the string representation from the source
            $image = Art::get_from_source($result, $type);
            if (strlen((string) $image) > '5') {
                $inserted = $art->insert($image, $result['mime']);
                // If they've enabled resizing of images generate a thumbnail
                if (AmpConfig::get('resize_images')) {
                    $size  = array('width' => 275, 'height' => 275);
                    $thumb = $art->generate_thumb($image, $size, $result['mime']);
                    if (!empty($thumb)) {
                        $art->save_thumb($thumb['thumb'], $thumb['thumb_mime'], $size);
                    }
                }
                if ($inserted) {
                    break;
                }
            } elseif ($result === true) {
                debug_event(self::class, 'Database already has image.', 3);
            } else {
                debug_event(self::class, 'Image less than 5 chars, not inserting', 3);
            }
        }

        if ($type == 'video' && AmpConfig::get('generate_video_preview')) {
            Video::generate_preview($object_id);
        }

        if (UI::check_ticker() && !$api) {
            UI::update_text('read_art_' . $object_id, $libitem->get_fullname());
        }
        if ($inserted) {
            return true;
        }

        return false;
    }

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and tries
     * to find the art for them from the mp3s
     * @param integer[]|null $songs
     * @param integer[]|null $videos
     * @return boolean
     */
    public function gather_art($songs = null, $videos = null)
    {
        // Make sure they've actually got methods
        $art_order       = AmpConfig::get('art_order');
        $gather_song_art = AmpConfig::get('gather_song_art', false);
        $db_art_first    = ($art_order[0] == 'db');
        if (!count($art_order)) {
            debug_event(self::class, 'art_order not set, self::gather_art aborting', 3);

            return false;
        }

        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        $searches     = array();
        if ($songs == null) {
            $searches['album']  = $this->get_album_ids('art');
            $searches['artist'] = $this->get_artist_ids('art');
            if ($gather_song_art) {
                $searches['song'] = $this->get_songs();
            }
        } else {
            $searches['album']  = array();
            $searches['artist'] = array();
            if ($gather_song_art) {
                $searches['song'] = array();
            }
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->id) {
                    if (!in_array($song->album, $searches['album'])) {
                        $searches['album'][] = $song->album;
                    }
                    if (!in_array($song->artist, $searches['artist'])) {
                        $searches['artist'][] = $song->artist;
                    }
                    if ($gather_song_art) {
                        $searches['song'][] = $song->id;
                    }
                }
            }
        }
        if ($videos == null) {
            $searches['video'] = $this->get_video_ids();
        } else {
            $searches['video'] = $videos;
        }

        debug_event(self::class, 'gather_art found ' . (string) count($searches) . ' items missing art', 4);
        // Run through items and get the art!
        foreach ($searches as $key => $values) {
            foreach ($values as $object_id) {
                self::gather_art_item($key, $object_id, $db_art_first);

                // Stupid little cutesie thing
                $search_count++;
                if (UI::check_ticker()) {
                    UI::update_text('count_art_' . $this->id, $search_count);
                }
            }
        }
        // One last time for good measure
        UI::update_text('count_art_' . $this->id, $search_count);

        return true;
    }

    /**
     * gather_artist_info
     *
     * This runs through all of the artists and refreshes last.fm information
     * including similar artists that exist in your catalog.
     * @param array $artist_list
     */
    public function gather_artist_info($artist_list = array())
    {
        // Prevent the script from timing out
        set_time_limit(0);

        $search_count       = 0;
        $searches           = array();
        if (empty($artist_list)) {
            $searches['artist'] = $this->get_artist_ids();
        } else {
            $searches['artist'] = $artist_list;
        }

        debug_event(self::class, 'gather_artist_info found ' . (string) count($searches) . 'items to check', 4);
        // Run through items and refresh info
        foreach ($searches as $key => $values) {
            foreach ($values as $object_id) {
                Recommendation::get_artist_info($object_id);
                Recommendation::get_artists_like($object_id);
                Artist::set_last_update($object_id);

                // Stupid little cutesie thing
                $search_count++;
                if (UI::check_ticker()) {
                    UI::update_text('count_artist_' . $object_id, $search_count);
                }
            }
        }

        // One last time for good measure
        UI::update_text('count_artist_complete', $search_count);
    }

    /**
     * get_songs
     *
     * Returns an array of song objects.
     * @return Song[]
     */
    public function get_songs()
    {
        $songs   = array();
        $results = array();

        $sql        = "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled`='1'";
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $songs[] = $row['id'];
        }

        if (AmpConfig::get('memory_cache')) {
            Song::build_cache($songs);
        }

        foreach ($songs as $song_id) {
            $results[] = new Song($song_id);
        }

        return $results;
    }
    /**
     * get_song_ids
     *
     * Returns an array of song ids.
     * @return integer[]
     */
    public function get_song_ids()
    {
        $songs = array();

        $sql        = "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled`='1'";
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $songs[] = $row['id'];
        }

        return $songs;
    }

    /**
     * dump_album_art
     *
     * This runs through all of the albums and tries to dump the
     * art for them into the 'folder.jpg' file in the appropriate dir.
     * @param array $methods
     */
    public function dump_album_art($methods = array())
    {
        // Get all of the albums in this catalog
        $albums = $this->get_album_ids();

        echo T_("Starting Album Art Dump") . "\n";
        $count = 0;

        // Run through them and get the art!
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $art   = new Art($album_id, 'album');

            if (!$art->has_db_info()) {
                continue;
            }

            // Get the first song in the album
            $songs = $album->get_songs(1);
            $song  = new Song($songs[0]);
            $dir   = dirname($song->file);

            $extension = Art::extension($art->raw_mime);

            // Try the preferred filename, if that fails use folder.???
            $preferred_filename = AmpConfig::get('album_art_preferred_filename');
            if (!$preferred_filename ||
                strpos($preferred_filename, '%') !== false) {
                $preferred_filename = "folder.$extension";
            }

            $file = $dir . DIRECTORY_SEPARATOR . $preferred_filename;
            if ($file_handle = fopen($file, "w")) {
                if (fwrite($file_handle, $art->raw)) {
                    // Also check and see if we should write
                    // out some metadata
                    if ($methods['metadata']) {
                        switch ($methods['metadata']) {
                            case 'windows':
                                $meta_file = $dir . '/desktop.ini';
                                $string    = "[.ShellClassInfo]\nIconFile=$file\nIconIndex=0\nInfoTip=$album->full_name";
                                break;
                            case 'linux':
                            default:
                                $meta_file = $dir . '/.directory';
                                $string    = "Name=$album->full_name\nIcon=$file";
                                break;
                        }

                        $meta_handle = fopen($meta_file, "w");
                        fwrite($meta_handle, $string);
                        fclose($meta_handle);
                    } // end metadata
                    $count++;
                    if (!($count % 100)) {
                        /* HINT: count of files written */
                        printf(T_("Art files written: %s"), $count);
                        echo "\n";
                        debug_event(self::class, "$album->name Art written to $file", 5);
                    }
                } else {
                    debug_event(self::class, "Unable to open $file for writing", 3);
                    /* HINT: filename (file path) */
                    printf(T_("Couldn't get write to create art file: %s"), $file);
                    echo "\n";
                }
            }
            fclose($file_handle);
        }

        echo T_("Album Art Dump Complete") . "\n";
    }

    /**
     * update_last_update
     * updates the last_update of the catalog
     */
    protected function update_last_update()
    {
        $date = time();
        $sql  = "UPDATE `catalog` SET `last_update` = ? WHERE `id` = ?";
        Dba::write($sql, array($date, $this->id));
    } // update_last_update

    /**
     * update_last_add
     * updates the last_add of the catalog
     */
    public function update_last_add()
    {
        $date = time();
        $sql  = "UPDATE `catalog` SET `last_add` = ? WHERE `id` = ?";
        Dba::write($sql, array($date, $this->id));
    } // update_last_add

    /**
     * update_last_clean
     * This updates the last clean information
     */
    public function update_last_clean()
    {
        $date = time();
        $sql  = "UPDATE `catalog` SET `last_clean` = ? WHERE `id` = ?";
        Dba::write($sql, array($date, $this->id));
    } // update_last_clean

    /**
     * update_settings
     * This function updates the basic setting of the catalog
     * @param array $data
     * @return boolean
     */
    public static function update_settings($data)
    {
        $sql    = "UPDATE `catalog` SET `name` = ?, `rename_pattern` = ?, `sort_pattern` = ? WHERE `id` = ?";
        $params = array($data['name'], $data['rename_pattern'], $data['sort_pattern'], $data['catalog_id']);
        Dba::write($sql, $params);

        return true;
    } // update_settings

    /**
     * update_single_item
     * updates a single album,artist,song from the tag data
     * this can be done by 75+
     * @param string $type
     * @param integer $object_id
     * @param boolean $api
     * @return integer
     */
    public static function update_single_item($type, $object_id, $api = false)
    {
        // Because single items are large numbers of things too
        set_time_limit(0);

        $songs   = array();
        $result  = $object_id;
        $libitem = 0;

        switch ($type) {
            case 'album':
                $libitem = new Album($object_id);
                $songs   = $libitem->get_songs();
                break;
            case 'artist':
                $libitem = new Artist($object_id);
                $songs   = $libitem->get_songs();
                break;
            case 'song':
                $songs[] = $object_id;
                break;
        } // end switch type

        if (!$api) {
            echo '<table class="tabledata">' . "\n";
            echo '<thead><tr class="th-top">' . "\n";
            echo "<th>" . T_("Song") . "</th><th>" . T_("Status") . "</th>\n";
            echo "<tbody>\n";
        }
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            $info = self::update_media_from_tags($song);
            // don't echo useless info when using api
            if (($info['change']) && (!$api)) {
                if ($info['element'][$type]) {
                    $change = explode(' --> ', (string) $info['element'][$type]);
                    $result = (int) $change[1];
                }
                $file   = scrub_out($song->file);
                echo '<tr class="' . UI::flip_class() . '">' . "\n";
                echo "<td>$file</td><td>" . T_('Updated') . "</td>\n";
                echo $info['text'];
                echo "</td>\n</tr>\n";
                flush();
            } else {
                if (!$api) {
                    echo '<tr class="' . UI::flip_class() . '"><td>' . scrub_out($song->file) . "</td><td>" . T_('No Update Needed') . "</td></tr>\n";
                }
                flush();
            }
        } // foreach songs
        if (!$api) {
            echo "</tbody></table>\n";
        }
        // Update the tags for
        switch ($type) {
            case 'album':
                $tags = self::getSongTags('album', $libitem->id);
                Tag::update_tag_list(implode(',', $tags), 'album', $libitem->id, false);
                $libitem->update_time();
                break;
            case 'artist':
                $tags = self::getSongTags('artist', $libitem->id);
                Tag::update_tag_list(implode(',', $tags), 'artist', $libitem->id, false);
                $libitem->update_time();
                break;
        } // end switch type

        // Cleanup old objects that are no longer needed
        if (!AmpConfig::get('cron_cache')) {
            Album::garbage_collection();
            Artist::garbage_collection();
        }

        return $result;
    } // update_single_item

    /**
     * update_media_from_tags
     * This is a 'wrapper' function calls the update function for the media
     * type in question
     * @param Song|Video|Podcast_Episode $media
     * @param array $gather_types
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @return array
     */
    public static function update_media_from_tags($media, $gather_types = array('music'), $sort_pattern = '', $rename_pattern = '')
    {
        $catalog = self::create_from_id($media->catalog);
        if ($catalog === null) {
            debug_event(self::class, 'update_media_from_tags: Error loading catalog ' . $media->catalog, 2);

            return array();
        }

        // Figure out what type of object this is and call the right  function
        $name     = (get_class($media) == 'Song') ? 'song' : 'video';
        $function = 'update_' . $name . '_from_tags';

        // try and get the tags from your file
        $extension    = strtolower(pathinfo($media->file, PATHINFO_EXTENSION));
        $results      = $catalog->get_media_tags($media, $gather_types, $sort_pattern, $rename_pattern);
        // for files without tags try to update from their file name instead
        if ($media->id && in_array($extension, array('wav', 'shn'))) {
            debug_event(self::class, 'update_media_from_tags: ' . $extension . ' extension: parse_pattern', 2);
            // match against your catalog 'Filename Pattern' and 'Folder Pattern'
            $patres  = vainfo::parse_pattern($media->file, $catalog->sort_pattern, $catalog->rename_pattern);
            $results = array_merge($results, $patres);

            return call_user_func(array('Catalog', $function), $results, $media);
        }
        debug_event(self::class, 'Reading tags from ' . $media->file, 4);


        return call_user_func(array('Catalog', $function), $results, $media);
    } // update_media_from_tags

    /**
     * update_song_from_tags
     * Updates the song info based on tags; this is called from a bunch of
     * different places and passes in a full fledged song object, so it's a
     * static function.
     * FIXME: This is an ugly mess, this really needs to be consolidated and
     * cleaned up.
     * @param array $results
     * @param Song $song
     * @return array
     * @throws ReflectionException
     */
    public static function update_song_from_tags($results, Song $song)
    {
        // info for the song table. This is all the primary file data that is song related
        $new_song           = new Song();
        $new_song->file     = $results['file'];
        $new_song->year     = (strlen((string) $results['year']) > 4) ? (int) substr($results['year'], -4, 4) : (int) ($results['year']);
        $new_song->title    = self::check_length(self::check_title($results['title'], $new_song->file));
        $new_song->bitrate  = $results['bitrate'];
        $new_song->rate     = $results['rate'];
        $new_song->mode     = ($results['mode'] == 'cbr') ? 'cbr' : 'vbr';
        $new_song->size     = $results['size'];
        $new_song->time     = (strlen((string) $results['time']) > 5) ? (int) substr($results['time'], -5, 5) : (int) ($results['time']);
        if ($new_song->time < 0) {
            // fall back to last time if you fail to scan correctly
            $new_song->time = $song->time;
        }
        $new_song->track    = self::check_track((string) $results['track']);
        $new_song->mbid     = $results['mb_trackid'];
        $new_song->composer = self::check_length($results['composer']);
        $new_song->mime     = $results['mime'];

        // info for the song_data table. used in Song::update_song
        $new_song->comment     = $results['comment'];
        $new_song->lyrics      = str_replace(
                        array("\r\n", "\r", "\n"),
                        '<br />',
                        strip_tags($results['lyrics']));
        $new_song->license = isset($results['license']) ? License::lookup((string) $results['license']) : null;
        $new_song->label   = isset($results['publisher']) ? Catalog::check_length($results['publisher'], 128) : null;
        if ($song->label && AmpConfig::get('label')) {
            // create the label if missing
            foreach (array_map('trim', explode(';', $new_song->label)) as $label_name) {
                Label::helper($label_name);
            }
        }
        $new_song->language              = self::check_length($results['language'], 128);
        $new_song->replaygain_track_gain = !is_null($results['replaygain_track_gain']) ? (float) $results['replaygain_track_gain'] : null;
        $new_song->replaygain_track_peak = !is_null($results['replaygain_track_peak']) ? (float) $results['replaygain_track_peak'] : null;
        $new_song->replaygain_album_gain = !is_null($results['replaygain_album_gain']) ? (float) $results['replaygain_album_gain'] : null;
        $new_song->replaygain_album_peak = !is_null($results['replaygain_album_peak']) ? (float) $results['replaygain_album_peak'] : null;
        $new_song->r128_track_gain       = !is_null($results['r128_track_gain']) ? (int) $results['r128_track_gain'] : null;
        $new_song->r128_album_gain       = !is_null($results['r128_album_gain']) ? (int) $results['r128_album_gain'] : null;

        // genre is used in the tag and tag_map tables
        $new_song->tags = $results['genre'];
        $tags           = Tag::get_object_tags('song', $song->id);
        if ($tags) {
            foreach ($tags as $tag) {
                $song->tags[] = $tag['name'];
            }
        }
        // info for the artist table.
        $artist           = self::check_length($results['artist']);
        $artist_mbid      = $results['mb_artistid'];
        $albumartist_mbid = $results['mb_albumartistid'];
        // info for the album table.
        $album            = self::check_length($results['album']);
        $album_mbid       = $results['mb_albumid'];
        $disk             = $results['disk'];
        // year is also included in album
        $album_mbid_group = $results['mb_albumid_group'];
        $release_type     = Catalog::check_length($results['release_type'], 32);
        $albumartist      = self::check_length($results['albumartist'] ?: $results['band']);
        $albumartist      = $albumartist ?: null;
        $original_year    = $results['original_year'];
        $barcode          = self::check_length($results['barcode'], 64);
        $catalog_number   = self::check_length($results['catalog_number'], 64);

        // check whether this artist exists (and the album_artist)
        $new_song->artist = Artist::check($artist, $artist_mbid);
        if ($albumartist) {
            $new_song->albumartist = Artist::check($albumartist, $albumartist_mbid);
            if (!$new_song->albumartist) {
                $new_song->albumartist = $song->albumartist;
            }
        }
        if (!$new_song->artist) {
            $new_song->artist = $song->artist;
        }

        // check whether this album exists
        $new_song->album = Album::check($album, $new_song->year, $disk, $album_mbid, $album_mbid_group, $new_song->albumartist, $release_type, $original_year, $barcode, $catalog_number);
        if (!$new_song->album) {
            $new_song->album = $song->album;
        }
        // set `song`.`update_time` when artist or album details change
        $update_time = time();
        if (self::migrate('artist', $song->artist, $new_song->artist) ||
                self::migrate('album', $song->album, $new_song->album)) {
            Song::update_utime($song->id, $update_time);
        }

        if ($artist_mbid) {
            $new_song->artist_mbid = $artist_mbid;
        }
        if ($album_mbid) {
            $new_song->album_mbid = $album_mbid;
        }
        if ($albumartist_mbid) {
            $new_song->albumartist_mbid = $albumartist_mbid;
        }

        /* Since we're doing a full compare make sure we fill the extended information */
        $song->fill_ext_info();

        if (Song::isCustomMetadataEnabled()) {
            $ctags = self::get_clean_metadata($song, $results);
            if (method_exists($song, 'updateOrInsertMetadata') && $song::isCustomMetadataEnabled()) {
                $ctags = array_diff_key($ctags, array_flip($song->getDisabledMetadataFields()));
                foreach ($ctags as $tag => $value) {
                    $field = $song->getField($tag);
                    $song->updateOrInsertMetadata($field, $value);
                }
            }
        }

        // Duplicate arts if required
        if (($song->artist && $new_song->artist) && $song->artist != $new_song->artist) {
            if (!Art::has_db($new_song->artist, 'artist')) {
                Art::duplicate('artist', $song->artist, $new_song->artist);
            }
        }
        if (($song->albumartist && $new_song->albumartist) && $song->albumartist != $new_song->albumartist) {
            if (!Art::has_db($new_song->albumartist, 'artist')) {
                Art::duplicate('artist', $song->albumartist, $new_song->albumartist);
            }
        }
        if (($song->album && $new_song->album) && $song->album != $new_song->album) {
            if (!Art::has_db($new_song->album, 'album')) {
                Art::duplicate('album', $song->album, $new_song->album);
            }
        }
        if ($song->label && AmpConfig::get('label')) {
            foreach (array_map('trim', explode(';', $song->label)) as $label_name) {
                $label_id = Label::lookup(array('name' => $label_name));
                if ($label_id > 0) {
                    $label   = new Label($label_id);
                    $artists = $label->get_artists();
                    if (!in_array($song->artist, $artists)) {
                        debug_event(self::class, "$song->artist: adding association to $label->name", 4);
                        $label->add_artist_assoc($song->artist);
                    }
                }
            }
        }

        $info = Song::compare_song_information($song, $new_song);
        if ($info['change']) {
            debug_event(self::class, "$song->file : differences found, updating database", 4);

            // Update song_data table
            Song::update_song($song->id, $new_song);

            if (!empty($new_song->tags) && $song->tags != $new_song->tags) {
                Tag::update_tag_list(implode(',', $new_song->tags), 'song', $song->id, true);
                self::updateAlbumTags($song);
                self::updateArtistTags($song);
            }
            if ($song->license != $new_song->license) {
                Song::update_license($new_song->license, $song->id);
            }
            // Refine our reference
            //$song = $new_song;
        } else {
            debug_event(self::class, "$song->file : no differences found", 5);
        }

        // If song rating tag exists and is well formed (array user=>rating), update it
        if ($song->id && array_key_exists('rating', $results) && is_array($results['rating'])) {
            // For each user's ratings, call the function
            foreach ($results['rating'] as $user => $rating) {
                debug_event(self::class, "Updating rating for Song " . $song->id . " to $rating for user $user", 5);
                $o_rating = new Rating($song->id, 'song');
                $o_rating->set_rating($rating, $user);
            }
        }

        return $info;
    } // update_song_from_tags

    /**
     * @param $results
     * @param Video $video
     * @return array
     */
    public static function update_video_from_tags($results, Video $video)
    {
        /* Setup the vars */
        $new_video                = new Video();
        $new_video->file          = $results['file'];
        $new_video->title         = $results['title'];
        $new_video->size          = $results['size'];
        $new_video->video_codec   = $results['video_codec'];
        $new_video->audio_codec   = $results['audio_codec'];
        $new_video->resolution_x  = $results['resolution_x'];
        $new_video->resolution_y  = $results['resolution_y'];
        $new_video->time          = $results['time'];
        $new_video->release_date  = $results['release_date'] ?: 0;
        $new_video->bitrate       = $results['bitrate'];
        $new_video->mode          = $results['mode'];
        $new_video->channels      = $results['channels'];
        $new_video->display_x     = $results['display_x'];
        $new_video->display_y     = $results['display_y'];
        $new_video->frame_rate    = $results['frame_rate'];
        $new_video->video_bitrate = (int) Catalog::check_int($results['video_bitrate'], 4294967294, 0);
        $tags                     = Tag::get_object_tags('video', $video->id);
        if ($tags) {
            foreach ($tags as $tag) {
                $video->tags[]     = $tag['name'];
            }
        }
        $new_video->tags        = $results['genre'];

        $info = Video::compare_video_information($video, $new_video);
        if ($info['change']) {
            debug_event(self::class, $video->file . " : differences found, updating database", 5);

            Video::update_video($video->id, $new_video);

            if ($video->tags != $new_video->tags) {
                Tag::update_tag_list(implode(',', $new_video->tags), 'video', $video->id, true);
            }
        } else {
            debug_event(self::class, $video->file . " : no differences found", 5);
        }

        return $info;
    }

    /**
     * Get rid of all tags found in the libraryItem
     * @param library_item $libraryItem
     * @param array $metadata
     * @return array
     */
    private static function get_clean_metadata(library_item $libraryItem, $metadata)
    {
        $tags = array_diff_key(
            $metadata,
            get_object_vars($libraryItem),
            array_flip($libraryItem::$aliases ?: array())
        );

        return array_filter($tags);
    }

    /**
     *
     * @param library_item $libraryItem
     * @param array $metadata
     */
    public static function add_metadata(library_item $libraryItem, $metadata)
    {
        $tags = self::get_clean_metadata($libraryItem, $metadata);

        foreach ($tags as $tag => $value) {
            $field = $libraryItem->getField($tag);
            $libraryItem->addMetadata($field, $value);
        }
    }

    /**
     * get_media_tags
     * @param Song|Video|Podcast_Episode $media
     * @param array $gather_types
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @return array
     */
    public function get_media_tags($media, $gather_types, $sort_pattern, $rename_pattern)
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }

        $vainfo = new vainfo($media->file, $gather_types, '', '', '', $sort_pattern, $rename_pattern);
        try {
            $vainfo->get_info();
        } catch (Exception $error) {
            debug_event(self::class, 'Error ' . $error->getMessage(), 1);

            return array();
        }

        $key = vainfo::get_tag_type($vainfo->tags);

        return vainfo::clean_tag_info($vainfo->tags, $key, $media->file);
    }

    /**
     * get_gather_types
     * @param string $media_type
     * @return array
     */
    public function get_gather_types($media_type = '')
    {
        $gtypes = $this->gather_types;
        if (empty($gtypes)) {
            $gtypes = "music";
        }
        $types = explode(',', $gtypes);

        if ($media_type == "video") {
            $types = array_diff($types, array('music'));
        }

        if ($media_type == "music") {
            $types = array_diff($types, array('personal_video', 'movie', 'tvshow', 'clip'));
        }

        return $types;
    }

    /**
     * get_table_from_type
     * @param string $gather_type
     * @return string
     */
    public static function get_table_from_type($gather_type)
    {
        switch ($gather_type) {
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
                $table = 'video';
                break;
            case 'podcast':
                $table = 'podcast_episode';
                break;
            case 'music':
            default:
                $table = 'song';
                break;
        }

        return $table;
    }

    /**
     * clean_empty_albums
     */
    public static function clean_empty_albums()
    {
        $sql = "SELECT `id` FROM `album` WHERE NOT EXISTS " .
            "(SELECT `id` FROM `song` WHERE `song`.`album` = `album`.`id`)";
        $db_results = Dba::read($sql);
        while ($albumid = Dba::fetch_assoc($db_results)) {
            $object_id   = $albumid['id'];
            $sql         = "DELETE FROM `album` WHERE `id` = ?";
            $db_results  = Dba::write($sql, array($object_id));
        }
    }

    /**
     * clean_catalog
     *
     * Cleans the catalog of files that no longer exist.
     */
    public function clean_catalog()
    {
        // We don't want to run out of time
        set_time_limit(0);

        debug_event(self::class, 'Starting clean on ' . $this->name, 5);

        if (!defined('SSE_OUTPUT')) {
            require AmpConfig::get('prefix') . UI::find_template('show_clean_catalog.inc.php');
            ob_flush();
            flush();
        }

        $dead_total = $this->clean_catalog_proc();
        self::clean_empty_albums();

        debug_event(self::class, 'clean finished, ' . $dead_total . ' removed from ' . $this->name, 4);

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top();
        }
        UI::update_text(T_("Catalog Cleaned"), sprintf(nT_("%d file removed.", "%d files removed.", $dead_total), $dead_total));
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }

        $this->update_last_clean();
    } // clean_catalog

    /**
     * verify_catalog
     * This function verify the catalog
     */
    public function verify_catalog()
    {
        if (!defined('SSE_OUTPUT')) {
            require AmpConfig::get('prefix') . UI::find_template('show_verify_catalog.inc.php');
            ob_flush();
            flush();
        }

        $verified = $this->verify_catalog_proc();

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top();
        }
        UI::update_text(T_("Catalog Verified"), sprintf(nT_('%d file updated.', '%d files updated.', $verified['updated']), $verified['updated']));
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }

        return true;
    } // verify_catalog

    /**
     * garbage_collection
     *
     * This is a wrapper function for all of the different cleaning
     * functions, it runs them in an order that resembles correctness.
     */
    public static function garbage_collection()
    {
        Song::garbage_collection();
        Album::garbage_collection();
        Artist::garbage_collection();
        Video::garbage_collection();
        Art::garbage_collection();
        Stats::garbage_collection();
        Rating::garbage_collection();
        Userflag::garbage_collection();
        Useractivity::garbage_collection();
        Playlist::garbage_collection();
        Tmp_Playlist::garbage_collection();
        Shoutbox::garbage_collection();
        Tag::garbage_collection();

        // TODO: use InnoDB with foreign keys and on delete cascade to get rid of garbage collection
        Metadata::garbage_collection();
        MetadataField::garbage_collection();
    }

    /**
     * trim_prefix
     * Splits the prefix from the string
     * @param string $string
     * @return array
     */
    public static function trim_prefix($string)
    {
        $prefix_pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern'))) . '\\s)(.*)/i';
        preg_match($prefix_pattern, $string, $matches);

        if (count($matches)) {
            $string = trim((string) $matches[2]);
            $prefix = trim((string) $matches[1]);
        } else {
            $prefix = null;
        }

        return array('string' => $string, 'prefix' => $prefix);
    } // trim_prefix

    /**
     * @param $year
     * @return integer
     */
    public static function normalize_year($year)
    {
        if (empty($year)) {
            return 0;
        }

        $year = (int) ($year);
        if ($year < 0 || $year > 9999) {
            return 0;
        }

        return $year;
    }

    /**
     * trim_slashed_list
     * Return only the first item from / separated list
     * @param string $string
     * @return string
     */
    public static function trim_slashed_list($string)
    {
        $first = '';
        if ($string) {
            $items = explode("\x00", $string);
            $first = trim((string) $items[0]);
            // if first is the same as string, nothing was exploded, try other delimiters
            if ($first === $string) {
                // try splitting with ; and then /
                $items = explode(";", $string);
                $first = trim((string) $items[0]);
                if ($first === $string) {
                    $items = explode("/", $string);
                    $first = trim((string) $items[0]);
                }
            }
        }

        return $first;
    } // trim_slashed_list

    /**
     * trim_featuring
     * Splits artists featuring from the string
     * @param string $string
     * @return array
     */
    public static function trim_featuring($string)
    {
        return array_map('trim', explode(' feat. ', $string));
    } // trim_featuring

    /**
     * check_title
     * this checks to make sure something is
     * set on the title, if it isn't it looks at the
     * filename and trys to set the title based on that
     * @param string $title
     * @param string $file
     * @return string
     */
    public static function check_title($title, $file = '')
    {
        if (strlen(trim((string) $title)) < 1) {
            $title = Dba::escape($file);
        }

        return $title;
    } // check_title

    /**
     * check_length
     * Check to make sure the string fits into the database
     * max_length is the maximum number of characters that the (varchar) column can hold
     * @param string $string
     * @param integer $max_length
     * @return string
     */
    public static function check_length($string, $max_length = 255)
    {
        $string = (string) $string;
        if (false !== $encoding = mb_detect_encoding($string, null, true)) {
            $string = trim(mb_substr($string, 0, $max_length, $encoding));
        } else {
            $string = trim(substr($string, 0, $max_length));
        }

        return $string;
    }

    /**
     * check_track
     * Check to make sure the track number fits into the database: max 32767, min -32767
     *
     * @param string $track
     * @return integer
     */
    public static function check_track($track)
    {
        $retval = ((int) $track > 32767 || (int) $track < -32767) ? (int) substr($track, -4, 4) : (int) $track;
        if ((int) $track !== $retval) {
            debug_event(self::class, "check_track: '{$track}' out of range. Changed into '{$retval}'", 4);
        }

        return $retval;
    }

    /**
     * check_int
     * Check to make sure a number fits into the database
     *
     * @param integer $track
     * @param integer $max
     * @param integer $min
     * @return integer
     */
    public static function check_int($track, $max, $min)
    {
        if ($track > $max) {
            return $max;
        }
        if ($track < $min) {
            return $min;
        }

        return $track;
    }

    /**
     * get_unique_string
     * Check to make sure the string doesn't have duplicate strings ({)e.g. "Enough Records; Enough Records")
     *
     * @param string $str_array
     * @return string
     */
    public static function get_unique_string($str_array)
    {
        $array = array_unique(array_map('trim', explode(';', $str_array)));

        return implode($array);
    }

    /**
     * playlist_import
     * Attempts to create a Public Playlist based on the playlist file
     * @param string $playlist
     * @return array
     */
    public static function import_playlist($playlist)
    {
        $data = file_get_contents($playlist);
        if (substr($playlist, -3, 3) == 'm3u' || substr($playlist, -4, 4) == 'm3u8') {
            $files = self::parse_m3u($data);
        } elseif (substr($playlist, -3, 3) == 'pls') {
            $files = self::parse_pls($data);
        } elseif (substr($playlist, -3, 3) == 'asx') {
            $files = self::parse_asx($data);
        } elseif (substr($playlist, -4, 4) == 'xspf') {
            $files = self::parse_xspf($data);
        }

        $songs = array();
        $pinfo = pathinfo($playlist);
        if (isset($files)) {
            foreach ($files as $file) {
                $file = trim((string) $file);
                // Check to see if it's a url from this ampache instance
                if (substr($file, 0, strlen(AmpConfig::get('web_path'))) == AmpConfig::get('web_path')) {
                    $data       = Stream_URL::parse($file);
                    $sql        = 'SELECT COUNT(*) FROM `song` WHERE `id` = ?';
                    $db_results = Dba::read($sql, array($data['id']));
                    if (Dba::num_rows($db_results)) {
                        $songs[] = $data['id'];
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
                    debug_event(self::class, 'Add file ' . $file . ' to playlist.', 5);

                    // First, try to found the file as absolute path
                    $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
                    $db_results = Dba::read($sql, array($file));
                    $results    = Dba::fetch_assoc($db_results);

                    if (isset($results['id'])) {
                        $songs[] = $results['id'];
                    } else {
                        // Not found in absolute path, create it from relative path
                        $file = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $file;
                        // Normalize the file path. realpath requires the files to exists.
                        $file = realpath($file);
                        if ($file) {
                            $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
                            $db_results = Dba::read($sql, array($file));
                            $results    = Dba::fetch_assoc($db_results);

                            if (isset($results['id'])) {
                                $songs[] = $results['id'];
                            }
                        }
                    }
                } // if it's a file
            }
        }

        debug_event(self::class, "import_playlist Parsed " . $playlist . ", found " . count($songs) . " songs", 5);

        if (count($songs)) {
            $name = $pinfo['extension'] . " - " . $pinfo['filename'];
            // Search for existing playlist
            $playlist_search = Playlist::get_playlists(null, $name);
            if (empty($playlist_search)) {
                // New playlist
                $playlist_id   = Playlist::create($name, 'public');
                $current_songs = array();
                $playlist      = ((int) $playlist_id > 0) ? new Playlist((int) $playlist_id) : null;
            } else {
                // Existing playlist
                $playlist_id    = $playlist_search[0];
                $playlist       = new Playlist($playlist_id);
                $current_songs  = $playlist->get_songs();
                debug_event(self::class, "import_playlist playlist has " . (string) count($current_songs) . " songs", 5);
            }

            if (!$playlist_id) {
                return array(
                    'success' => false,
                    'error' => T_('Failed to create playlist'),
                );
            }

            /* Recreate the Playlist; checking for current items. */
            $new_songs = $songs;
            if (count($current_songs)) {
                $new_songs = array_diff($songs, $current_songs);
                debug_event(self::class, "import_playlist filtered existing playlist, found " . count($new_songs) . " new songs", 5);
            }
            $playlist->add_songs($new_songs, (bool) AmpConfig::get('unique_playlist'));

            return array(
                'success' => true,
                'id' => $playlist_id,
                'count' => count($new_songs)
            );
        }

        return array(
            'success' => false,
            'error' => T_('No valid songs found in playlist file')
        );
    }

    /**
     * parse_m3u
     * this takes m3u filename and then attempts to found song filenames listed in the m3u
     * @param string $data
     * @return array
     */
    public static function parse_m3u($data)
    {
        $files   = array();
        $results = explode("\n", $data);

        foreach ($results as $value) {
            $value = trim((string) $value);
            if (!empty($value) && substr($value, 0, 1) != '#') {
                $files[] = $value;
            }
        }

        return $files;
    } // parse_m3u

    /**
     * parse_pls
     * this takes pls filename and then attempts to found song filenames listed in the pls
     * @param string $data
     * @return array
     */
    public static function parse_pls($data)
    {
        $files   = array();
        $results = explode("\n", $data);

        foreach ($results as $value) {
            $value = trim((string) $value);
            if (preg_match("/file[0-9]+[\s]*\=(.*)/i", $value, $matches)) {
                $file = trim((string) $matches[1]);
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
     * @param string $data
     * @return array
     */
    public static function parse_asx($data)
    {
        $files = array();
        $xml   = simplexml_load_string($data);

        if ($xml) {
            foreach ($xml->entry as $entry) {
                $file = trim((string) $entry->ref['href']);
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
     * @param string $data
     * @return array
     */
    public static function parse_xspf($data)
    {
        $files = array();
        $xml   = simplexml_load_string($data);
        if ($xml) {
            foreach ($xml->trackList->track as $track) {
                $file = trim((string) $track->location);
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
     * @param integer $catalog_id
     * @return boolean
     */
    public static function delete($catalog_id)
    {
        // Large catalog deletion can take time
        set_time_limit(0);

        // First remove the songs in this catalog
        $sql        = "DELETE FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, array($catalog_id));

        // Only if the previous one works do we go on
        if (!$db_results) {
            return false;
        }
        self::clean_empty_albums();

        $sql        = "DELETE FROM `video` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, array($catalog_id));

        if (!$db_results) {
            return false;
        }
        $catalog = self::create_from_id($catalog_id);

        if (!$catalog->id) {
            return false;
        }

        $sql        = 'DELETE FROM `catalog_' . $catalog->get_type() . '` WHERE catalog_id = ?';
        $db_results = Dba::write($sql, array($catalog_id));

        if (!$db_results) {
            return false;
        }

        // Next Remove the Catalog Entry it's self
        $sql = "DELETE FROM `catalog` WHERE `id` = ?";
        Dba::write($sql, array($catalog_id));

        // run garbage collection
        self::garbage_collection();

        return true;
    } // delete

    /**
     * exports the catalog
     * it exports all songs in the database to the given export type.
     * @param string $type
     * @param integer|null $catalog_id
     */
    public static function export($type, $catalog_id = null)
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
        $db_results  = Dba::read($sql, $params);
        $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : "Y-m-d\TH:i:s\Z";

        switch ($type) {
            case 'itunes':
                echo xml_get_header('itunes');
                while ($results = Dba::fetch_assoc($db_results)) {
                    $song = new Song($results['id']);
                    $song->format();

                    $xml                         = array();
                    $xml['key']                  = $results['id'];
                    $xml['dict']['Track ID']     = (int) ($results['id']);
                    $xml['dict']['Name']         = $song->title;
                    $xml['dict']['Artist']       = $song->f_artist_full;
                    $xml['dict']['Album']        = $song->f_album_full;
                    $xml['dict']['Total Time']   = (int) ($song->time) * 1000; // iTunes uses milliseconds
                    $xml['dict']['Track Number'] = (int) ($song->track);
                    $xml['dict']['Year']         = (int) ($song->year);
                    $xml['dict']['Date Added']   = get_datetime($time_format, (int) $song->addition_time);
                    $xml['dict']['Bit Rate']     = (int) ($song->bitrate / 1000);
                    $xml['dict']['Sample Rate']  = (int) ($song->rate);
                    $xml['dict']['Play Count']   = (int) ($song->played);
                    $xml['dict']['Track Type']   = "URL";
                    $xml['dict']['Location']     = $song->play_url();
                    echo (string) xoutput_from_array($xml, true, 'itunes');
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
                        $song->f_album_full . '","' .
                        $song->f_time . '","' .
                        $song->f_track . '","' .
                        $song->year . '","' .
                        get_datetime($time_format, (int) $song->addition_time) . '","' .
                        $song->f_bitrate . '","' .
                        $song->played . '","' .
                        $song->file . '"' . "\n";
                }
                break;
        } // end switch
    }
    // export

    /**
     * Updates album tags from given song
     * @param Song $song
     */
    protected static function updateAlbumTags(Song $song)
    {
        $tags = self::getSongTags('album', $song->album);
        Tag::update_tag_list(implode(',', $tags), 'album', $song->album, true);
    }

    /**
     * Updates artist tags from given song
     * @param Song $song
     */
    protected static function updateArtistTags(Song $song)
    {
        $tags = self::getSongTags('artist', $song->artist);
        Tag::update_tag_list(implode(',', $tags), 'artist', $song->artist, true);
    }

    /**
     * Get all tags from all Songs from [type] (artist, album, ...)
     * @param string $type
     * @param integer $object_id
     * @return array
     */
    protected static function getSongTags($type, $object_id)
    {
        $tags       = array();
        $db_results = Dba::read('SELECT `tag`.`name` FROM `tag`'
                        . ' JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id`'
                        . ' JOIN `song` ON `tag_map`.`object_id` = `song`.`id`'
                        . ' WHERE `song`.`' . $type . '` = ? AND `tag_map`.`object_type` = "song"'
                        . ' GROUP BY `tag`.`id`, `tag`.`name`', array($object_id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[] = $row['name'];
        }

        return $tags;
    }

    /**
     * @param Artist|Album|Song|Video|Podcast_Episode|Label|TVShow|TVShow_Season $libitem
     * @param integer|null $user_id
     * @return boolean
     */
    public static function can_remove($libitem, $user_id = null)
    {
        if (!$user_id) {
            $user_id = Core::get_global('user')->id;
        }

        if (!$user_id) {
            return false;
        }

        if (!AmpConfig::get('delete_from_disk')) {
            return false;
        }

        return (Access::check('interface', 75) || ($libitem->get_user_owner() == $user_id && AmpConfig::get('upload_allow_remove')));
    }

    /**
     * process_action
     * @param string $action
     * @param $catalogs
     * @param array $options
     */
    public static function process_action($action, $catalogs, $options = null)
    {
        if (!$options || !is_array($options)) {
            $options = array();
        }

        switch ($action) {
            case 'add_to_all_catalogs':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'add_to_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog($options);
                        }
                    }

                    if (!defined('SSE_OUTPUT')) {
                        AmpError::display('catalog_add');
                    }
                    Album::update_album_artist();
                }
                break;
            case 'update_all_catalogs':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'update_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->verify_catalog();
                        }
                    }
                }
                break;
            case 'full_service':
                if (!$catalogs) {
                    $catalogs = self::get_catalogs();
                }

                /* This runs the clean/verify/add in that order */
                foreach ($catalogs as $catalog_id) {
                    $catalog = self::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        $catalog->clean_catalog();
                        $catalog->verify_catalog();
                        $catalog->add_to_catalog();
                    }
                }
                Album::update_album_artist();
                Dba::optimize_tables();
                break;
            case 'clean_all_catalogs':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'clean_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->clean_catalog();
                        }
                    } // end foreach catalogs
                    Dba::optimize_tables();
                }
                break;
            case 'update_from':
                $catalog_id = 0;
                // First see if we need to do an add
                if ($options['add_path'] != '/' && strlen((string) $options['add_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($options['add_path'])) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog(array('subdirectory' => $options['add_path']));
                            Album::update_album_artist();
                        }
                    }
                } // end if add

                // Now check for an update
                if ($options['update_path'] != '/' && strlen((string) $options['update_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($options['update_path'])) {
                        $songs = Song::get_from_path($options['update_path']);
                        foreach ($songs as $song_id) {
                            self::update_single_item('song', $song_id);
                        }
                    }
                } // end if update

                if ($catalog_id < 1) {
                    AmpError::add('general', T_("This subdirectory is not inside an existing Catalog. The update can not be processed."));
                }
                break;
            case 'gather_media_art':
                if (!$catalogs) {
                    $catalogs = self::get_catalogs();
                }

                // Iterate throughout the catalogs and gather as needed
                foreach ($catalogs as $catalog_id) {
                    $catalog = self::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        require AmpConfig::get('prefix') . UI::find_template('show_gather_art.inc.php');
                        flush();
                        $catalog->gather_art();
                    }
                }
                break;
            case 'update_all_file_tags':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'update_file_tags':
                $write_id3 = AmpConfig::get('write_id3', false);
                AmpConfig::set('write_id3', 'true', true);
                $write_id3_art = AmpConfig::get('write_id3_art', false);
                AmpConfig::set('write_id3_art', 'true', true);
                foreach ($catalogs as $catalog_id) {
                    $catalog = self::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        $song_ids = $catalog->get_song_ids();
                        foreach ($song_ids as $song_id) {
                            $song = new Song($song_id);
                            $song->format();
                            $song->write_id3();
                        }
                    }
                }
                AmpConfig::set('write_id3', $write_id3, true);
                AmpConfig::set('write_id3', $write_id3_art, true);
        }

        // Remove any orphaned artists/albums/etc.
        if (!AmpConfig::get('cron_cache')) {
            self::garbage_collection();
        }
    }

    /**
     * Migrate an object associate images to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        if ($old_object_id != $new_object_id) {
            debug_event(self::class, 'migrate ' . $object_type . ' from ' . $old_object_id . ' to ' . $new_object_id, 4);

            Stats::migrate($object_type, $old_object_id, $new_object_id);
            UserActivity::migrate($object_type, $old_object_id, $new_object_id);
            Recommendation::migrate($object_type, $old_object_id, $new_object_id);
            Share::migrate($object_type, $old_object_id, $new_object_id);
            Shoutbox::migrate($object_type, $old_object_id, $new_object_id);
            Tag::migrate($object_type, $old_object_id, $new_object_id);
            Userflag::migrate($object_type, $old_object_id, $new_object_id);
            Rating::migrate($object_type, $old_object_id, $new_object_id);
            Art::migrate($object_type, $old_object_id, $new_object_id);

            return true;
        }

        return false;
    }
} // end catalog.class
