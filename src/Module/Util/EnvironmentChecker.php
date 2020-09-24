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

declare(strict_types=1);

namespace Ampache\Module\Util;

use PDO;

/**
 * This class checks the current environment if it's suitable to run ampache
 */
final class EnvironmentChecker implements EnvironmentCheckerInterface
{
    public const PHP_VERSION = 7.4;

    public function check(): bool
    {
        return $this->check_php() && $this->check_dependencies_folder() && $this->check_php_intl();
    }

    /**
     * check for required php version
     */
    public function check_php_version(): bool
    {
        return floatval(phpversion()) >= static::PHP_VERSION;
    }

    /**
     * check for required function exists
     */
    public function check_php_hash(): bool
    {
        return function_exists('hash_algos');
    }

    /**
     * check for required function exists
     */
    public function check_php_hash_algo(): bool
    {
        return function_exists('hash_algos') ? in_array('sha256', hash_algos()) : false;
    }

    /**
     * check for required function exists
     */
    public function check_php_json(): bool
    {
        return function_exists('json_encode');
    }

    /**
     * check for required function exists
     */
    public function check_php_curl(): bool
    {
        return function_exists('curl_version');
    }

    /**
     * check for required module
     */
    public function check_php_intl(): bool
    {
        return (extension_loaded('intl'));
    }

    /**
     * check for required function exists
     */
    public function check_php_session(): bool
    {
        return function_exists('session_set_save_handler');
    }

    /**
     * check for required function exists
     */
    public function check_php_pdo(): bool
    {
        return class_exists('PDO');
    }

    /**
     * check for required function exists
     */
    public function check_php_pdo_mysql(): bool
    {
        return class_exists('PDO') ? in_array('mysql', PDO::getAvailableDrivers()) : false;
    }

    /**
     * check for required function exists
     */
    public function check_mbstring_func_overload(): bool
    {
        if (ini_get('mbstring.func_overload') > 0) {
            return false;
        }

        return true;
    }

    /**
     * This checks to make sure that the php memory limit is withing the
     * recommended range, this doesn't take into account the size of your
     * catalog.
     */
    public function check_php_memory(): bool
    {
        $current_memory = ini_get('memory_limit');
        $current_memory = substr($current_memory, 0, strlen((string) $current_memory) - 1);

        if ((int) ($current_memory) < 48) {
            return false;
        }

        return true;
    }

    /**
     * This checks to make sure that the php timelimit is set to some
     * semi-sane limit, IE greater then 60 seconds
     */
    public function check_php_timelimit(): bool
    {
        $current = (int) (ini_get('max_execution_time'));

        return ($current >= 60 || $current == 0);
    }

    /**
     * This checks to see if we can manually override the memory limit
     */
    public function check_override_memory(): bool
    {
        /* Check memory */
        $current_memory = ini_get('memory_limit');
        $current_memory = substr($current_memory, 0, strlen((string) $current_memory) - 1);
        $new_limit      = ($current_memory + 16) . "M";

        /* Bump it by 16 megs (for getid3)*/
        if (!ini_set('memory_limit', $new_limit)) {
            return false;
        }

        // Make sure it actually worked
        $new_memory = ini_get('memory_limit');

        if ($new_limit != $new_memory) {
            return false;
        }

        return true;
    }

    /**
     * This checks to see if we can manually override the max execution time
     */
    public function check_override_exec_time(): bool
    {
        $current = ini_get('max_execution_time');
        set_time_limit((int) $current + 60);

        if ($current == ini_get('max_execution_time')) {
            return false;
        }

        return true;
    }

    /**
     * This checks to see if max upload size is not too small
     */
    public function check_upload_size(): bool
    {
        $upload_max = return_bytes(ini_get('upload_max_filesize'));
        $post_max   = return_bytes(ini_get('post_max_size'));
        $mini       = 20971520; // 20M

        return (($upload_max >= $mini || $upload_max < 1) && ($post_max >= $mini || $post_max < 1));
    }

    public function check_php_int_size(): bool
    {
        return (PHP_INT_SIZE > 4);
    }

    public function check_php_zlib(): bool
    {
        return function_exists('gzcompress');
    }

    public function check_php_simplexml(): bool
    {
        return function_exists('simplexml_load_string');
    }

    public function check_php_gd(): bool
    {
        return (extension_loaded('gd') || extension_loaded('gd2'));
    }

    public function check_dependencies_folder(): bool
    {
        return file_exists(__DIR__ . '/../../../vendor');
    }

    /**
     * check for required modules
     */
    private function check_php(): bool
    {
        return
            $this->check_php_version() &&
            $this->check_php_hash() &&
            $this->check_php_hash_algo() &&
            $this->check_php_pdo() &&
            $this->check_php_pdo_mysql() &&
            $this->check_php_session() &&
            $this->check_php_json();
    }
}
