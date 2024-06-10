<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;
use PDOStatement;

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
    public string $client;

    /**
     * Constructor
     * This takes a user_id as an optional argument and gathers the
     * information.  If no user_id is passed or the requested one isn't
     * found, return false.
     * @param int|null $user_id
     * @param string|null $client
     */
    public function __construct($user_id = 0, $client = null)
    {
        if (!$user_id) {
            return;
        }
        $client = $client ?? $this->get_latest();
        if (empty($client)) {
            return;
        }
        $this->user   = (int)$user_id;
        $this->client = substr($client, 0, 254);
    }

    /**
     * get_current_object
     * This returns the next object in the user_playlist.
     * @return array
     */
    public function get_current_object(): array
    {
        $items = array();
        // Select the current object for this user
        $sql        = "SELECT `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user` = ? AND `current_track` = 1 LIMIT 1";
        $db_results = Dba::read($sql, array($this->user));

        while ($results = Dba::fetch_assoc($db_results)) {
            $items = array(
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track_id' => $results['object_id'],
                'track' => $results['track'],
                'current_track' => $results['current_track'],
                'current_time' => $results['current_time']
            );
        }

        return $items;
    }

    /**
     * set_current_object
     * set the active object in the user_playlist.
     * @param string $object_type
     * @param int $object_id
     * @param int $position
     */
    public function set_current_object($object_type, $object_id, $position): void
    {
        // remove the old current
        $sql = "UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?";
        Dba::write($sql, array($this->user));
        // set the new one
        $sql = "UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `object_id` = ? AND `user` = ? LIMIT 1";
        Dba::write($sql, array($position, $object_type, $object_id, $this->user));
    }

    /**
     * set_current_id
     * set the active object using the row id in user_playlist.
     * @param string $object_type
     * @param int $track
     * @param int $position
     */
    public function set_current_id($object_type, $track, $position): void
    {
        // remove the old current
        $sql = "UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?";
        Dba::write($sql, array($this->user));
        // set the new one
        $sql = "UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `track` = ? AND `user` = ? LIMIT 1";
        Dba::write($sql, array($position, $object_type, $track, $this->user));
    }

    /**
     * get_count
     * This returns a count of the total number of tracks that are in this playlist
     */
    public function get_count(): int
    {
        $sql        = "SELECT MAX(`track`) AS `count` FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ?";
        $db_results = Dba::read($sql, array($this->user, $this->client));
        $results    = Dba::fetch_assoc($db_results);

        return (int)$results['count'];
    }

    /**
     * get_time
     * This returns a count of the total number of tracks that are in this playlist
     */
    public function get_time(): int
    {
        $sql        = "SELECT DISTINCT(`playqueue_time`) AS `time` FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ?";
        $db_results = Dba::read($sql, array($this->user, $this->client));
        $results    = Dba::fetch_assoc($db_results);
        if (empty($results)) {
            return time();
        }

        return (int)$results['time'];
    }

    /**
     * get_latest
     * get the most recent playqueue for the user
     */
    public function get_latest(): string
    {
        $sql        = "SELECT MAX(`playqueue_time`) AS `time`, `playqueue_client`, `user` FROM `user_playlist` WHERE `user` = ? GROUP BY `playqueue_client`, `user`";
        $db_results = Dba::read($sql, array($this->user));
        $results    = Dba::fetch_assoc($db_results);

        return $results['playqueue_client'] ?? '';
    }

    /**
     * clear
     * This clears all the objects out of a user's playlist for that client
     */
    public function clear(): void
    {
        $sql = "DELETE FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ?";
        Dba::write($sql, array($this->user, $this->client));
    }

    /**
     * add_items
     * Add an array of songs to the playlist
     * @return PDOStatement|false
     */
    public function add_items($data, $time)
    {
        $sql    = 'INSERT INTO `user_playlist` (`playqueue_time`, `playqueue_client`, `user`, `object_type`, `object_id`, `track`) VALUES ';
        $values = array();
        foreach ($data as $row) {
            $sql .= '(?, ?, ?, ?, ?, ?),';
            $values[] = $time;
            $values[] = $this->client;
            $values[] = $this->user;
            $values[] = $row['object_type'];
            $values[] = $row['object_id'];
            $values[] = $row['track'];
        }
        // remove last comma
        $sql = substr($sql, 0, -1) . ';';

        return Dba::write($sql, $values);
    }

    /**
     * set_items
     * This function resets the User_Playlist while optionally setting the update client and time for that user
     * @param array $playlist
     * @param string $current_type
     * @param int $current_id
     * @param int $current_time
     * @param int $time
     */
    public function set_items($playlist, $current_type, $current_id, $current_time, $time): void
    {
        if (!empty($playlist)) {
            // clear the old list
            $this->clear();
            // set the new items
            $this->add_items($playlist, $time);
            $this->set_current_object($current_type, $current_id, $current_time);

            // subsonic cares about queue dates so set them (and set them together)
            User::set_user_data($this->user, 'playqueue_time', $time);
            User::set_user_data($this->user, 'playqueue_client', $this->client);
        }
    }

    /**
     * get_items
     * Returns an array of all object_ids currently in this User_Playlist.
     * @return array
     */
    public function get_items(): array
    {
        $items = array();
        // Select all objects from this user
        $sql        = "SELECT `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ? ORDER BY `track`";
        $db_results = Dba::read($sql, array($this->user, $this->client));

        while ($results = Dba::fetch_assoc($db_results)) {
            $items[] = array(
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track_id' => $results['object_id'],
                'track' => $results['track'],
                'current_track' => $results['current_track'],
                'current_time' => $results['current_time']
            );
        }

        return $items;
    }
}
