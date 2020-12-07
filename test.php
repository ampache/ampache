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

$prefix = dirname(__FILE__);
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init-tiny.php';

$configfile = "$prefix/config/ampache.cfg.php";
switch ($_REQUEST['action']) {
    case 'config':
        // Check to see if the config file is working now, if so fall
        // through to the default, else show the appropriate template
        if (!count(parse_ini_file($configfile))) {
            require_once $prefix . '/templates/show_test_config.inc.php';
            break;
        }
    default:
        // Load config from file
        $results = array();
        if (!file_exists($configfile)) {
            $link = $path . '/install.php';
            header("Location: " . $link);
        } else {
            // Make sure the config file is set up and parsable
            $results = @parse_ini_file($configfile);

            if (empty($results)) {
                $link = $path . '/test.php?action=config';
            }
        }
        /* Temp Fixes */
        $results = Preference::fix_preferences($results);

        AmpConfig::set_by_array($results, true);
        unset($results);

        // Try to load localization from cookie
        $session_name = AmpConfig::get('session_name');
        if (filter_has_var(INPUT_COOKIE, $session_name . '_lang')) {
            AmpConfig::set('lang', $_COOKIE[$session_name . '_lang']);
        }
        if (!class_exists('Gettext\Translations')) {
            require_once $prefix . '/templates/test_error_page.inc.php';
            throw new Exception('load_gettext()');
        } else {
            load_gettext();
            // Load template
            require_once $prefix . '/templates/show_test.inc.php';
        }
        break;
} // end switch on action
