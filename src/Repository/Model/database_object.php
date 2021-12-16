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
use Redis;

/**
 * This is a general object that is extended by all of the basic
 * database based objects in ampache. It attempts to do some standard
 * caching for all of the objects to cut down on the database calls
 */
abstract class database_object
{
    protected const DB_TABLENAME = null;

    private static ?Redis $object_cache = null;

    // Statistics for debugging
    public static $cache_hit       = 0;
    private static ?bool $_enabled = null;

    /**
     * get_info
     * retrieves the info from the database and puts it in the cache
     * @param integer $object_id
     * @param string $table_name
     * @return array
     */
    public function get_info($object_id, $table_name = '')
    {
        $table     = $this->getTableName($table_name);
        $object_id = (int)$object_id;

        // Make sure we've got a real id
        if ($object_id < 1) {
            return array();
        }

        if (self::is_cached($table, $object_id)) {
            return self::get_from_cache($table, $object_id);
        }

        $params     = array($object_id);
        $sql        = "SELECT * FROM `$table` WHERE `id`= ?";
        $db_results = Dba::read($sql, $params);

        if (!$db_results) {
            return array();
        }

        $row = Dba::fetch_assoc($db_results);

        self::add_to_cache($table, $object_id, $row);

        return $row;
    } // get_info

    /**
     * getTableName
     * @param $table_name
     * @return string
     */
    private function getTableName($table_name): string
    {
        if (!$table_name) {
            $table_name = static::DB_TABLENAME;

            if ($table_name === null) {
                $table_name = Dba::escape(strtolower(get_class($this)));
            }
        }

        return Dba::escape($table_name);
    }

    /**
     * Setup the redis client to connect to the redis instance
     *
     * @return bool If the connection could be made, true is returned. false is returned for any other reason
     */
    private static function init_redis(): bool
    {
        if (self::$object_cache and self::$object_cache->isConnected()){
            return true;
        }
        if (self::$_enabled === null) {
            self::$_enabled = AmpConfig::get('memory_cache');
        }
        if (!self::$_enabled) {
            return false;
        }
        self::$object_cache = new Redis();

        self::$object_cache->connect(
            AmpConfig::get('redis_host'),
            AmpConfig::get('redis_port'),
            1,
        );

        return true;
    }

    /**
     * clear_cache
     */
    public static function clear_cache()
    {
        if (!self::init_redis()){
            return;
        }

        // Remove keys with the class prefix
        $keys = self::$object_cache->keys(self::DB_TABLENAME . ":*");
        self::$object_cache->del(...$keys);
    }

    /**
     * get_cache_key
     * Builds a key that can be used to find a hash set in redis
     * @param string $object_id
     * @return string
     */
    public static function get_cache_key($object_id): string
    {
        $output = Dba::escape(strtolower(get_called_class())) . ":" . $object_id;
        return $output;
    }

    /**
     * is_cached
     * this checks the cache to see if the specified object is there
     * @param string $index
     * @param string $object_id
     * @return boolean
     */
    public static function is_cached($index, $object_id): bool
    {
        if (!self::init_redis()) {
            return false;
        }
        return self::$object_cache->hExists(self::get_cache_key($object_id), $index);
    } // is_cached

    /**
     * get_from_cache
     * This attempts to retrieve the specified object from the cache we've got here
     * @param string $index
     * @param integer|string $object_id
     * @return array
     */
    public static function get_from_cache($index, $object_id)
    {
        if (!self::init_redis()) {
            return array();
        }

        $cache_result = self::$object_cache->hGet(self::get_cache_key($object_id), $index);
        if ($cache_result) {
            return json_decode($cache_result, true);
        }

        return array();
    } // get_from_cache

    /**
     * add_to_cache
     * This adds the specified object to the specified index in the cache
     * @param string $index
     * @param integer|string $object_id
     * @param array $data
     * @return boolean
     */
    public static function add_to_cache($index, $object_id, $data)
    {
        if (!self::init_redis()) {
            return false;
        }

        $value = false;
        if (!empty($data)) {
            $value = json_encode($data);
        }

        self::$object_cache->hSet(self::DB_TABLENAME . ":" . $object_id, $index, $value);

        return true;
    } // add_to_cache

    /**
     * remove_from_cache
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     * @param string $index
     * @param integer $object_id
     */
    public static function remove_from_cache($index, $object_id)
    {
        if (!self::init_redis()) {
            return;
        }
        self::$object_cache->hDel(self::get_cache_key($object_id), $index);
    } // remove_from_cache
}
