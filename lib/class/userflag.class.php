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
 * Userflag class
 *
 * This user flag/unflag songs, albums, artists, videos, tvshows, movies ... as favorite.
 *
 */
class Userflag extends database_object
{
    // Public variables
    public $id;        // The ID of the object flagged
    public $type;        // The type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the flag for
     * @param integer $object_id
     * @param string $type
     */
    public function __construct($object_id, $type)
    {
        $this->id   = (int) ($object_id);
        $this->type = $type;

        return true;
    } // Constructor

    /**
     * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     * @param string $type
     * @param array $ids
     * @param integer $user_id
     * @return boolean
     */
    public static function build_cache($type, $ids, $user_id = null)
    {
        if (empty($ids)) {
            return false;
        }
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        $userflags  = array();
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT `object_id`, `date` FROM `user_flag` " .
                      "WHERE `user` = ? AND `object_id` IN $idlist " .
                      "AND `object_type` = ?";
        $db_results = Dba::read($sql, array($user_id, $type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $userflags[$row['object_id']] = $row['date'];
        }

        foreach ($ids as $object_id) {
            if (isset($userflags[$object_id])) {
                parent::add_to_cache('userflag_' . $type . '_user' . $user_id, $object_id, array(1, $userflags[$object_id]));
            } else {
                parent::add_to_cache('userflag_' . $type . '_user' . $user_id, $object_id, array(false));
            }
        }

        return true;
    } // build_cache

    /**
     * garbage_collection
     *
     * Remove userflag for items that no longer exist.
     * @param string $object_type
     * @param integer $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null)
    {
        $types = array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season', 'playlist', 'podcast', 'podcast_episode');

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_flag` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `user_flag` USING `user_flag` LEFT JOIN `$type` ON `$type`.`id` = `user_flag`.`object_id` WHERE `object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }

    /**
     * get_flag
     * @param integer $user_id
     * @param boolean $get_date
     * @return boolean|array
     */
    public function get_flag($user_id = null, $get_date = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }

        $key = 'userflag_' . $this->type . '_user' . $user_id;
        if (parent::is_cached($key, $this->id)) {
            $object = parent::get_from_cache($key, $this->id);
            if (empty($object) || !$object[0]) {
                return false;
            }

            return $object;
        }

        $sql = "SELECT `id`, `date` FROM `user_flag` WHERE `user` = ? " .
            "AND `object_id` = ? AND `object_type` = ?";
        $db_results = Dba::read($sql, array($user_id, $this->id, $this->type));

        $flagged = false;
        if ($row = Dba::fetch_assoc($db_results)) {
            if ($get_date) {
                $flagged = array(1, $row['date']);
            } else {
                $flagged = array(1);
            }
            parent::add_to_cache($key, $this->id, $flagged);
        }

        return $flagged;
    }

    /**
     * set_flag
     * This function sets the user flag for the current object.
     * If no user_id is passed in, we use the currently logged in user.
     * @param boolean $flagged
     * @param integer $user_id
     * @return boolean
     */
    public function set_flag($flagged, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = (int) (Core::get_global('user')->id);
        }
        if ($user_id === 0) {
            return false;
        }
        $results = array();
        if ($this->type == 'album' && AmpConfig::get('album_group')) {
            $sql = "SELECT `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`mbid`, `album`.`year` FROM `album`" .
                    " WHERE `id` = ?";
            $db_results = Dba::read($sql, array($this->id));
            $results    = Dba::fetch_assoc($db_results);
        }
        if (!empty($results)) {
            return self::set_flag_for_group($flagged, $results, $user_id);
        }
        debug_event(self::class, "Setting userflag for $this->type $this->id to $flagged", 4);

        if (!$flagged) {
            $sql = "DELETE FROM `user_flag` WHERE " .
                "`object_id` = ? AND " .
                "`object_type` = ? AND " .
                "`user` = ?";
            $params = array($this->id, $this->type, $user_id);
            parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, array(false));
        } else {
            $date = time();
            $sql  = "REPLACE INTO `user_flag` " .
                "(`object_id`, `object_type`, `user`, `date`) " .
                "VALUES (?, ?, ?, ?)";
            $params = array($this->id, $this->type, $user_id, $date);
            parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, array(1, $date));

            Useractivity::post_activity($user_id, 'userflag', $this->type, $this->id, time());
        }
        Dba::write($sql, $params);

        if ($this->type == 'song') {
            $user = new User($user_id);
            $song = new Song($this->id);
            if ($song->id) {
                $song->format();
                self::save_flag($user, $song, $flagged);
            }
        }

        return true;
    } // set_flag

    /**
     * save_flag
     * Forward flag to last.fm and Libre.fm (song only)
     * @param User $user
     * @param Song $song
     * @param boolean $flagged
     */
    public static function save_flag($user, $song, $flagged)
    {
        foreach (Plugin::get_plugins('set_flag') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($user)) {
                    debug_event(self::class, 'save_flag...' . $plugin->_plugin->name, 5);
                    $plugin->_plugin->set_flag($song, $flagged);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_flag plugin error: ' . $error->getMessage(), 1);
            }
        }
    }

    /**
     * set_flag_for_group
     * This function sets the user flag for an album group.
     * @param boolean $flagged
     * @param $album
     * @param integer $user_id
     * @return boolean
     */
    public static function set_flag_for_group($flagged, $album, $user_id = null)
    {
        $sql = "SELECT `album`.`id` FROM `album`" .
                " WHERE `album`.`name` = '" . Dba::escape($album['name']) . "'";
        if ($album['album_artist']) {
            $sql .= " AND `album`.`album_artist` = " . $album['album_artist'];
        } else {
            $sql .= " AND `album`.`album_artist` IS NULL";
        }
        if ($album['mbid']) {
            $sql .= " AND `album`.`mbid` = '" . $album['mbid'] . "'";
        } else {
            $sql .= " AND `album`.`mbid` IS NULL";
        }
        if ($album['prefix']) {
            $sql .= " AND `album`.`prefix` = '" . $album['prefix'] . "'";
        } else {
            $sql .= " AND `album`.`prefix` IS NULL";
        }
        $results    = array();
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }
        foreach ($results as $album_id) {
            if (!$flagged) {
                $sql = "DELETE FROM `user_flag` WHERE " .
                    "`object_id` = " . $album_id . " AND " .
                    "`object_type` = 'album' AND " .
                    "`user` = " . $user_id;
                Dba::write($sql);
            } else {
                $sql = "REPLACE INTO `user_flag` " .
                "(`object_id`, `object_type`, `user`, `date`) " .
                "VALUES (?, ?, ?, ?)";
                $params = array($album_id, 'album', $user_id, time());

                Useractivity::post_activity($user_id, 'userflag', 'album', $album_id, time());
                Dba::write($sql, $params);
            }

            parent::add_to_cache('userflag_album_user' . $user_id, $album_id, array($flagged));
        }

        return true;
    } // set_flag_for_group

    /**
     * get_latest_sql
     * Get the latest sql
     * @param string $type
     * @param string $user_id
     * @return string
     */
    public static function get_latest_sql($type, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        $user_id = (int) ($user_id);
        $type    = Stats::validate_type($type);
        $sql     = "SELECT DISTINCT(`user_flag`.`object_id`) as `id`, `user_flag`.`object_type` as `type`, " .
                   "MAX(`user_flag`.`user`) as `user` FROM `user_flag`";
        if ($user_id < 1) {
            // Get latest only from user rights >= content manager
            $sql .= " LEFT JOIN `user` ON `user`.`id` = `user_flag`.`user`" .
                    " WHERE `user`.`access` >= 50";
        }
        if ($user_id < 1) {
            $sql .= " AND";
        } else {
            $sql .= " WHERE";
        }
        $sql .= " `user_flag`.`object_type` = '" . $type . "'";
        if ($user_id > 0) {
            $sql .= " AND `user_flag`.`user` = '" . $user_id . "'";
        }
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        $sql .= " GROUP BY `object_id`, `type` ORDER BY `user_flag`.`date` DESC ";

        return $sql;
    }

    /**
     * get_latest
     * Get the latest user flagged objects
     * @param string $type
     * @param string $user_id
     * @param integer $count
     * @param integer $offset
     * @return integer[]
     */
    public static function get_latest($type = null, $user_id = null, $count = 0, $offset = 0)
    {
        if ($count < 1) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        $limit = ($offset < 1) ? $count : $offset . "," . $count;

        /* Select Top objects counting by # of rows */
        $sql = self::get_latest_sql($type, $user_id);
        $sql .= "LIMIT $limit";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($type === null) {
                $results[] = $row;
            } else {
                $results[] = (int) $row['id'];
            }
        }

        return $results;
    } // get_latest

    /**
     * show
     * This takes an id and a type and displays the flag state
     * enabled.
     * @param integer $object_id
     * @param string $type
     * @return boolean
     */
    public static function show($object_id, $type)
    {
        // If user flags aren't enabled don't do anything
        if (!AmpConfig::get('userflags')) {
            return false;
        }

        $userflag = new Userflag($object_id, $type);
        require AmpConfig::get('prefix') . UI::find_template('show_object_userflag.inc.php');

        return true;
    } // show

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE IGNORE `user_flag` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
} // end userflag.class
