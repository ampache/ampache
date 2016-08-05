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
 * Stats Class
 *
 * this class handles the object_count
 * stuff, before this was done in the user class
 * but that's not good, all done through here.
 *
 */
class Stats
{
    /* Base vars */
    public $id;
    public $object_type;
    public $object_id;
    public $date;
    public $user;
    public $agent;


    /**
      * Constructor
     * This doesn't do anything currently
     */
    public function __construct()
    {
        return true;
    } // Constructor

    /**
     * clear
     *
     * This clears all stats for _everything_.
     * @param int $user
     */
    public static function clear($user = 0)
    {
        if ($user > 0) {
            Dba::write("DELETE FROM `object_count` WHERE `user` = ?", array($user));
        } else {
            Dba::write("TRUNCATE `object_count`");
        }
        Dba::write("UPDATE `song` SET `played` = 0");
    }

    /**
     * gc
     *
     * This removes stats for things that no longer exist.
     */
    public static function gc()
    {
        foreach (array('song', 'album', 'artist', 'live_stream', 'video') as $object_type) {
            Dba::write("DELETE FROM `object_count` USING `object_count` LEFT JOIN `$object_type` ON `$object_type`.`id` = `object_count`.`object_id` WHERE `object_type` = '$object_type' AND `$object_type`.`id` IS NULL");
        }
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `object_count` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }

    /**
      * insert
     * This inserts a new record for the specified object
     * with the specified information, amazing!
     */
    public static function insert($type, $oid, $user, $agent='', $location, $count_type = 'stream')
    {
        if (!self::is_already_inserted($type, $oid, $user)) {
            $type = self::validate_type($type);

            $latitude  = null;
            $longitude = null;
            $geoname   = null;
            if (isset($location['latitude'])) {
                $latitude = $location['latitude'];
            }
            if (isset($location['longitude'])) {
                $longitude = $location['longitude'];
            }
            if (isset($location['name'])) {
                $geoname = $location['name'];
            }

            $sql = "INSERT INTO `object_count` (`object_type`,`object_id`,`count_type`,`date`,`user`,`agent`, `geo_latitude`, `geo_longitude`, `geo_name`) " .
                " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db_results = Dba::write($sql, array($type, $oid, $count_type, time(), $user, $agent, $latitude, $longitude, $geoname));
            
            if (Core::is_media($type)) {
                Useractivity::post_activity($user, 'play', $type, $oid);
            }

            if (!$db_results) {
                debug_event('statistics', 'Unabled to insert statistics:' . $sql, '3');
            }
        } else {
            debug_event('statistics', 'Statistics insertion ignored due to graceful delay.', '3');
        }
    } // insert

    /**
      * is_already_inserted
     * Check if the same stat has not already been inserted within a graceful delay
     */
    public static function is_already_inserted($type, $oid, $user, $count_type = 'stream')
    {
        $delay = time() - 10; // We look 10 seconds in the past

        $sql = "SELECT `id` FROM `object_count` ";
        $sql .= "WHERE `object_count`.`user` = ? AND `object_count`.`object_type` = ? AND `object_count`.`object_id` = ?  AND `object_count`.`count_type` = ? AND `object_count`.`date` >= ? ";
        $sql .= "ORDER BY `object_count`.`date` DESC";

        $db_results = Dba::read($sql, array($user, $type, $oid, $count_type, $delay));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return count($results) > 0;
    } // is_already_inserted

    /**
      * get_object_count
     * Get count for an object
     */
    public static function get_object_count($object_type, $object_id, $threshold = '', $count_type = 'stream')
    {
        $date = '';
        if ($threshold) {
            $date = time() - (86400*$threshold);
        }

        $sql = "SELECT COUNT(*) AS `object_cnt` FROM `object_count` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ?";
        if ($date) {
            $sql .= "AND `date` >= '" . $date . "'";
        }

        $db_results = Dba::read($sql, array($object_type, $object_id, $count_type));

        $results = Dba::fetch_assoc($db_results);

        return $results['object_cnt'];
    } // get_object_count

    public static function get_cached_place_name($latitude, $longitude)
    {
        $name       = null;
        $sql        = "SELECT `geo_name` FROM `object_count` WHERE `geo_latitude` = ? AND `geo_longitude` = ? AND `geo_name` IS NOT NULL ORDER BY `id` DESC LIMIT 1";
        $db_results = Dba::read($sql, array($latitude, $longitude));
        if ($results = Dba::fetch_assoc($db_results)) {
            $name = $results['geo_name'];
        }
        return $name;
    }

    /**
     * get_last_song
     * This returns the full data for the last song that was played, including when it
     * was played, this is used by, among other things, the LastFM plugin to figure out
     * if we should re-submit or if this is a duplicate / if it's too soon. This takes an
     * optional user_id because when streaming we don't have $GLOBALS()
     */
    public static function get_last_song($user_id='')
    {
        $user_id = $user_id ? $user_id : $GLOBALS['user']->id;

        $sql = "SELECT * FROM `object_count` " .
            "LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `object_count`.`date` DESC LIMIT 1";
        $db_results = Dba::read($sql, array($user_id));

        $results = Dba::fetch_assoc($db_results);

        return $results;
    } // get_last_song

    /**
      * get_object_history
     * This returns the objects that have happened for $user_id sometime after $time
     * used primarly by the democratic cooldown code
     */
    public static function get_object_history($user_id='', $time)
    {
        $user_id = $user_id ? $user_id : $GLOBALS['user']->id;

        $sql = "SELECT * FROM `object_count` " .
            "LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' AND `object_count`.`date` >= ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `object_count`.`date` DESC";
        $db_results = Dba::read($sql, array($user_id, $time));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        }

        return $results;
    } // get_object_history

    /**
     * get_top_sql
     * This returns the get_top sql
     */
    public static function get_top_sql($type, $threshold = '', $count_type = 'stream')
    {
        $type = self::validate_type($type);
        /* If they don't pass one, then use the preference */
        if (!$threshold) {
            $threshold = AmpConfig::get('stats_threshold');
        }
        $date = time() - (86400*$threshold);

        /* Select Top objects counting by # of rows */
        $sql = "SELECT object_id as `id`, COUNT(*) AS `count` FROM object_count" .
            " WHERE `object_type` = '" . $type . "' AND `date` >= '" . $date . "' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        $sql .= " AND `count_type` = '" . $count_type . "'";
        $sql .= " GROUP BY object_id ORDER BY `count` DESC ";
        return $sql;
    }

    /**
      * get_top
     * This returns the top X for type Y from the
     * last stats_threshold days
     */
    public static function get_top($type, $count='', $threshold = '', $offset='')
    {
        if (!$count) {
            $count = AmpConfig::get('popular_threshold');
        }

        $count = intval($count);
        if (!$offset) {
            $limit = $count;
        } else {
            $limit = intval($offset) . "," . $count;
        }

        $sql = self::get_top_sql($type, $threshold);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }
        return $results;
    } // get_top

    /**
     * get_recent_sql
     * This returns the get_recent sql
     */
    public static function get_recent_sql($type, $user_id='')
    {
        $type = self::validate_type($type);

        $user_sql = '';
        if (!empty($user_id)) {
            $user_sql = " AND `user` = '" . $user_id . "'";
        }

        $sql = "SELECT DISTINCT(`object_id`) as `id`, MAX(`date`) FROM object_count" .
            " WHERE `object_type` = '" . $type . "'" . $user_sql;
        if (AmpConfig::get('catalog_disable')) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        $sql .= " GROUP BY `object_id` ORDER BY MAX(`date`) DESC, `id` ";

        return $sql;
    }

    /**
     * get_recent
     * This returns the recent X for type Y
    */
    public static function get_recent($type, $count='', $offset='')
    {
        if (!$count) {
            $count = AmpConfig::get('popular_threshold');
        }

        $count = intval($count);
        $type  = self::validate_type($type);
        if (!$offset) {
            $limit = $count;
        } else {
            $limit = intval($offset) . "," . $count;
        }

        $sql = self::get_recent_sql($type);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql);

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_recent

    /**
      * get_user
     * This gets all stats for atype based on user with thresholds and all
     * If full is passed, doesn't limit based on date
     */
    public static function get_user($count, $type, $user, $full='')
    {
        $count = intval($count);
        $type  = self::validate_type($type);

        /* If full then don't limit on date */
        if ($full) {
            $date = '0';
        } else {
            $date = time() - (86400*AmpConfig::get('stats_threshold'));
        }

        /* Select Objects based on user */
        //FIXME:: Requires table scan, look at improving
        $sql = "SELECT object_id,COUNT(id) AS `count` FROM object_count" .
            " WHERE object_type = ? AND date >= ? AND user = ?" .
            " GROUP BY object_id ORDER BY `count` DESC LIMIT $count";
        $db_results = Dba::read($sql, array($type, $date, $user));

        $results = array();

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r;
        }

        return $results;
    } // get_user

    /**
     * validate_type
     * This function takes a type and returns only those
     * which are allowed, ensures good data gets put into the db
     */
    public static function validate_type($type)
    {
        switch ($type) {
            case 'artist':
            case 'album':
            case 'genre':
            case 'song':
            case 'video':
            case 'tvshow':
            case 'tvshow_season':
            case 'tvshow_episode':
            case 'movie':
            case 'playlist':
                return $type;
            default:
                return 'song';
        } // end switch
    } // validate_type

    /**
     * get_newest_sql
     * This returns the get_newest sql
     */
    public static function get_newest_sql($type, $catalog=0)
    {
        $type = self::validate_type($type);

        $base_type = 'song';
        if ($type == 'video') {
            $base_type = $type;
            $type      = $type . '`.`id';
        }

        $sql = "SELECT DISTINCT(`$type`) as `id`, MIN(`addition_time`) AS `real_atime` FROM `" . $base_type . "` ";
        $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `" . $base_type . "`.`catalog` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "WHERE `catalog`.`enabled` = '1' ";
        }
        if ($catalog > 0) {
            $sql .= "AND `catalog` = '" . scrub_in($catalog) . "' ";
        }
        $sql .= "GROUP BY `$type` ORDER BY `real_atime` DESC ";

        return $sql;
    }

    /**
     * get_newest
     * This returns an array of the newest artists/albums/whatever
     * in this ampache instance
     */
    public static function get_newest($type, $count='', $offset='', $catalog=0)
    {
        if (!$count) {
            $count = AmpConfig::get('popular_threshold');
        }
        if (!$offset) {
            $limit = $count;
        } else {
            $limit = $offset . ',' . $count;
        }

        $sql = self::get_newest_sql($type, $catalog);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql);

        $items = array();

        while ($row = Dba::fetch_row($db_results)) {
            $items[] = $row[0];
        } // end while results

        return $items;
    } // get_newest
} // Stats class
