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

/**
 * Album Class
 *
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 *
 */
class Album extends database_object implements library_item
{
    /* Variables from DB */

    /**
     *  @var integer $id
     */
    public $id;

    /**
     *  @var string $name
     */
    public $name;

    /**
     *  @var integer $album_artist
     */
    public $album_artist;

    /**
     *  @var integer $disk
     */
    public $disk;

    /**
     *  @var integer $year
     */
    public $year;

    /**
     *  @var string $prefix
     */
    public $prefix;

    /**
     *  @var string $mbid
     */
    public $mbid; // MusicBrainz ID

    /**
     *  @var string $mbid_group
     */
    public $mbid_group; // MusicBrainz Release Group ID

    /**
     * @var string $release_type
     */
    public $release_type;

    /**
     *  @var string $catalog_number
     */
    public $catalog_number;

    /**
     *  @var string $barcode
     */
    public $barcode;

    /**
     *  @var integer $time
     */
    public $time;

    /**
     *  @var integer $total_duration
     */
    public $total_duration;
    /**
     *  @var integer $original_year
     */
    public $original_year;

    /**
     * @var integer $catalog_id
     */
    public $catalog_id;

    /**
     *  @var integer $song_count
     */
    public $song_count;

    /**
     *  @var string $artist_prefix
     */
    public $artist_prefix;

    /**
     *  @var string $artist_name
     */
    public $artist_name;

    /**
     *  @var integer $artist_id
     */
    public $artist_id;

    /**
     *  @var array $tags
     */
    public $tags;

    /**
     *  @var string $full_name
     */
    public $full_name; // Prefix + Name, generated

    /**
     *  @var integer $artist_count
     */
    public $artist_count;

    /**
     *  @var string $f_artist_name
     */
    public $f_artist_name;

    /**
     *  @var string $f_artist_link
     */
    public $f_artist_link;

    /**
     *  @var string $f_artist
     */
    public $f_artist;

    /**
     *  @var string $album_artist_name
     */
    public $album_artist_name;

    /**
     *  @var string $f_album_artist_name
     */
    public $f_album_artist_name;

    /**
     *  @var string $f_album_artist_link
     */
    public $f_album_artist_link;

    /**
     *  @var string $f_name
     */
    public $f_name;

    /**
     *  @var string $link
     */
    public $link;

    /**
     *  @var string $f_link
     */
    public $f_link;

    /**
     *  @var string $f_tags
     */
    public $f_tags;

    /**
     * @var string $f_year
     */
    public $f_year;

    /**
      * @var string f_year_link
      */
    public $f_year_link;

    /**
     *  @var string $f_title
     */
    public $f_title;

    /**
     *  @var string $f_release_type
     */
    public $f_release_type;

    // cached information

    /**
     *  @var boolean $_fake
     */
    public $_fake;

    /**
     *  @var array $_songs
     */
    public $_songs = array();

    /**
     *  @var array $_mapcache
     */
    private static $_mapcache = array();

    /**
     *  @var array $album_suite
     */
    public $album_suite = array();

    /**
     *  @var boolean $allow_group_disks
     */
    public $allow_group_disks = false;

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     * @param integer $album_id
     */
    public function __construct($album_id)
    {
        /* Get the information from the db */
        $info = $this->get_info($album_id);

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
        if (!$this->time) {
            $this->time = $this->update_time();
        }

        // Little bit of formatting here
        $this->full_name      = trim(trim((string) $info['prefix']) . ' ' . trim((string) $info['name']));
        $this->total_duration = $this->time;

        // Looking for other albums with same mbid, ordering by disk ascending
        if (AmpConfig::get('album_group')) {
            $this->allow_group_disks = true;
            $this->album_suite       = $this->get_album_suite();
            $this->total_duration    = $this->get_total_duration($this->album_suite);
        }

        return true;
    } // constructor

    /**
     * construct_from_array
     * This is often used by the metadata class, it fills out an album object from a
     * named array, _fake is set to true
     * @param array $data
     * @return Album
     */
    public static function construct_from_array(array $data)
    {
        $album = new Album(0);
        foreach ($data as $key => $value) {
            $album->$key = $value;
        }

        $album->_fake = true;   // Make sure that we tell em it's fake

        return $album;
    } // construct_from_array

    /**
     * garbage_collection
     *
     * Cleans out unused albums
     */
    public static function garbage_collection()
    {
        Dba::write('DELETE FROM `album` USING `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` WHERE `song`.`id` IS NULL');
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * with a single query
     * @param array $ids
     * @return boolean
     */
    public static function build_cache(array $ids)
    {
        if (empty($ids)) {
            return false;
        }
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `album` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('album', $row['id'], $row);
        }

        return true;
    } // build_cache

    /**
     * _get_extra_info
     * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
     * do it
     * @param string $limit_threshold
     * @return array
     */
    private function _get_extra_info($limit_threshold = '')
    {
        if (!$this->id) {
            return array();
        }
        if (parent::is_cached('album_extra', $this->id)) {
            return parent::get_from_cache('album_extra', $this->id);
        }

        $full_name    = Dba::escape($this->full_name);
        $release_type = "is null";
        $mbid         = "is null";
        $artist       = "is null";
        // for all the artists who love using bad strings for album titles!
        if (strpos($this->full_name, '>') || strpos($this->full_name, '<') || strpos($this->full_name, '\\')) {
            $full_name = Dba::escape(str_replace(array('<', '>', '\\'), '_', $this->full_name));
            $name_sql  = "LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) LIKE '$full_name' AND ";
        } else {
            $name_sql = "LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = '$full_name' AND ";
        }
        if ($this->release_type) {
            $release_type = "= '" . ucwords((string) $this->release_type) . "'";
        }
        if ($this->mbid) {
            $mbid = "= '$this->mbid'";
        }
        if ($this->album_artist) {
            $artist = "= $this->album_artist";
        }

        // Calculation
        $sql = "SELECT " .
                "COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, " .
                "COUNT(`song`.`id`) AS `song_count`, " .
                "`song`.`catalog` AS `catalog_id` ";

        $suite_array = $this->album_suite;
        if (!count($suite_array)) {
            $suite_array[] = $this->id;
        }

        $sqlj   = '';
        $idlist = '(' . implode(',', $suite_array) . ')';
        if ($this->allow_group_disks) {
            $sql .= "FROM `album` ";
            $sqlj .= "LEFT JOIN `song` ON `song`.`album` = `album`.`id` ";
            $sqlw = "WHERE " . $name_sql .
                "`song`.`album` IN (SELECT `id` FROM `album` WHERE `album`.`release_type` $release_type AND " .
                "`album`.`mbid` $mbid AND `album`.`album_artist` $artist AND `album`.`year` = " . (string) $this->year . ") ";
        } else {
            $sql .= "FROM `song` ";
            $sqlw = "WHERE `song`.`album` IN $idlist ";
        }

        if (AmpConfig::get('catalog_disable')) {
            $sqlj .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $sqlw .= "AND `catalog`.`enabled` = '1' ";
        }
        if ($this->allow_group_disks) {
            $sqlw .= "GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`mbid`, `album`.`year`, `catalog_id`";
        } else {
            $sqlw .= "GROUP BY `song`.`album`, `catalog_id`";
        }
        $sql .= $sqlj . $sqlw;
        $db_results = Dba::read($sql);
        $results    = Dba::fetch_assoc($db_results);
        $where_sql  = "`album`.`release_type` $release_type AND " .
                      "`album`.`mbid` $mbid AND " .
                      "`album`.`album_artist` $artist AND " .
                      "`album`.`year` = " . (string) $this->year;

        if ($artist == "is null") {
            // no album_artist is set
            // Get associated information from first song only
            $sql = "SELECT MIN(`song`.`id`) AS `song_id`, " .
                   "`artist`.`name` AS `artist_name`, " .
                   "`artist`.`prefix` AS `artist_prefix`, " .
                   "MIN(`artist`.`id`) AS `artist_id` " .
                   "FROM `album` " .
                   "LEFT JOIN `song` ON `song`.`album` = `album`.`id` " .
                   "INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
                   "WHERE `song`.`album` IN (SELECT `id` FROM `album` WHERE " .
                   $name_sql . $where_sql . ") " .
                   "GROUP BY `artist`.`prefix`, `artist`.`name`, `album`.`prefix`, `album`.`name`, `album`.`release_type`, `album`.`mbid`, `album`.`year` " .
                   "LIMIT 1";
        } else {
            // album_artist is set
            $sql = "SELECT DISTINCT `artist`.`name` AS `artist_name`, " .
                   "`artist`.`prefix` AS `artist_prefix`, " .
                   "MIN(`artist`.`id`) AS `artist_id` " .
                   "FROM `album` " .
                   "LEFT JOIN `artist` ON `artist`.`id`=`album`.`album_artist` WHERE " .
                   $name_sql . $where_sql . " " .
                   "GROUP BY `artist`.`prefix`, `artist`.`name`, `album`.`prefix`, `album`.`name`, `album`.`release_type`, `album`.`mbid`, `album`.`year`";
        }
        $db_results = Dba::read($sql);
        $results    = array_merge($results, Dba::fetch_assoc($db_results));

        $art = new Art($this->id, 'album');
        $art->has_db_info();
        $results['has_art']   = make_bool($art->raw);
        $results['has_thumb'] = make_bool($art->thumb);

        if (AmpConfig::get('show_played_times')) {
            $results['object_cnt'] = Stats::get_object_count('album', $this->id, $limit_threshold);
        }

        parent::add_to_cache('album_extra', $this->id, $results);

        return $results;
    } // _get_extra_info

    /**
     * can_edit
     * @param integer $user_id
     * @return boolean
     */
    public function can_edit($user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }

        if (!$user_id) {
            return false;
        }

        if (Access::check('interface', 50, $user_id)) {
            return true;
        }

        if (!$this->album_artist) {
            return false;
        }

        if (!AmpConfig::get('upload_allow_edit')) {
            return false;
        }

        $owner = $this->get_user_owner();

        return ($owner === $user_id);
    }

    /**
     * check
     *
     * Searches for an album; if none is found, insert a new one.
     * @param string $name
     * @param integer $year
     * @param integer $disk
     * @param string $mbid
     * @param string $mbid_group
     * @param string $album_artist
     * @param string $release_type
     * @param integer $original_year
     * @param string $barcode
     * @param string $catalog_number
     * @param boolean $readonly
     * @return integer
     */
    public static function check($name, $year = 0, $disk = 1, $mbid = null, $mbid_group = null, $album_artist = null, $release_type = null, $original_year = 0, $barcode = null, $catalog_number = null, $readonly = false)
    {
        $trimmed        = Catalog::trim_prefix(trim((string) $name));
        $name           = $trimmed['string'];
        $prefix         = $trimmed['prefix'];
        $album_artist   = (int) $album_artist;
        $album_artist   = ($album_artist < 1) ? null : $album_artist;
        $mbid           = empty($mbid) ? null : $mbid;
        $mbid_group     = empty($mbid_group) ? null : $mbid_group;
        $release_type   = empty($release_type) ? null : $release_type;
        $disk           = (self::sanitize_disk($disk) < 1) ? 1 : self::sanitize_disk($disk);
        $original_year  = ((int) substr((string) $original_year, 0, 4) < 1) ? null : substr((string) $original_year, 0, 4);
        $barcode        = empty($barcode) ? null : $barcode;
        $catalog_number = empty($catalog_number) ? null : $catalog_number;

        if (!$name) {
            $name          = T_('Unknown (Orphaned)');
            $year          = 0;
            $original_year = 0;
            $disk          = 1;
            $album_artist  = null;
        }
        if (isset(self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist])) {
            return self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist];
        }

        $sql    = "SELECT MIN(`album`.`id`) AS `id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`disk` = ? AND `album`.`year` = ? ";
        $params = array($name, $name, $disk, $year);

        if ($mbid) {
            $sql .= 'AND `album`.`mbid` = ? ';
            $params[] = $mbid;
        } else {
            $sql .= 'AND `album`.`mbid` IS NULL ';
        }
        if ($prefix) {
            $sql .= 'AND `album`.`prefix` = ? ';
            $params[] = $prefix;
        }
        if ($album_artist) {
            $sql .= 'AND `album`.`album_artist` = ? ';
            $params[] = $album_artist;
        }
        if ($original_year) {
            $sql .= 'AND `album`.`original_year` = ? ';
            $params[] = $original_year;
        }
        if ($release_type) {
            $sql .= 'AND `album`.`release_type` = ? ';
            $params[] = $release_type;
        }

        $db_results = Dba::read($sql, $params);

        if ($row = Dba::fetch_assoc($db_results)) {
            $album_id = (int) $row['id'];
            if ($album_id > 0) {
                // cache the album id against it's details
                self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist] = $album_id;

                return $album_id;
            }
        }

        if ($readonly) {
            return 0;
        }

        $sql = 'INSERT INTO `album` (`name`, `prefix`, `year`, `disk`, `mbid`, `mbid_group`, `release_type`, `album_artist`, `original_year`, `barcode`, `catalog_number`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array($name, $prefix, $year, $disk, $mbid, $mbid_group, $release_type, $album_artist, $original_year, $barcode, $catalog_number));
        if (!$db_results) {
            return 0;
        }

        $album_id = Dba::insert_id();
        debug_event(self::class, 'Album check created new album id ' . $album_id, 4);
        // Remove from wanted album list if any request on it
        if (!empty($mbid) && AmpConfig::get('wanted')) {
            try {
                Wanted::delete_wanted_release((string) $mbid);
            } catch (Exception $error) {
                debug_event(self::class, 'Cannot process wanted releases auto-removal check: ' . $error->getMessage(), 2);
            }
        }

        self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist] = $album_id;

        return $album_id;
    }

    /**
     * get_songs
     * gets the songs for this album takes an optional limit
     * and an optional artist, if artist is passed it only gets
     * songs with this album + specified artist
     * @param integer $limit
     * @param string $artist
     * @return integer[]
     */
    public function get_songs($limit = 0, $artist = '')
    {
        $results = array();

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`album` = ? ";
        $params = array($this->id);
        if (strlen((string) $artist)) {
            $sql .= "AND `artist` = ? ";
            $params[] = $artist;
        }
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`track`, `song`.`title`";
        if ($limit) {
            $sql .= " LIMIT " . (string) $limit;
        }
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_songs

    /**
     * get_http_album_query_ids
     * return the html album parameters with all album suite ids
     * @param string $url_param_name
     * @return string
     */
    public function get_http_album_query_ids($url_param_name)
    {
        if ($this->allow_group_disks) {
            $suite_array = $this->get_group_disks_ids();
        } else {
            $suite_array = array($this->id);
        }

        return http_build_query(array($url_param_name => $suite_array));
    }

    /**
     * get_http_album_query_id
     * return the html album parameters for a single album id
     * @param string $url_param_name
     * @return string
     */
    public function get_http_album_query_id($url_param_name)
    {
        return http_build_query(array($url_param_name => array($this->id)));
    }

    /**
     * get_group_disks_ids
     * return all album suite ids or current album if no albums
     * @return integer[]
     */
    public function get_group_disks_ids()
    {
        $suite_array = $this->album_suite;
        if (!count($suite_array)) {
            $suite_array[] = $this->id;
        }

        return $suite_array;
    }

    /**
     * get_album_suite
     * gets the album ids with the same musicbrainz identifier
     * @param integer $catalog
     * @return integer[]
     */
    public function get_album_suite($catalog = 0)
    {
        $full_name = Dba::escape($this->full_name);
        if ($full_name == '') {
            return array();
        }
        $album_artist = "is null";
        $release_type = "is null";
        $mbid         = "is null";
        $year         = (string) $this->year;

        if ($this->album_artist) {
            $album_artist = "= '" . ucwords((string) $this->album_artist) . "'";
        }
        if ($this->release_type) {
            $release_type = "= '" . ucwords((string) $this->release_type) . "'";
        }
        if ($this->mbid) {
            $mbid = "= '$this->mbid'";
        }
        $results       = array();
        $where         = "WHERE `album`.`album_artist` $album_artist AND `album`.`mbid` $mbid AND `album`.`release_type` $release_type AND " .
                         "(`album`.`name` = '$full_name' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = '$full_name') " .
                         "AND `album`.`year` = $year ";
        $catalog_where = "";
        $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";

        if ($catalog) {
            $catalog_where .= " AND `catalog`.`id` = '$catalog'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
        }

        $sql = "SELECT DISTINCT `album`.`id`, MAX(`album`.`disk`) AS `disk` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " .
                "$where $catalog_where GROUP BY `album`.`id` ORDER BY `album`.`disk` ASC";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['disk']] = $row['id'];
        }

        return $results;
    } // get_album_suite

    /**
     * has_track
     * This checks to see if this album has a track of the specified title
     * @param string $title
     * @return array
     */
    public function has_track($title)
    {
        $sql        = "SELECT `id` FROM `song` WHERE `album` = ? AND `title` = ?";
        $db_results = Dba::read($sql, array($this->id, $title));

        return Dba::fetch_assoc($db_results);
    } // has_track

    /**
     * get_addtime_first_song
     * Get the add date of first added song.
     * @return integer
     */
    public function get_addtime_first_song()
    {
        $time = 0;

        $sql        = "SELECT MIN(`addition_time`) AS `addition_time` FROM `song` WHERE `album` = ?";
        $db_results = Dba::read($sql, array($this->id));
        if ($data = Dba::fetch_row($db_results)) {
            $time = $data[0];
        }

        return $time;
    }

    /**
     * format
     * This is the format function for this object. It sets cleaned up
     * album information with the base required
     * f_link, f_name
     * @param boolean $details
     * @param string $limit_threshold
     */
    public function format($details = true, $limit_threshold = '')
    {
        $web_path = AmpConfig::get('web_path');

        $this->f_release_type = ucwords((string) $this->release_type);

        if ($details) {
            /* Pull the advanced information */
            $data = $this->_get_extra_info($limit_threshold);
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }

            if ($this->album_artist) {
                $album_artist = new Artist($this->album_artist);
                $album_artist->format();
                $this->album_artist_name   = $album_artist->name;
                $this->f_album_artist_name = $album_artist->f_name;
                $this->f_album_artist_link = "<a href=\"" . $web_path . "/artists.php?action=show&artist=" . $this->album_artist . "\" title=\"" . scrub_out($this->album_artist_name) . "\">" . $this->f_album_artist_name . "</a>";
            }

            $this->tags   = Tag::get_top_tags('album', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'album');
        }

        /* Truncate the string if it's to long */
        $this->f_name = $this->full_name;

        $this->link   = $web_path . '/albums.php?action=show&album=' . scrub_out($this->id);
        $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . scrub_out($this->full_name) . "\">" . scrub_out($this->f_name);

        // Looking if we need to combine or display disks
        if ($this->disk && !$this->allow_group_disks && count($this->get_album_suite()) > 1) {
            $this->f_link .= " <span class=\"discnb\">[" . T_('Disk') . " " . $this->disk . "]</span>";
        }

        $this->f_link .= "</a>";

        $this->f_title = $this->full_name;
        if ($this->artist_count == '1') {
            $artist              = trim(trim((string) $this->artist_prefix) . ' ' . trim((string) $this->artist_name));
            $this->f_artist_name = $artist;
            $this->f_artist_link = "<a href=\"$web_path/artists.php?action=show&artist=" . $this->artist_id . "\" title=\"" . scrub_out($this->artist_name) . "\">" . $artist . "</a>";
            $this->f_artist      = $artist;
        } else {
            $this->f_artist_link = "<span title=\"$this->artist_count " . T_('Artists') . "\">" . T_('Various') . "</span>";
            $this->f_artist      = T_('Various');
            $this->f_artist_name = $this->f_artist;
        }

        if (!$this->year) {
            $this->f_year = "N/A";
        } else {
            $year              = $this->year;
            $this->f_year_link = "<a href=\"$web_path/search.php?type=album&action=search&limit=0rule_1=year&rule_1_operator=2&rule_1_input=" . $year . "\">" . $year . "</a>";
        }

        if (!$this->time) {
            $this->time = $this->update_time();
        }
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords               = array();
        $keywords['mb_albumid'] = array('important' => false,
            'label' => T_('Album MusicBrainzID'),
            'value' => $this->mbid);
        $keywords['mb_albumid_group'] = array('important' => false,
            'label' => T_('Release Group MusicBrainzID'),
            'value' => $this->mbid_group);
        $keywords['artist'] = array('important' => true,
            'label' => T_('Artist'),
            'value' => (($this->artist_count < 2) ? $this->f_artist_name : ''));
        $keywords['album'] = array('important' => true,
            'label' => T_('Album'),
            'value' => $this->f_name);

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        if ($this->artist_count == 1) {
            return array('object_type' => 'artist', 'object_id' => $this->artist_id);
        }

        return null;
    }

    /**
     * Get item children.
     * @return array
     */
    public function get_childrens()
    {
        return $this->get_medias();
    }

    /**
     * get_time
     *
     * Get time for an album disk.
     * @param integer $album_id
     * @return integer
     */
    public static function get_time($album_id)
    {
        $params     = array($album_id);
        $sql        = "SELECT SUM(`song`.`time`) AS `time` from `song` WHERE `song`.`album` = ?";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['time'];
    }

    /**
     * get_total_duration
     *
     * Get time for a whole album
     * @param array $album_ids
     * @return integer
     */
    public function get_total_duration($album_ids)
    {
        $total_duration = 0;
        foreach ($album_ids as $object_id) {
            $total_duration = self::get_time((int) $object_id) + $total_duration;
        }

        return $total_duration;
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "song";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $search['rule_1_input']    = $this->name;
        $search['rule_1_operator'] = 4;
        $search['rule_1']          = "album";
        $search['rule_2_input']    = $this->album_artist_name;
        $search['rule_2_operator'] = 4;
        $search['rule_2']          = "artist";
        $songs                     = Search::run($search);

        $childrens = array();
        foreach ($songs as $song_id) {
            $childrens[] = array(
                'object_type' => 'song',
                'object_id' => $song_id
            );
        }

        return $childrens;
    }

    /**
     * Get all children and sub-childrens media.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if (!$filter_type || $filter_type == 'song') {
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => 'song',
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog_id);
    }

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner()
    {
        if (!$this->album_artist) {
            return null;
        }

        $artist = new Artist($this->album_artist);

        return $artist->get_user_owner();
    }

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * get_description
     * @return string
     */
    public function get_description()
    {
        // Album description is not supported yet, always return artist description
        $artist = new Artist($this->artist_id);

        return $artist->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $album_id = null;
        $type     = null;

        if (Art::has_db($this->id, 'album')) {
            $album_id = $this->id;
            $type     = 'album';
        } else {
            if (Art::has_db($this->artist_id, 'artist') || $force) {
                $album_id = $this->artist_id;
                $type     = 'artist';
            }
        }

        if ($album_id !== null && $type !== null) {
            $title = '[' . ($this->f_album_artist_name ?: $this->f_artist) . '] ' . $this->f_name;
            Art::display($type, $album_id, $title, $thumb, $this->link);
        }
    }

    /**
     * get_random_songs
     * gets a random number, and a random assortment of songs from this album
     * @return integer[]
     */
    public function get_random_songs()
    {
        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`album` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY RAND()";
        $db_results = Dba::read($sql, array($this->id));

        $results = array();
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_random_songs

    /**
     * update_time
     *
     * Get time for an album disk and set it.
     * @return integer
     */
    public function update_time()
    {
        $time = self::get_time((int) $this->id);
        if ($time !== $this->time && $this->id) {
            $sql = "UPDATE `album` SET `time`=$time WHERE `id`=" . $this->id;
            Dba::write($sql);
        }

        return $time;
    }

    /**
     * update
     * This function takes a key'd array of data and updates this object
     * as needed
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        $name           = (isset($data['name'])) ? $data['name'] : $this->name;
        //$artist         = isset($data['artist']) ? (int) $data['artist'] : $this->artist_id;
        $album_artist   = (isset($data['album_artist'])) ? (int) $data['album_artist'] : $this->album_artist;
        $year           = (isset($data['year'])) ? $data['year'] : $this->year;
        $disk           = (self::sanitize_disk($data['disk']) > 0) ? self::sanitize_disk($data['disk']) : $this->disk;
        $mbid           = (isset($data['mbid'])) ? $data['mbid'] : $this->mbid;
        $mbid_group     = (isset($data['mbid_group'])) ? $data['mbid_group'] : $this->mbid_group;
        $release_type   = (isset($data['release_type'])) ? $data['release_type'] : $this->release_type;
        $barcode        = (isset($data['barcode'])) ? $data['barcode'] : $this->barcode;
        $catalog_number = (isset($data['catalog_number'])) ? $data['catalog_number'] : $this->catalog_number;
        $original_year  = (isset($data['original_year'])) ? $data['original_year'] : $this->original_year;

        // If you have created an album_artist using 'add new...' we need to create a new artist
        if (!empty($data['album_artist_name'])) {
            $album_artist = Artist::check($data['album_artist_name']);
            self::update_field('album_artist', $album_artist, $this->id);
        }

        $current_id = $this->id;
        $updated    = false;
        $songs      = $this->get_songs();
        // run an album check on the current object READONLY means that it won't insert a new album
        $album_id   = self::check($name, $year, $disk, $mbid, $mbid_group, $album_artist, $release_type, $original_year, $barcode, $catalog_number, true);
        $cron_cache = AmpConfig::get('cron_cache');
        if ($album_id > 0 && $album_id != $this->id) {
            debug_event(self::class, "Updating $this->id to new id and migrating stats {" . $album_id . '}.', 4);
            foreach ($songs as $song_id) {
                Song::update_album($album_id, $song_id, $this->id);
                Song::update_year($year, $song_id);
                Song::write_id3_for_song($song_id);
            }
            $current_id = $album_id;
            $updated    = true;
            Stats::migrate('album', $this->id, $album_id);
            UserActivity::migrate('album', $this->id, $album_id);
            Recommendation::migrate('album', $this->id, $album_id);
            Share::migrate('album', $this->id, $album_id);
            Shoutbox::migrate('album', $this->id, $album_id);
            Tag::migrate('album', $this->id, $album_id);
            Userflag::migrate('album', $this->id, $album_id);
            Rating::migrate('album', $this->id, $album_id);
            Art::migrate('album', $this->id, $album_id);
            if (!$cron_cache) {
                self::garbage_collection();
            }
        } else {
            // run updates on the single fields
            if (!empty($name) && $name != $this->name) {
                self::update_field('name', $name, $this->id);
            }
            if (empty($data['album_artist_name']) && !empty($album_artist) && $album_artist != $this->album_artist) {
                self::update_field('album_artist', $album_artist, $this->id);
            }
            if (!empty($year) && $year != $this->year) {
                self::update_field('year', $year, $this->id);
                foreach ($songs as $song_id) {
                    Song::update_year($year, $song_id);
                    Song::write_id3_for_song($song_id);
                }
            }
            if (!empty($disk) && $disk != $this->disk) {
                self::update_field('disk', $disk, $this->id);
            }
            if (!empty($mbid) && $mbid != $this->mbid) {
                self::update_field('mbid', $mbid, $this->id);
            }
            if (!empty($mbid_group) && $mbid_group != $this->mbid_group) {
                self::update_field('mbid_group', $mbid_group, $this->id);
            }
            if (!empty($release_type) && $release_type != $this->release_type) {
                self::update_field('release_type', $release_type, $this->id);
            }
            if (!empty($catalog_number) && $catalog_number != $this->catalog_number) {
                self::update_field('catalog_number', $catalog_number, $this->id);
            }
            if (!empty($barcode) && $barcode != $this->barcode) {
                self::update_field('barcode', $barcode, $this->id);
            }
            if (!empty($original_year) && $original_year != $this->original_year) {
                self::update_field('original_year', $original_year, $this->id);
            }
        }

        $this->year           = $year;
        $this->mbid_group     = $mbid_group;
        $this->release_type   = $release_type;
        $this->name           = $name;
        $this->disk           = $disk;
        $this->mbid           = $mbid;
        $this->album_artist   = $album_artist;
        $this->original_year  = $original_year;
        $this->barcode        = $barcode;
        $this->catalog_number = $catalog_number;

        if ($updated && is_array($songs)) {
            foreach ($songs as $song_id) {
                Song::update_utime($song_id);
            } // foreach song of album
            if (!$cron_cache) {
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                Useractivity::garbage_collection();
            }
        } // if updated

        $override_childs = false;
        if ($data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if ($data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->update_tags($data['edit_tags'], $override_childs, $add_to_childs, true);
        }

        return $current_id;
    } // update

    /**
     * update_tags
     *
     * Update tags of albums and/or songs
     * @param string $tags_comma
     * @param boolean $override_childs
     * @param boolean $add_to_childs
     * @param boolean $force_update
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $force_update = false)
    {
        // When current_id not empty we force to overwrite current object
        Tag::update_tag_list($tags_comma, 'album', $this->id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                Tag::update_tag_list($tags_comma, 'song', $song_id, $override_childs);
            }
        }
    }

    /**
     * update_album_artist
     *
     * find albums that are missing an album_artist and generate one.
     * @param array $album_ids
     */
    public static function update_album_artist($album_ids = array())
    {
        $results = $album_ids;
        if (empty($results)) {
            // Find all albums that are missing an album artist
            $sql        = "SELECT `id` FROM `album` WHERE `album_artist` IS NULL AND `name` != 'Unknown (Orphaned)'";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = (int) $row['id'];
            }
        }
        foreach ($results as $album_id) {
            $artists    = array();
            $sql        = "SELECT `artist` FROM `song` WHERE `album` = ? GROUP BY `artist` HAVING COUNT(DISTINCT `artist`) = 1 LIMIT 1";
            $db_results = Dba::read($sql, array($album_id));

            // these are albums that only have 1 artist
            while ($row = Dba::fetch_assoc($db_results)) {
                $artists[] = (int) $row['artist'];
            }

            // if there isn't a distinct artist, sort by the count with another fall back to id order
            if (empty($artists)) {
                $sql        = "SELECT `artist` FROM `song` WHERE `album` = ? GROUP BY `artist`, `id` ORDER BY COUNT(`id`) DESC, `id` ASC LIMIT 1";
                $db_results = Dba::read($sql, array($album_id));

                // these are album pick the artist by majority count
                while ($row = Dba::fetch_assoc($db_results)) {
                    $artists[] = (int) $row['artist'];
                }
            }
            // Update the album
            if (!empty($artists)) {
                debug_event(self::class, 'Found album_artist {' . $artists[0] . '} for: ' . $album_id, 5);
                Album::update_field('album_artist', $artists[0], $album_id);
            }
        }
    }

    /**
     * remove
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $deleted  = true;
        $song_ids = $this->get_songs();
        foreach ($song_ids as $song_id) {
            $song    = new Song($song_id);
            $deleted = $song->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the song `' . $song_id . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `album` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::garbage_collection('album', $this->id);
                Userflag::garbage_collection('album', $this->id);
                Rating::garbage_collection('album', $this->id);
                Shoutbox::garbage_collection('album', $this->id);
                Useractivity::garbage_collection('album', $this->id);
            }
        }

        return $deleted;
    }

    /**
     * Update an album field.
     * @param string $field
     * @param $value
     * @param integer $album_id
     * @return PDOStatement|boolean
     */
    private static function update_field($field, $value, $album_id)
    {
        $sql = "UPDATE `album` SET `" . $field . "` = ? WHERE `id` = ?";

        return Dba::write($sql, array($value, $album_id));
    }

    /**
     * get_random
     *
     * This returns a number of random albums.
     * @param integer $count
     * @param boolean $with_art
     * @param integer $user_id
     * @return integer[]
     */
    public static function get_random($count = 1, $with_art = false, $user_id = null)
    {
        $results = array();

        if (!$count) {
            $count = 1;
        }
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        $sort_disk = (AmpConfig::get('album_group')) ? "AND `album`.`disk` = 1 " : "";

        $sql = "SELECT DISTINCT `album`.`id` FROM `album` " .
                "LEFT JOIN `song` ON `song`.`album` = `album`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $where = "WHERE `catalog`.`enabled` = '1' " . $sort_disk;
        } else {
            $where = "WHERE 1=1 " . $sort_disk;
        }
        if ($with_art) {
            $sql .= "LEFT JOIN `image` ON (`image`.`object_type` = 'album' AND `image`.`object_id` = `album`.`id`) ";
            $where .= "AND `image`.`id` IS NOT NULL ";
        }
        $sql .= $where;

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id !== null) {
            $sql .= "AND `album`.`id` NOT IN " .
                    "(SELECT `object_id` FROM `rating` " .
                    "WHERE `rating`.`object_type` = 'album' " .
                    "AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ") ";
        }
        $sql .= "ORDER BY RAND() LIMIT " . (string) $count;
        $db_results = Dba::read($sql);
        //debug_event(self::class, 'get_random ' . $sql, 5);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * sanitize_disk
     * Change letter disk numbers (like vinyl/cassette) to an integer
     * @param string|integer $disk
     * @return integer
     */
    public static function sanitize_disk($disk)
    {
        $alphabet = range('A', 'Z');
        if ((int) $disk == 0) {
            // A is 0 but we want to start at disk 1
            $disk = (int) array_search(strtoupper((string) $disk), $alphabet) + 1;
        }

        return (int) $disk;
    }
} // end album.class
