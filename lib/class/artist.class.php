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

class Artist extends database_object implements library_item
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
     *  @var string $summary
     */
    public $summary;

    /**
     *  @var string $placeformed
     */
    public $placeformed;

    /**
     *  @var integer $yearformed
     */
    public $yearformed;

    /**
     *  @var integer $last_update
     */
    public $last_update;

    /**
     *  @var integer $songs
     */
    public $songs;

    /**
     *  @var integer $albums
     */
    public $albums;

    /**
     *  @var string $prefix
     */
    public $prefix;

    /**
     *  @var string $mbid
     */
    public $mbid; // MusicBrainz ID

    /**
     *  @var integer $catalog_id
     */
    public $catalog_id;

    /**
     *  @var integer $time
     */
    public $time;

    /**
     *  @var integer $user
     */
    public $user;

    /**
     * @var boolean $manual_update
     */
    public $manual_update;


    /**
     *  @var array $tags
     */
    public $tags;

    /**
     *  @var string $f_tags
     */
    public $f_tags;

    /**
     *  @var array $labels
     */
    public $labels;

    /**
     *  @var string $f_labels
     */
    public $f_labels;

    /**
     *  @var integer $object_cnt
     */
    public $object_cnt;

    /**
     *  @var string $f_name
     */
    public $f_name;

    /**
     *  @var string $f_full_name
     */
    public $f_full_name;

    /**
     *  @var string $link
     */
    public $link;

    /**
     *  @var string $f_link
     */
    public $f_link;

    /**
     *  @var string $f_time
     */
    public $f_time;

    // Constructed vars
    /**
     *  @var boolean $_fake
     */
    public $_fake = false; // Set if construct_from_array() used

    /**
     *  @var array $_mapcache
     */
    private static $_mapcache = array();

    /**
     * Artist
     * Artist class, for modifying an artist
     * Takes the ID of the artist and pulls the info from the db
     * @param integer|null $artist_id
     * @param integer $catalog_init
     */
    public function __construct($artist_id = null, $catalog_init = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if ($artist_id === null) {
            return false;
        }

        $this->catalog_id = $catalog_init;
        /* Get the information from the db */
        $info = $this->get_info($artist_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        if (!$this->time) {
            $this->time = $this->update_time();
        }

        return true;
    } // constructor

    /**
     * construct_from_array
     * This is used by the metadata class specifically but fills out a Artist object
     * based on a key'd array, it sets $_fake to true
     * @param array $data
     * @return Artist
     */
    public static function construct_from_array($data)
    {
        $artist = new Artist(0);
        foreach ($data as $key => $value) {
            $artist->$key = $value;
        }

        // Ack that this is not a real object from the DB
        $artist->_fake = true;

        return $artist;
    } // construct_from_array

    /**
     * garbage_collection
     *
     * This cleans out unused artists
     */
    public static function garbage_collection()
    {
        Dba::write('DELETE FROM `artist` USING `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ' .
            'LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` ' .
            'LEFT JOIN `wanted` ON `wanted`.`artist` = `artist`.`id` ' .
            'LEFT JOIN `clip` ON `clip`.`artist` = `artist`.`id` ' .
            'WHERE `song`.`id` IS NULL AND `album`.`id` IS NULL AND `wanted`.`id` IS NULL AND `clip`.`id` IS NULL');
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     * @param integer[] $ids
     * @param boolean $extra
     * @param string $limit_threshold
     * @return boolean
     */
    public static function build_cache($ids, $extra = false, $limit_threshold = '')
    {
        if (empty($ids)) {
            return false;
        }
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `artist` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('artist', $row['id'], $row);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if ($extra) {
            $sql = "SELECT `song`.`artist`, COUNT(DISTINCT `song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` WHERE `song`.`artist` IN $idlist GROUP BY `song`.`artist`";

            //debug_event("artist.class", "build_cache sql: " . $sql, 5);
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                if (AmpConfig::get('show_played_times')) {
                    $row['object_cnt'] = Stats::get_object_count('artist', $row['artist'], $limit_threshold);
                }
                parent::add_to_cache('artist_extra', $row['artist'], $row);
            }
        } // end if extra

        return true;
    } // build_cache

    /**
     * get_from_name
     * This gets an artist object based on the artist name
     * @param string $name
     * @return Artist
     */
    public static function get_from_name($name)
    {
        $sql        = "SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ? ";
        $db_results = Dba::read($sql, array($name, $name));

        $row = Dba::fetch_assoc($db_results);

        return new Artist($row['id']);
    } // get_from_name

    /**
     * get_albums
     * gets the album ids that this artist is a part
     * of
     * @param integer|null $catalog
     * @param boolean $group_release_type
     * @return integer[]
     */
    public function get_albums($catalog = null, $group_release_type = false)
    {
        $catalog_where = "";
        $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
        if ($catalog !== null) {
            $catalog_where .= " AND `catalog`.`id` = '" . Dba::escape($catalog) . "'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
        }

        $sort_type = AmpConfig::get('album_sort');
        $sort_disk = (AmpConfig::get('album_group')) ? "" : ", `album`.`disk`";
        switch ($sort_type) {
            case 'year_asc':
                $sql_sort = '`album`.`year` ASC' . $sort_disk;
                break;
            case 'year_desc':
                $sql_sort = '`album`.`year` DESC' . $sort_disk;
                break;
            case 'name_asc':
                $sql_sort = '`album`.`name` ASC' . $sort_disk;
                break;
            case 'name_desc':
                $sql_sort = '`album`.`name` DESC' . $sort_disk;
                break;
            default:
                $sql_sort  = '`album`.`name`' . $sort_disk . ', `album`.`year`';
        }

        $sql = "SELECT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " .
            "WHERE (`song`.`artist`='$this->id' OR `album`.`album_artist`='$this->id') $catalog_where GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY $sql_sort";

        if (AmpConfig::get('album_group')) {
            $sql = "SELECT MAX(`album`.`id`) AS `id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " .
                    "WHERE (`song`.`artist`='$this->id' OR `album`.`album_artist`='$this->id') $catalog_where GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`mbid`, `album`.`year` ORDER BY $sql_sort";
        }
        //debug_event(self::class, 'get_albums ' . $sql, 5);

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($group_release_type) {
                // We assume undefined release type is album
                $rtype = $row['release_type'] ?: 'album';
                if (!isset($results[$rtype])) {
                    $results[$rtype] = array();
                }
                $results[$rtype][] = $row['id'];

                $sort = (string) AmpConfig::get('album_release_type_sort');
                if ($sort) {
                    $results_sort = array();
                    $asort        = explode(',', $sort);

                    foreach ($asort as $rtype) {
                        if (array_key_exists($rtype, $results)) {
                            $results_sort[$rtype] = $results[$rtype];
                            unset($results[$rtype]);
                        }
                    }

                    $results = array_merge($results_sort, $results);
                }
            } else {
                $results[] = $row['id'];
            }
        }

        return $results;
    } // get_albums

    /**
     * get_songs
     * gets the songs for this artist
     * @return integer[]
     */
    public function get_songs()
    {
        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`artist` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";
        $db_results = Dba::read($sql, array($this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    } // get_songs

    /**
     * get_top_songs
     * gets the songs for this artist
     * @param integer $artist
     * @param integer $count
     * @return integer[]
     */
    public static function get_top_songs($artist, $count = 50)
    {
        $sql = "SELECT `song`.`id`, COUNT(`object_count`.`object_id`) AS `counting` FROM `song` ";
        $sql .= "LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_type` = 'song' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`artist` = " . $artist . " ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "GROUP BY `song`.`id` ORDER BY count(`object_count`.`object_id`) DESC LIMIT " . (string) $count;
        $db_results = Dba::read($sql);
        //debug_event(self::class, 'get_top_songs sql: ' . $sql, 5);


        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_top_songs

    /**
     * get_random_songs
     * Gets the songs from this artist in a random order
     * @return integer[]
     */
    public function get_random_songs()
    {
        $results = array();

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`artist` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY RAND()";
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_random_songs

    /**
     * get_random
     *
     * This returns a number of random artists.
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
            $user_id = Core::get_global('user');
        }

        $sql = "SELECT DISTINCT `artist`.`id` FROM `artist` " .
                "LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $where = "WHERE `catalog`.`enabled` = '1' ";
        } else {
            $where = "WHERE 1=1 ";
        }
        if ($with_art) {
            $sql .= "LEFT JOIN `image` ON (`image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id`) ";
            $where .= "AND `image`.`id` IS NOT NULL ";
        }
        $sql .= $where;

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id !== null) {
            $sql .= " AND `artist`.`id` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'artist'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ") ";
        }

        $sql .= "ORDER BY RAND() LIMIT " . (string) $count;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_time
     *
     * Get time for an artist's songs.
     * @param integer $artist_id
     * @return integer
     */
    public static function get_time($artist_id)
    {
        $params     = array($artist_id);
        $sql        = "SELECT SUM(`song`.`time`) AS `time` from `song` WHERE `song`.`artist` = ?";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);
        // album artists that don't have any songs
        if ((int) $results['time'] == 0) {
            $sql        = "SELECT SUM(`album`.`time`) AS `time` from `album` WHERE `album`.`album_artist` = ?";
            $db_results = Dba::read($sql, $params);
            $results    = Dba::fetch_assoc($db_results);
        }

        return (int) $results['time'];
    }

    /**
     * _get_extra info
     * This returns the extra information for the artist, this means totals etc
     * @param integer $catalog
     * @param string $limit_threshold
     * @return array
     */
    private function _get_extra_info($catalog = 0, $limit_threshold = '')
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('artist_extra', $this->id)) {
            $row = parent::get_from_cache('artist_extra', $this->id);
        } else {
            $params = array($this->id);
            // Calculation
            $sql  = "SELECT COUNT(DISTINCT `song`.`id`) AS `song_count`, " .
                    "COUNT(DISTINCT `song`.`album`) AS `album_count`, " .
                    "SUM(`song`.`time`) AS `time` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $sqlw = "WHERE `song`.`artist` = ? ";
            if (AmpConfig::get('album_group')) {
                $sql  = "SELECT COUNT(DISTINCT `song`.`id`) AS `song_count`, " .
                        "COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_count`, " .
                        "SUM(`song`.`time`) AS `time` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` LEFT JOIN `album` ON `album`.`id` = `song`.`album` ";
                $sqlw = "WHERE `song`.`artist` = ? ";
            }
            if ($catalog) {
                $params[] = $catalog;
                $sqlw .= "AND (`song`.`catalog` = ?) ";
            }
            if (AmpConfig::get('catalog_disable')) {
                $sqlw .= " AND `catalog`.`enabled` = '1' ";
            }
            $sql .= $sqlw . "GROUP BY `song`.`artist`";

            $db_results = Dba::read($sql, $params);
            $row        = Dba::fetch_assoc($db_results);

            // Get associated information from first song only
            $sql = "SELECT `song`.`artist`, `song`.`catalog` as `catalog_id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $sql .= $sqlw . "LIMIT 1";

            $db_results = Dba::read($sql, $params);
            $row        = array_merge($row, Dba::fetch_assoc($db_results));

            if (AmpConfig::get('show_played_times')) {
                $row['object_cnt'] = Stats::get_object_count('artist', $row['artist'], $limit_threshold);
            }
            parent::add_to_cache('artist_extra', $row['artist'], $row);
        }

        /* Set Object Vars */
        $this->songs      = $row['song_count'];
        $this->albums     = $row['album_count'];
        $this->time       = (int) $row['time'];
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes an array of artist
     * information and reformats the relevent values
     * so they can be displayed in a table for example
     * it changes the title into a full link.
     * @param boolean $details
     * @param string $limit_threshold
     * @return boolean
     */
    public function format($details = true, $limit_threshold = '')
    {
        /* Combine prefix and name, trim then add ... if needed */
        $name              = trim((string) $this->prefix . " " . $this->name);
        $this->f_name      = $name;
        $this->f_full_name = trim(trim((string) $this->prefix) . ' ' . trim((string) $this->name));

        // If this is a memory-only object, we're done here
        if (!$this->id) {
            return true;
        }

        if ($this->catalog_id) {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&catalog=' . $this->catalog_id . '&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_full_name . "\">" . $name . "</a>";
        } else {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_full_name . "\">" . $name . "</a>";
        }

        if ($details) {
            // Get the counts
            $extra_info = $this->_get_extra_info($this->catalog_id, $limit_threshold);

            // Format the new time thingy that we just got
            $min = sprintf("%02d", (floor($extra_info['time'] / 60) % 60));

            $sec   = sprintf("%02d", ($extra_info['time'] % 60));
            $hours = floor($extra_info['time'] / 3600);

            $this->f_time = ltrim((string) $hours . ':' . $min . ':' . $sec, '0:');

            $this->tags   = Tag::get_top_tags('artist', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'artist');

            if (AmpConfig::get('label')) {
                $this->labels   = Label::get_labels($this->id);
                $this->f_labels = Label::get_display($this->labels, true);
            }

            $this->object_cnt = $extra_info['object_cnt'];
        }
        if (!$this->time) {
            $this->time = $this->update_time();
        }

        return true;
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords                = array();
        $keywords['mb_artistid'] = array('important' => false,
            'label' => T_('Artist MusicBrainzID'),
            'value' => $this->mbid);
        $keywords['artist'] = array('important' => true,
            'label' => T_('Artist'),
            'value' => $this->f_full_name);

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_full_name;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        $medias = array();
        $albums = $this->get_albums();
        foreach ($albums as $album_id) {
            $medias[] = array(
                'object_type' => 'album',
                'object_id' => $album_id
            );
        }

        return array('album' => $medias);
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "album";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $search['rule_1_input']    = $this->name;
        $search['rule_1_operator'] = 4;
        $search['rule_1']          = "artist";
        $albums                    = Search::run($search);

        $childrens = array();
        foreach ($albums as $album_id) {
            $childrens[] = array(
                'object_type' => 'album',
                'object_id' => $album_id
            );
        }

        return $childrens;
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song') {
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
        return $this->user;
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
        return $this->summary;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $artist_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'artist') || $force) {
            $artist_id = $this->id;
            $type      = 'artist';
        }

        if ($artist_id !== null && $type !== null) {
            Art::display($type, $artist_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * can_edit
     * @param integer $user_id
     * @return boolean
     */
    public function can_edit($user_id = null)
    {
        if (!$user_id) {
            $user_id = Core::get_global('user')->id;
        }

        if (!$user_id) {
            return false;
        }

        if (AmpConfig::get('upload_allow_edit')) {
            if ($this->user !== null && $user_id == $this->user) {
                return true;
            }
        }

        return Access::check('interface', 50, $user_id);
    }

    /**
     * check
     *
     * Checks for an existing artist; if none exists, insert one.
     * @param string $name
     * @param string $mbid
     * @param boolean $readonly
     * @return integer|null
     */
    public static function check($name, $mbid = '', $readonly = false)
    {
        $trimmed = Catalog::trim_prefix(trim((string) $name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];

        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $mbid = Catalog::trim_slashed_list($mbid);

        if (!$name) {
            $name   = T_('Unknown (Orphaned)');
            $prefix = null;
        }
        if ($name == 'Various Artists') {
            $mbid = '';
        }

        if (isset(self::$_mapcache[$name][$prefix][$mbid])) {
            return self::$_mapcache[$name][$prefix][$mbid];
        }

        $artist_id = 0;
        $exists    = false;

        if ($mbid !== '') {
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, array($mbid));

            if ($row = Dba::fetch_assoc($db_results)) {
                $artist_id = (int) $row['id'];
                $exists    = true;
            }
        }

        if (!$exists) {
            $sql        = 'SELECT `id`, `mbid` FROM `artist` WHERE `name` LIKE ?';
            $db_results = Dba::read($sql, array($name));

            $id_array = array();
            while ($row = Dba::fetch_assoc($db_results)) {
                $key            = $row['mbid'] ?: 'null';
                $id_array[$key] = $row['id'];
            }

            if (count($id_array)) {
                if ($mbid !== '') {
                    if (isset($id_array['null']) && !$readonly) {
                        $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                        Dba::write($sql, array($mbid, $id_array['null']));
                    }
                    if (isset($id_array['null'])) {
                        $artist_id = $id_array['null'];
                        $exists    = true;
                    }
                } else {
                    // Pick one at random
                    $artist_id = array_shift($id_array);
                    $exists    = true;
                }
            }
        }

        if ($exists) {
            self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

            return (int) $artist_id;
        }

        $sql = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) ' .
            'VALUES(?, ?, ?)';

        $db_results = Dba::write($sql, array($name, $prefix, $mbid));
        if (!$db_results) {
            return null;
        }
        $artist_id = (int) Dba::insert_id();
        debug_event(self::class, 'Artist check created new artist id `' . $artist_id . '`.', 4);

        self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

        return $artist_id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        // Save our current ID
        $name        = isset($data['name']) ? $data['name'] : $this->name;
        $mbid        = isset($data['mbid']) ? $data['mbid'] : $this->mbid;
        $summary     = isset($data['summary']) ? $data['summary'] : $this->summary;
        $placeformed = isset($data['placeformed']) ? $data['placeformed'] : $this->placeformed;
        $yearformed  = isset($data['yearformed']) ? $data['yearformed'] : $this->yearformed;

        $current_id = $this->id;

        // Check if name is different than current name
        if ($this->name != $name) {
            $updated    = false;
            $songs      = array();
            $artist_id  = self::check($name, $mbid, true);
            $cron_cache = AmpConfig::get('cron_cache');

            // If it's changed we need to update
            if ($artist_id !== null && $artist_id !== $this->id) {
                $songs = $this->get_songs();
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id, $song_id, $this->id);
                }
                $updated    = true;
                $current_id = $artist_id;
                Stats::migrate('artist', $this->id, $artist_id);
                UserActivity::migrate('artist', $this->id, $artist_id);
                Recommendation::migrate('artist', $this->id, $artist_id);
                Share::migrate('artist', $this->id, $artist_id);
                Shoutbox::migrate('artist', $this->id, $artist_id);
                Tag::migrate('artist', $this->id, $artist_id);
                Userflag::migrate('artist', $this->id, $artist_id);
                Rating::migrate('artist', $this->id, $artist_id);
                Art::migrate('artist', $this->id, $artist_id);
                if (!$cron_cache) {
                    self::garbage_collection();
                }
            } // end if it changed

            if ($updated) {
                foreach ($songs as $song_id) {
                    Song::update_utime($song_id);
                }
                if (!$cron_cache) {
                    Stats::garbage_collection();
                    Rating::garbage_collection();
                    Userflag::garbage_collection();
                    Useractivity::garbage_collection();
                }
            } // if updated
        } else {
            if ($this->mbid != $mbid) {
                $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                Dba::write($sql, array($mbid, $current_id));
            }
        }

        // Update artist name (if we don't want to use the MusicBrainz name)
        $trimmed = Catalog::trim_prefix(trim((string) $name));
        $name    = $trimmed['string'];
        if ($name != '' && $name != $this->name) {
            $sql = 'UPDATE `artist` SET `name` = ? WHERE `id` = ?';
            Dba::write($sql, array($name, $current_id));
        }

        $this->update_artist_info($summary, $placeformed, $yearformed, true);

        $this->name = $name;
        $this->mbid = $mbid;

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

        if (AmpConfig::get('label') && isset($data['edit_labels'])) {
            Label::update_label_list($data['edit_labels'], $this->id, true);
        }

        return $current_id;
    } // update

    /**
     * update_tags
     *
     * Update tags of artists and/or albums
     * @param string $tags_comma
     * @param boolean $override_childs
     * @param boolean $add_to_childs
     * @param boolean $force_update
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $force_update = false)
    {
        Tag::update_tag_list($tags_comma, 'artist', $this->id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $albums = $this->get_albums();
            foreach ($albums as $album_id) {
                $album = new Album($album_id);
                $album->update_tags($tags_comma, $override_childs, $add_to_childs);
            }
        }
    }

    /**
     * Update artist information.
     * @param string $summary
     * @param string $placeformed
     * @param integer $yearformed
     * @param boolean $manual
     * @return PDOStatement|boolean
     */
    public function update_artist_info($summary, $placeformed, $yearformed, $manual = false)
    {
        $sql    = "UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ? WHERE `id` = ?";
        $sqlret = Dba::write($sql, array($summary, $placeformed, Catalog::normalize_year($yearformed), time(), $manual ? 1 : 0, $this->id));

        $this->summary     = $summary;
        $this->placeformed = $placeformed;
        $this->yearformed  = $yearformed;

        return $sqlret;
    }

    /**
     * Update artist associated user.
     * @param integer $user
     * @return PDOStatement|boolean
     */
    public function update_artist_user($user)
    {
        $sql = "UPDATE `artist` SET `user` = ? WHERE `id` = ?";

        return Dba::write($sql, array($user, $this->id));
    }

    /**
     * Update artist last_update time.
     * @param integer $object_id
     */
    public static function set_last_update($object_id)
    {
        $sql = "UPDATE `artist` SET `last_update` = ? WHERE `id` = ?";
        Dba::write($sql, array(time(), $object_id));
    }

    /**
     * update_time
     *
     * Get time for an artist and set it.
     * @return integer
     */
    public function update_time()
    {
        $time = self::get_time((int) $this->id);
        if ($time !== $this->time && $this->id) {
            $sql = "UPDATE `artist` SET `time`=$time WHERE `id`=" . $this->id;
            Dba::write($sql);
        }

        return $time;
    }

    /**
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $deleted   = true;
        $album_ids = $this->get_albums();
        foreach ($album_ids as $albumid) {
            $album   = new Album($albumid);
            $deleted = $album->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the album `' . $albumid . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `artist` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::garbage_collection('artist', $this->id);
                Userflag::garbage_collection('artist', $this->id);
                Rating::garbage_collection('artist', $this->id);
                Shoutbox::garbage_collection('artist', $this->id);
                Useractivity::garbage_collection('artist', $this->id);
            }
        }

        return $deleted;
    }
} // end artist.class
