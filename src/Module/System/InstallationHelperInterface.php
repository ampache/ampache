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

namespace Ampache\Module\System;

use Exception;

interface InstallationHelperInterface
{
    /**
     * this function checks to see if we actually still need to install Ampache.
     * This function is very important, we don't want to reinstall over top of an existing install
     */
    public function install_check_status(string $configfile): bool;

    /**
     * install_check_server_apache
     */
    public function install_check_server_apache(): bool;

    public function install_check_rewrite_rules(string $file, string $web_path, bool $fix = false): bool|string;

    public function install_rewrite_rules(string $file, string $web_path, bool $download): bool;

    /**
     * Inserts the database using the values from Config.
     */
    public function install_insert_db(
        ?string $db_user = null,
        ?string $db_pass = null,
        bool $create_db = true,
        bool $overwrite = false,
        bool $create_tables = true,
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_unicode_ci'
    ): bool;

    /**
     * Attempts to write out the config file or offer it as a download.
     * @throws Exception
     */
    public function install_create_config(bool $download = false): bool;

    /**
     * this creates your initial account and sets up the preferences for the -1 user and you
     */
    public function install_create_account(string $username, string $password, string $password2): bool;

    /**
     * get transcode modes available on this machine.
     * @return string[]
     */
    public function install_get_transcode_modes(): array;

    public function install_config_transcode_mode(string $mode): void;

    public function install_config_use_case(string $case): void;

    /**
     * @param string[] $backends
     */
    public function install_config_backends(array $backends): void;

    /**
     * Write new configuration into the current configuration file by keeping old values.
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
