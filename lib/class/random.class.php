<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
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
 * Random Class
 *
 * All of the 'random' type events, elements, voodoo done by Ampache is done
 * by this class. There isn't a table for this class so most of its functions
 * are static.
 */
class Random implements media {

    public $type;
    public $id;

    /**
     * Constructor
     * nothing to see here, move along
     */
    public function __construct($id) {

        $this->type = Random::get_id_type($id);
        $this->id = intval($id);

    } // constructor

    /**
     * album
     * This returns the ID of a random album, nothing special
     */
    public static function album() {

        $sql = "SELECT `id` FROM `album` ORDER BY RAND() LIMIT 1";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];

    } // album

    /**
     * artist
     * This returns the ID of a random artist, nothing special here for now
     */
    public static function artist() {

        $sql = "SELECT `id` FROM `artist` ORDER BY RAND() LIMIT 1";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];

    } // artist

    /**
     * playlist
     * This returns a random Playlist with songs little bit of extra
     * logic require
     */
    public static function playlist() {

        $sql = "SELECT `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` " .
            " ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist_data`.`object_id` IS NOT NULL " .
            " ORDER BY RAND()";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];

    } // playlist

    /**
     * play_url
     * This generates a random play url based on the passed type
     * and returns it
     */
    public static function play_url($id,$sid='',$force_http='') {

        if (!$type = self::get_id_type($id)) {
            return false;
        }

        $uid = $GLOBALS['user']->id;

        $url = Stream::get_base_url() . "random=1&type=$type&uid=$uid";

        return $url;

    } // play_url

    /**
     * get_single_song
     * This returns a single song pulled based on the passed random method
     */
    public static function get_single_song($type) {

        if (!$type = self::validate_type($type)) {
            return false;
        }

        $method_name = 'get_' . $type;

        if (method_exists('Random',$method_name)) {
            $song_ids = self::$method_name(1);
            $song_id = array_pop($song_ids);
        }

        return $song_id;

    } // get_single_song

    /**
     * get_default
     * This just randomly picks a song at whim from all catalogs
     * nothing special here...
     */
    public static function get_default($limit) {

        $results = array();

        $sql = "SELECT `id` FROM `song` ORDER BY RAND() LIMIT $limit";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;

    } // get_default

    /**
     * get_album
     * This looks at the last album played by the current user and
     * picks something else in the same album
     */
    public static function get_album($limit) {

        $results = array();

        // Get the last album playbed by us
        $data = $GLOBALS['user']->get_recently_played('1','album');
        if ($data['0']) {
            $where_sql = " WHERE `album`='" . $data['0'] . "' ";
        }

        $sql = "SELECT `id` FROM `song` $where_sql ORDER BY RAND() LIMIT $limit";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;

    } // get_album

    /**
     * get_artist
     * This looks at the last artist played and then randomly picks a song from the
     * same artist
     */
    public static function get_artist($limit) {

        $results = array();

        $data = $GLOBALS['user']->get_recently_played('1','artist');
        if ($data['0']) {
            $where_sql = " WHERE `artist`='" . $data['0'] . "' ";
        }

        $sql = "SELECT `id` FROM `song` $where_sql ORDER BY RAND() LIMIT $limit";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;

    } // get_artist

    /**
     * advanced
     * This processes the results of a post from a form and returns an
     * array of song items that were returned from said randomness
     */
    public static function advanced($type, $data) {

        /* Figure out our object limit */
        $limit = intval($data['random']);

        // Generate our matchlist

        /* If they've passed -1 as limit then get everything */
        if ($data['random'] == "-1") { unset($data['random']); }
        else { $limit_sql = "LIMIT " . Dba::escape($limit); }

        $search_data = Search::clean_request($data);

        $search_info = false;

        if (count($search_data) > 1) {
            $search = new Search($type);
            $search->parse_rules($search_data);
            $search_info = $search->to_sql();
        }

        switch ($type) {
            case 'song':
                $sql = "SELECT `song`.`id`, `size`, `time` " .
                    "FROM `song` ";
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                    $sql .= ' WHERE ' . $search_info['where_sql'];
                }
            break;
            case 'album':
                $sql = "SELECT `album`.`id`, SUM(`song`.`size`) AS `size`, SUM(`song`.`time`) AS `time` FROM `album` ";
                if (! $search_info || ! $search_info['join']['song']) {
                    $sql .= "LEFT JOIN `song` ON `song`.`album`=`album`.`id` ";
                }
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                    $sql .= ' WHERE ' . $search_info['where_sql'];
                }
                $sql .= ' GROUP BY `album`.`id`';
            break;
            case 'artist':
                $sql = "SELECT `artist`.`id`, SUM(`song`.`size`) AS `size`, SUM(`song`.`time`) AS `time` FROM `artist` ";
                if (! $search_info || ! $search_info['join']['song']) {
                    $sql .= "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id` ";
                }
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                    $sql .= ' WHERE ' . $search_info['where_sql'];
                }
                $sql .= ' GROUP BY `artist`.`id`';
            break;
        }
        $sql .= " ORDER BY RAND() $limit_sql";

        // Run the query generated above so we can while it
        $db_results = Dba::read($sql);
        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {

            // If size limit is specified
            if ($data['size_limit']) {
                // Convert
                $new_size = ($row['size'] / 1024) / 1024;

                // Only fuzzy 100 times
                if ($fuzzy_size > 100) {
                    break;
                }

                // Add and check, skip if over size
                if (($size_total + $new_size) > $data['size_limit']) {
                    $fuzzy_size++;
                    continue;
                }

                $size_total = $size_total + $new_size;
                $results[] = $row['id'];

                // If we are within 4mb of target then jump ship
                if (($data['size_limit'] - floor($size_total)) < 4) {
                    break; }
            } // if size_limit

            // If length really does matter
            if ($data['length']) {
                // base on min, seconds are for chumps and chumpettes
                $new_time = floor($row['time'] / 60);

                if ($fuzzy_time > 100) {
                    break;;
                }

                // If the new one would go over skip!
                if (($time_total + $new_time) > $data['length']) {
                    $fuzzy_time++;
                    continue;
                }

                $time_total = $time_total + $new_time;
                $results[] = $row['id'];

                // If there are less then 2 min of free space return
                if (($data['length'] - $time_total) < 2) {
                    return $results;
                }
            } // if length does matter

            if (!$data['size_limit'] && !$data['length']) {
                $results[] = $row['id'];
            }

        } // end while results

        switch ($type) {
            case 'song':
                return $results;
            break;
            case 'album':
                $songs = array();
                foreach ($results as $result) {
                    $album = new Album($result);
                    $songs = array_merge($songs, $album->get_songs());
                }
                return $songs;
            break;
            case 'artist':
                $songs = array();
                foreach ($results as $result) {
                    $artist = new Artist($result);
                    $songs = array_merge($songs, $artist->get_songs());
                }
                return $songs;
            break;
            default:
                return false;
            break;
        }
    } // advanced

    /**
     * get_type_name
     * This returns a 'purrty' name for the different random types
     */
    public static function get_type_name($type) {

        switch ($type) {
            case 'album':
                return T_('Related Album');
            break;
            case 'genre':
                return T_('Related Genre');
            break;
            case 'artist':
                return T_('Related Artist');
            break;
            default:
                return T_('Pure Random');
            break;
        } // end switch

    } // get_type_name

    /**
     * get_type_id
     * This takes random type and returns the ID
     * MOTHER OF PEARL THIS MAKES BABY JESUS CRY
     * HACK HACK HACK HACK HACK HACK HACK HACK
     */
    public static function get_type_id($type) {

        switch ($type) {
            case 'album':
                return '1';
            break;
            case 'artist':
                return '2';
            break;
            case 'tag':
                return '3';
            break;
            default:
                return '4';
            break;
        }

    } // get_type_id

    /**
     * get_id_name
     * This takes an ID and returns the 'name' of the random dealie
     * HACK HACK HACK HACK HACK HACK HACK
     * Can you tell I don't like this code?
     */
    public static function get_id_type($id) {

        switch ($id) {
            case '1':
                return 'album';
            break;
            case '2':
                return 'artist';
            break;
            case '3':
                return 'tag';
            break;
            default:
                return 'default';
            break;
        } // end switch

    } // get_id_name

    /**
     * validate_type
     * this validates the random type
     */
    public static function validate_type($type) {

        switch ($type) {
            case 'default':
            case 'genre':
            case 'album':
            case 'artist':
            case 'rated':
                return $type;
            break;
        } // end switch

        return 'default';

    } // validate_type

    public function get_stream_types() { }
    public function get_transcode_settings($target = null) { }
    public function has_flag() { }
    public function format() { }

} //end of random class

?>
