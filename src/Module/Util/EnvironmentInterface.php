<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Util;

interface EnvironmentInterface
{
    public function check(): bool;

    /**
     * check for required php version
     */
    public function check_php_version(): bool;

    /**
     * check for required function exists
     */
    public function check_php_hash(): bool;

    /**
     * check for required function exists
     */
    public function check_php_hash_algo(): bool;

    /**
     * check for required function exists
     */
    public function check_php_json(): bool;

    /**
     * check for required function exists
     */
    public function check_php_curl(): bool;

    /**
     * check for required module
     */
    public function check_php_intl(): bool;

    /**
     * check for required function exists
     */
    public function check_php_session(): bool;

    /**
     * check for required function exists
     */
    public function check_php_pdo(): bool;

    /**
     * check for required function exists
     */
    public function check_php_pdo_mysql(): bool;

    /**
     * check for required function exists
     */
    public function check_mbstring_func_overload(): bool;

    /**
     * This checks to make sure that the php memory limit is withing the
     * recommended range, this doesn't take into account the size of your
     * catalog.
     */
    public function check_php_memory(): bool;

    /**
     * This checks to make sure that the php time limit is set to some
     * semi-sane limit, IE greater then 60 seconds
     */
    public function check_php_timelimit(): bool;

    /**
     * This checks to see if we can manually override the memory limit
     */
    public function check_override_memory(): bool;

    /**
     * This checks to see if we can manually override the max execution time
     */
    public function check_override_exec_time(): bool;

    /**
     * This checks to see if max upload size is not too small
     */
    public function check_upload_size(): bool;

    public function check_php_int_size(): bool;

    public function check_php_zlib(): bool;

    public function check_php_simplexml(): bool;

    public function check_php_gd(): bool;

    public function check_dependencies_folder(): bool;

    public function isDevJS(string $entry): bool;

    public function isCli(): bool;

    public function isSsl(): bool;

    /**
     * Checks if the application is used by a mobile client (like smartphones)
     */
    public function isMobile(): bool;

    public function getHttpPort(): int;

    public function setUp(): void;
}
