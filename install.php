<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$prefix = dirname(__FILE__);
require_once $prefix . '/lib/init-tiny.php';
require_once $prefix . '/lib/install.lib.php';

set_error_handler('ampache_error_handler');

// Redirect if installation is already complete.
if (!install_check_status($configfile)) {
    $redirect_url = 'login.php';
    require_once Config::get('prefix') . '/templates/error_page.inc.php';
    exit;
}

define('INSTALL', 1);

// Clean up incoming variables
$web_path = scrub_in($_REQUEST['web_path']);
$username = scrub_in($_REQUEST['local_username']);
$password = $_REQUEST['local_pass'];
$hostname = scrub_in($_REQUEST['local_host']);
$database = scrub_in($_REQUEST['local_db']);

// Charset and gettext setup
$htmllang = $_REQUEST['htmllang'];
$charset  = $_REQUEST['charset'];

if (!$htmllang) {
    if ($_ENV['LANG']) {
        $lang = $_ENV['LANG'];
    }
    else {
        $lang = 'en_US';
    }
    if(strpos($lang, '.')) {
        $langtmp = explode('.', $lang);
        $htmllang = $langtmp[0];
        $charset = $langtmp[1];
    }
    else {
        $htmllang = $lang;
    }
}
Config::set('lang', $htmllang, true);
Config::set('site_charset', $charset ?: 'UTF-8', true);
load_gettext();
header ('Content-Type: text/html; charset=' . Config::get('site_charset'));

// Correct potential \ or / in the dirname
$safe_dirname = rtrim(dirname($_SERVER['PHP_SELF']),"/\\"); 

$web_path = $http_type . $_SERVER['HTTP_HOST'] . $safe_dirname;

unset($safe_dirname); 

switch ($_REQUEST['action']) {
    case 'create_db':
        if (!install_insert_db($username,$password,$hostname,$database)) {
            require_once 'templates/show_install.inc.php';
            break;
        }

        // Now that it's inserted save the lang preference
        Preference::update('lang', '-1', Config::get('lang'));

        header ('Location: ' . $web_path . "/install.php?action=show_create_config&local_db=$database&local_host=$hostname&htmllang=$htmllang&charset=$charset");
    break;
    case 'create_config':
        // Test and make sure that the values they give us actually work
        Config::set_by_array(array(
            'database_username' => $username,
            'database_password' => $password,
            'database_hostname' => $hostname
            ), true
        );
        if (!Dba::check_database()) {
            Error::add('config', T_('Error: Unable to make Database Connection: ') . Dba::error());
        }

        // Was download pressed?
        $download = (!isset($_POST['write']));

        if (!Error::occurred()) {
            $created_config = install_create_config($web_path,$username,$password,$hostname,$database,$download);
        }

        require_once 'templates/show_install_config.inc.php';
    break;
    case 'show_create_config':
        require_once 'templates/show_install_config.inc.php';
    break;
    case 'create_account':
        $results = parse_ini_file($configfile);
        Config::set_by_array($results, true);

        $password2 = scrub_in($_REQUEST['local_pass2']);

        if (!install_create_account($username, $password, $password2)) {
            require_once Config::get('prefix') . '/templates/show_install_account.inc.php';
            break;
        }

        header ("Location: " . $web_path . '/login.php');
    break;
    case 'show_create_account':
        $results = parse_ini_file($configfile);

        /* Make sure we've got a valid config file */
        if (!check_config_values($results)) {
            Error::add('general', T_('Error: Config file not found or unreadable'));
            require_once Config::get('prefix') . '/templates/show_install_config.inc.php';
            break;
        }

        require_once Config::get('prefix') . '/templates/show_install_account.inc.php';
    break;
    case 'init':
        require_once 'templates/show_install.inc.php';
    break;
    default:
        // Show the language options first
        require_once 'templates/show_install_lang.inc.php';
    break;
} // end action switch

?>
