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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;

/**
 * TempPlaylist Class
 *
 * This class handles the temporary playlists in Ampache. It handles the
 * tmp_playlist and tmp_playlist_data tables, and sneaks out at night to
 * visit user_vote from time to time.
 */
class Tmp_Playlist extends database_object
{
    protected const DB_TABLENAME = 'tmp_playlist';

    // Variables from the Database
    public $id;
    public $session;
    public $type;
    public $object_type;
    public $base_playlist;

    // Generated Elements
    public $items = array();

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the
     * information.  If no playlist_id is passed or the requested one isn't
     * found, return false.
     * @param string $playlist_id
     */
    public function __construct($playlist_id = '')
    {
        if (!$playlist_id) {
            return false;
        }

        $this->id = (int)($playlist_id);
        $info     = $this->has_info();

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // __construct

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * has_info
     * This is an internal (private) function that gathers the information
     * for this object from the playlist_id that was passed in.
     * @return array
     */
    private function has_info()
    {
        $sql        = "SELECT * FROM `tmp_playlist` WHERE `id`='" . Dba::escape($this->id) . "'";
        $db_results = Dba::read($sql);

        return Dba::fetch_assoc($db_results);
    } // has_info

    /**
     * get_from_session
     * This returns a playlist object based on the session that is passed to
     * us.  This is used by the load_playlist on user for the most part.
     * @param string $session_id
     * @return Tmp_Playlist
     */
    public static function get_from_session($session_id)
    {
        $session_id = Dba::escape($session_id);

        $sql        = "SELECT `id` FROM `tmp_playlist` WHERE `session`='$session_id'";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_row($db_results);

        if (!array_key_exists('0', $results)) {
            $results['0'] = Tmp_Playlist::create(array(
                'session_id' => $session_id,
                'type' => 'user',
                'object_type' => 'song'
            ));
        }

        return new Tmp_Playlist($results['0']);
    } // get_from_session

    /**
     * get_from_username
     * This returns a tmp playlist object based on a userid passed
     * this is used for the user profiles page
     * @param string $username
     * @return mixed
     */
    public static function get_from_username($username)
    {
        $sql        = "SELECT `tmp_playlist`.`id` FROM `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` WHERE `session`.`username` = ? ORDER BY `session`.`expire` DESC";
        $db_results = Dba::read($sql, array($username));
        $results    = Dba::fetch_assoc($db_results);

        // user doesn't have an active play queue
        if (!$results) {
            return false;
        }

        return $results['id'];
    } // get_from_username

    /**
     * get_items
     * Returns an array of all object_ids currently in this Tmp_Playlist.
     * @return array
     */
    public function get_items()
    {
        $session_name = AmpConfig::get('session_name');
        if (isset($_COOKIE[$session_name])) {
            // Select all objects for this session
            $session    = $_COOKIE[$session_name];
            $sql        = "SELECT `tmp_playlist_data`.`object_type`, `tmp_playlist_data`.`id`, `tmp_playlist_data`.`object_id` FROM `tmp_playlist_data` LEFT JOIN `tmp_playlist` ON `tmp_playlist`.`id` = `tmp_playlist_data`.`tmp_playlist` WHERE `tmp_playlist`.`session` = ?;";
            $db_results = Dba::read($sql, array($session));
        } else {
            // try to guess
            $sql        = "SELECT `object_type`, `id`, `object_id` FROM `tmp_playlist_data` WHERE `tmp_playlist` = ? ORDER BY `id`";
            $db_results = Dba::read($sql, array($this->id));
        }

        /* Define the array */
        $items = array();

        $count = 1;
        while ($results = Dba::fetch_assoc($db_results)) {
            $items[] = array(
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track_id' => $results['id'],
                'track' => $count++,
            );
        }

        return $items;
    } // get_items

    /**
     * get_next_object
     * This returns the next object in the tmp_playlist.
     */
    public function get_next_object()
    {
        $id = Dba::escape($this->id);

        $sql        = "SELECT `object_id` FROM `tmp_playlist_data` WHERE `tmp_playlist`='$id' ORDER BY `id` LIMIT 1";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);

        return $results['object_id'];
    } // get_next_object

    /**
     * count_items
     * This returns a count of the total number of tracks that are in this
     * tmp playlist
     */
    public function count_items()
    {
        $id = Dba::escape($this->id);

        $sql        = "SELECT COUNT(`id`) FROM `tmp_playlist_data` WHERE `tmp_playlist`='$id'";
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
        $sql = "DELETE FROM `tmp_playlist_data` WHERE `tmp_playlist` = ?";
        Dba::write($sql, array($this->id));

        return true;
    } // clear

    /**
     * create
     * This function initializes a new Tmp_Playlist. It is associated with
     * the current session rather than a user, as you could have the same
     * user logged in from multiple locations.
     * @param array $data
     * @return string|null
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `tmp_playlist` (`session`, `type`, `object_type`) VALUES (?, ?, ?)";
        Dba::write($sql, array($data['session_id'], $data['type'], $data['object_type']));

        $tmp_id = Dba::insert_id();

        /* Clean any other playlists associated with this session */
        self::session_clean($data['session_id'], $tmp_id);

        return $tmp_id;
    } // create

    /**
     * session_clean
     * This deletes any other tmp_playlists associated with this
     * session
     * @param $sessid
     * @param string|null $plist_id
     * @return boolean
     */
    public static function session_clean($sessid, $plist_id)
    {
        $sql = "DELETE FROM `tmp_playlist` WHERE `session`= ? AND `id` != ?";
        Dba::write($sql, array($sessid, $plist_id));

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
        // Ampache\Module\System\Dba::write("DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `song` ON `tmp_playlist_data`.`object_id` = `song`.`id` WHERE `song`.`id` IS NULL;");
    }

    /**
     * prune_playlists
     * This deletes any playlists that don't have an associated session
     */
    public static function prune_playlists()
    {
        /* Just delete if no matching session row */
        $sql = "DELETE FROM `tmp_playlist` USING `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` WHERE `session`.`id` IS NULL AND `tmp_playlist`.`type` != 'vote'";
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
        $sql = "DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `tmp_playlist` ON `tmp_playlist_data`.`tmp_playlist`=`tmp_playlist`.`id` WHERE `tmp_playlist`.`id` IS NULL";
        Dba::write($sql);
    } // prune_tracks

    /**
     * add_object
     * This adds the object of $this->object_type to this tmp playlist
     * it takes an optional type, default is song
     * @param integer $object_id
     * @param string $object_type
     * @return boolean
     */
    public function add_object($object_id, $object_type)
    {
        $sql = "INSERT INTO `tmp_playlist_data` (`object_id`, `tmp_playlist`, `object_type`) VALUES (?, ?, ?)";
        Dba::write($sql, array($object_id, $this->id, $object_type));

        return true;
    } // add_object

    /**
     * @param $medias
     */
    public function add_medias($medias)
    {
        foreach ($medias as $media) {
            $this->add_object($media['object_id'], $media['object_type']);
        }
    }

    /**
     * delete_track
     * This deletes a track from the tmpplaylist
     * @param $object_id
     * @return boolean
     */
    public function delete_track($object_id)
    {
        /* delete the track its self */
        $sql = "DELETE FROM `tmp_playlist_data` WHERE `id` = ?";
        Dba::write($sql, array($object_id));

        return true;
    } // delete_track
}
