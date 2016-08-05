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
     */
    public function __construct($id, $type)
    {
        $this->id   = intval($id);
        $this->type = $type;

        return true;
    } // Constructor

    /**
      * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     */
    public static function build_cache($type, $ids, $user_id = null)
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        if ($user_id === null) {
            $user_id = $GLOBALS['user']->id;
        }

        $userflags = array();

        $idlist = '(' . implode(',', $ids) . ')';
        $sql    = "SELECT `object_id` FROM `user_flag` " .
            "WHERE `user` = ? AND `object_id` IN $idlist " .
            "AND `object_type` = ?";
        $db_results = Dba::read($sql, array($user_id, $type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $userflags[$row['object_id']] = true;
        }

        foreach ($ids as $id) {
            if (!isset($userflags[$id])) {
                $userflag = 0;
            } else {
                $userflag = intval($userflags[$id]);
            }
            parent::add_to_cache('userflag_' . $type . '_user' . $user_id, $id, $userflag);
        }

        return true;
    } // build_cache

    /**
     * gc
     *
     * Remove userflag for items that no longer exist.
     */
    public static function gc($object_type = null, $object_id = null)
    {
        $types = array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season', 'podcast', 'podcast_episode');

        if ($object_type != null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_flag` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event('userflag', 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `user_flag` USING `user_flag` LEFT JOIN `$type` ON `$type`.`id` = `user_flag`.`object_id` WHERE `object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }

    public function get_flag($user_id = null)
    {
        if ($user_id === null) {
            $user_id = $GLOBALS['user']->id;
        }

        $key = 'userflag_' . $this->type . '_user' . $user_id;
        if (parent::is_cached($key, $this->id)) {
            return parent::get_from_cache($key, $this->id);
        }

        $sql = "SELECT `id` FROM `user_flag` WHERE `user` = ? " .
            "AND `object_id` = ? AND `object_type` = ?";
        $db_results = Dba::read($sql, array($user_id, $this->id, $this->type));

        $flagged = false;
        if (Dba::fetch_assoc($db_results)) {
            $flagged = true;
        }

        parent::add_to_cache($key, $this->id, $flagged);
        return $flagged;
    }

    /**
     * set_flag
     * This function sets the user flag for the current object.
     * If no userid is passed in, we use the currently logged in user.
     */
    public function set_flag($flagged, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = $GLOBALS['user']->id;
        }
        $user_id = intval($user_id);

        debug_event('Userflag', "Setting userflag for $this->type $this->id to $flagged", 5);

        if (!$flagged) {
            $sql = "DELETE FROM `user_flag` WHERE " .
                "`object_id` = ? AND " .
                "`object_type` = ? AND " .
                "`user` = ?";
            $params = array($this->id, $this->type, $user_id);
        } else {
            $sql = "REPLACE INTO `user_flag` " .
            "(`object_id`, `object_type`, `user`, `date`) " .
            "VALUES (?, ?, ?, ?)";
            $params = array($this->id, $this->type, $user_id, time());
            
            Useractivity::post_activity($user_id, 'userflag', $this->type, $this->id);
        }
        Dba::write($sql, $params);

        parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, $flagged);

        // Forward flag to last.fm and Libre.fm (song only)
        if ($this->type == 'song') {
            $user = new User($user_id);
            $song = new Song($this->id);
            if ($song) {
                $song->format();
                foreach (Plugin::get_plugins('save_mediaplay') as $plugin_name) {
                    try {
                        $plugin = new Plugin($plugin_name);
                        if ($plugin->load($user)) {
                            $plugin->_plugin->set_flag($song, $flagged);
                        }
                    } catch (Exception $e) {
                        debug_event('user.class.php', 'Stats plugin error: ' . $e->getMessage(), '1');
                    }
                }
            }
        }

        return true;
    } // set_flag

    /**
     * get_latest_sql
     * Get the latest sql
     */
    public static function get_latest_sql($type, $user_id=null)
    {
        if ($user_id === null) {
            $user_id = $GLOBALS['user']->id;
        }
        $user_id = intval($user_id);

        $sql = "SELECT `user_flag`.`object_id` as `id`, `user_flag`.`object_type` as `type`, `user_flag`.`user` as `user` FROM `user_flag`";
        if ($user_id <= 0) {
            // Get latest only from user rights >= content manager
            $sql .= " LEFT JOIN `user` ON `user`.`id` = `user_flag`.`user`" .
                    " WHERE `user`.`access` >= 50";
        }
        if ($type !== null) {
            if ($user_id <= 0) {
                $sql .= " AND";
            } else {
                $sql .= " WHERE";
            }
            $type = Stats::validate_type($type);
            $sql .= " `user_flag`.`object_type` = '" . $type . "'";
            if ($user_id > 0) {
                $sql .= " AND `user_flag`.`user` = '" . $user_id . "'";
            }
            if (AmpConfig::get('catalog_disable')) {
                $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
            }
        }
        $sql .= " ORDER BY `user_flag`.`date` DESC ";
        return $sql;
    }
    /**
     * get_latest
     * Get the latest user flagged objects
     */
    public static function get_latest($type=null, $user_id=null, $count='', $offset='')
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

        /* Select Top objects counting by # of rows */
        $sql = self::get_latest_sql($type, $user_id);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            if ($type === null) {
                $results[] = $row;
            } else {
                $results[] = $row['id'];
            }
        }

        return $results;
    } // get_latest

    /**
     * show
     * This takes an id and a type and displays the flag state
     * enabled.
     */
    public static function show($object_id, $type)
    {
        // If user flags aren't enabled don't do anything
        if (!AmpConfig::get('userflags')) {
            return false;
        }

        $userflag = new Userflag($object_id, $type);
        require AmpConfig::get('prefix') . UI::find_template('show_object_userflag.inc.php');
    } // show
} //end rating class
