<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
        $this->id = intval($id);
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
        if (!is_array($ids) OR !count($ids)) { return false; }

        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }

        $userflags = array();

        $idlist = '(' . implode(',', $ids) . ')';
        $sql = "SELECT `object_id` FROM `user_flag` " .
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
    public static function gc()
    {
        foreach (array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season') as $object_type) {
            Dba::write("DELETE FROM `user_flag` USING `user_flag` LEFT JOIN `$object_type` ON `$object_type`.`id` = `user_flag`.`object_id` WHERE `object_type` = '$object_type' AND `$object_type`.`id` IS NULL");
        }
    }

    public function get_flag($user_id = null)
    {
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }

        $key = 'userflag_' . $this->type . '_user' . $user_id;
        if (parent::is_cached($key, $this->id)) {
            return parent::get_from_cache($key, $this->id);
        }

        $sql = "SELECT `id` FROM `user_flag` WHERE `user` = ? ".
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
        if (is_null($user_id)) {
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
        }
        Dba::write($sql, $params);

        parent::add_to_cache('userflag_' . $this->type . '_user' . $user_id, $this->id, $flagged);

        return true;

    } // set_flag

    /**
     * get_latest_sql
     * Get the latest sql
     */
    public static function get_latest_sql($type, $user_id=null)
    {
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }
        $user_id = intval($user_id);
        $type = Stats::validate_type($type);

        $sql = "SELECT `object_id` as `id` FROM user_flag" .
                " WHERE object_type = '" . $type . "' AND `user` = '" . $user_id . "'";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        $sql .= " ORDER BY `date` DESC ";
        return $sql;
    }
    /**
     * get_latest
     * Get the latest user flagged objects
     */
    public static function get_latest($type, $user_id=null, $count='', $offset='')
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
        $db_results = Dba::read($sql, array($type, $user_id));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
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
        if (!AmpConfig::get('userflags')) { return false; }

        $userflag = new Userflag($object_id, $type);
        require AmpConfig::get('prefix') . '/templates/show_object_userflag.inc.php';

    } // show

} //end rating class
