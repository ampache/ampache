<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
class Album extends database_object
{
    /* Variables from DB */
    public $id;
    public $name;
    public $disk;
    public $year;
    public $prefix;
    public $mbid; // MusicBrainz ID

    public $full_name; // Prefix + Name, generated

    // cached information
    public $_songs = array();
    private static $_mapcache = array();

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     */
    public function __construct($id='')
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

        return true;

    } // constructor

    /**
     * construct_from_array
     * This is often used by the metadata class, it fills out an album object from a
     * named array, _fake is set to true
     */
    public static function construct_from_array($data)
    {
        $album = new Album(0);
        foreach ($data as $key=>$value) {
            $album->$key = $value;
        }

        // Make sure that we tell em it's fake
        $album->_fake = true;

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
     */
    public static function build_cache($ids,$extra=false)
    {
        // Nothing to do if they pass us nothing
        if (!is_array($ids) OR !count($ids)) { return false; }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = "SELECT * FROM `album` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('album',$row['id'],$row);
        }

        // If we're extra'ing cache the extra info as well
        if ($extra) {
            $sql = "SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, " .
                "COUNT(`song`.`id`) AS `song_count`, " .
                "SUM(`song`.`time`) as `total_duration`," .
                "`song`.`catalog` as `catalog_id`," .
                "`artist`.`name` AS `artist_name`, " .
                "`artist`.`prefix` AS `artist_prefix`, " .
                "`artist`.`id` AS `artist_id`, `song`.`album`" .
                "FROM `song` " .
                "INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
                "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                "WHERE `song`.`album` IN $idlist " .
                "AND `catalog`.`enabled` = '1' " .
                "GROUP BY `song`.`album`";

            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                $art = new Art($row['album'], 'album');
                $art->get_db();
                $row['has_art'] = make_bool($art->raw);
                $row['has_thumb'] = make_bool($art->thumb);
                if (AmpConfig::get('show_played_times')) {
                    $row['object_cnt'] = Stats::get_object_count('album', $row['album']);
                }
                parent::add_to_cache('album_extra',$row['album'],$row);
            } // while rows
        } // if extra

        return true;

    } // build_cache

    /**
     * _get_extra_info
     * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
     * do it
     */
    private function _get_extra_info()
    {
        if (parent::is_cached('album_extra',$this->id)) {
            return parent::get_from_cache('album_extra',$this->id);
        }

        $sql = "SELECT " .
            "COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, " .
            "COUNT(`song`.`id`) AS `song_count`, " .
            "SUM(`song`.`time`) as `total_duration`," .
            "`song`.`catalog` as `catalog_id`,".
            "`artist`.`name` AS `artist_name`, " .
            "`artist`.`prefix` AS `artist_prefix`, " .
            "`artist`.`id` AS `artist_id` " .
            "FROM `song` INNER JOIN `artist` " .
            "ON `artist`.`id`=`song`.`artist` " .
            "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
            "WHERE `song`.`album` = ? " .
            "AND `catalog`.`enabled` = '1' " .
            "GROUP BY `song`.`album`";
        $db_results = Dba::read($sql, array($this->id));

        $results = Dba::fetch_assoc($db_results);

        $art = new Art($this->id, 'album');
        $art->get_db();
        $results['has_art'] = make_bool($art->raw);
        $results['has_thumb'] = make_bool($art->thumb);

        if (AmpConfig::get('show_played_times')) {
            $results['object_cnt'] = Stats::get_object_count('album', $this->id);
        }

        parent::add_to_cache('album_extra',$this->id,$results);

        return $results;

    } // _get_extra_info

    /**
     * check
     *
     * Searches for an album; if none is found, insert a new one.
     */
    public static function check($name, $year = 0, $disk = 0, $mbid = null,
        $readonly = false) {

        if ($mbid == '') $mbid = null;

        $trimmed = Catalog::trim_prefix(trim($name));
        $name = $trimmed['string'];
        $prefix = $trimmed['prefix'];

        // Not even sure if these can be negative, but better safe than llama.
        $year = abs(intval($year));
        $disk = abs(intval($disk));

        if (!$name) {
            $name = T_('Unknown (Orphaned)');
            $year = 0;
            $disk = 0;
        }

        if (isset(self::$_mapcache[$name][$year][$disk][$mbid])) {
            return self::$_mapcache[$name][$year][$disk][$mbid];
        }

        $sql = 'SELECT `id` FROM `album` WHERE `name` = ? AND `disk` = ? AND ' .
            '`year` = ? AND `mbid` ';
        $params = array($name, $disk, $year);

        if ($mbid) {
            $sql .= '= ? ';
            $params[] = $mbid;
        } else {
            $sql .= 'IS NULL ';
        }

        $sql .= 'AND `prefix` ';
        if ($prefix) {
            $sql .= '= ?';
            $params[] = $prefix;
        } else {
            $sql .= 'IS NULL';
        }

        $db_results = Dba::read($sql, $params);

        if ($row = Dba::fetch_assoc($db_results)) {
            $id = $row['id'];
            self::$_mapcache[$name][$year][$disk][$mbid] = $id;
            return $id;
        }

        if ($readonly) {
            return null;
        }

        $sql = 'INSERT INTO `album` (`name`, `prefix`, `year`, `disk`, `mbid`) '.
            'VALUES (?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array($name, $prefix, $year, $disk, $mbid));
        if (!$db_results) {
            return null;
        }

        $id = Dba::insert_id();

        // Remove from wanted album list if any request on it
        if (!empty($mbid) && Config::get('wanted')) {
            try
            {
                Wanted::delete_wanted_release($mbid);
            } catch (Exception $e)
            {
                debug_event('wanted', 'Cannot process wanted releases auto-removal check: ' . $e->getMessage(), '1');
            }
        }

        self::$_mapcache[$name][$year][$disk][$mbid] = $id;
        return $id;
    }

    /**
     * get_songs
     * gets the songs for this album takes an optional limit
     * and an optional artist, if artist is passed it only gets
     * songs with this album + specified artist
     */
    public function get_songs($limit = 0,$artist='')
    {
        $results = array();

        $sql = "SELECT `song`.`id` FROM `song` ";
        $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        $sql .= "WHERE `song`.`album` = ? ";
        $params = array($this->id);
        if (strlen($artist)) {
            $sql .= "AND `artist` = ? ";
            $params[] = $artist;
        }
        $sql .= "AND `catalog`.`enabled` = '1' ";
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
     * has_track
     * This checks to see if this album has a track of the specified title
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
        $this->f_name        = UI::truncate($this->full_name,AmpConfig::get('ellipse_threshold_album'));

        $this->f_link_src = $web_path . '/albums.php?action=show&album=' . scrub_out($this->id);
        $this->f_name_link    = "<a href=\"" . $this->f_link_src . "\" title=\"" . scrub_out($this->full_name) . "\">" . scrub_out($this->f_name);
        // If we've got a disk append it
        if ($this->disk) {
            $this->f_name_link .= " <span class=\"discnb disc" .$this->disk. "\">[" . T_('Disk') . " " . $this->disk . "]</span>";
        }
        $this->f_name_link .="</a>";

        $this->f_link         = $this->f_name_link;
        $this->f_title        = $this->full_name; // FIXME: Legacy?
        if ($this->artist_count == '1') {
            $artist = trim(trim($this->artist_prefix) . ' ' . trim($this->artist_name));
            $this->f_artist_name = $artist;
            $artist = scrub_out(UI::truncate($artist), AmpConfig::get('ellipse_threshold_artist'));
            $this->f_artist_link = "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $this->artist_id . "\" title=\"" . scrub_out($this->artist_name) . "\">" . $artist . "</a>";
            $this->f_artist = $artist;
        } else {
            $this->f_artist_link = "<span title=\"$this->artist_count " . T_('Artists') . "\">" . T_('Various') . "</span>";
            $this->f_artist = T_('Various');
            $this->f_artist_name =  $this->f_artist;
        }

        if ($this->year == '0') {
            $this->year = "N/A";
        }

        $tags = Tag::get_top_tags('album',$this->id);
        $this->tags = $tags;

        $this->f_tags = Tag::get_display($tags, $this->id, 'album');

    } // format

    /**
     * get_random_songs
     * gets a random number, and a random assortment of songs from this album
     */
    public function get_random_songs()
    {
        $sql = "SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' ORDER BY RAND()";
        $db_results = Dba::read($sql, array($this->id));

        while ($r = Dba::fetch_row($db_results)) {
            $results[] = $r['0'];
        }

        return $results;

    } // get_random_songs

    /**
     * update
     * This function takes a key'd array of data and updates this object
     * as needed
     */
    public function update($data)
    {
        $year        = $data['year'];
        $artist      = $data['artist'];
        $name        = $data['name'];
        $disk        = $data['disk'];
        $mbid        = $data['mbid'];

        $current_id = $this->id;

        if ($artist != $this->artist_id AND $artist) {
            // Update every song
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                Song::update_artist($artist,$song_id);
            }
            $updated = 1;
            Artist::gc();
        }

        $album_id = self::check($name, $year, $disk, $mbid);
        if ($album_id != $this->id) {
            if (!is_array($songs)) { $songs = $this->get_songs(); }
            foreach ($songs as $song_id) {
                Song::update_album($album_id,$song_id);
                Song::update_year($year,$song_id);
            }
            $current_id = $album_id;
            $updated = 1;
            self::gc();
        }

        if ($updated) {
            foreach ($songs as $song_id) {
                Song::update_utime($song_id);
            } // foreach song of album
            Stats::gc();
            Rating::gc();
            Userflag::gc();
        } // if updated

        Tag::update_tag_list($data['edit_tags'], 'album', $current_id);

        return $current_id;

    } // update

    /**
     * get_random
     *
     * This returns a number of random albums.
     */
    public static function get_random($count = 1, $with_art = false)
    {
        $results = false;

        $sql = "SELECT `album`.`id` FROM `album` " .
            "LEFT JOIN `song` ON `song`.`album` = `album`.`id` " .
            "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        $where = "WHERE `catalog`.`enabled` = '1' ";
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
