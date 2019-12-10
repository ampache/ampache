<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
 * User_Queue Class
 *
 * This class handles the current playlist in Ampache for subsonic. It handles the
 * user_queue and user_queue_data tables.
 *
 */
class User_Queue extends database_object
{
    /* Variables from the Datbase */
    public $user_queue;
    public $current;
    public $position;
    public $username;
    public $changed;
    public $changedby;

    /* Generated Elements */
    public $items = array();

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the
     * information.  If no playlist_id is passed or the requested one isn't
     * found, return false.
     */
    public function __construct($playlist_id = '')
    {
        if (!$playlist_id) {
            return false;
        }

        $this->user_queue     = (int) ($playlist_id);
        $info         = $this->has_info();

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // __construct

    /**
     * has_info
     * This is an internal (private) function that gathers the information
     * for this object from the playlist_id that was passed in.
     * @return array
     */
    private function has_info()
    {
        $sql        = "SELECT * FROM `user_queue` WHERE `user_queue`='" . Dba::escape($this->user_queue) . "'";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results;
    } // has_info

    /**
     * get_from_userid
     * This returns a tmp playlist object based on a userid passed
     * this is used for the user profiles page
     */
    public static function get_from_userid($user_id)
    {
        $client   = new User($user_id);
        $username = Dba::escape($client->username);

        $sql = "SELECT `user_queue` FROM `user_queue` " .
            "WHERE `user_queue`.`username`='$username'";
        $db_results = Dba::read($sql);

        $data = Dba::fetch_assoc($db_results);

        return $data['user_queue'];
    } // get_from_userid

    /**
     * get_items
     * Returns an array of all object_ids currently in this User_Queue.
     * @return array
     */
    public function get_items()
    {
        /* Select all objects from this playlist */
        $sql = "SELECT `object_type`, `id`, `object_id` " .
            "FROM `user_queue_data` " .
            "WHERE `user_queue` = ? ORDER BY `id` ASC";
        $db_results = Dba::read($sql, array($this->user_queue));

        /* Define the array */
        $items = array();

        $count = 1;
        while ($results = Dba::fetch_assoc($db_results)) {
            $items[]     = array(
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track_id' => $results['id'],
                'track' => $count++,
            );
        }

        return $items;
    } // get_items

    /**
     * count_items
     * This returns a count of the total number of tracks that are in this
     * tmp playlist
     */
    public function count_items()
    {
        $id = Dba::escape($this->user_queue);

        $sql = "SELECT COUNT(`id`) FROM `user_queue_data` WHERE " .
            "`user_queue`='$id'";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_row($db_results);

        return $results['0'];
    } // count_items

    /**
      * clear
     * This clears all the objects out of a single playlist
     */
    public function clear()
    {
        $sql = "DELETE FROM `user_queue_data` WHERE `user_queue` = ?";
        Dba::write($sql, array($this->user_queue));

        return true;
    } // clear

    /**
     * create
     * This function initializes a new User_Queue. It is associated with
     * the current user.
     * @return string|null
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `user_queue` " .
            "(`current`, `position`, `username`, `changed`) " .
            " VALUES (?, ?, ?)";
        Dba::write($sql, array($data['current'], $data['position'], $data['u'], time()));

        $tmp_id = Dba::insert_id();

        /* Clean any other playlists associated with this session */
        self::session_clean($data['u'], $tmp_id);

        return $tmp_id;
    } // create

    /**
     * session_clean
     * This deletes any other user_queues associated with this
     * session
     * @param string|null $id
     */
    public static function session_clean($sessid, $id)
    {
        $sql = "DELETE FROM `user_queue` WHERE `username`= ? AND `id` != ?";
        Dba::write($sql, array($sessid, $id));

        /* Remove associated tracks */
        self::prune_tracks();

        return true;
    } // session_clean

    /**
     * garbage_collection
     * This cleans up old data
     */
    public static function garbage_collection()
    {
        self::prune_playlists();
        self::prune_tracks();
        //Dba::write("DELETE FROM `user_queue_data` USING `user_queue_data` LEFT JOIN `song` ON `user_queue_data`.`object_id` = `song`.`id` WHERE `song`.`id` IS NULL");
    }

    /**
     * prune_playlists
     * This deletes any playlists that don't have an associated session
     */
    public static function prune_playlists()
    {
        /* Just delete if no matching session row */
        $sql = "DELETE FROM `user_queue` USING `user_queue` " .
            "LEFT JOIN `user` " .
            "ON `user`.`username`=`user_queue`.`username` " .
            "WHERE `user_queue`.`username` IS NULL";
        Dba::write($sql);

        return true;
    } // prune_playlists

    /**
     * prune_tracks
     * This prunes tracks that don't have playlists or don't have votes
     */
    public static function prune_tracks()
    {
        // This prune is always run and clears data for playlists that
        // don't exist anymore
        $sql = "DELETE FROM `user_queue_data` USING " .
            "`user_queue_data` LEFT JOIN `user_queue` ON " .
            "`user_queue_data`.`user_queue`=`user_queue`.`id` " .
            "WHERE `user_queue`.`id` IS NULL";
        Dba::write($sql);
    } // prune_tracks
} // class User_Queue
