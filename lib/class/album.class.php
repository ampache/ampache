<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
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
     *  @var int $id
     */
    public $id;
    /**
     *  @var string $name
     */
    public $name;
    /**
     *  @var int $album_artist
     */
    public $album_artist;
    /**
     *  @var int $disk
     */
    public $disk;
    /**
     *  @var int $year
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
     * @var int $catalog_id
     */
    public $catalog_id;
    /**
     *  @var int $song_count
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
     *  @var int $artist_id
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
     *  @var int $artist_count
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
     *  @var string $f_name_link
     */
    public $f_name_link;
    /**
     *  @var string $f_link_src
     */
    public $f_link_src;
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
     * @param int|null $id
     */
    public function __construct($id=null)
    {
        if (!$id) { return false; }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }

        // Little bit of formatting here
        $this->full_name = trim(trim($info['prefix']) . ' ' . trim($info['name']));

        // Looking for other albums with same mbid, ordering by disk ascending
        if ($this->disk && !empty($this->mbid) && AmpConfig::get('album_group')) {
            $this->album_suite = $this->get_album_suite();
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
        foreach ($data as $key=>$value) {
            $album->$key = $value;
        }

        $album->_fake = true;   // Make sure that we tell em it's fake

        return $album;

    } // construct_from_array

    /**
     * gc
     *
     * Cleans out unused albums
     */
    public static function gc()
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
        // Nothing to do if they pass us nothing
        if (!is_array($ids) OR !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = "SELECT * FROM `album` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('album',$row['id'],$row);
        }

        return true;

    } // build_cache

    /**
     * _get_extra_info
     * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
     * do it
     * @return array
     */
    private function _get_extra_info()
    {
        if (!$this->id) {
            return array();
        }

        if (parent::is_cached('album_extra', $this->id)) {
            return parent::get_from_cache('album_extra', $this->id);
        }

        $sql = "SELECT " .
            "COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, " .
            "COUNT(`song`.`id`) AS `song_count`, " .
            "`song`.`album_artist` AS `album_artist`, " .
            "SUM(`song`.`time`) as `total_duration`," .
            "`song`.`catalog` as `catalog_id`,".
            "`artist`.`name` AS `artist_name`, " .
            "`artist`.`prefix` AS `artist_prefix`, " .
            "`artist`.`id` AS `artist_id` " .
            "FROM `song` INNER JOIN `artist` " .
            "ON `artist`.`id`=`song`.`artist` ";

        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }

        $suite_array = array();
        if ($this->allow_group_disks) {
            $suite_array = $this->album_suite;
        }
        if (!count($suite_array)) {
            $suite_array[] = $this->id;
        }

        $idlist = '(' . implode(',', $suite_array) . ')';
        $sql .= "WHERE `song`.`album` IN $idlist ";

        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        if (!count($this->album_suite)) {
            $sql .= "GROUP BY `song`.`album`";
        } else {
            $sql .= "GROUP BY `song`.`artist`";
        }

        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        $art = new Art($this->id, 'album');
        $art->get_db();
        $results['has_art'] = make_bool($art->raw);
        $results['has_thumb'] = make_bool($art->thumb);

        if (AmpConfig::get('show_played_times')) {
            $results['object_cnt'] = Stats::get_object_count('album', $this->id);
        }

        parent::add_to_cache('album_extra', $this->id, $results);

        return $results;

    } // _get_extra_info

    /**
     * check
     *
     * Searches for an album; if none is found, insert a new one.
     * @param string $name
     * @param int $year
     * @param int $disk
     * @param string $mbid
     * @param string $mbid_group
     * @param string $album_artist
     * @param string $release_type
     * @param boolean $readonly
     * @return int|null
     */
    public static function check($name, $year = 0, $disk = 0, $mbid = null, $mbid_group = null, $album_artist = null, $release_type = null, $readonly = false)
    {
        $trimmed = Catalog::trim_prefix(trim($name));
        $name = $trimmed['string'];
        $prefix = $trimmed['prefix'];
        $album_artist = empty($album_artist) ? null : $album_artist;
        $mbid = empty($mbid) ? null : $mbid;
        $mbid_group = empty($mbid_group) ? null : $mbid_group;
        $release_type = empty($release_type) ? null : $release_type;

        // Not even sure if these can be negative, but better safe than llama.
        $year = abs(intval($year));
        $disk = abs(intval($disk));

        if (!$name) {
            $name = T_('Unknown (Orphaned)');
            $year = 0;
            $disk = 0;
            $album_artist = null;
        }
        if (isset(self::$_mapcache[$name][$disk][$mbid][$album_artist])) {
            return self::$_mapcache[$name][$disk][$mbid][$album_artist];
        }

        $sql = 'SELECT `album`.`id` FROM `album` WHERE `album`.`name` = ? AND `album`.`disk` = ? ';
        $params = array($name, $disk);

        if ($mbid) {
           $sql .= 'AND `album`.`mbid` = ? ';
           $params[] = $mbid;
        }
        if ($prefix) {
           $sql .= 'AND `album`.`prefix` = ? ';
           $params[] = $prefix;
        }

        if ($album_artist) {
            $sql .= 'AND ? IN (SELECT `song`.`album_artist` FROM `song` WHERE `song`.`album`= `album`.`id`) ';
            $params[] = $album_artist;
        }

        $db_results = Dba::read($sql, $params);

        if ($row = Dba::fetch_assoc($db_results)) {
            $id = $row['id'];
            self::$_mapcache[$name][$disk][$mbid][$album_artist] = $id;
            return $id;
        }

        if ($readonly) {
            return null;
        }

        $sql = 'INSERT INTO `album` (`name`, `prefix`, `year`, `disk`, `mbid`, `mbid_group`, `release_type`) VALUES (?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array($name, $prefix, $year, $disk, $mbid, $mbid_group, $release_type));
        if (!$db_results) {
            return null;
        }

        $id = Dba::insert_id();

        // Remove from wanted album list if any request on it
        if (!empty($mbid) && AmpConfig::get('wanted')) {
            try {
                Wanted::delete_wanted_release($mbid);
            } catch (Exception $e) {
                debug_event('wanted', 'Cannot process wanted releases auto-removal check: ' . $e->getMessage(), '1');
            }
        }

        self::$_mapcache[$name][$disk][$mbid][$album_artist] = $id;
        return $id;
    }

    /**
     * get_songs
     * gets the songs for this album takes an optional limit
     * and an optional artist, if artist is passed it only gets
     * songs with this album + specified artist
     * @param int $limit
     * @param string $artist
     * @return int[]
     */
    public function get_songs($limit = 0,$artist='')
    {
        $results = array();

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`album` = ? ";
        $params = array($this->id);
        if (strlen($artist)) {
            $sql .= "AND `artist` = ? ";
            $params[] = $artist;
        }
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`track`, `song`.`title`";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $db_results = Dba::read($sql, $params);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
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
     * get_group_disks_ids
     * return all album suite ids or current album if no albums
     * @return int[]
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
     * @param int $catalog
     * return int[]
     */
    public function get_album_suite($catalog = 0)
    {
        $results = array();

        $catalog_where = "";
        $catalog_join = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
        if ($catalog) {
            $catalog_where .= " AND `catalog`.`id` = '$catalog'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= " AND `catalog`.`enabled` = '1'";
        }

        $sql = "SELECT DISTINCT `album`.`id` FROM album LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " .
            "WHERE `album`.`mbid`='$this->mbid' $catalog_where ORDER BY `album`.`disk` ASC";

        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
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
        $sql = "SELECT `id` FROM `song` WHERE `album` = ? AND `title` = ?";
        $db_results = Dba::read($sql, array($this->id, $title));

        $data = Dba::fetch_assoc($db_results);

        return $data;

    } // has_track

    /**
     * format
     * This is the format function for this object. It sets cleaned up
     * album information with the base required
     * f_link, f_name
     */
    public function format()
    {
        $web_path = AmpConfig::get('web_path');

        /* Pull the advanced information */
        $data = $this->_get_extra_info();
        foreach ($data as $key=>$value) { $this->$key = $value; }

        /* Truncate the string if it's to long */
        $this->f_name = $this->full_name;

        $this->f_link_src = $web_path . '/albums.php?action=show&album=' . scrub_out($this->id);
        $this->f_name_link    = "<a href=\"" . $this->f_link_src . "\" title=\"" . scrub_out($this->full_name) . "\">" . scrub_out($this->f_name);

        // Looking if we need to combine or display disks
        if ($this->disk && (!$this->allow_group_disks || ($this->allow_group_disks && !AmpConfig::get('album_group')))) {
            $this->f_name_link .= " <span class=\"discnb\">[" . T_('Disk') . " " . $this->disk . "]</span>";
        }

        $this->f_name_link .="</a>";

        $this->f_link = $this->f_name_link;
        $this->f_title = $this->full_name;
        if ($this->artist_count == '1') {
            $artist = trim(trim($this->artist_prefix) . ' ' . trim($this->artist_name));
            $this->f_artist_name = $artist;
            $this->f_artist_link = "<a href=\"$web_path/artists.php?action=show&artist=" . $this->artist_id . "\" title=\"" . scrub_out($this->artist_name) . "\">" . $artist . "</a>";
            $this->f_artist = $artist;
        } else {
            $this->f_artist_link = "<span title=\"$this->artist_count " . T_('Artists') . "\">" . T_('Various') . "</span>";
            $this->f_artist = T_('Various');
            $this->f_artist_name =  $this->f_artist;
        }

        if ($this->album_artist) {
            $Album_artist = new Artist($this->album_artist);
            $Album_artist->format();
            $this->album_artist_name = $Album_artist->name;
            $this->f_album_artist_name = $Album_artist->f_name;
            $this->f_album_artist_link = "<a href=\"" . $web_path . "/artists.php?action=show&artist=" . $this->album_artist . "\" title=\"" . scrub_out($this->album_artist_name) . "\">" . $this->f_album_artist_name . "</a>";
        }

        if (!$this->year) {
            $this->f_year = "N/A";
        }

        $this->tags = Tag::get_top_tags('album', $this->id);
        $this->f_tags = Tag::get_display($this->tags, true, 'album');

        $this->f_release_type = ucwords($this->release_type);

    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords = array();
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
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        return $this->get_medias();
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
        return null;
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
     * get_random_songs
     * gets a random number, and a random assortment of songs from this album
     * @return int[]
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
        while ($r = Dba::fetch_row($db_results)) {
            $results[] = $r['0'];
        }

        return $results;

    } // get_random_songs

    /**
     * update
     * This function takes a key'd array of data and updates this object
     * as needed
     * @param array $data
     * @return int
     */
    public function update(array $data)
    {
        $year = $data['year'] ?: $this->year;
        $artist = $data['artist'] ? intval($data['artist']) : $this->artist;
        $album_artist = $data['album_artist'] ? intval($data['album_artist']) : $this->album_artist;
        $name = $data['name'] ?: $this->name;
        $disk = $data['disk'] ?: $this->disk;
        $mbid = $data['mbid'] ?: $this->mbid;
        $mbid_group = $data['mbid_group'] ?: $this->mbid_group;
        $release_type = $data['release_type'] ?: $this->release_type;

        $current_id = $this->id;

        $updated = false;
        $songs = null;
        if ($artist != $this->artist_id AND $artist) {
            // Update every song
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                Song::update_artist($artist,$song_id);
            }
            $updated = true;
            Artist::gc();
        }

        if ($album_artist != $this->album_artist AND $album_artist > 0) {
            // Update every song
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                Song::update_album_artist($album_artist, $song_id);
            }
            $updated = true;
            Artist::gc();
        }

        $album_id = self::check($name, $year, $disk, $mbid, $mbid_group, null, $release_type);
        if ($album_id != $this->id) {
            if (!is_array($songs)) { $songs = $this->get_songs(); }
            foreach ($songs as $song_id) {
                Song::update_album($album_id,$song_id);
                Song::update_year($year,$song_id);
            }
            $current_id = $album_id;
            $updated = true;
            self::gc();
        } else {
            Album::update_year($year, $album_id);
            Album::update_mbid_group($mbid_group, $album_id);
            Album::update_release_type($release_type, $album_id);
        }
        $this->year = $year;
        $this->mbid_group = $mbid_group;
        $this->release_type = $release_type;
        $this->name = $name;
        $this->disk = $disk;
        $this->mb_id = $mbid;

        if ($updated && is_array($songs)) {
            foreach ($songs as $song_id) {
                Song::update_utime($song_id);
            } // foreach song of album
            Stats::gc();
            Rating::gc();
            Userflag::gc();
        } // if updated

        $override_songs = false;
        if ($data['apply_childs'] == 'checked') {
            $override_songs = true;
        }
        if (isset($data['edit_tags'])) {
            $this->update_tags($data['edit_tags'], $override_songs, $current_id);
        }

        return $current_id;

    } // update

    /**
     * update_tags
     *
     * Update tags of albums and/or songs
     * @param string $tags_comma
     * @param boolean $override_songs
     * @param int|null $current_id
     */
    public function update_tags($tags_comma, $override_songs, $current_id = null)
    {
        if ($current_id == null) {
            $current_id = $this->id;
        }

        Tag::update_tag_list($tags_comma, 'album', $current_id);

        if ($override_songs) {
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                Tag::update_tag_list($tags_comma, 'song', $song_id);
            }
        }
    }

    /**
     * Update album year.
     * @param int $year
     * @param int $album_id
     */
    public static function update_year($year, $album_id)
    {
        self::update_field('year', $year, $album_id);
    }

    /**
     * Update album mbid group.
     * @param string $mbid_group
     * @param int $album_id
     */
    public static function update_mbid_group($mbid_group, $album_id)
    {
        $mbid_group = (!empty($mbid_group)) ? $mbid_group : null;
        self::update_field('mbid_group', $mbid_group, $album_id);
    }

    /**
     * Update album release type.
     * @param string $release_type
     * @param int $album_id
     */
    public static function update_release_type($release_type, $album_id)
    {
        $release_type = (!empty($release_type)) ? $release_type : null;
        self::update_field('release_type', $release_type, $album_id);
    }

    /**
     * Update an album field.
     * @param string $field
     * @param int $album_id
     * @return boolean
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
     * @param int $count
     * @param boolean $with_art
     * @return int[]
     */
    public static function get_random($count = 1, $with_art = false)
    {
        $results = array();

        if (!$count) {
            $count = 1;
        }

        $sql = "SELECT DISTINCT `album`.`id` FROM `album` " .
            "LEFT JOIN `song` ON `song`.`album` = `album`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $where = "WHERE `catalog`.`enabled` = '1' ";
        } else {
            $where = "WHERE '1' = '1' ";
        }
        if ($with_art) {
            $sql .= "LEFT JOIN `image` ON (`image`.`object_type` = 'album' AND `image`.`object_id` = `album`.`id`) ";
            $where .="AND `image`.`id` IS NOT NULL ";
        }

        $sql .= $where;
        $sql .= "ORDER BY RAND() LIMIT " . intval($count);
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

} //end of album class
