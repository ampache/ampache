<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * Democratic Class
 *
 * This class handles democratic play, which is a fancy
 * name for voting based playback.
 *
 */
class Democratic extends Tmp_Playlist
{
    public $name;
    public $cooldown;
    public $level;
    public $user;
    public $primary;
    public $base_playlist;

    public $f_cooldown;
    public $f_primary;
    public $f_level;

    // Build local, buy local
    public $tmp_playlist;
    public $object_ids = array();
    public $vote_ids   = array();
    public $user_votes = array();

    /**
     * constructor
     * We need a constructor for this class. It does it's own thing now
     */
    public function __construct($id='')
    {
        if (!$id) {
            return false;
        }

        $info = $this->get_info($id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // constructor

    /**
     * build_vote_cache
     * This builds a vote cache of the objects we've got in the playlist
     */
    public static function build_vote_cache($ids)
    {
        if (!is_array($ids) || !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = 'SELECT `object_id`, COUNT(`user`) AS `count` ' .
            'FROM `user_vote` ' .
            "WHERE `object_id` IN $idlist GROUP BY `object_id`";

        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('democratic_vote', $row['object_id'], $row['count']);
        }

        return true;
    } // build_vote_cache

    /**
     * is_enabled
     * This function just returns true / false if the current democratic
     * playlist is currently enabled / configured
     */
    public function is_enabled()
    {
        if ($this->tmp_playlist) {
            return true;
        }

        return false;
    } // is_enabled

    /**
     * set_parent
     * This returns the Tmp_Playlist for this democratic play instance
     */
    public function set_parent()
    {
        $demo_id = Dba::escape($this->id);

        $sql        = "SELECT * FROM `tmp_playlist` WHERE `session`='$demo_id'";
        $db_results = Dba::read($sql);

        $row = Dba::fetch_assoc($db_results);

        $this->tmp_playlist = $row['id'];
    } // set_parent

    /**
     * set_user_preferences
     * This sets up a (or all) user(s) to use democratic play. This sets
     * their play method and playlist method (clear on send) If no user is
     * passed it does it for everyone and also locks down the ability to
     * change to admins only
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function set_user_preferences($user = null)
    {
        //FIXME: Code in single user stuff

        $preference_id = Preference::id_from_name('play_type');
        Preference::update_level($preference_id, '75');
        Preference::update_all($preference_id, 'democratic');

        $allow_demo = Preference::id_from_name('allow_democratic_playback');
        Preference::update_all($allow_demo, '1');

        $play_method = Preference::id_from_name('playlist_method');
        Preference::update_all($play_method, 'clear');

        return true;
    } // set_user_preferences

    /**
     * format
     * This makes the variables all purrty so that they can be displayed
     */
    public function format($details = true)
    {
        $this->f_cooldown    = $this->cooldown . ' ' . T_('minutes');
        $this->f_primary     = $this->primary ? T_('Primary') : '';

        switch ($this->level) {
            case '5':
                $this->f_level = T_('Guest');
            break;
            case '25':
                $this->f_level = T_('User');
            break;
            case '50':
                $this->f_level = T_('Content Manager');
            break;
            case '75':
                $this->f_level = T_('Catalog Manager');
            break;
            case '100':
                $this->f_level = T_('Admin');
            break;
        }
    } // format

    /**
     * get_playlists
     * This returns all of the current valid 'Democratic' Playlists
     * that have been created.
     */
    public static function get_playlists()
    {
        $sql        = "SELECT `id` FROM `democratic` ORDER BY `name`";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_playlists

    /**
     * get_current_playlist
     * This returns the curren users current playlist, or if specified
     * this current playlist of the user
     */
    public static function get_current_playlist()
    {
        $democratic_id = AmpConfig::get('democratic_id');

        if (!$democratic_id) {
            $level = Dba::escape($GLOBALS['user']->access);
            $sql   = "SELECT `id` FROM `democratic` WHERE `level` <= '$level' " .
                " ORDER BY `level` DESC,`primary` DESC";
            $db_results    = Dba::read($sql);
            $row           = Dba::fetch_assoc($db_results);
            $democratic_id = $row['id'];
        }

        $object = new Democratic($democratic_id);

        return $object;
    } // get_current_playlist

    /**
     * get_items
     * This returns a sorted array of all object_ids in this Tmp_Playlist.
     * The array is multidimensional; the inner array needs to contain the
     * keys 'id', 'object_type' and 'object_id'.
     *
     * Sorting is highest to lowest vote count, then by oldest to newest
     * vote activity.
     */
    public function get_items($limit = null)
    {
        // Remove 'unconnected' users votes
        if (AmpConfig::get('demo_clear_sessions')) {
            $sql = 'DELETE FROM `user_vote` WHERE `user_vote`.`sid` NOT IN (SELECT `session`.`id` FROM `session`)';
            Dba::write($sql);
        }

        $sql = 'SELECT `tmp_playlist_data`.`object_type`, ' .
            '`tmp_playlist_data`.`object_id`, ' .
            '`tmp_playlist_data`.`id` ' .
            'FROM `tmp_playlist_data` INNER JOIN `user_vote` ' .
            'ON `user_vote`.`object_id` = `tmp_playlist_data`.`id` ' .
            "WHERE `tmp_playlist_data`.`tmp_playlist` = '" .
            Dba::escape($this->tmp_playlist) . "' " .
            'GROUP BY 1, 2 ' .
            'ORDER BY COUNT(*) DESC, MAX(`user_vote`.`date`), `tmp_playlist_data`.`id` ';

        if ($limit) {
            $sql .= 'LIMIT ' . intval($limit);
        }

        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['id']) {
                $results[] = $row;
            }
        }

        return $results;
    } // get_items

    /**
     * play_url
     * This returns the special play URL for democratic play, only open to ADMINs
     */
    public function play_url()
    {
        $link = Stream::get_base_url() . 'uid=' . scrub_out($GLOBALS['user']->id) . '&demo_id=' . scrub_out($this->id);

        return Stream_URL::format($link);
    } // play_url

    /**
     * get_next_object
     * This returns the next object in the tmp_playlist.
     * Most of the time this will just be the top entry, but if there is a
     * base_playlist and no items in the playlist then it returns a random
     * entry from the base_playlist
     */
    public function get_next_object($offset = 0)
    {
        // FIXME: Shouldn't this return object_type?

        $offset = intval($offset);

        $items = $this->get_items($offset + 1);

        if (count($items) > $offset) {
            return $items[$offset]['object_id'];
        }


        // If nothing was found and this is a voting playlist then get
        // from base_playlist
        if ($this->base_playlist) {
            $base_playlist = new Playlist($this->base_playlist);
            $data          = $base_playlist->get_random_items(1);

            return $data[0]['object_id'];
        } else {
            $sql        = "SELECT `id` FROM `song` WHERE `enabled`='1' ORDER BY RAND() LIMIT 1";
            $db_results = Dba::read($sql);
            $results    = Dba::fetch_assoc($db_results);

            return $results['id'];
        }
    } // get_next_object

    /**
     * get_uid_from_object_id
     * This takes an object_id and an object type and returns the ID for the row
     */
    public function get_uid_from_object_id($object_id, $object_type = 'song')
    {
        $object_id      = Dba::escape($object_id);
        $object_type    = Dba::escape($object_type);
        $tmp_id         = Dba::escape($this->tmp_playlist);

        $sql = 'SELECT `id` FROM `tmp_playlist_data` ' .
            "WHERE `object_type`='$object_type' AND " .
            "`tmp_playlist`='$tmp_id' AND `object_id`='$object_id'";
        $db_results = Dba::read($sql);

        $row = Dba::fetch_assoc($db_results);

        return $row['id'];
    } // get_uid_from_object_id

    /**
     * get_cool_songs
     * This returns all of the song_ids for songs that have happened within
     * the last 'cooldown' for this user.
     */
    public function get_cool_songs()
    {
        // Convert cooldown time to a timestamp in the past
        $cool_time = time() - ($this->cooldown * 60);

        $song_ids = Stats::get_object_history($GLOBALS['user']->id, $cool_time);

        return $song_ids;
    } // get_cool_songs

    /**
     * vote
     * This function is called by users to vote on a system wide playlist
     * This adds the specified objects to the tmp_playlist and adds a 'vote'
     * by this user, naturally it checks to make sure that the user hasn't
     * already voted on any of these objects
     */
    public function add_vote($items)
    {
        /* Iterate through the objects if no vote, add to playlist and vote */
        foreach ($items as $element) {
            $type      = array_shift($element);
            $object_id = array_shift($element);
            if (!$this->has_vote($object_id, $type)) {
                $this->_add_vote($object_id, $type);
            }
        } // end foreach
    } // vote

    /**
     * has_vote
     * This checks to see if the current user has already voted on this object
     */
    public function has_vote($object_id, $type = 'song')
    {
        $params = array($type, $object_id, $this->tmp_playlist);

        /* Query vote table */
        $sql = 'SELECT `tmp_playlist_data`.`object_id` ' .
            'FROM `user_vote` INNER JOIN `tmp_playlist_data` ' .
            'ON `tmp_playlist_data`.`id`=`user_vote`.`object_id` ' .
            "WHERE `tmp_playlist_data`.`object_type` = ? " .
            "AND `tmp_playlist_data`.`object_id` = ? " .
            "AND `tmp_playlist_data`.`tmp_playlist` = ? ";
        if ($GLOBALS['user']->id > 0) {
            $sql .= "AND `user_vote`.`user` = ? ";
            $params[] = $GLOBALS['user']->id;
        } else {
            $sql .= "AND `user_vote`.`sid` = ? ";
            $params[] = session_id();
        }
        $db_results = Dba::read($sql, $params);

        /* If we find  row, they've voted!! */
        if (Dba::num_rows($db_results)) {
            return true;
        }

        return false;
    } // has_vote

    /**
     * _add_vote
     * This takes a object id and user and actually inserts the row
     */
    private function _add_vote($object_id, $object_type = 'song')
    {
        if (!$this->tmp_playlist) {
            return false;
        }

        $media = new $object_type($object_id);
        $track = isset($media->track) ? intval($media->track) : null;

        /* If it's on the playlist just vote */
        $sql = "SELECT `id` FROM `tmp_playlist_data` " .
            "WHERE `tmp_playlist_data`.`object_id` = ? AND `tmp_playlist_data`.`tmp_playlist` = ?";
        $db_results = Dba::write($sql, array($object_id, $this->tmp_playlist));

        /* If it's not there, add it and pull ID */
        if (!$results = Dba::fetch_assoc($db_results)) {
            $sql = "INSERT INTO `tmp_playlist_data` (`tmp_playlist`,`object_id`,`object_type`,`track`) " .
                "VALUES (?, ?, ?, ?)";
            Dba::write($sql, array($this->tmp_playlist, $object_id, $object_type, $track));
            $results['id'] = Dba::insert_id();
        }

        /* Vote! */
        $time = time();
        $sql  = "INSERT INTO user_vote (`user`,`object_id`,`date`,`sid`) " .
            "VALUES (?, ?, ?, ?)";
        Dba::write($sql, array($GLOBALS['user']->id, $results['id'], $time, session_id()));

        return true;
    }

    /**
     * remove_vote
     * This is called to remove a vote by a user for an object, it uses the object_id
     * As that's what we'll have most the time, no need to check if they've got an existing
     * vote for this, just remove anything that is there
     */
    public function remove_vote($row_id)
    {
        $sql    = "DELETE FROM `user_vote` WHERE `object_id` = ? ";
        $params = array($row_id);
        if ($GLOBALS['user']->id > 0) {
            $sql .= "AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        } else {
            $sql .= "AND `user_vote`.`sid` = ? ";
            $params[] = session_id();
        }
        Dba::write($sql, $params);

        /* Clean up anything that has no votes */
        self::prune_tracks();

        return true;
    } // remove_vote

    /**
     * delete_votes
     * This removes the votes for the specified object on the current playlist
     */
    public function delete_votes($row_id)
    {
        $row_id        = Dba::escape($row_id);

        $sql = "DELETE FROM `user_vote` WHERE `object_id`='$row_id'";
        Dba::write($sql);

        $sql = "DELETE FROM `tmp_playlist_data` WHERE `id`='$row_id'";
        Dba::write($sql);

        return true;
    } // delete_votes

    /**
     * delete_from_oid
     * This takes an OID and type and removes the object from the democratic playlist
     */
    public function delete_from_oid($oid, $object_type)
    {
        $row_id = $this->get_uid_from_object_id($oid, $object_type);
        if ($row_id) {
            debug_event('Democratic', 'Removing Votes for ' . $oid . ' of type ' . $object_type, '5');
            $this->delete_votes($row_id);
        } else {
            debug_event('Democratic', 'Unable to find Votes for ' . $oid . ' of type ' . $object_type, '3');
        }

        return true;
    } // delete_from_oid

    /**
     * delete
     * This deletes a democratic playlist
     */
    public static function delete($democratic_id)
    {
        $democratic_id = Dba::escape($democratic_id);

        $sql = "DELETE FROM `democratic` WHERE `id`='$democratic_id'";
        Dba::write($sql);

        $sql = "DELETE FROM `tmp_playlist` WHERE `session`='$democratic_id'";
        Dba::write($sql);

        self::prune_tracks();

        return true;
    } // delete

    /**
     * update
     * This updates an existing democratic playlist item. It takes a key'd array just like the create
     */
    public function update(array $data)
    {
        $name     = Dba::escape($data['name']);
        $base     = Dba::escape($data['democratic']);
        $cool     = Dba::escape($data['cooldown']);
        $level    = Dba::escape($data['level']);
        $default  = Dba::escape($data['make_default']);
        $id       = Dba::escape($this->id);

        $sql = "UPDATE `democratic` SET `name` = ?, `base_playlist` = ?,`cooldown` = ?, `primary` = ?, `level` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $base, $cool, $default, $level, $id));

        return true;
    } // update

    /**
     * create
     * This is the democratic play create function it inserts this into the democratic table
     */
    public static function create($data)
    {
        // Clean up the input
        $name     = Dba::escape($data['name']);
        $base     = Dba::escape($data['democratic']);
        $cool     = Dba::escape($data['cooldown']);
        $level    = Dba::escape($data['level']);
        $default  = Dba::escape($data['make_default']);
        $user     = Dba::escape($GLOBALS['user']->id);

        $sql = "INSERT INTO `democratic` (`name`,`base_playlist`,`cooldown`,`level`,`user`,`primary`) " .
            "VALUES ('$name','$base','$cool','$level','$user','$default')";
        $db_results = Dba::write($sql);

        if ($db_results) {
            $insert_id = Dba::insert_id();
            parent::create(array(
                'session_id' => $insert_id,
                'type' => 'vote',
                'object_type' => 'song'
            ));
        }

        return $db_results;
    } // create

    /**
     * prune_tracks
     * This replaces the normal prune tracks and correctly removes the votes
     * as well
     */
    public static function prune_tracks()
    {
        // This deletes data without votes, if it's a voting democratic playlist
        $sql = "DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` " .
            "LEFT JOIN `user_vote` ON `tmp_playlist_data`.`id`=`user_vote`.`object_id` " .
            "LEFT JOIN `tmp_playlist` ON `tmp_playlist`.`id`=`tmp_playlist_data`.`tmp_playlist` " .
            "WHERE `user_vote`.`object_id` IS NULL AND `tmp_playlist`.`type` = 'vote'";
        Dba::write($sql);

        return true;
    } // prune_tracks

    /**
     * clear
     * This is really just a wrapper function, it clears the entire playlist
     * including all votes etc.
     */
    public function clear()
    {
        $tmp_id = Dba::escape($this->tmp_playlist);

        if ($tmp_id) {
            /* Clear all votes then prune */
            $sql = "DELETE FROM `user_vote` USING `user_vote` " .
                "LEFT JOIN `tmp_playlist_data` ON `user_vote`.`object_id` = `tmp_playlist_data`.`id` " .
                "WHERE `tmp_playlist_data`.`tmp_playlist`='$tmp_id'";
            Dba::write($sql);
        }

        // Prune!
        self::prune_tracks();

        // Clean the votes
        self::clear_votes();

        return true;
    } // clear_playlist

    /**
     * clean_votes
     * This removes in left over garbage in the votes table
     */
    public function clear_votes()
    {
        $sql = "DELETE FROM `user_vote` USING `user_vote` " .
            "LEFT JOIN `tmp_playlist_data` ON `user_vote`.`object_id`=`tmp_playlist_data`.`id` " .
            "WHERE `tmp_playlist_data`.`id` IS NULL";
        Dba::write($sql);

        return true;
    } // clear_votes

    /**
     * get_vote
     * This returns the current count for a specific song
     */
    public function get_vote($id)
    {
        if (parent::is_cached('democratic_vote', $id)) {
            return parent::get_from_cache('democratic_vote', $id);
        }

        $sql = 'SELECT COUNT(`user`) AS `count` FROM `user_vote` ' .
            "WHERE `object_id` = ?";
        $db_results = Dba::read($sql, array($id));

        $results = Dba::fetch_assoc($db_results);
        parent::add_to_cache('democratic_vote', $id, $results['count']);

        return $results['count'];
    } // get_vote

    /**
     * get_voters
     * This returns the users that voted for the specified object
     * This is an array of user ids
     */
    public function get_voters($object_id)
    {
        if (parent::is_cached('democratic_voters', $object_id)) {
            return parent::get_from_cache('democratic_voters', $object_id);
        }

        $sql        = "SELECT `user` FROM `user_vote` WHERE `object_id` = ?";
        $db_results = Dba::read($sql, array($object_id));

        $voters = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $voters[] = $results['user'];
        }
        parent::add_to_cache('democratic_vote', $object_id, $voters);

        return $voters;
    } // get_voters
} // Democratic class
