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

class Artist extends database_object {

    /* Variables from DB */
    public $id;
    public $name;
    public $songs;
    public $albums;
    public $prefix;
    public $mbid; // MusicBrainz ID
    public $catalog_id;

    // Constructed vars
    public $_fake = false; // Set if construct_from_array() used
    private static $_mapcache = array();

    /**
     * Artist
     * Artist class, for modifing a artist
     * Takes the ID of the artist and pulls the info from the db
     */
    public function __construct($id='',$catalog_init=0) {

        /* If they failed to pass in an id, just run for it */
        if (!$id) { return false; }

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
     */
    public static function construct_from_array($data) {

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
    public static function gc() {
        Dba::write('DELETE FROM `artist` USING `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `song`.`id` IS NULL');
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     */
    public static function build_cache($ids,$extra=false) {
        if(!is_array($ids) OR !count($ids)) { return false; }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = "SELECT * FROM `artist` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

          while ($row = Dba::fetch_assoc($db_results)) {
              parent::add_to_cache('artist',$row['id'],$row);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if ($extra) {
            $sql = "SELECT `song`.`artist`, COUNT(`song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` WHERE `song`.`artist` IN $idlist GROUP BY `song`.`artist`";
            
            debug_event("Artist", "build_cache sql: " . $sql, "6");
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                parent::add_to_cache('artist_extra',$row['artist'],$row);
            }

        } // end if extra

        return true;

    } // build_cache

    /**
     * get_from_name
     * This gets an artist object based on the artist name
     */
    public static function get_from_name($name) {

        $name = Dba::escape($name);
        $sql = "SELECT `id` FROM `artist` WHERE `name`='$name'";
        $db_results = Dba::write($sql);

        $row = Dba::fetch_assoc($db_results);

        $object = new Artist($row['id']);

        return $object;

    } // get_from_name

    /**
     * get_albums
     * gets the album ids that this artist is a part
     * of
     */
    public function get_albums($catalog = null) {

        if($catalog) {
            $catalog_join = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
            $catalog_where = "AND `catalog`.`id` = '$catalog'";
        }

        $results = array();

        $sql = "SELECT `album`.`id` FROM album LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " .
            "WHERE `song`.`artist`='$this->id' $catalog_where GROUP BY `album`.`id` ORDER BY `album`.`name`,`album`.`disk`,`album`.`year`";

        debug_event("Artist", "$sql", "6");
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;

    } // get_albums

    /**
     * get_songs
     * gets the songs for this artist
     */
    public function get_songs() {

        $sql = "SELECT `song`.`id` FROM `song` WHERE `song`.`artist`='" . Dba::escape($this->id) . "' ORDER BY album, track";
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;

    } // get_songs

    /**
     * get_random_songs
     * Gets the songs from this artist in a random order
     */
    public function get_random_songs() {

        $results = array();

        $sql = "SELECT `id` FROM `song` WHERE `artist`='$this->id' ORDER BY RAND()";
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;

    } // get_random_songs

    /**
     * _get_extra info
     * This returns the extra information for the artist, this means totals etc
     */
    private function _get_extra_info($catalog=FALSE) {

        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('artist_extra',$this->id) ) {
            $row = parent::get_from_cache('artist_extra',$this->id);
        }
        else {
            $uid = Dba::escape($this->id);
            $sql = "SELECT `song`.`artist`,COUNT(`song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` WHERE `song`.`artist`='$uid' ";
            if ($catalog) {
                $sql .= "AND (`song`.`catalog` = '$catalog') ";
            }

            $sql .= "GROUP BY `song`.`artist`";
                
            $db_results = Dba::read($sql);
            $row = Dba::fetch_assoc($db_results);
            parent::add_to_cache('artist_extra',$row['artist'],$row);
        }

        /* Set Object Vars */
        $this->songs = $row['song_count'];
        $this->albums = $row['album_count'];
        $this->time = $row['time'];

        return $row;

    } // _get_extra_info

    /**
     * format
     * this function takes an array of artist
     * information and reformats the relevent values
     * so they can be displayed in a table for example
     * it changes the title into a full link.
      */
    public function format() {

        /* Combine prefix and name, trim then add ... if needed */
        $name = UI::truncate(trim($this->prefix . " " . $this->name),Config::get('ellipse_threshold_artist'));
        $this->f_name = $name;
        $this->f_full_name = trim(trim($this->prefix) . ' ' . trim($this->name));

        // If this is a fake object, we're done here
        if ($this->_fake) { return true; }

        if ($this->catalog_id) {
            $this->f_name_link = "<a href=\"" . Config::get('web_path') . "/artists.php?action=show&amp;catalog=" . $this->catalog_id . "&amp;artist=" . $this->id . "\" title=\"" . $this->f_full_name . "\">" . $name . "</a>";
            $this->f_link = Config::get('web_path') . '/artists.php?action=show&amp;catalog=' . $this->catalog_id . '&amp;artist=' . $this->id;
        } else {
            $this->f_name_link = "<a href=\"" . Config::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->id . "\" title=\"" . $this->f_full_name . "\">" . $name . "</a>";
            $this->f_link = Config::get('web_path') . '/artists.php?action=show&amp;artist=' . $this->id;
        }
        // Get the counts
        $extra_info = $this->_get_extra_info($this->catalog_id);

        //Format the new time thingy that we just got
        $min = sprintf("%02d",(floor($extra_info['time']/60)%60));

        $sec = sprintf("%02d",($extra_info['time']%60));
        $hours = floor($extra_info['time']/3600);

        $this->f_time = ltrim($hours . ':' . $min . ':' . $sec,'0:');

        $this->tags = Tag::get_top_tags('artist',$this->id);

        $this->f_tags = Tag::get_display($this->tags,$this->id,'artist');

        return true;

    } // format

    /**
     * check
     *
     * Checks for an existing artist; if none exists, insert one.
     */
    public static function check($name, $mbid = null, $readonly = false) {
        $trimmed = Catalog::trim_prefix(trim($name));
        $name = $trimmed['string'];
        $prefix = $trimmed['prefix'];
        
        if (!$name) {
            $name = T_('Unknown (Orphaned)');
            $prefix = null;
        }

        if (isset(self::$_mapcache[$name][$mbid])) {
            return self::$_mapcache[$name][$mbid];
        }

        $exists = false;

        if ($mbid) {
            $sql = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, array($mbid));

            if ($row = Dba::fetch_assoc($db_results)) {
                $id = $row['id'];
                $exists = true;
            }
        }

        if (!$exists) {
            $sql = 'SELECT `id`, `mbid` FROM `artist` WHERE `name` LIKE ?';
            $db_results = Dba::read($sql, array($name));

            while ($row = Dba::fetch_assoc($db_results)) {
                $key = $row['mbid'] ?: 'null';
                $id_array[$key] = $row['id'];
            }

            if (isset($id_array)) {
                if ($mbid) {
                    if (isset($id_array['null']) && !$readonly) {
                        $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                        Dba::write($sql, array($mbid, $id_array['null']));
                    }
                    if (isset($id_array['null'])) {
                        $id = $id_array['null'];
                        $exists = true;
                    }
                }
                else {
                    // Pick one at random
                    $id = array_shift($id_array);
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
     * it will flag songs as neeed
     */
    public function update($data) {

        // Save our current ID
        $current_id = $this->id;

        $artist_id = self::check($data['name'], $this->mbid);

        // If it's changed we need to update
        if ($artist_id != $this->id) {
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                Song::update_artist($artist_id,$song_id);
            }
            $updated = 1;
            $current_id = $artist_id;
            self::gc();
        } // end if it changed

        if ($updated) {
            foreach ($songs as $song_id) {
                Flag::add($song_id,'song','retag','Interface Artist Update');
                Song::update_utime($song_id);
            }
            Stats::gc();
            Rating::gc();
        } // if updated

        return $current_id;

    } // update

} // end of artist class
?>
