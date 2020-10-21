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

// Minimal init for use in install
$a_root = realpath(__DIR__ . "/../");

// Do a check for PHP because nothing will work without the correct version
if (version_compare(phpversion(), '7.1.0', '<')) {
    echo "PHP version " . phpversion() . " < 7.1";
    throw new RuntimeException("PHP version " . phpversion() . " < 7.1");
}

error_reporting(E_ERROR); // Only show fatal errors in production

$load_time_begin = microtime(true);
$configfile      = $a_root . '/config/ampache.cfg.php';

// We still allow scripts to run (it could be the purpose of the maintenance)
if (!defined('CLI')) {
    if (file_exists($a_root . '/.maintenance')) {
        require_once $a_root . '/.maintenance';
    }
}

require_once $a_root . '/lib/general.lib.php';
require_once $a_root . '/lib/class/ampconfig.class.php';
require_once $a_root . '/lib/class/core.class.php';

// Define some base level config options
AmpConfig::set('prefix', $a_root);

// Register autoloaders
spl_autoload_register(array('Core', 'autoload'), true, true);
$composer_autoload = $a_root . '/lib/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

// Check to see if this is http or https
if ((filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO') && Core::get_server('HTTP_X_FORWARDED_PROTO') == 'https')
    || (filter_has_var(INPUT_SERVER, 'HTTPS') && Core::get_server('HTTPS') == 'on')) {
    $http_type = 'https://';
} else {
    $http_type = 'http://';
}

if (filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_PORT')) {
    $http_port = $_SERVER['HTTP_X_FORWARDED_PORT'];
} else {
    if (filter_has_var(INPUT_SERVER, 'SERVER_PORT')) {
        $http_port = $_SERVER['SERVER_PORT'];
    }
}
if (!isset($http_port) || empty($http_port)) {
    $http_port = 80;
}

// Define that we've loaded the INIT file
define('INIT_LOADED', 1);

// Core includes we can't do with the autoloader
require_once $a_root . '/lib/preferences.php';
require_once $a_root . '/lib/debug.lib.php';
require_once $a_root . '/lib/log.lib.php';
require_once $a_root . '/lib/ui.lib.php';
require_once $a_root . '/lib/i18n.php';
require_once $a_root . '/lib/batch.lib.php';
require_once $a_root . '/lib/themes.php';
require_once $a_root . '/lib/class/localplay_controller.abstract.php';
require_once $a_root . '/lib/class/database_object.abstract.php';
require_once $a_root . '/lib/class/media.interface.php';
require_once $a_root . '/lib/class/playable_item.interface.php';
require_once $a_root . '/lib/class/library_item.interface.php';
require_once $a_root . '/lib/class/playlist_object.abstract.php';
require_once $a_root . '/modules/horde/Browser.php';

/* Set up the flip class */
UI::flip_class(array('odd', 'even'));

// Merge GET then POST into REQUEST effectively stripping COOKIE without
// depending on a PHP setting change for the effect
$_REQUEST = array_merge($_GET, $_POST);
