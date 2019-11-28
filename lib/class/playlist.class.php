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
 * Playlist Class
 * This class handles playlists in ampache. it references the playlist* tables
 *
 */
class Playlist extends playlist_object
{
    /* Variables from the database */
    public $genre;
    public $date;
    public $last_update;

    public $link;
    public $f_link;
    public $f_date;
    public $f_last_update;

    /* Generated Elements */
    public $items = array();

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the information
     * if not playlist_id is passed returns false (or if it isn't found
     */
    public function __construct($object_id)
    {
        $info = $this->get_info($object_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // Playlist

    /**
     * garbage_collection
     *
     * Clean dead items out of playlists
     */
    public static function garbage_collection()
    {
        foreach (array('song', 'video') as $object_type) {
            Dba::write("DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `" . $object_type . "` ON `" . $object_type . "`.`id` = `playlist_data`.`object_id` WHERE `" . $object_type . "`.`file` IS NULL AND `playlist_data`.`object_type`='" . $object_type . "'");
        }
        Dba::write("DELETE FROM `playlist` USING `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` WHERE `playlist_data`.`object_id` IS NULL");
    }

    /**
     * build_cache
     * This is what builds the cache from the objects
     */
    public static function build_cache($ids)
    {
        if (!count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql        = "SELECT * FROM `playlist` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('playlist', $row['id'], $row);
        }
    } // build_cache

    /**
     * get_playlists
     * Returns a list of playlists accessible by the user.
     * @param boolean $incl_public
     * @param int $user_id
     * @return array
     */
    public static function get_playlists($incl_public = true, $user_id = -1, $playlist_name = '')
    {
        if (!$user_id) {
            $user_id = Core::get_global('user')->id;
        }

        $sql    = 'SELECT `id` FROM `playlist`';
        $params = array();

        if ($user_id > -1 && $incl_public) {
            $sql .= " WHERE (`user` = ? OR `type` = 'public')";
            $params[] = $user_id;
        }
        if ($user_id > -1 && !$incl_public) {
            $sql .= ' WHERE `user` = ?';
            $params[] = $user_id;
        }
        if (!$user_id > -1 && $incl_public) {
            if (count($params) === 0) {
                $sql .= " WHERE `type` = 'public'";
            }
        }

        if ($playlist_name !== '') {
            if (count($params) > 0 || $incl_public) {
                $sql .= " AND `name` = '" . $playlist_name . "'";
            } else {
                $sql .= " WHERE `name` = '" . $playlist_name . "'";
            }
        }
        $sql .= ' ORDER BY `name`';
        //debug_event('playlist.class', 'get_playlists query: ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_playlists

    /**
     * get_smartlists
     * Returns a list of playlists accessible by the user.
     * @return array
     */
    public static function get_smartlists($incl_public = true, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }

        // Search for smartplaylists
        $sql    = "SELECT CONCAT('smart_', `id`) AS id FROM `search`";
        $params = array();
        if ($user_id > -1) {
            $sql .= ' WHERE `user` = ?';
            $params[] = $user_id;
        }

        if ($incl_public) {
            if (count($params) > 0) {
                $sql .= ' OR ';
            } else {
                $sql .= ' WHERE ';
            }
            $sql .= "`type` = 'public'";
        }

        $sql .= ' ORDER BY `name`';

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_smartlists

    /**
     * format
     * This takes the current playlist object and gussies it up a little
     * bit so it is presentable to the users
     */
    public function format($details = true)
    {
        parent::format($details);
        $this->link   = AmpConfig::get('web_path') . '/playlist.php?action=show_playlist&playlist_id=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '">' . $this->f_name . '</a>';

        $this->f_date        = $this->date ? date('d/m/Y h:i', $this->date) : T_('Unknown');
        $this->f_last_update = $this->last_update ? date('d/m/Y h:i', $this->last_update) : T_('Unknown');
    } // format

    /**
     * get_track
     * Returns the single item on the playlist and all of it's information, restrict
     * it to this Playlist
     */
    public function get_track($track_id)
    {
        $sql        = "SELECT * FROM `playlist_data` WHERE `id` = ? AND `playlist` = ?";
        $db_results = Dba::read($sql, array($track_id, $this->id));

        $row = Dba::fetch_assoc($db_results);

        return $row;
    } // get_track

    /**
     * get_items
     * This returns an array of playlist medias that are in this playlist.
     * Because the same media can be on the same playlist twice they are
     * keyed by the uid from playlist_data
     */
    public function get_items()
    {
        $results = array();

        $sql        = "SELECT `id`, `object_id`, `object_type`, `track` FROM `playlist_data` WHERE `playlist`= ? ORDER BY `track`";
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'object_type' => $row['object_type'],
                'object_id' => $row['object_id'],
                'track' => $row['track'],
                'track_id' => $row['id']
            );
        } // end while

        return $results;
    } // get_items

    /**
     * get_random_items
     * This is the same as before but we randomize the buggers!
     */
    public function get_random_items($limit = '')
    {
        $results = array();

        $limit_sql = $limit ? 'LIMIT ' . (string) ($limit) : '';

        $sql = "SELECT `object_id`, `object_type` FROM `playlist_data` " .
            "WHERE `playlist` = ? ORDER BY RAND() $limit_sql";
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'object_type' => $row['object_type'],
                'object_id' => $row['object_id']
            );
        } // end while

        return $results;
    } // get_random_items

    /**
     * get_songs
     * This is called by the batch script, because we can't pass in Dynamic objects they pulled once and then their
     * target song.id is pushed into the array
     */
    public function get_songs()
    {
        $results = array();

        $sql         = "SELECT * FROM `playlist_data` WHERE `playlist` = ? AND `object_type` = 'song' ORDER BY `track`";
        $db_results  = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        } // end while

        return $results;
    } // get_songs

    /**
     * get_media_count
     * This simply returns a int of how many media elements exist in this playlist
     * For now let's consider a dyn_media a single entry
     * @return string|null
     */
    public function get_media_count($type = '')
    {
        $params     = array($this->id);
        $sql        = "SELECT COUNT(`id`) FROM `playlist_data` WHERE `playlist` = ?";
        if (!empty($type)) {
            $sql .= " AND `object_type` = ?";
            $params[] = $type;
        }
        $db_results = Dba::read($sql, $params);

        $results = Dba::fetch_row($db_results);

        return $results['0'];
    } // get_media_count

    /**
    * get_total_duration
    * Get the total duration of all songs.
    * @return string|null
    */
    public function get_total_duration()
    {
        $songs  = self::get_songs();
        $idlist = '(' . implode(',', $songs) . ')';

        $sql        = "SELECT SUM(`time`) FROM `song` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        $results = Dba::fetch_row($db_results);

        return $results['0'];
    } // get_total_duration

    /**
     * get_users
     * This returns the specified users playlists as an array of
     * playlist ids
     */
    public static function get_users($user_id)
    {
        $results = array();

        $sql        = "SELECT `id` FROM `playlist` WHERE `user` = ? ORDER BY `name`";
        $db_results = Dba::read($sql, array($user_id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_users

    /**
      * update
     * This function takes a key'd array of data and runs updates
     */
    public function update(array $data)
    {
        if (isset($data['name']) && $data['name'] != $this->name) {
            $this->update_name($data['name']);
        }
        if (isset($data['pl_type']) && $data['pl_type'] != $this->type) {
            $this->update_type($data['pl_type']);
        }

        return $this->id;
    } // update

    /**
     * update_type
     * This updates the playlist type, it calls the generic update_item function
     */
    private function update_type($new_type)
    {
        if ($this->_update_item('type', $new_type, 50)) {
            $this->type = $new_type;
        }
    } // update_type

    /**
     * update_name
     * This updates the playlist name, it calls the generic update_item function
     */
    private function update_name($new_name)
    {
        if ($this->_update_item('name', $new_name, 50)) {
            $this->name = $new_name;
        }
    } // update_name

    /**
     * update_last_update
     * This updates the playlist last update, it calls the generic update_item function
     */
    private function update_last_update()
    {
        $last_update = time();
        if ($this->_update_item('last_update', $last_update, 50)) {
            $this->last_update = $last_update;
        }
    } // update_last_update

    /**
     * _update_item
     * This is the generic update function, it does the escaping and error checking
     * @param string $field
     * @param integer $level
     */
    private function _update_item($field, $value, $level)
    {
        if (Core::get_global('user')->id != $this->user && !Access::check('interface', $level)) {
            return false;
        }

        $sql        = "UPDATE `playlist` SET `$field` = ? WHERE `id` = ?";
        $db_results = Dba::write($sql, array($value, $this->id));

        return $db_results;
    } // update_item

    /**
     * update_track_number
     * This takes a playlist_data.id and a track (int) and updates the track value
     * @param integer $index
     */
    public function update_track_number($track_id, $index)
    {
        $sql = "UPDATE `playlist_data` SET `track` = ? WHERE `id` = ?";
        Dba::write($sql, array($index, $track_id));
    } // update_track_number

    /**
     * Regenerate track numbers to fill gaps.
     */
    public function regenerate_track_numbers()
    {
        $items = $this->get_items();
        $index = 1;
        foreach ($items as $item) {
            $this->update_track_number($item['track_id'], $index);
            $index++;
        }

        $this->update_last_update();
    }

    /**
     * add_songs
     * This takes an array of song_ids and then adds it to the playlist
     */
    public function add_songs($song_ids = array(), $ordered = false)
    {
        $medias = array();
        foreach ($song_ids as $song_id) {
            $medias[] = array(
                'object_type' => 'song',
                'object_id' => $song_id,
            );
        }
        $this->add_medias($medias, $ordered);
    } // add_songs

    public function add_medias($medias, $ordered = false)
    {
        /* We need to pull the current 'end' track and then use that to
         * append, rather then integrate take end track # and add it to
         * $song->track add one to make sure it really is 'next'
         */
        $sql        = "SELECT `track` FROM `playlist_data` WHERE `playlist` = ? ORDER BY `track` DESC LIMIT 1";
        $db_results = Dba::read($sql, array($this->id));
        $track_data = Dba::fetch_assoc($db_results);
        $base_track = $track_data['track'] ?: 0;
        debug_event('playlist.class', 'Adding Media; Track number: ' . $base_track, 5);

        $count = 0;
        foreach ($medias as $data) {
            $media = new $data['object_type']($data['object_id']);

            // Based on the ordered prop we use track + base or just $count++
            if (!$ordered && $data['object_type'] == 'song') {
                $track    = $media->track + $base_track;
            } else {
                $count++;
                $track = $base_track + $count;
            }

            /* Don't insert dead media */
            if ($media->id) {
                $sql = "INSERT INTO `playlist_data` (`playlist`, `object_id`, `object_type`, `track`) " .
                    " VALUES (?, ?, ?, ?)";
                Dba::write($sql, array($this->id, $data['object_id'], $data['object_type'], $track));
            } // if valid id
        } // end foreach medias

        $this->update_last_update();
    }

    /**
     * create
     * This function creates an empty playlist, gives it a name and type
     * @param string $type
     * @param integer $user_id
     */
    public static function create($name, $type, $user_id = null, $date = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        if (!is_int($date)) {
            $date = time();
        }

        $sql = "INSERT INTO `playlist` (`name`, `user`, `type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($name, $user_id, $type, $date, $date));

        $insert_id = Dba::insert_id();

        return $insert_id;
    } // create

    /**
     * set_items
     * This calls the get_items function and sets it to $this->items which is an array in this object
     */
    public function set_items()
    {
        $this->items = $this->get_items();
    } // set_items

    /**
     * delete_track
     * this deletes a single track, you specify the playlist_data.id here
     */
    public function delete_track($object_id)
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? LIMIT 1";
        Dba::write($sql, array($this->id, $object_id));

        $this->update_last_update();

        return true;
    } // delete_track

    /**
    * delete_track_number
    * this deletes a single track by it's track #, you specify the playlist_data.track here
    */
    public function delete_track_number($track)
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? LIMIT 1";
        Dba::write($sql, array($this->id, $track));

        $this->update_last_update();

        return true;
    } // delete_track_number

    /**
    * has_item
    * look for the track id or the object id in a playlist
    * @param integer $object
    * @param integer $track
    * @return boolean
    */
    public function has_item($object = null, $track = null)
    {
        $results = array();
        if ($object) {
            $sql        = "SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? LIMIT 1";
            $db_results = Dba::read($sql, array($this->id, $object));
            $results    = Dba::fetch_assoc($db_results);
        } elseif ($track) {
            $sql        = "SELECT `track` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? LIMIT 1";
            $db_results = Dba::read($sql, array($this->id, $track));
            $results    = Dba::fetch_assoc($db_results);
        }
        if (isset($results['object_id']) || isset($results['track'])) {
            debug_event('playlist.class', 'has_item results: ' . $results['object_id'], 5);

            return true;
        }

        return false;
    } // delete_track_number

    /**
     * delete
     * This deletes the current playlist and all associated data
     */
    public function delete()
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `playlist` WHERE `id` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `object_count` WHERE `object_type`='playlist' AND `object_id` = ?";
        Dba::write($sql, array($this->id));

        return true;
    } // delete

    /**
    * Sort the tracks and save the new position
    */
    public function sort_tracks()
    {
        /* First get all of the songs in order of their tracks */
        $sql = "SELECT A.`id`
                FROM `playlist_data` AS A
           LEFT JOIN `song` AS B ON A.object_id = B.id
           LEFT JOIN `artist` AS C ON B.artist = C.id
           LEFT JOIN `album` AS D ON B.album = D.id
               WHERE A.`playlist` = ?
            ORDER BY C.`name` ASC,
                     B.`title` ASC,
                     D.`year` ASC,
                     D.`name` ASC,
                     B.`track` ASC";
        $db_results = Dba::query($sql, array($this->id));

        $count   = 1;
        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $new_data               = array();
            $new_data['id']         = $row['id'];
            $new_data['track']      = $count;
            $results[]              = $new_data;
            $count++;
        } // end while results

        foreach ($results as $data) {
            $sql = "UPDATE `playlist_data` SET `track` = ? WHERE `id` = ?";
            Dba::write($sql, array($data['track'], $data['id']));
        } // foreach re-ordered results

        $this->update_last_update();

        return true;
    } // sort_tracks
} // class Playlist
