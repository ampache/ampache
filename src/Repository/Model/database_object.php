<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;

/**
 * This is a general object that is extended by all of the basic
 * database based objects in ampache. It attempts to do some standard
 * caching for all of the objects to cut down on the database calls
 */
abstract class database_object
{
    protected const DB_TABLENAME = null;

    public static int $cache_hit   = 0; // Statistics for debugging
    private static ?bool $_enabled = null;

    /** @var array<string, array<int|string, array<string, mixed>|bool>> */
    private static array $object_cache = [];

    /**
     * add_to_cache
     * This adds the specified object to the specified index in the cache
     */
    public static function add_to_cache(string $index, int|string $object_id, array $data): bool
    {
        if (!self::isCacheEnabled()) {
            return false;
        }

        $value = false;
        if (!empty($data)) {
            $value = $data;
        }

        self::$object_cache[$index][$object_id] = $value;

        return true;
    }

    /**
     * clear_cache
     */
    public static function clear_cache(): void
    {
        self::$object_cache = [];
    }

    /**
     * get_from_cache
     * This attempts to retrieve the specified object from the cache we've got here
     */
    public static function get_from_cache(string $index, int|string $object_id): array
    {
        // Check if the object is set
        if (isset(self::$object_cache[$index][$object_id]) && is_array(self::$object_cache[$index][$object_id])) {
            ++self::$cache_hit;

            return self::$object_cache[$index][$object_id];
        }

        return [];
    }

    /**
     * is_cached
     * this checks the cache to see if the specified object is there
     */
    public static function is_cached(string $index, int|string $object_id): bool
    {
        // Make sure we've got some parents here before we dive below
        if (!array_key_exists($index, self::$object_cache)) {
            return false;
        }

        return (
            $object_id
            && array_key_exists((string) $object_id, self::$object_cache[$index])
            && !empty(self::$object_cache[$index][$object_id])
        );
    }

    /**
     * Whether the in-request object cache is on
     *
     * Public because every `build_cache()` has to ask before doing its batch query: with the cache off
     * the rows it fetches are thrown away and the per-object queries run anyway, so the prefetch is a
     * net loss rather than a no-op.
     */
    public static function isCacheEnabled(): bool
    {
        // lazy loaded to avoid some magic auto_init logic
        if (self::$_enabled === null) {
            self::$_enabled = (bool) AmpConfig::get('memory_cache', true);
        }

        return self::$_enabled;
    }

    /**
     * remove_from_cache
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     */
    public static function remove_from_cache(string $index, ?int $object_id = null): void
    {
        if (isset(self::$object_cache[$index])) {
            if (is_null($object_id)) {
                // unset the whole index
                unset(self::$object_cache[$index]);
            } elseif (isset(self::$object_cache[$index][$object_id])) {
                // unset a single value
                unset(self::$object_cache[$index][$object_id]);
            }
        }
    }

    /**
     * get_info
     * retrieves the info from the database and puts it in the cache
     */
    protected function get_info(int $object_id, ?string $table_name = null): array
    {
        $table = $this->getTableName($table_name);

        // Make sure we've got a real id and table
        if ($table === null || $object_id < 1) {
            return [];
        }

        if (self::is_cached($table, $object_id)) {
            $info = self::get_from_cache($table, $object_id);
            if (!empty($info)) {
                return $info;
            }
        }

        $params     = [$object_id];
        $sql        = sprintf('SELECT * FROM `%s` WHERE `id` = ?', $table);
        $db_results = Dba::read($sql, $params);

        if (!$db_results) {
            return [];
        }

        $row = Dba::fetch_assoc($db_results);

        self::add_to_cache($table, $object_id, $row);

        return $row;
    }

    /**
     * getTableName
     */
    private function getTableName(?string $table_name = null): ?string
    {
        if (!$table_name) {
            $table_name = static::DB_TABLENAME;

            if ($table_name === null) {
                $table_name = Dba::escape(strtolower(static::class));
            }
        }

        return Dba::escape($table_name);
    }
}
