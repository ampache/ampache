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
        $multi_where = 'WHERE';
        $sql         = "SELECT `artist`.`id` FROM `artist` " .
                "LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                    "WHERE `catalog`.`enabled` = '1' ";
            $multi_where = 'AND';
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && Core::get_global('user')) {
            $user_id = Core::get_global('user')->id;
            $sql .= " " . $multi_where . " `artist`.`id` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'artist'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
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
     * @return integer
     */
    public static function playlist()
    {
        $sql = "SELECT `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` " .
                " ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist_data`.`object_id` IS NOT NULL " .
                " ORDER BY RAND()";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['id'];
    } // playlist

    /**
     * get_single_song
     * This returns a single song pulled based on the passed random method
     * @param $type
     * @return mixed
     */
    public static function get_single_song($type)
    {
        $method_name = 'get_' . $type;

        if (!method_exists('Random', $method_name)) {
            $method_name = 'get_default';
        }
        $song_ids = self::$method_name(1);

        return array_pop($song_ids);
    } // get_single_song

    /**
     * get_default
     * This just randomly picks a song at whim from all catalogs
     * nothing special here...
     * @param string $limit
     * @param integer $user_id
     * @return integer[]
     */
    public static function get_default($limit = '', $user_id = null)
    {
        $results = array();

        if (empty($limit)) {
            $limit = AmpConfig::get('offset_limit') ? AmpConfig::get('offset_limit') : '25';
        }
        if ((int) $user_id < 1) {
            $user_id = Core::get_global('user')->id;
        }

        $multi_where = 'WHERE';
        $sql         = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                    "WHERE `catalog`.`enabled` = '1' ";
            $multi_where = 'AND';
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id !== null) {
            $sql .= " " . $multi_where . " `song`.`artist` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'artist'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
            $sql .= " AND `song`.`album` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'album'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
        }
        $sql .= "ORDER BY RAND() LIMIT $limit";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    } // get_default

    /**
     * get_flagged
     * This just randomly picks songs based on whether the artist, album, song is flagged by the user.
     * @param string $type
     * @param string $limit
     * @param integer $user_id
     * @return array
     */
    public static function get_flagged($type = 'song', $limit = '', $user_id = null)
    {
        $results = array();

        if (empty($limit)) {
            $limit = AmpConfig::get('offset_limit') ? AmpConfig::get('offset_limit') : '25';
        }
        if ((int) $user_id < 1) {
            $user_id = Core::get_global('user')->id;
        }
        $type_sql = '`song`.`' . $type . '`';
        if ($type == 'song') {
            $type_sql = '`song`.`id`';
        }
        $multi_where = 'WHERE';
        $sql         = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                    "WHERE `catalog`.`enabled` = '1' ";
            $multi_where = 'AND';
        }
        if ($user_id !== null) {
            $sql .= " " . $multi_where . " " . $type_sql . " IN" .
                    " (SELECT `object_id` FROM `user_flag`" .
                    " WHERE `user_flag`.`object_type` = '" . $type . "'" .
                    " AND `user_flag`.`user` = " . $user_id . ") ";
        }
        $sql .= "ORDER BY RAND() LIMIT $limit";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    } // get_flagged

    /**
     * get_album
     * This looks at the last album played by the current user and
     * picks something else in the same album
     * @param $limit
     * @return array
     */
    public static function get_album($limit)
    {
        $results = array();

        // Get the last album played by us
        $data        = Core::get_global('user')->get_recently_played('album', 1);
        $where_sql   = "";
        $multi_where = 'WHERE';

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                    "WHERE `catalog`.`enabled` = '1' ";
            $multi_where = 'AND';
        }
        if (AmpConfig::get('album_group')) {
            $sql .= " LEFT JOIN `album` ON `rating`.`object_id` = `album`.`id` AND `rating`.`object_type` = 'album'";
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && Core::get_global('user')) {
            $user_id = Core::get_global('user')->id;
            $sql .= " " . $multi_where . " `song`.`artist` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'artist'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
            $sql .= " AND `album`.`id` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'album'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
        }
        if ($data[0]) {
            $where_sql = " " . $multi_where . " `song`.`album`='" . $data[0] . "' ";
        }
        if (AmpConfig::get('album_group')) {
            $where_sql .= " GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`mbid`, `album`.`year`";
        }
        $sql .= "$where_sql ORDER BY RAND() LIMIT $limit";
        //debug_event(self::class, 'get_album ' . $sql, 5);
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
     * @param $limit
     * @return array
     */
    public static function get_artist($limit)
    {
        $results = array();

        $data        = Core::get_global('user')->get_recently_played('artist', 1);
        $where_sql   = "";
        $multi_where = 'WHERE';
        if ($data[0]) {
            $where_sql = " AND `song`.`artist`='" . $data[0] . "' ";
        }

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` " .
                    "WHERE `catalog`.`enabled` = '1' ";
            $multi_where = 'AND';
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && Core::get_global('user')) {
            $user_id = Core::get_global('user')->id;
            $sql .= " " . $multi_where . " `song`.`artist` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = 'artist'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
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
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function advanced($type, $data)
    {
        /* Figure out our object limit */
        $limit     = (int) ($data['random']);
        $limit_sql = "LIMIT " . Dba::escape($limit);

        /* If they've passed -1 as limit then get everything */
        if ($data['random'] == "-1") {
            unset($data['random']);
            $limit_sql = "";
        }

        $sql     = self::advanced_sql($data, $type, $limit_sql);
        $results = self::advanced_results($sql, $data);

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
                return array();
        }
    } // advanced

    /**
     * advanced_results
     * Run the query generated above by self::advanced so we can while it
     * @param string $sql
     * @param array $data
     * @return array
     */
    private static function advanced_results($sql, $data)
    {
        // Run the query generated above so we can while it
        $db_results = Dba::read($sql, $data);
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

        return $results;
    }
    /**
     * advanced_sql
     * Generate the sql query for self::advanced
     * @param array $data
     * @param string $type
     * @param string $limit_sql
     * @return string
     */
    private static function advanced_sql($data, $type, $limit_sql)
    {
        $catalog_disable = AmpConfig::get('catalog_disable');
        $search_data     = Search::clean_request($data);
        $search_info     = false;

        if (count($search_data) > 1) {
            $search = new Search(null, $type);
            $search->parse_rules($search_data);
            $search_info = $search->to_sql();
        }

        $catalog_disable_sql = "";
        if ($catalog_disable) {
            $catalog_disable_sql = " LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1'";
        }

        $sql = "";
        switch ($type) {
            case 'song':
                $sql = "SELECT `song`.`id`, `size`, `time` " .
                        "FROM `song` ";
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                }
                $sql .= $catalog_disable_sql;
                if ($search_info) {
                    if ($catalog_disable) {
                        $sql .= ' AND ' . $search_info['where_sql'];
                    } else {
                        $sql .= ' WHERE ' . $search_info['where_sql'];
                    }
                }
                break;
            case 'album':
                $sql = "SELECT `album`.`id`, SUM(`song`.`size`) AS `size`, SUM(`song`.`time`) AS `time` FROM `album` ";
                if (!$search_info || !$search_info['join']['song']) {
                    $sql .= "LEFT JOIN `song` ON `song`.`album`=`album`.`id` ";
                }
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                }
                $sql .= $catalog_disable_sql;
                if ($search_info) {
                    if ($catalog_disable) {
                        $sql .= ' AND ' . $search_info['where_sql'];
                    } else {
                        $sql .= ' WHERE ' . $search_info['where_sql'];
                    }
                }
                $sql .= ' GROUP BY `album`.`id`';
                break;
            case 'artist':
                $sql = "SELECT `artist`.`id`, SUM(`song`.`size`) AS `size`, SUM(`song`.`time`) AS `time` FROM `artist` ";
                if (!$search_info || !$search_info['join']['song']) {
                    $sql .= "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id` ";
                }
                if ($search_info) {
                    $sql .= $search_info['table_sql'];
                }
                $sql .= $catalog_disable_sql;
                if ($search_info) {
                    if ($catalog_disable) {
                        $sql .= ' AND ' . $search_info['where_sql'];
                    } else {
                        $sql .= ' WHERE ' . $search_info['where_sql'];
                    }
                }
                $sql .= ' GROUP BY `artist`.`id`';
                break;
        }
        $sql .= " ORDER BY RAND() $limit_sql";

        return $sql;
    }
} // end random.class
