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
require_once $a_root . '/lib/install.lib.php';

set_error_handler('ampache_error_handler');

// Redirect if installation is already complete.
if (!install_check_status($configfile)) {
    $redirect_url = 'login.php';
    require_once AmpConfig::get('prefix') . UI::find_template('error_page.inc.php');

    return false;
}

define('INSTALL', 1);

$htaccess_play_file    = AmpConfig::get('prefix') . '/play/.htaccess';
$htaccess_rest_file    = AmpConfig::get('prefix') . '/rest/.htaccess';
$htaccess_channel_file = AmpConfig::get('prefix') . '/channel/.htaccess';

// Clean up incoming variables
$web_path   = scrub_in($_REQUEST['web_path']);
$username   = (string) scrub_in($_REQUEST['local_username']);
$password   = $_REQUEST['local_pass'];
$hostname   = scrub_in($_REQUEST['local_host']);
$database   = scrub_in($_REQUEST['local_db']);
$port       = scrub_in($_REQUEST['local_port']);
$skip_admin = isset($_REQUEST['skip_admin']);

AmpConfig::set_by_array(array(
    'web_path' => $web_path,
    'database_name' => $database,
    'database_hostname' => $hostname,
    'database_port' => $port
), true);
if (!$skip_admin) {
    AmpConfig::set_by_array(array(
        'database_username' => $username,
        'database_password' => $password
    ), true);
}

if (isset($_REQUEST['transcode_template'])) {
    $mode = $_REQUEST['transcode_template'];
    install_config_transcode_mode($mode);
}

if (isset($_REQUEST['usecase'])) {
    $case = $_REQUEST['usecase'];
    if (Dba::check_database()) {
        install_config_use_case($case);
    }
}

if (isset($_REQUEST['backends'])) {
    $backends = $_REQUEST['backends'];
    if (Dba::check_database()) {
        install_config_backends($backends);
    }
}

// Charset and gettext setup
$htmllang = $_REQUEST['htmllang'];
$charset  = $_REQUEST['charset'];

if (!$htmllang) {
    if ($_ENV['LANG']) {
        $lang = $_ENV['LANG'];
    } else {
        $lang = 'en_US';
    }
    if (strpos($lang, '.')) {
        $langtmp  = explode('.', $lang);
        $htmllang = $langtmp[0];
        $charset  = $langtmp[1];
    } else {
        $htmllang = $lang;
    }
}
AmpConfig::set('lang', $htmllang, true);
AmpConfig::set('site_charset', $charset ?: 'UTF-8', true);
if (!class_exists('Gettext\Translations')) {
    require_once $prefix . '/templates/test_error_page.inc.php';
    throw new Exception('load_gettext()');
} else {
    load_gettext();
}
header('Content-Type: text/html; charset=' . AmpConfig::get('site_charset'));

// Correct potential \ or / in the dirname
$safe_dirname = get_web_path();

$web_path = $http_type . Core::get_server('HTTP_HOST') . $safe_dirname;

unset($safe_dirname);

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'create_db':
        $new_user = '';
        $new_pass = '';
        if (Core::get_post('db_user') == 'create_db_user') {
            $new_user = Core::get_post('db_username');
            $new_pass = Core::get_post('db_password');

            if (!strlen($new_user) || !strlen($new_pass)) {
                AmpError::add('general', T_('The Ampache database username or password is missing'));
                require_once 'templates/show_install.inc.php';
                break;
            }
        }

        if (!$skip_admin) {
            if (!install_insert_db($new_user, $new_pass, $_REQUEST['create_db'], $_REQUEST['overwrite_db'], $_REQUEST['create_tables'])) {
                require_once 'templates/show_install.inc.php';
                break;
            }
        }

        // Now that it's inserted save the lang preference
        Preference::update('lang', -1, AmpConfig::get('lang'));
    case 'show_create_config':
        require_once 'templates/show_install_config.inc.php';
        break;
    case 'create_config':
        $all  = (filter_has_var(INPUT_POST, 'create_all'));
        $skip = (filter_has_var(INPUT_POST, 'skip_config'));
        if (!$skip) {
            $write                     = (filter_has_var(INPUT_POST, 'write'));
            $download                  = (filter_has_var(INPUT_POST, 'download'));
            $download_htaccess_channel = (filter_has_var(INPUT_POST, 'download_htaccess_channel'));
            $download_htaccess_rest    = (filter_has_var(INPUT_POST, 'download_htaccess_rest'));
            $download_htaccess_play    = (filter_has_var(INPUT_POST, 'download_htaccess_play'));
            $write_htaccess_channel    = (filter_has_var(INPUT_POST, 'write_htaccess_channel'));
            $write_htaccess_rest       = (filter_has_var(INPUT_POST, 'write_htaccess_rest'));
            $write_htaccess_play       = (filter_has_var(INPUT_POST, 'write_htaccess_play'));

            $created_config = true;
            if ($write_htaccess_channel || $download_htaccess_channel || $all) {
                $created_config = $created_config && install_rewrite_rules($htaccess_channel_file, Core::get_post('web_path'), $download_htaccess_channel);
            }
            if ($write_htaccess_rest || $download_htaccess_rest || $all) {
                $created_config = $created_config && install_rewrite_rules($htaccess_rest_file, Core::get_post('web_path'), $download_htaccess_rest);
            }
            if ($write_htaccess_play || $download_htaccess_play || $all) {
                $created_config = $created_config && install_rewrite_rules($htaccess_play_file, Core::get_post('web_path'), $download_htaccess_play);
            }
            if ($write || $download || $all) {
                $created_config = $created_config && install_create_config($download);
            }
        }
        // No break on purpose
    case 'show_create_account':
        $results = parse_ini_file($configfile);
        if (!isset($created_config)) {
            $created_config = true;
        }

        /* Make sure we've got a valid config file */
        if (!check_config_values($results) || !$created_config) {
            AmpError::add('general', T_('Configuration files were either not found or unreadable'));
            require_once AmpConfig::get('prefix') . UI::find_template('show_install_config.inc.php');
            break;
        }

        // Don't try to add administrator user on existing database
        if (install_check_status($configfile)) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_install_account.inc.php');
        } else {
            header("Location: " . $web_path . '/login.php');
        }
        break;
    case 'create_account':
        $results = parse_ini_file($configfile);
        AmpConfig::set_by_array($results, true);

        $password2 = $_REQUEST['local_pass2'];

        if (!install_create_account($username, $password, $password2)) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_install_account.inc.php');
            break;
        }

        header("Location: " . $web_path . '/index.php');
        break;
    case 'init':
        require_once 'templates/show_install.inc.php';
        break;
    case 'check':
        require_once 'templates/show_install_check.inc.php';
        break;
    default:
        // Show the language options first
        require_once 'templates/show_install_lang.inc.php';
        break;
} // end action switch
