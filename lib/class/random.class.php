<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

/**
 * Random Class
 *
 * All of the 'random' type events, elements
 */
class Random
{
    /**
     * artist
     * This returns the ID of a random artist, nothing special here for now
     */
    public static function artist()
    {
        $sql = "SELECT `artist`.`id` FROM `artist` " .
            "LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                "WHERE `catalog`.`enabled` = '1' ";
        }
        $sql .= "GROUP BY `artist`.`id` " .
            "ORDER BY RAND() LIMIT 1";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];
    } // artist

    /**
     * playlist
     * This returns a random Playlist with songs little bit of extra
     * logic require
     */
    public static function playlist()
    {
        $sql = "SELECT `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` " .
            " ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist_data`.`object_id` IS NOT NULL " .
            " ORDER BY RAND()";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];
    } // playlist

    /**
     * get_single_song
     * This returns a single song pulled based on the passed random method
     */
    public static function get_single_song($type)
    {
        $method_name = 'get_' . $type;

        if (!method_exists('Random', $method_name)) {
            $method_name = 'get_default';
        }
        $song_ids = self::$method_name(1);
        $song_id  = array_pop($song_ids);

        return $song_id;
    } // get_single_song

    /**
     * get_default
     * This just randomly picks a song at whim from all catalogs
     * nothing special here...
     */
    public static function get_default($limit = '')
    {
        $results = array();

        if (empty($limit)) {
            $limit = AmpConfig::get('offset_limit') ? AmpConfig::get('offset_limit') : '25';
        }

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                "WHERE `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY RAND() LIMIT $limit";
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
    public static function get_album($limit)
    {
        $results = array();

        // Get the last album played by us
        $data      = $GLOBALS['user']->get_recently_played('1', 'album');
        $where_sql = "";
        if ($data[0]) {
            $where_sql = " AND `song`.`album`='" . $data[0] . "' ";
        }

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                "WHERE `catalog`.`enabled` = '1' ";
        } else {
            $sql .= "WHERE '1' = '1' ";
        }
        $sql .= "$where_sql ORDER BY RAND() LIMIT $limit";
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
    public static function get_artist($limit)
    {
        $results = array();

        $data      = $GLOBALS['user']->get_recently_played('1', 'artist');
        $where_sql = "";
        if ($data[0]) {
            $where_sql = " AND `song`.`artist`='" . $data[0] . "' ";
        }

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                "WHERE `catalog`.`enabled` = '1' ";
        } else {
            $sql .= "WHERE '1' = '1' ";
        }
        $sql .= "$where_sql ORDER BY RAND() LIMIT $limit";
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
    public static function advanced($type, $data)
    {
        /* Figure out our object limit */
        $limit = intval($data['random']);

        // Generate our matchlist

        /* If they've passed -1 as limit then get everything */
        $limit_sql = "";
        if ($data['random'] == "-1") {
            unset($data['random']);
        } else {
            $limit_sql = "LIMIT " . Dba::escape($limit);
        }

        $search_data = Search::clean_request($data);

        $search_info = false;

        if (count($search_data) > 1) {
            $search = new Search(null, $type);
            $search->parse_rules($search_data);
            $search_info = $search->to_sql();
        }

        $sql = "";
        switch ($type) {
            case 'song':
                $sql = "SELECT `song`.`id`, `size`, `time` " .
                    "FROM `song` ";
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                }
                if (AmpConfig::get('catalog_disable')) {
                    $sql .= " LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
                    $sql .= " WHERE `catalog`.`enabled` = '1'";
                }
                if ($search_info) {
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= ' AND ' . $search_info['where_sql'];
                    } else {
                        $sql .= ' WHERE ' . $search_info['where_sql'];
                    }
                }
            break;
            case 'album':
                $sql = "SELECT `album`.`id`, SUM(`song`.`size`) AS `size`, SUM(`song`.`time`) AS `time` FROM `album` ";
                if (! $search_info || ! $search_info['join']['song']) {
                    $sql .= "LEFT JOIN `song` ON `song`.`album`=`album`.`id` ";
                }
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                }
                if (AmpConfig::get('catalog_disable')) {
                    $sql .= " LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
                    $sql .= " WHERE `catalog`.`enabled` = '1'";
                }
                if ($search_info) {
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= ' AND ' . $search_info['where_sql'];
                    } else {
                        $sql .= ' WHERE ' . $search_info['where_sql'];
                    }
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
                }
                if (AmpConfig::get('catalog_disable')) {
                    $sql .= " LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
                    $sql .= " WHERE `catalog`.`enabled` = '1'";
                }
                if ($search_info) {
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= ' AND ' . $search_info['where_sql'];
                    } else {
                        $sql .= ' WHERE ' . $search_info['where_sql'];
                    }
                }
                $sql .= ' GROUP BY `artist`.`id`';
            break;
        }
        $sql .= " ORDER BY RAND() $limit_sql";

        // Run the query generated above so we can while it
        $db_results = Dba::read($sql);
        $results    = array();

        $size_total = 0;
        $fuzzy_size = 0;
        $time_total = 0;
        $fuzzy_time = 0;
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
                $results[]  = $row['id'];

                // If we are within 4mb of target then jump ship
                if (($data['size_limit'] - floor($size_total)) < 4) {
                    break;
                }
            } // if size_limit

            // If length really does matter
            if ($data['length']) {
                // base on min, seconds are for chumps and chumpettes
                $new_time = floor($row['time'] / 60);

                if ($fuzzy_time > 100) {
                    break;
                }

                // If the new one would go over skip!
                if (($time_total + $new_time) > $data['length']) {
                    $fuzzy_time++;
                    continue;
                }

                $time_total = $time_total + $new_time;
                $results[]  = $row['id'];

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
            case 'album':
                $songs = array();
                foreach ($results as $result) {
                    $album = new Album($result);
                    $songs = array_merge($songs, $album->get_songs());
                }
                return $songs;
            case 'artist':
                $songs = array();
                foreach ($results as $result) {
                    $artist = new Artist($result);
                    $songs  = array_merge($songs, $artist->get_songs());
                }
                return $songs;
            default:
                return false;
        }
    } // advanced
} //end of random class
