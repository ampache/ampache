<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
        // Load config from file
        $results = array();
        if (!file_exists($configfile)) {
            $link = $path . '/install.php';
        } else {
            // Make sure the config file is set up and parsable
            $results = @parse_ini_file($configfile);

            if (!count($results)) {
                $link = $path . '/test.php?action=config';
            }
        }
        /* Temp Fixes */
        $results = Preference::fix_preferences($results);

        AmpConfig::set_by_array($results, true);
        unset($results);

        // Try to load localization from cookie
        $session_name = AmpConfig::get('session_name');
        if (isset($_COOKIE[$session_name . '_lang'])) {
            AmpConfig::set('lang', $_COOKIE[$session_name . '_lang']);
        }

        // Load gettext mojo
        load_gettext();

        // Load template
        require_once $prefix . '/templates/show_test.inc.php';
    break;
} // end switch on action
