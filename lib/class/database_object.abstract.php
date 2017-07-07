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
 * database_object
 *
 * This is a general object that is extended by all of the basic
 * database based objects in ampache. It attempts to do some standard
 * caching for all of the objects to cut down on the database calls
 *
 */
abstract class database_object
{
    private static $object_cache = array();

    // Statistics for debugging
    public static $cache_hit = 0;
    private static $_enabled = false;

    /**
     * get_info
     * retrieves the info from the database and puts it in the cache
     */
    public function get_info($id, $table_name='')
    {
        $table_name = $table_name ? Dba::escape($table_name) : Dba::escape(strtolower(get_class($this)));

        // Make sure we've got a real id
        if (!is_numeric($id)) {
            return array();
        }

        if (self::is_cached($table_name, $id)) {
            return self::get_from_cache($table_name, $id);
        }

        $sql        = "SELECT * FROM `$table_name` WHERE `id`='$id'";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return array();
        }

        $row = Dba::fetch_assoc($db_results);

        self::add_to_cache($table_name, $id, $row);

        return $row;
    } // get_info

    /**
     * clear_cache
     */
    public static function clear_cache()
    {
        self::$object_cache = array();
    }

    /**
     * is_cached
     * this checks the cache to see if the specified object is there
     */
    public static function is_cached($index, $id)
    {
        // Make sure we've got some parents here before we dive below
        if (!isset(self::$object_cache[$index])) {
            return false;
        }

        return isset(self::$object_cache[$index][$id]);
    } // is_cached

    /**
     * get_from_cache
     * This attempts to retrieve the specified object from the cache we've got here
     */
    public static function get_from_cache($index, $id)
    {
        // Check if the object is set
        if (isset(self::$object_cache[$index]) && isset(self::$object_cache[$index][$id])) {
            self::$cache_hit++;

            return self::$object_cache[$index][$id];
        }

        return false;
    } // get_from_cache

    /**
     * add_to_cache
     * This adds the specified object to the specified index in the cache
     */
    public static function add_to_cache($index, $id, $data)
    {
        if (!self::$_enabled) {
            return false;
        }

        $value                           = is_null($data) ? false : $data;
        self::$object_cache[$index][$id] = $value;
    } // add_to_cache

    /**
     * remove_from_cache
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     */
    public static function remove_from_cache($index, $id)
    {
        if (isset(self::$object_cache[$index]) && isset(self::$object_cache[$index][$id])) {
            unset(self::$object_cache[$index][$id]);
        }
    } // remove_from_cache

    /**
     * _auto_init
     * Load in the cache settings once so we can avoid function calls
     */
    public static function _auto_init()
    {
        self::$_enabled = AmpConfig::get('memory_cache');
    } // _auto_init
} // end database_object
