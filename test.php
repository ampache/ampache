<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

$prefix = dirname(__FILE__);
require_once $prefix . '/lib/init-tiny.php';

$action = 'default';
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}
switch ($action) {
    case 'config':
        // Check to see if the config file is working now, if so fall
        // through to the default, else show the appropriate template
        $configfile = "$prefix/config/ampache.cfg.php";

        if (!count(parse_ini_file($configfile))) {
            require_once $prefix . '/templates/show_test_config.inc.php';
            break;
        }
    default:
        require_once $prefix . '/templates/show_test.inc.php';
    break;
} // end switch on action

