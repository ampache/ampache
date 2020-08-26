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
// Do a check for PHP5.4 because nothing will work without it
if (version_compare(phpversion(), '7.1.0', '<')) {
    echo T_("Ampache requires PHP version >= 7.1");
    throw new RuntimeException(T_("Ampache requires PHP version >= 7.1"));
}

//error_reporting(E_ERROR); // Only show fatal errors in production

$load_time_begin = microtime(true);
$configfile      = __DIR__ . '/../config/ampache.cfg.php';

// We still allow scripts to run (it could be the purpose of the maintenance)
if (!defined('CLI')) {
    if (file_exists(__DIR__ . '/../.maintenance')) {
        require_once  __DIR__ . '/../.maintenance';
    }
}

require_once __DIR__ . '/general.lib.php';
require_once __DIR__ . '/class/ampconfig.class.php';
require_once __DIR__ . '/class/core.class.php';

// Register autoloaders
spl_autoload_register(array('Core', 'autoload'), true, true);
$composer_autoload = __DIR__ . '/../vendor/autoload.php';

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
require_once __DIR__ . '/preferences.php';
require_once __DIR__ . '/debug.lib.php';
require_once __DIR__ . '/log.lib.php';
require_once __DIR__ . '/ui.lib.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/batch.lib.php';
require_once __DIR__ . '/themes.php';
require_once __DIR__ . '/class/localplay_controller.abstract.php';
require_once __DIR__ . '/class/media.interface.php';
require_once __DIR__ . '/class/playable_item.interface.php';
require_once __DIR__ . '/class/library_item.interface.php';
require_once __DIR__ . '/class/playlist_object.abstract.php';

/* Set up the flip class */
UI::flip_class(array('odd', 'even'));

// Merge GET then POST into REQUEST effectively stripping COOKIE without
// depending on a PHP setting change for the effect
$_REQUEST = array_merge($_GET, $_POST);
