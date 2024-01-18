<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\System;

use Exception;

interface InstallationHelperInterface
{
    /**
     * this function checks to see if we actually
     * still need to install ampache. This function is
     * very important, we don't want to reinstall over top of an existing install
     * @param $configfile
     */
    public function install_check_status($configfile): bool;

    /**
     * install_check_server_apache
     */
    public function install_check_server_apache(): bool;

    /**
     * @param string $file
     * @param $web_path
     * @param bool $fix
     * @return bool|string
     */
    public function install_check_rewrite_rules($file, $web_path, $fix = false);

    /**
     * @param string $file
     * @param $web_path
     * @param bool $download
     */
    public function install_rewrite_rules($file, $web_path, $download): bool;

    /**
     * Inserts the database using the values from Config.
     * @param string $db_user
     * @param string $db_pass
     * @param bool $create_db
     * @param bool $overwrite
     * @param bool $create_tables
     * @param string $charset
     * @param string $collation
     */
    public function install_insert_db(
        $db_user = null,
        $db_pass = null,
        $create_db = true,
        $overwrite = false,
        $create_tables = true,
        $charset = 'utf8mb4',
        $collation = 'utf8mb4_unicode_ci'
    ): bool;

    /**
     * Attempts to write out the config file or offer it as a download.
     * @param bool $download
     * @return bool
     * @throws Exception
     */
    public function install_create_config($download = false): bool;

    /**
     * this creates your initial account and sets up the preferences for the -1 user and you
     * @param string $username
     * @param string $password
     * @param string $password2
     */
    public function install_create_account($username, $password, $password2): bool;

    /**
     * get transcode modes available on this machine.
     * @return array
     */
    public function install_get_transcode_modes(): array;

    /**
     * @param $mode
     */
    public function install_config_transcode_mode($mode);

    /**
     * @param $case
     */
    public function install_config_use_case($case);

    /**
     * @param array $backends
     */
    public function install_config_backends(array $backends);

    /**
     * Write new configuration into the current configuration file by keeping old values.
     * @param string $current_file_path
     */
    public function write_config(string $current_file_path): bool;

    /**
     * This takes an array of results and re-generates the config file
     * this is used by the installer and by the admin/system page
     * @param array $current
     * @return string
     * @throws Exception
     */
    public function generate_config(array $current): string;
}
