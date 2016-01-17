<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

class Artist extends database_object implements library_item
{
    /* Variables from DB */

    /**
     *  @var int $id
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
     *  @var int $yearformed
     */
    public $yearformed;
    /**
     *  @var int $last_update
     */
    public $last_update;
    /**
     *  @var int $songs
     */
    public $songs;
    /**
     *  @var int $albums
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
     *  @var int $catalog_id
     */
    public $catalog_id;
    /**
     *  @var int $time
     */
    public $time;
    /**
     *  @var int $user
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
     *  @var int $object_cnt
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
     * Artist class, for modifing a artist
     * Takes the ID of the artist and pulls the info from the db
     * @param int|null $id
     * @param int $catalog_init
     */
    public function __construct($id=null,$catalog_init=0)
    {
        /* If they failed to pass in an id, just run for it */
        if (!$id) {
            return false;
        }

        $this->catalog_id = $catalog_init;
        /* Get the information from the db */
        $info = $this->get_info($id);

        foreach ($info as $key=>$value) {
            $this->$key = $value;
        } // foreach info

        return true;
    } //constructor

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
        foreach ($data as $key=>$value) {
            $artist->$key = $value;
        }

        //Ack that this is not a real object from the DB
        $artist->_fake = true;

        return $artist;
    } // construct_from_array

    /**
     * gc
     *
     * This cleans out unused artists
     */
    public static function gc()
    {
        Dba::write('DELETE FROM `artist` USING `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ' .
            'LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` ' .
            'LEFT JOIN `wanted` ON `wanted`.`artist` = `artist`.`id` ' .
            'LEFT JOIN `clip` ON `clip`.`artist` = `artist`.`id` ' .
            'WHERE `song`.`id` IS NULL AND `album`.`id` IS NULL AND `wanted`.`id` IS NULL AND `clip`.`id` IS NULL');
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     * @param int[] $ids
     * @param boolean $extra
     * @return boolean
     */
    public static function build_cache($ids, $extra=false, $limit_threshold = '')
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql        = "SELECT * FROM `artist` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('artist',$row['id'],$row);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if ($extra) {
            $sql = "SELECT `song`.`artist`, COUNT(DISTINCT `song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` WHERE `song`.`artist` IN $idlist GROUP BY `song`.`artist`";

            debug_event("Artist", "build_cache sql: " . $sql, "6");
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                if (AmpConfig::get('show_played_times')) {
                    $row['object_cnt'] = Stats::get_object_count('artist', $row['artist'], $limit_threshold);
                }
                parent::add_to_cache('artist_extra',$row['artist'],$row);
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
        $sql        = "SELECT `id` FROM `artist` WHERE `name` = ?'";
        $db_results = Dba::read($sql, array($name));

        $row = Dba::fetch_assoc($db_results);

        $object = new Artist($row['id']);

        return $object;
    } // get_from_name

    /**
     * get_albums
     * gets the album ids that this artist is a part
     * of
     * @param int|null $catalog
     * @param boolean $ignoreAlbumGroups
     * @param boolean $group_release_type
     * @return int[]
     */
    public function get_albums($catalog = null, $ignoreAlbumGroups = false, $group_release_type = false)
    {
        $catalog_where = "";
        $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
        if ($catalog) {
            $catalog_where .= " AND `catalog`.`id` = '" . $catalog . "'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= " AND `catalog`.`enabled` = '1'";
        }

        $results = array();

        $sort_type = AmpConfig::get('album_sort');
        $sql_sort  = '`album`.`name`,`album`.`disk`,`album`.`year`';
        if ($sort_type == 'year_asc') {
            $sql_sort = '`album`.`year` ASC';
        } elseif ($sort_type == 'year_desc') {
            $sql_sort = '`album`.`year` DESC';
        } elseif ($sort_type == 'name_asc') {
            $sql_sort = '`album`.`name` ASC';
        } elseif ($sort_type == 'name_desc') {
            $sql_sort = '`album`.`name` DESC';
        }

        if (!$ignoreAlbumGroups) {
            $ignoreAlbumGroups = !AmpConfig::get('album_group');
        }

        $sql = "SELECT `album`.`id`, `album`.`release_type`,`album`.`mbid` FROM album LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " .
            "WHERE (`song`.`artist`='$this->id' OR `album`.`album_artist`='$this->id') $catalog_where GROUP BY `album`.`id`, `album`.`release_type`,`album`.`mbid` ORDER BY $sql_sort";

        $db_results = Dba::read($sql);

        $mbids = array();
        while ($r = Dba::fetch_assoc($db_results)) {
            if ($ignoreAlbumGroups || empty($r['mbid']) || !in_array($r['mbid'], $mbids)) {
                if ($group_release_type) {
                    // We assume undefined release type is album
                    $rtype = $r['release_type'] ?: 'album';
                    if (!isset($results[$rtype])) {
                        $results[$rtype] = array();
                    }
                    $results[$rtype][] = $r['id'];

                    $sort = AmpConfig::get('album_release_type_sort');
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
                    $results[] = $r['id'];
                }
                if (!empty($r['mbid'])) {
                    $mbids[] = $r['mbid'];
                }
            }
        }

        return $results;
    } // get_albums

    /**
     * get_songs
     * gets the songs for this artist
     * @return int[]
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
        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;
    } // get_songs

    /**
     * get_random_songs
     * Gets the songs from this artist in a random order
     * @return int[]
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

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;
    } // get_random_songs

    /**
     * _get_extra info
     * This returns the extra information for the artist, this means totals etc
     * @param int $catalog
     * @return array
     */
    private function _get_extra_info($catalog=0, $limit_threshold ='')
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('artist_extra',$this->id) ) {
            $row = parent::get_from_cache('artist_extra',$this->id);
        } else {
            $params = array($this->id);
            // Calculation
            $sql  = "SELECT COUNT(DISTINCT `song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $sqlw = "WHERE `song`.`artist` = ? ";
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
            parent::add_to_cache('artist_extra',$row['artist'],$row);
        }

        /* Set Object Vars */
        $this->songs      = $row['song_count'];
        $this->albums     = $row['album_count'];
        $this->time       = $row['time'];
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes an array of artist
     * information and reformats the relevent values
     * so they can be displayed in a table for example
     * it changes the title into a full link.
     * @return boolean
      */
    public function format($details = true, $limit_threshold = '')
    {
        /* Combine prefix and name, trim then add ... if needed */
        $name              = trim($this->prefix . " " . $this->name);
        $this->f_name      = $name;
        $this->f_full_name = trim(trim($this->prefix) . ' ' . trim($this->name));

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

            //Format the new time thingy that we just got
            $min = sprintf("%02d",(floor($extra_info['time']/60)%60));

            $sec   = sprintf("%02d",($extra_info['time']%60));
            $hours = floor($extra_info['time']/3600);

            $this->f_time = ltrim($hours . ':' . $min . ':' . $sec,'0:');

            $this->tags   = Tag::get_top_tags('artist', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'artist');

            if (AmpConfig::get('label')) {
                $this->labels   = Label::get_labels($this->id);
                $this->f_labels = Label::get_display($this->labels, true);
            }

            $this->object_cnt = $extra_info['object_cnt'];
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
        $search['type']            = "album";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $search['rule_1_input']    = $this->name;
        $search['rule_1_operator'] = 4;
        $search['rule_1']          = "artist";
        $albums                    = Search::run($search);

        $childrens = array();
        foreach ($albums as $album) {
            $childrens[] = array(
                'object_type' => 'album',
                'object_id' => $album
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
     * @return int[]
     */
    public function get_catalogs()
    {
        return array($this->catalog_id);
    }

    /**
     * Get item's owner.
     * @return int|null
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

    public function get_description()
    {
        return $this->summary;
    }

    public function display_art($thumb = 2)
    {
        $id   = null;
        $type = null;

        if (Art::has_db($this->id, 'artist')) {
            $id   = $this->id;
            $type = 'artist';
        }

        if ($id !== null && $type !== null) {
            Art::display($type, $id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    public function can_edit($user = null)
    {
        if (!$user) {
            $user = $GLOBALS['user']->id;
        }

        if (!$user) {
            return false;
        }

        if (AmpConfig::get('upload_allow_edit')) {
            if ($this->user !== null && $user == $this->user) {
                return true;
            }
        }

        return Access::check('interface', 50, $user);
    }

    /**
     * check
     *
     * Checks for an existing artist; if none exists, insert one.
     * @param string $name
     * @param string $mbid
     * @param boolean $readonly
     * @return int|null
     */
    public static function check($name, $mbid = null, $readonly = false)
    {
        $trimmed = Catalog::trim_prefix(trim($name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];

        if ($mbid == '') {
            $mbid = null;
        }

        if (!$name) {
            $name   = T_('Unknown (Orphaned)');
            $prefix = null;
        }

        if (isset(self::$_mapcache[$name][$mbid])) {
            return self::$_mapcache[$name][$mbid];
        }

        $id     = 0;
        $exists = false;

        if ($mbid) {
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, array($mbid));

            if ($row = Dba::fetch_assoc($db_results)) {
                $id     = $row['id'];
                $exists = true;
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
                if ($mbid) {
                    if (isset($id_array['null']) && !$readonly) {
                        $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                        Dba::write($sql, array($mbid, $id_array['null']));
                    }
                    if (isset($id_array['null'])) {
                        $id     = $id_array['null'];
                        $exists = true;
                    }
                } else {
                    // Pick one at random
                    $id     = array_shift($id_array);
                    $exists = true;
                }
            }
        }

        if ($exists) {
            self::$_mapcache[$name][$mbid] = $id;
            return $id;
        }

        if ($readonly) {
            return null;
        }

        $sql = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) ' .
            'VALUES(?, ?, ?)';

        $db_results = Dba::write($sql, array($name, $prefix, $mbid));
        if (!$db_results) {
            return null;
        }
        $id = Dba::insert_id();

        self::$_mapcache[$name][$mbid] = $id;
        return $id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array $data
     * @return int
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
            $artist_id = self::check($name, $mbid, true);

            $updated = false;
            $songs   = array();

            // If it's changed we need to update
            if ($artist_id != null && $artist_id != $this->id) {
                $songs = $this->get_songs();
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id,$song_id);
                }
                $updated    = true;
                $current_id = $artist_id;
                Stats::migrate('artist', $this->id, $artist_id);
                Art::migrate('artist', $this->id, $artist_id);
                self::gc();
            } // end if it changed

            if ($updated) {
                foreach ($songs as $song_id) {
                    Song::update_utime($song_id);
                }
                Stats::gc();
                Rating::gc();
                Userflag::gc();
                Useractivity::gc();
            } // if updated
        } else {
            if ($this->mbid != $mbid) {
                $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                Dba::write($sql, array($mbid, $current_id));
            }
        }

        // Update artist name (if we don't want to use the MusicBrainz name)
        $trimmed = Catalog::trim_prefix(trim($name));
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
            $this->update_tags($data['edit_tags'], $override_childs, $add_to_childs, $current_id, true);
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
     * @param int|null $current_id
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $current_id = null, $force_update = false)
    {
        if ($current_id == null) {
            $current_id = $this->id;
        }

        Tag::update_tag_list($tags_comma, 'artist', $current_id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $albums = $this->get_albums(null, true);
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
     * @param int $yearformed
     * @return boolean
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
     * @param int $user
     * @return boolean
     */
    public function update_artist_user($user)
    {
        $sql = "UPDATE `artist` SET `user` = ? WHERE `id` = ?";
        return Dba::write($sql, array($user, $this->id));
    }

    public function remove_from_disk()
    {
        $deleted   = true;
        $album_ids = $this->get_albums();
        foreach ($album_ids as $id) {
            $album   = new Album($id);
            $deleted = $album->remove_from_disk();
            if (!$deleted) {
                debug_event('artist', 'Error when deleting the album `' . $id . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `artist` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::gc('artist', $this->id);
                Userflag::gc('artist', $this->id);
                Rating::gc('artist', $this->id);
                Shoutbox::gc('artist', $this->id);
                Useractivity::gc('artist', $this->id);
            }
        }

        return $deleted;
    }
} // end of artist class

