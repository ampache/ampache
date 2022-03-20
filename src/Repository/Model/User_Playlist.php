<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;

/**
 * UserPlaylist Class
 *
 * This class handles the user playlists in Ampache. It handles the
 * user_playlist table creating a global play queue for each user
 */
class User_Playlist extends database_object
{
    protected const DB_TABLENAME = 'user_playlist';

    public int $user;

    /**
     * Constructor
     * This takes a user_id as an optional argument and gathers the
     * information.  If no user_id is passed or the requested one isn't
     * found, return false.
     * @param string $user_id
     */
    public function __construct($user_id = '')
    {
        if (!$user_id) {
            return false;
        }

        $this->user = (int)$user_id;

        return true;
    } // __construct

    /**
     * get_items
     * Returns an array of all object_ids currently in this User_Playlist.
     * @return array
     */
    public function get_items()
    {
        $items = array();
        // Select all objects from this user
        $sql        = "SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user` = ? ORDER BY `track`, `id`";
        $db_results = Dba::read($sql, array($this->user));

        while ($results = Dba::fetch_assoc($db_results)) {
            $items[] = array(
                'id' => $results['id'],
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track' => $results['track'],
                'current_track' => $results['current_track'],
                'current_time' => $results['current_time']
            );
        }

        return $items;
    } // get_items

    /**
     * get_current_object
     * This returns the next object in the user_playlist.
     * @return array
     */
    public function get_current_object()
    {
        $items = array();
        // Select the current object for this user
        $sql        = "SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user`= ? AND `current_track` = 1 LIMIT 1";
        $db_results = Dba::read($sql, array($this->user));

        while ($results = Dba::fetch_assoc($db_results)) {
            $items = array(
                'id' => $results['id'],
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track' => $results['track'],
                'current_track' => $results['current_track'],
                'current_time' => $results['current_time']
            );
        }

        return $items;
    } // get_current_object

    /**
     * set_current_object
     * set the active object in the user_playlist.
     * @param string $object_type
     * @param int $object_id
     * @param int $position
     */
    public function set_current_object($object_type, $object_id, $position)
    {
        // remove the old current
        $sql = "UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?";
        Dba::write($sql, array($this->user));
        // set the new one
        $sql = "UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `object_id` = ? AND `user` = ? LIMIT 1";
        Dba::write($sql, array($position, $object_type, $object_id, $this->user));
    } // set_current_object

    /**
     * set_current_id
     * set the active object using the row id in user_playlist.
     * @param string $object_type
     * @param int $playlist_id
     * @param int $position
     */
    public function set_current_id($object_type, $playlist_id, $position)
    {
        // remove the old current
        $sql = "UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?";
        Dba::write($sql, array($this->user));
        // set the new one
        $sql = "UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `id` = ? AND `user` = ? LIMIT 1";
        Dba::write($sql, array($position, $object_type, $playlist_id, $this->user));
    } // set_current_object

    /**
     * get_count
     * This returns a count of the total number of tracks that are in this playlist
     * @return int
     */
    public function get_count()
    {
        $sql        = "SELECT COUNT(`user`) AS `count` FROM `user_playlist` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($this->user));
        $results    = Dba::fetch_assoc($db_results);

        return (int)$results['count'];
    } // get_count

    /**
     * clear
     * This clears all the objects out of a user's playlist
     */
    public function clear()
    {
        $sql = "DELETE FROM `user_playlist` WHERE `user` = ?";
        Dba::write($sql, array($this->user));
    } // clear

    /**
     * add_item
     * This adds a new song to the playlist
     */
    public function add_item($data)
    {
        $sql = "INSERT INTO `user_playlist` (`user`, `object_type`, `object_id`, `track`) VALUES (?, ?, ?, ?)";
        Dba::write($sql, array($this->user, $data['object_type'], $data['object_id'], $data['track']));
    } // add_item

    /**
     * set_items
     * This function resets the User_Playlist while optionally setting the update client and time for that user
     * @param array $playlist
     * @param string $current_type
     * @param int $current_id
     * @param int $current_time
     * @param int $time
     * @param string $client
     */
    public function set_items($playlist, $current_type, $current_id, $current_time, $time = null, $client = null)
    {
        if (!empty($playlist)) {
            // clear the old list
            $this->clear();
            // set the new items
            $index = 1;
            foreach ($playlist as $row) {
                $row['track'] = $index;
                $this->add_item($row);
                $index++;
            }
            $this->set_current_object($current_type, $current_id, $current_time);

            // subsonic cares about queue dates so set them (and set them together)
            if ($time && $client) {
                User::set_user_data($this->user, 'playqueue_time', $time);
                User::set_user_data($this->user, 'playqueue_client', $client);
            }
        }
    } // set_items
}
