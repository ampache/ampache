<?php
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

if(php_sapi_name() != 'cli') {
        exit(1);
}

define('NO_SESSION', 1);
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath(__DIR__ . '/../../');
$a_root = realpath(__DIR__ . '/../../');
require_once $a_root . '/lib/init-tiny.php';
require_once $a_root . '/lib/install.lib.php';

$options = getopt(
    'h:d:fp:P:u:U:w:',
    array(
        'database-user:',
        'database-password:',
        'database-host:',
        'database-port:',
        'database-name:',
        'ampache-database-user:',
        'ampache-database-password:',
        'webpath:',
        'force'
    )
);

$force = isset($options['f']) || isset($options['force']);

$db_user = $options['U'] ?: $options['database-user'];
$db_pass = $options['P'] ?: $options['database-password'];
$db_host = $options['h'] ?: $options['database-host'];
$db_port = $options['database-port'];
$db_name = $options['d'] ?: $options['database-name'];
$new_db_user = $options['u'] ?: $options['ampache-database-user'];
$new_db_pass = $options['p'] ?: $options['ampache-database-password'];
$web_path = $options['w'] ?: $options ['webpath'];

// Make sure we have all the required information
if (!$db_user || !$db_pass || !$db_host || !$db_name) {
    install_usage();
}

// Now let's make sure it's not already installed
if (!install_check_status($configfile)) {
    echo "\n", T_('Existing Ampache installation found'), "\n";
    if ($force) {
        echo T_('Force specified, proceeding anyway'), "\n";
    }
    else {
        echo T_('Exiting'), "\n";
        exit(1);
    }
}

AmpConfig::set_by_array(array(
    'web_path' => $web_path,
    'database_name' => $db_name,
    'database_username' => $db_user,
    'database_password' => $db_pass,
    'database_hostname' => $db_host,
    'database_port' => $db_port
), true);

// Install the database
if (!install_insert_db($new_db_user, $new_db_pass, true, $force)) {
    echo T_('Database creation failed'), "\n";
    echo AmpError::get('general'), "\n\n";
    exit(1);
}

AmpConfig::set_by_array(array(
    'database_username' => $new_db_user ?: $db_user,
    'database_password' => $new_db_pass ?: $db_pass
), true);

// Write the config file
/** @noinspection PhpUnhandledExceptionInspection */
if (!install_create_config()) {
    echo T_('Config file creation failed'), "\n";
    echo AmpError::get('general') . "\n\n";
    exit(1);
}

/**
 * install_usage
 * This just prints out the required params for this script
 **/
function install_usage() {
    echo T_("Usage:") . "\n";
    echo "install_db.inc [" . T_('Options') . "]";
    echo "\n\t-U, --database-user\t\t\t";
    echo T_('MySQL Administrative Username');
    echo "\n\t-P, --database-password\t\t\t";
    echo T_('MySQL Administrative Password');
    echo "\n\t-h, --database-host\t\t\t";
    echo T_('MySQL Hostname');
    echo "\n\t--database-port\t\t\t";
    echo T_('MySQL Port');
    echo "\n\t-d, --database-name\t\t\t";
    echo T_("Database Name");
    echo "\n\t-u, --ampache-database-user\t";
    echo T_('MySQL Username');
    echo "\n\t-p, --ampache-database-password\t";
    echo T_('MySQL Password');
    echo "\n\t-w, --webpath\t\t\t\t";
    echo T_('Web path');
    echo "\n\t-f, --force\t\t\t\t";
    echo T_('Force installation'), "\n";

    exit(1);
}
