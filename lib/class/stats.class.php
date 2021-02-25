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
     * @param integer $user
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
     * garbage_collection
     *
     * This removes stats for things that no longer exist.
     */
    public static function garbage_collection()
    {
        foreach (array('song', 'album', 'artist', 'live_stream', 'video') as $object_type) {
            Dba::write("DELETE FROM `object_count` USING `object_count` LEFT JOIN `$object_type` ON `$object_type`.`id` = `object_count`.`object_id` WHERE `object_type` = '$object_type' AND `$object_type`.`id` IS NULL");
        }
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
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
     * @param string $input_type
     * @param integer $object_id
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param string $count_type
     * @param integer $date
     * @return boolean
     */
    public static function insert($input_type, $object_id, $user, $agent = '', $location = [], $count_type = 'stream', $date = null)
    {
        if (AmpConfig::get('use_auth') && $user < 0) {
            debug_event(self::class, 'Invalid user given ' . $user, 3);

            return false;
        }
        $type = self::validate_type($input_type);
        if (self::is_already_inserted($type, $object_id, $user, $agent, $date)) {
            return false;
        }

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
        // allow setting date for scrobbles
        if (!is_numeric($date)) {
            $date = time();
        }

        $sql = "INSERT INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) " .
                " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($type, $object_id, $count_type, $date, $user, $agent, $latitude, $longitude, $geoname));

        if (in_array($type, array('song', 'video')) && $count_type === 'stream' && $user > 0 && $agent !== 'debug') {
            Useractivity::post_activity($user, 'play', $type, $object_id, $date);
        }

        if (!$db_results) {
            debug_event(self::class, 'Unable to insert statistics for ' . $user . ':' . $object_id, 3);
        }

        return true;
    } // insert

    /**
     * is_already_inserted
     * Check if the same stat has not already been inserted within a graceful delay
     * @param string $type
     * @param integer $object_id
     * @param integer $user
     * @param string $agent
     * @param integer $time
     * @return boolean
     */
    public static function is_already_inserted($type, $object_id, $user, $agent, $time)
    {
        $agent = Dba::escape($agent);
        $sql   = "SELECT `object_id`, `date`, `count_type` FROM `object_count` " .
                "WHERE `object_count`.`user` = ? AND `object_count`.`object_type` = ? AND " .
                "`object_count`.`count_type` = 'stream' AND " .
                "(`object_count`.`date` >= ($time - 5) AND `object_count`.`date` <= ($time + 5)) ";
        if ($agent !== '') {
            $sql .= "AND `object_count`.`agent` = '$agent' ";
        }
        $sql .= "ORDER BY `object_count`.`date` DESC";

        $db_results = Dba::read($sql, array($user, $type));
        while ($row = Dba::fetch_assoc($db_results)) {
            // Stop double ups within 20s
            if ($row['object_id'] == $object_id) {
                debug_event(self::class, 'Object already inserted {' . (string) $object_id . '} date: ' . (string) $time, 5);

                return true;
            }
            // if you've skipped recently it's also not needed!
            if (($row['date'] < $time && $row['date'] > ($time - 20)) && $row['count_type'] == 'skip') {
                debug_event(self::class, 'Recent skip inserted {' . (string) $row['object_id'] . '} date: ' . (string) $row['date'], 5);

                return true;
            }
            // if you've recorded in less than 5 seconds i don't believe you
            if (($row['date'] < $time && $row['date'] > ($time - 5)) && $row['count_type'] !== 'download') {
                debug_event(self::class, 'Too fast! Skipping {' . (string) $object_id . '} date: ' . (string) $time, 5);

                return true;
            }
        }

        return false;
    } // is_already_inserted

    /**
     * get_object_count
     * Get count for an object
     * @param string $object_type
     * @param integer $object_id
     * @param string $threshold
     * @param string $count_type
     * @return integer
     */
    public static function get_object_count($object_type, $object_id, $threshold = null, $count_type = 'stream')
    {
        if ($threshold === null || $threshold === '') {
            $threshold = 0;
        }

        if (AmpConfig::get('cron_cache') && !defined('NO_CRON_CACHE')) {
            $sql = "SELECT `count` AS `object_cnt` FROM `cache_object_count` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ? AND `threshold` = " . $threshold;
        } else {
            $sql = "SELECT COUNT(*) AS `object_cnt` FROM `object_count` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ?";
            if ($threshold > 0) {
                $date = time() - (86400 * (int) $threshold);
                $sql .= "AND `date` >= '" . $date . "'";
            }
        }

        $db_results = Dba::read($sql, array($object_type, $object_id, $count_type));
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['object_cnt'];
    } // get_object_count

    /**
     * get_cached_place_name
     * @param $latitude
     * @param $longitude
     * @return mixed|null
     */
    public static function get_cached_place_name($latitude, $longitude)
    {
        $name       = null;
        $sql        = "SELECT `geo_name` FROM `object_count` WHERE `geo_latitude` = ? AND `geo_longitude` = ? AND `geo_name` IS NOT NULL ORDER BY `id` DESC LIMIT 1";
        $db_results = Dba::read($sql, array($latitude, $longitude));
        $results    = Dba::fetch_assoc($db_results);
        if (!empty($results)) {
            $name = $results['geo_name'];
        }

        return $name;
    }

    /**
     * get_last_play
     * This returns the full data for the last song/video/podcast_episode that was played, including when it
     * was played, this is used by, among other things, the LastFM plugin to figure out
     * if we should re-submit or if this is a duplicate / if it's too soon. This takes an
     * optional user_id because when streaming we don't have $GLOBALS()
     * @param string $user_id
     * @param string $agent
     * @param integer $date
     * @return array
     */
    public static function get_last_play($user_id = '', $agent = '', $date = 0)
    {
        if ($user_id === '') {
            $user_id = Core::get_global('user')->id;
        }

        $sqlres = array($user_id);

        $sql = "SELECT `object_count`.`id`, `object_count`.`object_type`, `object_count`.`object_id`, " .
               "`object_count`.`user`, `object_count`.`agent`, `object_count`.`date`, " .
               "`object_count`.`count_type` FROM `object_count` " .
               "WHERE `object_count`.`user` = ? AND `object_count`.`object_type` " .
               "IN ('song', 'video', 'podcast_episode') AND `object_count`.`count_type` IN ('stream', 'skip') ";
        if ($agent) {
            $sql .= "AND `object_count`.`agent` = ? ";
            array_push($sqlres, $agent);
        }
        if ($date > 0) {
            $sql .= "AND `object_count`.`date` <= ? ";
            array_push($sqlres, $date);
        }
        $sql .= "ORDER BY `object_count`.`date` DESC LIMIT 1";
        $db_results = Dba::read($sql, $sqlres);

        return Dba::fetch_assoc($db_results);
    } // get_last_play

    /**
     * shift_last_play
     * When you play or pause the song, shift the start time to allow better skip recording
     *
     * @param string $user_id
     * @param string $agent
     * @param integer $original_date
     * @param integer $new_date
     */
    public static function shift_last_play($user_id, $agent, $original_date, $new_date)
    {
        // update the object_count table
        $sql = "UPDATE `object_count` SET `object_count`.`date` = ? " .
            "WHERE `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`date` = ?";
        Dba::write($sql, array($new_date, $user_id, $agent, $original_date));

        // update the user_activity table
        $sql = "UPDATE `user_activity` SET `user_activity`.`activity_date` = ? " .
            "WHERE `user_activity`.`user` = ? AND `user_activity`.`activity_date` = ?";
        Dba::write($sql, array($new_date, $user_id, $original_date));
    } // shift_last_play

    /**
     * get_time
     *
     * get the time for the object (song, video, podcast_episode)
     * @param integer $object_id
     * @param string $object_type
     * @return integer
     */
    public static function get_time($object_id, $object_type)
    {
        // you can't get the last played when you haven't played something before
        if (!$object_id || !$object_type) {
            return 0;
        }
        $sql = "SELECT `time` FROM `$object_type` " .
               "WHERE `id` = ?";
        $db_results = Dba::read($sql, array($object_id));
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['time'];
    } // get_time

    /**
     * skip_last_play
     * this sets the object_counts count type to skipped
     * Gets called when the next song is played in quick succession
     *
     * @param integer $date
     * @param string $agent
     * @param integer $user_id
     * @return PDOStatement|boolean
     */
    public static function skip_last_play($date, $agent, $user_id)
    {
        $sql  = "UPDATE `object_count` SET `count_type` = 'skip' WHERE `date` = ? AND `agent` = ? AND " .
                "`user` = ? AND `object_count`.`object_type` IN ('song', 'video', 'podcast_episode') " .
                "ORDER BY `object_count`.`date` DESC";
        Dba::write($sql, array($date, $agent, $user_id));

        // To remove associated album and artist entries
        $sql = "DELETE FROM `object_count` WHERE `object_type` IN ('album', 'artist', 'podcast')  AND `date` = ? " .
               "AND `agent` = ? AND `user` = ? ";

        return Dba::write($sql, array($date, $agent, $user_id));
    } // skip_last_play

    /**
     * has_played_history
     * this checks to see if the current object has been played recently by the user
     * @param Song|Podcast_Episode|Video $object
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public static function has_played_history($object, $user, $agent, $date)
    {
        if ($user == -1) {
            return false;
        }
        $previous  = self::get_last_play($user, $agent, $date);
        $last_time = self::get_time($previous['object_id'], $previous['object_type']);
        $diff      = $date - (int) $previous['date'];
        $item_time = $object->time;
        $skip_time = AmpConfig::get_skip_timer($last_time);

        // if your last song is 30 seconds and your skip timer if 40 you don't want to keep skipping it.
        if ($last_time > 0 && $last_time < $skip_time) {
            return true;
        }

        // this object was your last play and the length between plays is too short.
        if ($previous['object_id'] == $object->id && $diff < ($item_time)) {
            debug_event(self::class, 'Repeated the same ' . get_class($object) . ' too quickly (' . $diff . '/' . ($item_time) . 's), not recording stats for {' . $object->id . '}', 3);

            return false;
        }

        // when the difference between recordings is too short, the previous object has been skipped, so note that
        if (($diff < $skip_time || ($diff < $skip_time && $last_time > $skip_time))) {
            debug_event(self::class, 'Last ' . $previous['object_type'] . ' played within skip limit (' . $diff . '/' . $skip_time . 's). Skipping {' . $previous['object_id'] . '}', 3);
            self::skip_last_play($previous['date'], $previous['agent'], $previous['user']);
            // delete song, podcast_episode and video from user_activity to keep stats in line
            Useractivity::del_activity($previous['date'], 'play', $previous['user']);
        }

        return true;
    } // has_played_history

    /**
     * get_object_history
     * This returns the objects that have happened for $user_id sometime after $time
     * used primarily by the democratic cooldown code
     * @param integer $user_id
     * @param integer $time
     * @param boolean $newest
     * @return array
     */
    public static function get_object_history($user_id, $time, $newest = true)
    {
        if (!in_array((string) $user_id, User::get_valid_users())) {
            $user_id = Core::get_global('user')->id;
        }
        $order = ($newest) ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM `object_count` " .
                "LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' AND `object_count`.`date` >= ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `object_count`.`date` " . $order;
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
     * @param string $input_type
     * @param string $threshold
     * @param string $count_type
     * @param integer $user_id
     * @param boolean $random
     * @return string
     */
    public static function get_top_sql($input_type, $threshold, $count_type = 'stream', $user_id = null, $random = false)
    {
        $type = self::validate_type($input_type);
        $date = time() - (86400 * (int) $threshold);
        if ($type == 'playlist') {
            $sql = "SELECT `id` as `id`, `last_update` FROM `playlist`";
            if ($threshold > 0) {
                $sql .= " WHERE `last_update` >= '" . $date . "' ";
            }
            $sql .= " GROUP BY `id`, `last_update` ORDER BY `last_update` DESC ";
            //debug_event(self::class, 'get_top_sql ' . $sql, 5);

            return $sql;
        }
        if ($user_id === null && AmpConfig::get('cron_cache') && !defined('NO_CRON_CACHE')) {
            $sql = "SELECT `object_id` as `id`, MAX(`count`) AS `count` FROM `cache_object_count` " .
                   "WHERE `object_type` = '" . $type . "' AND `count_type` = '" . $count_type . "' AND `threshold` = '" . $threshold . "' " .
                   "GROUP BY `object_id`, `object_type`";
        } else {
            $allow_group_disks = (AmpConfig::get('album_group')) ? true : false;
            // Select Top objects counting by # of rows for you only
            $sql = "SELECT MAX(`object_id`) as `id`, COUNT(*) AS `count`";
            // Add additional columns to use the select query as insert values directly
            if (defined('NO_CRON_CACHE')) {
                $sql .= ", `object_type`, `count_type`, " . $threshold . " AS `threshold`";
            }
            $sql .= " FROM `object_count`";
            if ($allow_group_disks && $type == 'album') {
                $sql .= " LEFT JOIN `album` on `album`.`id` = `object_count`.`object_id`" .
                        " AND `object_count`.`object_type` = 'album'";
            }
            if ($user_id !== null) {
                $sql .= " WHERE `object_type` = '" . $type . "' AND `user` = " . (string) $user_id;
            } else {
                $sql .= " WHERE `object_type` = '" . $type . "' ";
                if ($threshold > 0) {
                    $sql .= "AND `date` >= '" . $date . "'";
                }
            }
            if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
                $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
            }
            $rating_filter = AmpConfig::get_rating_filter();
            if ($rating_filter > 0 && $rating_filter <= 5 && $user_id !== null) {
                $sql .= " AND `object_id` NOT IN" .
                        " (SELECT `object_id` FROM `rating`" .
                        " WHERE `rating`.`object_type` = '" . $type . "'" .
                        " AND `rating`.`rating` <=" . $rating_filter .
                        " AND `rating`.`user` = " . $user_id . ")";
            }
            $sql .= " AND `count_type` = '" . $count_type . "'";
            if ($allow_group_disks && $type == 'album') {
                $sql .= " GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`mbid`, `album`.`year`, `object_count`.`object_type`, `object_count`.`count_type`";
            } else {
                $sql .= " GROUP BY `object_count`.`object_id`, `object_count`.`object_type`, `object_count`.`count_type`";
            }
        }
        if ($random) {
            $sql .= " ORDER BY RAND() DESC ";
        } else {
            $sql .= " ORDER BY `count` DESC ";
        }
        //debug_event(self::class, 'get_top_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_top
     * This returns the top X for type Y from the
     * last stats_threshold days
     * @param string $type
     * @param integer $count
     * @param integer $threshold
     * @param integer $offset
     * @param integer $user_id
     * @param boolean $random
     * @return integer[]
     */
    public static function get_top($type, $count, $threshold, $offset = 0, $user_id = null, $random = false)
    {
        if ($count < 1) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        $limit = ($offset < 1) ? $count : $offset . "," . $count;
        $sql   = self::get_top_sql($type, $threshold, 'stream', $user_id, $random);

        if ($user_id === null) {
            $sql .= "LIMIT $limit";
        }
        //debug_event(self::class, 'get_top ' . $sql, 5);

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    } // get_top

    /**
     * get_recent_sql
     * This returns the get_recent sql
     * @param string $input_type
     * @param string $user_id
     * @param boolean $newest
     * @return string
     */
    public static function get_recent_sql($input_type, $user_id = null, $newest = true)
    {
        $type = self::validate_type($input_type);

        $ordersql = ($newest === true) ? 'DESC' : 'ASC';
        $user_sql = (!empty($user_id)) ? " AND `user` = '" . $user_id . "'" : '';

        $sql = "SELECT `object_id` as `id`, MAX(`date`) AS `date` FROM `object_count`" .
                " WHERE `object_type` = '" . $type . "'" . $user_sql;
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && !empty($user_id)) {
            $sql .= " AND `object_id` NOT IN" .
                    " (SELECT `object_id` FROM `rating`" .
                    " WHERE `rating`.`object_type` = '" . $type . "'" .
                    " AND `rating`.`rating` <=" . $rating_filter .
                    " AND `rating`.`user` = " . $user_id . ")";
        }
        $sql .= " GROUP BY `object_id` ORDER BY MAX(`date`) " . $ordersql . ", `id` ";

        // playlists aren't the same as other objects so change the sql
        if ($type === 'playlist') {
            $sql = "SELECT `id`, `last_update` as `date` FROM `playlist`";
            if (!empty($user_id)) {
                $sql .= " WHERE `user` = '" . $user_id . "'";
            }
            $sql .= " ORDER BY `last_update` " . $ordersql;
        }
        //debug_event(self::class, 'get_recent ' . $sql, 5);

        return $sql;
    }

    /**
     * get_recent
     * This returns the recent X for type Y
     * @param string $input_type
     * @param integer $count
     * @param integer $offset
     * @param boolean $newest
     * @return integer[]
     */
    public static function get_recent($input_type, $count = 0, $offset = 0, $newest = true)
    {
        if ($count < 1) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        $limit = ($offset < 1) ? $count : $offset . "," . $count;

        $type = self::validate_type($input_type);
        $sql  = self::get_recent_sql($type, null, $newest);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql);

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    } // get_recent

    /**
     * get_user
     * This gets all stats for a type based on user with thresholds and all
     * If full is passed, doesn't limit based on date
     * @param string $input_count
     * @param string $input_type
     * @param integer $user
     * @param integer $full
     * @return array
     */
    public static function get_user($input_count, $input_type, $user, $full = 0)
    {
        $type  = self::validate_type($input_type);

        // If full then don't limit on date
        $date = ($full > 0) ? '0' : time() - (86400 * AmpConfig::get('stats_threshold'));

        // Select Objects based on user
        // FIXME:: Requires table scan, look at improving
        $sql = "SELECT `object_id`, COUNT(`id`) AS `count` FROM `object_count`" .
                " WHERE `object_type` = ? AND `date` >= ? AND `user` = ?" .
                " GROUP BY `object_id` ORDER BY `count` DESC LIMIT $input_count";
        $db_results = Dba::read($sql, array($type, $date, $user));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
        }

        return $results;
    } // get_user

    /**
     * validate_type
     * This function takes a type and returns only those
     * which are allowed, ensures good data gets put into the db
     * @param string $type
     * @return string
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
            case 'podcast_episode':
                return $type;
            default:
                return 'song';
        } // end switch
    } // validate_type

    /**
     * get_newest_sql
     * This returns the get_newest sql
     * @param string $input_type
     * @param integer $catalog
     * @return string
     */
    public static function get_newest_sql($input_type, $catalog = 0)
    {
        $type = self::validate_type($input_type);

        $base_type         = 'song';
        $multi_where       = 'WHERE';
        $sql_type          = ($type === 'song' || $type === 'playlist' || $type === 'video') ? $type . '`.`id' : $base_type . "`.`" . $type;
        $allow_group_disks = (AmpConfig::get('album_group')) ? true : false;

        // add playlists to mashup browsing
        if ($type == 'playlist') {
            $type = $type . '`.`id';
            $sql  = "SELECT `$type` as `id`, MAX(`playlist`.`last_update`) AS `real_atime` FROM `playlist` ";
        } else {
            $sql = "SELECT MAX(`$type`) as `id`, MAX(`song`.`addition_time`) AS `real_atime` FROM `" . $base_type . "` ";
            if ($type === 'song') {
                $sql = "SELECT DISTINCT(`$type`.`id`) as `id`, `song`.`addition_time` AS `real_atime` FROM `" . $base_type . "` ";
            }
            if ($allow_group_disks && $type == 'album') {
                $sql .= "LEFT JOIN `album` ON `album`.`id` = `" . $base_type . "`.`album` ";
            }
            if ($type === 'video') {
                $base_type = 'video';
                $sql       = "SELECT DISTINCT(`$type`.`id`) as `id`, `video`.`addition_time` AS `real_atime` FROM `" . $base_type . "` ";
                $type      = 'video`.`id';
            }
            if (AmpConfig::get('catalog_disable')) {
                $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `" . $base_type . "`.`catalog` ";
                $sql .= $multi_where . " `catalog`.`enabled` = '1' ";
                $multi_where = 'AND';
            }
            if ($catalog > 0) {
                $sql .= $multi_where . " `catalog` = '" . (string) scrub_in($catalog) . "' ";
                $multi_where = 'AND';
            }
            $rating_filter = AmpConfig::get_rating_filter();
            $user_id       = (int) Core::get_global('user')->id;
            if ($rating_filter > 0 && $rating_filter <= 5 && $user_id > 0) {
                $sql .= $multi_where . " `" . $sql_type . "` NOT IN" .
                        " (SELECT `object_id` FROM `rating`" .
                        " WHERE `rating`.`object_type` = '" . $type . "'" .
                        " AND `rating`.`rating` <=" . $rating_filter .
                        " AND `rating`.`user` = " . $user_id . ") ";
                $multi_where = 'AND';
            }
        }
        if ($allow_group_disks && $type == 'album') {
            $sql .= $multi_where . " `album`.`id` IS NOT NULL GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`mbid`, `album`.`year` ORDER BY `real_atime` DESC ";
        } elseif ($type === 'song' || $base_type === 'video') {
            $sql .= "GROUP BY `$sql_type`, `real_atime` ORDER BY `real_atime` DESC ";
        } else {
            $sql .= "GROUP BY `$sql_type` ORDER BY `real_atime` DESC ";
        }
        //debug_event(self::class, 'get_newest_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_newest
     * This returns an array of the newest artists/albums/whatever
     * in this Ampache instance
     * @param string $type
     * @param integer $count
     * @param integer $offset
     * @param integer $catalog
     * @return integer[]
     */
    public static function get_newest($type, $count = 0, $offset = 0, $catalog = 0)
    {
        if ($count < 1) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        if ($offset < 1) {
            $limit = $count;
        } else {
            $limit = $offset . ', ' . $count;
        }

        $sql = self::get_newest_sql($type, $catalog);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql);

        $items = array();

        while ($row = Dba::fetch_row($db_results)) {
            $items[] = (int) $row[0];
        } // end while results

        return $items;
    } // get_newest
} // end stats.class
