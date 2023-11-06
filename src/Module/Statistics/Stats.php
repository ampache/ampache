<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Statistics;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PDOStatement;

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
     * clear
     *
     * This clears all stats for _everything_.
     * @param int $user_id
     */
    public static function clear($user_id = 0)
    {
        if ($user_id > 0) {
            Dba::write("DELETE FROM `object_count` WHERE `user` = ?;", array($user_id));
        } else {
            Dba::write("TRUNCATE `object_count`;");
        }
        Dba::write("UPDATE `song` SET `played` = 0;");
    }

    /**
     * garbage_collection
     *
     * This removes stats for things that no longer exist.
     */
    public static function garbage_collection()
    {
        foreach (array('album', 'artist', 'song', 'playlist', 'tag', 'live_stream', 'video', 'podcast', 'podcast_episode') as $object_type) {
            Dba::write("DELETE FROM `object_count` WHERE `object_type` = '$object_type' AND `object_count`.`object_id` NOT IN (SELECT `$object_type`.`id` FROM `$object_type`);");
        }
        // if deletes are copmleted you can have left over stuff
        Dba::write("DELETE FROM `object_count` WHERE `object_type` IN ('album', 'artist', 'podcast') AND `count_type` = ('skip');");
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @param int $child_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id, $child_id)
    {
        if (!in_array($object_type, array('song', 'album', 'artist', 'video', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'tvshow'))) {
            return false;
        }
        $sql    = "UPDATE IGNORE `object_count` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        $params = array($new_object_id, $object_type, $old_object_id);
        if ((int)$child_id > 0) {
            $sql .= " AND `date` IN (SELECT `date` FROM (SELECT `date` FROM `object_count` WHERE `object_type` = 'song' AND object_id = ?) AS `song_date`)";
            $params[] = $child_id;
        }

        return Dba::write($sql, $params);
    }

    /**
     * When creating an artist_map, duplicate the stat rows
     */
    public static function duplicate_map(string $source_type, int $source_id, string $dest_type, int $dest_id)
    {
        if ($source_id > 0 && $dest_id > 0) {
            debug_event(__CLASS__, "duplicate_map " . $source_type . " {" . $source_id . "} => " . $dest_type . " {" . $dest_id . "}", 5);
            $sql        = "SELECT `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = ? AND `object_count`.`object_id` = ?;";
            $db_results = Dba::read($sql, array($source_type, $source_id));
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                Dba::write($sql, array($dest_type, $dest_id, $row['count_type'], $row['date'], $row['user'], $row['agent'], $row['geo_latitude'], $row['geo_longitude'], $row['geo_name']));
            }
        }
    }

    /**
     * When deleting an artist_map, remove the stat rows too
     */
    public static function delete_map(string $source_type, int $source_id, string $dest_type, int $dest_id)
    {
        if ($source_id > 0 && $dest_id > 0) {
            debug_event(__CLASS__, "delete_map " . $source_type . " {" . $source_id . "} => " . $dest_type . " {" . $dest_id . "}", 5);
            $sql        = "SELECT `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = ? AND `object_count`.`object_id` = ?;";
            $db_results = Dba::read($sql, array($source_type, $source_id));
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "DELETE FROM `object_count` WHERE `object_count`.`object_type` = ? AND `object_count`.`object_id` = ? AND `object_count`.`date` = ? AND `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`geo_latitude` = ? AND `object_count`.`geo_longitude` = ? AND `object_count`.`geo_name` = ? AND `object_count`.`count_type` = ?";
                Dba::write($sql, array($dest_type, $dest_id, $row['date'], $row['user'], $row['agent'], $row['geo_latitude'], $row['geo_longitude'], $row['geo_name'], $row['count_type']));
            }
        }
    }

    /**
     * Delete a user activity in object_count, find related objects and reduce counts for parent/child objects
     */
    public static function delete(int $activity_id)
    {
        if ($activity_id > 0) {
            $sql        = "SELECT `object_count`.`object_id`, `object_count`.`object_type`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`id` = ?;";
            $db_results = Dba::read($sql, array($activity_id));
            if ($row = Dba::fetch_assoc($db_results)) {
                $params     = array($row['date'], $row['user'], $row['agent'], $row['count_type']);
                $sql        = "SELECT `object_id`, `object_type` FROM `object_count` WHERE `object_count`.`date` = ? AND `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`count_type` = ? AND `count_type` = 'stream'";
                $db_results = Dba::read($sql, $params);
                while ($row = Dba::fetch_assoc($db_results)) {
                    // reduce the counts for these objects too
                    if (in_array($row['object_type'], array('song', 'album', 'artist', 'video', 'podcast', 'podcast_episode'))) {
                        self::count($row['object_type'], $row['object_id'], 'down');
                    }
                }
                // delete the row and all related activities
                $sql = "DELETE FROM `object_count` WHERE `object_count`.`date` = ? AND `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`count_type` = ?";
                Dba::write($sql, $params);
            }
        }
    }

    /**
     * update the play_count for an object
     */
    public static function count(string $type, int $object_id, $count_type = 'up')
    {
        switch ($type) {
            case 'song':
            case 'podcast':
            case 'podcast_episode':
            case 'video':
                $sql = ($count_type == 'down')
                    ? "UPDATE `$type` SET `total_count` = `total_count` - 1, `total_skip` = `total_skip` + 1 WHERE `id` = ? AND `total_count` > 0"
                    : "UPDATE `$type` SET `total_count` = `total_count` + 1 WHERE `id` = ?";
                Dba::write($sql, array($object_id));
                break;
            case 'album':
            case 'artist':
                $sql = ($count_type == 'down')
                    ? "UPDATE `$type` SET `total_count` = `total_count` - 1 WHERE `id` = ? AND `total_count` > 0"
                    : "UPDATE `$type` SET `total_count` = `total_count` + 1 WHERE `id` = ?";
                Dba::write($sql, array($object_id));
                break;
        }
    }

    /**
     * insert
     * This inserts a new record for the specified object
     * with the specified information, amazing!
     * @param string $input_type
     * @param integer $object_id
     * @param integer $user_id
     * @param string $agent
     * @param array $location
     * @param string $count_type
     * @param integer $date
     * @return boolean
     */
    public static function insert(
        $input_type,
        $object_id,
        $user_id,
        $agent = '',
        $location = [],
        $count_type = 'stream',
        $date = null
    ) {
        if (AmpConfig::get('use_auth') && $user_id < 0) {
            debug_event(self::class, 'Invalid user given ' . $user_id, 3);

            return false;
        }
        $type = self::validate_type($input_type);
        if (self::is_already_inserted($type, $object_id, $user_id, $agent, $date)) {
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

        $sql        = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($type, $object_id, $count_type, $date, $user_id, $agent, $latitude, $longitude, $geoname));

        // the count was inserted
        if ($db_results) {
            if (in_array($type, array('song', 'album', 'artist', 'video', 'podcast', 'podcast_episode')) && $count_type === 'stream' && $user_id > 0 && $agent !== 'debug') {
                self::count($type, $object_id);
                // don't register activity for album or artist plays
                if (!in_array($type, array('album', 'artist', 'podcast'))) {
                    static::getUserActivityPoster()->post((int)$user_id, 'play', $type, (int)$object_id, (int)$date);
                }
            }

            return true;
        }
        debug_event(self::class, 'Unable to insert statistics for ' . $user_id . ':' . $object_id, 3);

        return false;
    } // insert

    /**
     * is_already_inserted
     * Check if the same stat has not already been inserted within a graceful delay
     * @param string $type
     * @param integer $object_id
     * @param integer $user
     * @param string $agent
     * @param integer $time
     * @param bool $exact
     * @return boolean
     */
    public static function is_already_inserted($type, $object_id, $user, $agent, $time, $exact = false)
    {
        $sql = ($exact)
            ? "SELECT `object_id`, `date`, `count_type` FROM `object_count` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` = ? AND `object_count`.`count_type` = 'stream' AND `object_count`.`date` = $time "
            : "SELECT `object_id`, `date`, `count_type` FROM `object_count` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` = ? AND `object_count`.`count_type` = 'stream' AND (`object_count`.`date` >= ($time - 5) AND `object_count`.`date` <= ($time + 5)) ";
        $params = array($user, $type);
        if ($agent !== '') {
            $sql .= "AND `object_count`.`agent` = ? ";
            $params[] = $agent;
        }
        $sql .= "ORDER BY `object_count`.`date` DESC";

        $db_results = Dba::read($sql, $params);
        while ($row = Dba::fetch_assoc($db_results)) {
            // Stop double ups
            if ($row['object_id'] == $object_id) {
                debug_event(self::class, 'Object already inserted {' . (string) $object_id . '} date: ' . (string) $time, 5);

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

        if (AmpConfig::get('cron_cache')) {
            $sql = "SELECT `count` AS `total_count` FROM `cache_object_count` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ? AND `threshold` = " . $threshold;
        } else {
            $sql = "SELECT COUNT(*) AS `total_count` FROM `object_count` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ?";
            if ($threshold > 0) {
                $date = time() - (86400 * (int)$threshold);
                $sql .= "AND `date` >= '" . $date . "'";
            }
        }

        $db_results = Dba::read($sql, array($object_type, $object_id, $count_type));
        $results    = Dba::fetch_assoc($db_results);

        return (int)$results['total_count'];
    } // get_object_count

    /**
     * get_object_total
     * Get count for an object
     * @param string $object_type
     * @param integer $object_id
     * @param string $threshold
     * @param string $count_type
     * @return integer
     */
    public static function get_object_total($object_type, $object_id, $threshold = null, $count_type = 'stream')
    {
        if ($threshold === null || $threshold === '') {
            $threshold = 0;
        }

        if (AmpConfig::get('cron_cache')) {
            $sql = "SELECT `count_total` AS `total_count` FROM `object_total` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ? AND `threshold` = " . $threshold;
        } else {
            $sql = "SELECT COUNT(*) AS `total_count` FROM `object_count` WHERE `object_type`= ? AND `object_id` = ? AND `count_type` = ?";
            if ($threshold > 0) {
                $date = time() - (86400 * (int)$threshold);
                $sql .= "AND `date` >= '" . $date . "'";
            }
        }

        $db_results = Dba::read($sql, array($object_type, $object_id, $count_type));
        $results    = Dba::fetch_assoc($db_results);

        return (int)$results['total_count'];
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
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        if ((int)$user_id == 0) {
            return array(
                'id' => 0,
                'object_type' => false,
                'object_id' => false,
                'user' => 0,
                'agent' => '',
                'date' => 0,
                'count_type' => ''
            );
        }

        $sql    = "SELECT `object_count`.`id`, `object_count`.`object_type`, `object_count`.`object_id`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`date`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` IN ('song', 'video', 'podcast_episode') AND `object_count`.`count_type` IN ('stream', 'skip') ";
        $params = array($user_id);
        if ($agent) {
            $sql .= "AND `object_count`.`agent` = ? ";
            $params[] = $agent;
        }
        if ($date > 0) {
            $sql .= "AND `object_count`.`date` <= ? ";
            $params[] = $date;
        }
        $sql .= "ORDER BY `object_count`.`date` DESC LIMIT 1";
        $db_results = Dba::read($sql, $params);

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
        $sql = "UPDATE `object_count` SET `object_count`.`date` = ? WHERE `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`date` = ?";
        Dba::write($sql, array($new_date, $user_id, $agent, $original_date));

        // update the user_activity table
        $sql = "UPDATE `user_activity` SET `user_activity`.`activity_date` = ? WHERE `user_activity`.`user` = ? AND `user_activity`.`activity_date` = ?";
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
        $sql        = "SELECT `time` FROM `$object_type` WHERE `id` = ?";
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
     * @param integer $object_id
     * @param string $object_type
     * @return PDOStatement|boolean
     */
    public static function skip_last_play($date, $agent, $user_id, $object_id, $object_type)
    {
        // change from a stream to a skip
        $sql = "UPDATE `object_count` SET `count_type` = 'skip' WHERE `date` = ? AND `agent` = ? AND `user` = ? AND `object_count`.`object_type` = ? ORDER BY `object_count`.`date` DESC";
        Dba::write($sql, array($date, $agent, $user_id, $object_type));

        // update the total counts (and total_skip counts) as well
        if ($user_id > 0 && $agent !== 'debug') {
            $song = new Song($object_id);
            self::count('song', $song->id, 'down');
            self::count('album', $song->album, 'down');
            $artists = array_unique(array_merge(Song::get_parent_array($song->id), Song::get_parent_array($song->album, 'album')));
            foreach ($artists as $artist_id) {
                self::count('artist', $artist_id, 'down');
            }
            if (in_array($object_type, array('song', 'video', 'podcast_episode'))) {
                $sql  = "UPDATE `user_data`, (SELECT `$object_type`.`size` FROM `$object_type` WHERE `$object_type`.`id` = ?) AS `$object_type` SET `value` = `value` - `$object_type`.`size` WHERE `user` = ? AND `value` = 'play_size'";
                Dba::write($sql, array($object_id, $object_id));
            }
        }

        // To remove associated album and artist entries
        $sql = "DELETE FROM `object_count` WHERE `object_type` IN ('album', 'artist', 'podcast') AND `date` = ? AND `agent` = ? AND `user` = ? ";

        return Dba::write($sql, array($date, $agent, $user_id));
    } // skip_last_play

    /**
     * has_played_history
     * this checks to see if the current object has been played recently by the user
     * @param string $object_type
     * @param Song|Podcast_Episode|Video $object
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public static function has_played_history($object_type, $object, $user, $agent, $date)
    {
        if (AmpConfig::get('use_auth') && $user == -1) {
            return false;
        }
        // if it's already recorded (but from a different agent), don't do it again
        if (self::is_already_inserted($object_type, $object->id, $user, '', $date, true)) {
            return false;
        }
        $previous  = self::get_last_play($user, $agent, $date);
        // no previous data?
        if (!array_key_exists('object_id', $previous) || !array_key_exists('object_type', $previous)) {
            return true;
        }
        $last_time = self::get_time($previous['object_id'], $previous['object_type']);
        $diff      = $date - (int) $previous['date'];
        $item_time = $object->time;
        $skip_time = AmpConfig::get_skip_timer($last_time);

        // if your last song is 30 seconds and your skip timer is 40 you don't want to keep skipping it.
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
            self::skip_last_play($previous['date'], $previous['agent'], $previous['user'], $previous['object_id'], $previous['object_type']);
            // delete song, podcast_episode and video from user_activity to keep stats in line
            static::getUseractivityRepository()->deleteByDate($previous['date'], 'play', (int) $previous['user']);
        }

        return true;
    } // has_played_history

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }

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
        if (!in_array((string)$user_id, static::getUserRepository()->getValid())) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        $order = ($newest) ? 'DESC' : 'ASC';
        $sql   = (AmpConfig::get('catalog_disable'))
            ? "SELECT * FROM `object_count` LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' AND `object_count`.`date` >= ? AND `catalog`.`enabled` = '1' "
            : "SELECT * FROM `object_count` LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' AND `object_count`.`date` >= ? ";
        $sql .= (AmpConfig::get('catalog_filter') && $user_id > 0)
            ? " AND" . Catalog::get_user_filter('song', $user_id) . "ORDER BY `object_count`.`date` " . $order
            : "ORDER BY `object_count`.`date` " . $order;
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
     * @param boolean $addAdditionalColumns
     * @return string
     */
    public static function get_top_sql(
        $input_type,
        $threshold,
        $count_type = 'stream',
        $user_id = null,
        $random = false,
        bool $addAdditionalColumns = false
    ) {
        $type           = self::validate_type($input_type);
        $date           = time() - (86400 * (int)$threshold);
        $catalog_filter = (AmpConfig::get('catalog_filter'));
        if ($type == 'playlist' && !$addAdditionalColumns) {
            $sql = "SELECT `id` FROM `playlist`";
            if ($threshold > 0) {
                $sql .= " WHERE `last_update` >= '" . $date . "' ";
            }
            if ($catalog_filter && $user_id > 0) {
                $sql .= ($threshold > 0)
                    ? " AND" . Catalog::get_user_filter($type, $user_id)
                    : " WHERE" . Catalog::get_user_filter($type, $user_id);
            }
            // playlist is now available in object_count too
            $sql .= "UNION SELECT `object_id` FROM `object_count` WHERE `object_type` = 'playlist'";
            if ($threshold > 0) {
                $sql .= " AND `date` >= '" . $date . "' ";
            }
            if ($catalog_filter && $user_id > 0) {
                $sql .= " AND" . Catalog::get_user_filter("object_count_" . $type, $user_id);
            }
            //debug_event(self::class, 'get_top_sql ' . $sql, 5);

            return $sql;
        }
        if ($user_id === null && AmpConfig::get('cron_cache') && !$addAdditionalColumns && in_array($type, array('album', 'artist', 'song', 'genre', 'catalog', 'live_stream', 'video', 'podcast', 'podcast_episode', 'playlist'))) {
            $sql = "SELECT `object_id` AS `id`, MAX(`count`) AS `count` FROM `cache_object_count` WHERE `object_type` = '" . $type . "' AND `count_type` = '" . $count_type . "' AND `threshold` = '" . $threshold . "' GROUP BY `object_id`, `object_type`";
        } else {
            $is_podcast = ($type == 'podcast');
            $select_sql = ($is_podcast)
                ? "`podcast_episode`.`podcast`"
                : "MIN(`object_id`)";
            // Select Top objects counting by # of rows for you only
            $sql   = "SELECT $select_sql AS `id`, COUNT(*) AS `count`";
            $group = '`object_count`.`object_id`';
            // Add additional columns to use the select query as insert values directly
            if ($addAdditionalColumns) {
                $sql .= ($is_podcast)
                    ? ", 'podcast' AS `object_type`, `count_type`, " . $threshold . " AS `threshold`"
                    : ", `object_type`, `count_type`, " . $threshold . " AS `threshold`";
            }
            $sql .= " FROM `object_count`";
            if ($is_podcast) {
                $group = '`podcast_episode`.`podcast`';
                $type  = 'podcast_episode';
                $sql .= " LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `object_count`.`object_id` AND `object_count`.`object_type` = 'podcast_episode'";
            }
            if ($input_type == 'album_artist' || $input_type == 'song_artist') {
                $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `object_count`.`object_id` AND `object_count`.`object_type` = 'artist'";
            }
            if ($input_type == 'album_disk') {
                $sql   = "SELECT `album_disk`.`id` AS `id`, COUNT(*) AS `count` FROM `object_count` LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` AND `object_type` = 'song' LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `song`.`disk` = `album_disk`.`disk`";
                $group = '`album_disk`.`id`';
                $type  = 'song';
            }
            if ($user_id !== null) {
                $sql .= " WHERE `object_count`.`object_type` = '" . $type . "' AND `object_count`.`user` = " . (string)$user_id;
            } else {
                $sql .= " WHERE `object_count`.`object_type` = '" . $type . "' ";
                if ($threshold > 0) {
                    $sql .= "AND `object_count`.`date` >= '" . $date . "'";
                }
            }
            if ($input_type == 'album_artist') {
                $sql .= " AND `artist`.`album_count` > 0";
            }
            if ($input_type == 'song_artist') {
                $sql .= " AND `artist`.`song_count` > 0";
            }
            if (AmpConfig::get('catalog_disable') && in_array($type, array('artist', 'album', 'album_disk', 'song', 'video'))) {
                $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
            }
            if (AmpConfig::get('catalog_filter') && in_array($type, array('artist', 'album', 'album_disk', 'podcast_episode', 'song', 'video')) && $user_id > 0) {
                $sql .= " AND" . Catalog::get_user_filter("object_count_$type", $user_id);
            }
            $rating_filter = AmpConfig::get_rating_filter();
            if ($rating_filter > 0 && $rating_filter <= 5 && $user_id !== null) {
                $sql .= " AND `object_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user_id . ")";
            }
            $sql .= " AND `count_type` = '" . $count_type . "' GROUP BY $group, `object_count`.`object_type`, `object_count`.`count_type`";
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
     * @param string $input_type
     * @param integer $count
     * @param integer $threshold
     * @param integer $offset
     * @param integer $user_id
     * @param boolean $random
     * @return array
     */
    public static function get_top($input_type, $count, $threshold, $offset = 0, $user_id = null, $random = false)
    {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }
        $sql   = self::get_top_sql($input_type, $threshold, 'stream', $user_id, $random);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= "LIMIT $limit";
        }

        //debug_event(self::class, 'get_top ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_top

    /**
     * get_recent_sql
     * This returns the get_recent sql
     * @param string $input_type
     * @param int $user_id
     * @param boolean $newest
     * @return string
     */
    public static function get_recent_sql($input_type, $user_id = null, $newest = true)
    {
        $type              = self::validate_type($input_type);
        $ordersql          = ($newest === true) ? 'DESC' : 'ASC';
        $user_sql          = (!empty($user_id)) ? " AND `user` = '" . $user_id . "'" : '';
        $catalog_filter    = (AmpConfig::get('catalog_filter'));

        $sql = "SELECT `object_id` AS `id`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_type` = '" . $type . "' AND `count_type` = 'stream'" . $user_sql;
        if ($input_type == 'album_disk') {
            $sql = "SELECT `album_disk`.`id` AS `id`, MAX(`date`) AS `date` FROM `object_count` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `object_id` AND `object_type` = 'album' WHERE `object_type` = 'album' AND `count_type` = 'stream'" . $user_sql;
        }
        if ($input_type == 'album_artist') {
            $sql = "SELECT `object_id` AS `id`, MAX(`date`) AS `date` FROM `object_count` LEFT JOIN `artist` ON `artist`.`id` = `object_id` AND `object_type` = 'artist' WHERE `artist`.`album_count` > 0 AND `object_type` = 'artist' AND `count_type` = 'stream'" . $user_sql;
        }
        if (AmpConfig::get('catalog_disable') && in_array($type, array('artist', 'album', 'album_disk', 'song', 'video'))) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        if ($catalog_filter && in_array($type, array('video', 'artist', 'album_artist', 'album', 'album_disk', 'song')) && $user_id > 0) {
            $sql .= " AND" . Catalog::get_user_filter("object_count_$type", $user_id);
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && !empty($user_id)) {
            $sql .= " AND `object_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user_id . ")";
        }
        if ($input_type == 'album_disk') {
            $sql .= " GROUP BY `album_disk`.`id` ORDER BY MAX(`date`) " . $ordersql . ", `album_disk`.`id` ";
        } else {
            $sql .= " GROUP BY `object_count`.`object_id` ORDER BY MAX(`date`) " . $ordersql . ", `object_count`.`object_id` ";
        }
        // playlists aren't the same as other objects so change the sql
        if ($type === 'playlist') {
            $sql = "SELECT `id`, `last_update` AS `date` FROM `playlist`";
            if (!empty($user_id)) {
                $sql .= " WHERE `user` = '" . $user_id . "'";
                if ($catalog_filter) {
                    $sql .= " AND" . Catalog::get_user_filter($type, $user_id);
                }
            }
            $sql .= " ORDER BY `last_update` " . $ordersql;
        }
        //debug_event(self::class, 'get_recent_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_recent
     * This returns the recent X for type Y
     * @param string $input_type
     * @param integer $count
     * @param integer $offset
     * @param boolean $newest
     * @return array
     */
    public static function get_recent($input_type, $count = 0, $offset = 0, $newest = true)
    {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        $sql   = self::get_recent_sql($input_type, null, $newest);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= "LIMIT $limit";
        }

        //debug_event(self::class, 'get_recent ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_recent

    /**
     * get_recently_played
     * This function returns the last X played media objects ('live_stream','podcast_episode','song','video')
     * It uses the popular threshold to figure out how many to pull it will only return unique object
     * @param integer $user_id
     * @param string $count_type
     * @param string $object_type
     * @param bool $user_only
     * @return array
     */
    public static function get_recently_played($user_id, $count_type = 'stream', $object_type = false, $user_only = false)
    {
        $personal_info_recent = 91;
        $personal_info_time   = 92;
        $personal_info_agent  = 93;
        $limit                = AmpConfig::get('popular_threshold', 10);
        $access100            = Access::check('interface', 100);
        $object_string        = (!$object_type)
            ? "'song', 'live_stream', 'podcast_episode', 'video'"
            : "'$object_type'";

        $results = array();
        $sql     = "SELECT `object_count`.`object_id`, `catalog_map`.`catalog_id`, `object_count`.`user`, `object_count`.`object_type`, `date`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `pref_recent`.`value` AS `user_recent`, `pref_time`.`value` AS `user_time`, `pref_agent`.`value` AS `user_agent`, `object_count`.`id` AS `activity_id` FROM `object_count` LEFT JOIN `user_preference` AS `pref_recent` ON `pref_recent`.`preference`='$personal_info_recent' AND `pref_recent`.`user` = `object_count`.`user` LEFT JOIN `user_preference` AS `pref_time` ON `pref_time`.`preference`='$personal_info_time' AND `pref_time`.`user` = `object_count`.`user` LEFT JOIN `user_preference` AS `pref_agent` ON `pref_agent`.`preference`='$personal_info_agent' AND `pref_agent`.`user` = `object_count`.`user` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `object_count`.`object_type` AND `catalog_map`.`object_id` = `object_count`.`object_id` WHERE `object_count`.`object_type` IN ($object_string) AND `object_count`.`count_type` = '$count_type' ";
        // check for valid catalogs
        $sql .= " AND `catalog_map`.`catalog_id` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ";

        if ((int)$user_id > 0 || !$access100) {
            $sql .= ($user_only)
                ? "AND `object_count`.`user` IN (SELECT `user` FROM `user_preference` WHERE (`preference`='$personal_info_recent' AND `value`='1') AND `user`='$user_id') "
                : "AND `object_count`.`user` IN (SELECT `user` FROM `user_preference` WHERE (`preference`='$personal_info_recent' AND `value`='1') OR `user`='$user_id') ";
        }
        $sql .= "ORDER BY `date` DESC LIMIT " . (string)$limit;
        //debug_event(self::class, 'get_recently_played ' . $sql, 5);

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if (empty($row['geo_name']) && array_key_exists('latitude', $row) && array_key_exists('longitude', $row)) {
                $row['geo_name'] = Stats::get_cached_place_name($row['latitude'], $row['longitude']);
            }
            $results[] = $row;
        }

        return $results;
    } // get_recently_played

    /**
     * get_user
     * This gets all stats for a type based on user with thresholds and all
     * If full is passed, doesn't limit based on date
     * @param string $count
     * @param string $input_type
     * @param integer $user
     * @param integer $full
     * @return array
     */
    public static function get_user($count, $input_type, $user, $full = 0)
    {
        $type = self::validate_type($input_type);

        // If full then don't limit on date
        $date = ($full > 0) ? '0' : time() - (86400 * (int)AmpConfig::get('stats_threshold', 7));

        // Select Objects based on user
        // FIXME:: Requires table scan, look at improving
        $sql        = "SELECT `object_id`, COUNT(`id`) AS `count` FROM `object_count` WHERE `object_type` = ? AND `date` >= ? AND `user` = ? GROUP BY `object_id` ORDER BY `count` DESC LIMIT " . (int)$count;
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
            case 'album_disk':
            case 'tag':
            case 'song':
            case 'video':
            case 'tvshow':
            case 'tvshow_season':
            case 'tvshow_episode':
            case 'movie':
            case 'playlist':
            case 'podcast':
            case 'podcast_episode':
            case 'live_stream':
                return $type;
            case 'album_artist':
            case 'song_artist':
                return 'artist';
            case 'genre':
                return 'tag';
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
    public static function get_newest_sql($input_type, $catalog = 0, $user_id = null)
    {
        $type = self::validate_type($input_type);
        // all objects could be filtered
        $catalog_filter = (AmpConfig::get('catalog_filter'));

        // add playlists to mashup browsing
        if ($type == 'playlist') {
            $sql = ($catalog_filter && $user_id > 0)
                ? "SELECT `playlist`.`id`, MAX(`playlist`.`last_update`) AS `real_atime` FROM `playlist` WHERE" . Catalog::get_user_filter($type, $user_id) . "GROUP BY `playlist`.`id` ORDER BY `real_atime` DESC "
                : "SELECT `playlist`.`id`, MAX(`playlist`.`last_update`) AS `real_atime` FROM `playlist` GROUP BY `playlist`.`id` ORDER BY `real_atime` DESC ";

            return $sql;
        }
        $base_type   = 'song';
        $filter_type = $type;
        // everything else
        if ($type === 'song') {
            $sql      = "SELECT DISTINCT(`song`.`id`) AS `id`, `song`.`addition_time` AS `real_atime` FROM `song` ";
            $sql_type = "`song`.`id`";
        } elseif ($type === 'album') {
            $base_type = 'album';
            $sql       = "SELECT DISTINCT(`album`.`id`) AS `id`, `album`.`addition_time` AS `real_atime` FROM `album` ";
            $sql_type  = "`album`.`id`";
        } elseif ($type === 'album_disk') {
            $base_type = 'album';
            $sql       = "SELECT DISTINCT(`album_disk`.`id`) AS `id`, MIN(`album`.`addition_time`) AS `real_atime` FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` ";
            $sql_type  = "`album_disk`.`id`";
        } elseif ($type === 'video') {
            $base_type = 'video';
            $sql       = "SELECT DISTINCT(`video`.`id`) AS `id`, `video`.`addition_time` AS `real_atime` FROM `video` ";
            $sql_type  = "`video`.`id`";
        } elseif ($type === 'artist') {
            $sql         = "SELECT DISTINCT(`song`.`artist`) AS `id`, MIN(`song`.`addition_time`) AS `real_atime` FROM `song` ";
            $sql_type    = "`song`.`artist`";
            $filter_type = 'song_artist';
        } elseif ($type === 'album_artist') {
            $base_type   = 'album';
            $sql         = "SELECT DISTINCT(`album`.`album_artist`) AS `id`, MIN(`album`.`addition_time`) AS `real_atime` FROM `album` LEFT JOIN `artist` ON `album`.`album_artist` = `artist`.`id` ";
            $sql_type    = "`album`.`album_artist`";
            $filter_type = 'artist';
            $type        = 'artist';
        } elseif ($type === 'podcast') {
            $base_type = 'podcast';
            $sql       = "SELECT DISTINCT(`podcast`.`id`) AS `id`, MIN(`podcast`.`lastsync`) AS `real_atime` FROM `podcast` ";
            $sql_type  = "`podcast`.`id`";
        } elseif ($type === 'podcast_episode') {
            $base_type = 'podcast_episode';
            $sql       = "SELECT DISTINCT(`podcast_episode`.`id`) AS `id`, MIN(`podcast_episode`.`addition_time`) AS `real_atime` FROM `podcast_episode` ";
            $sql_type  = "`podcast_episode`.`id`";
        } else {
            // what else?
            $sql      = "SELECT MIN(`$type`) AS `id`, MIN(`song`.`addition_time`) AS `real_atime` FROM `$base_type` ";
            $sql_type = "`song`.`" . $type . "`";
        }
        // join valid catalogs or a specific one
        $sql .= ($catalog > 0)
            ? "LEFT JOIN `catalog` ON `catalog`.`id` = `" . $base_type . "`.`catalog` WHERE `catalog` = '" . (string)scrub_in($catalog) . "' "
            : "LEFT JOIN `catalog` ON `catalog`.`id` = `" . $base_type . "`.`catalog` WHERE `catalog`.`id` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        $user_id       = (!empty(Core::get_global('user'))) ? Core::get_global('user')->id : 0;
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id > 0) {
            $sql .= "AND " . $sql_type . " NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user_id . ") ";
        }
        if ($type === 'song' || $type == 'album' || $base_type === 'video') {
            $sql .= "GROUP BY $sql_type, `real_atime` ORDER BY `real_atime` DESC ";
        } else {
            $sql .= "GROUP BY $sql_type ORDER BY `real_atime` DESC ";
        }
        //debug_event(self::class, 'get_newest_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_newest
     * This returns an array of the newest artists/albums/whatever
     * in this Ampache instance
     * @param string $input_type
     * @param integer $count
     * @param integer $offset
     * @param integer $catalog
     * @param integer $user_id
     * @return integer[]
     */
    public static function get_newest($input_type, $count = 0, $offset = 0, $catalog = 0, $user_id = null)
    {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        $sql   = self::get_newest_sql($input_type, $catalog, $user_id);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= "LIMIT $limit";
        }

        //debug_event(self::class, 'get_newest ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row[0];
        } // end while results

        return $results;
    } // get_newest

    /**
     * @deprecated inject dependency
     */
    private static function getUserActivityPoster(): UserActivityPosterInterface
    {
        global $dic;

        return $dic->get(UserActivityPosterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
