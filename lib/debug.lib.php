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

function check_php()
{
    if (
        check_php_version() &&
        check_php_hash() &&
        check_php_hash_algo() &&
        check_php_pdo() &&
        check_php_pdo_mysql() &&
        check_php_session() &&
        check_php_json() &&
        check_php_safemode()
    ) {
        return true;
    }

    return false;
}

function check_php_version()
{
    if (floatval(phpversion()) < 5.3) {
        return false;
    }

    return true;
}

function check_php_hash()
{
    return function_exists('hash_algos');
}

function check_php_hash_algo()
{
    return function_exists('hash_algos') ? in_array('sha256', hash_algos()) : false;
}

function check_php_json()
{
    return function_exists('json_encode');
}

function check_php_curl()
{
    return function_exists('curl_version');
}

function check_php_session()
{
    return function_exists('session_set_save_handler');
}

function check_php_pdo()
{
    return class_exists('PDO');
}

function check_php_pdo_mysql()
{
    return class_exists('PDO') ? in_array('mysql', PDO::getAvailableDrivers()) : false;
}

function check_mbstring_func_overload()
{
    if (ini_get('mbstring.func_overload') > 0) {
        return false;
    }

    return true;
}

/**
 * check_config_values
 * checks to make sure that they have at least set the needed variables
 */
function check_config_values($conf)
{
    if (!$conf['database_hostname']) {
        return false;
    }
    if (!$conf['database_name']) {
        return false;
    }
    if (!$conf['database_username']) {
        return false;
    }
    /* Don't check for password to support mysql socket auth
     * if (!$conf['database_password']) {
        return false;
    }*/
    if (!$conf['session_length']) {
        return false;
    }
    if (!$conf['session_name']) {
        return false;
    }
    if (!isset($conf['session_cookielife'])) {
        return false;
    }
    if (!isset($conf['session_cookiesecure'])) {
        return false;
    }
    if (isset($conf['debug'])) {
        if (!isset($conf['log_path'])) {
            return false;
        }
    }

    return true;
} // check_config_values

/**
 * check_php_memory
 * This checks to make sure that the php memory limit is withing the
 * recommended range, this doesn't take into account the size of your
 * catalog.
 */
function check_php_memory()
{
    $current_memory = ini_get('memory_limit');
    $current_memory = substr($current_memory, 0, strlen($current_memory) - 1);

    if (intval($current_memory) < 48) {
        return false;
    }

    return true;
} // check_php_memory

/**
 * check_php_timelimit
 * This checks to make sure that the php timelimit is set to some
 * semi-sane limit, IE greater then 60 seconds
 */
function check_php_timelimit()
{
    $current = intval(ini_get('max_execution_time'));

    return ($current >= 60 || $current == 0);
} // check_php_timelimit

/**
 * check_safe_mode
 * Checks to make sure we aren't in safe mode
 */
function check_php_safemode()
{
    if (ini_get('safe_mode')) {
        return false;
    }

    return true;
}

/**
 * check_override_memory
 * This checks to see if we can manually override the memory limit
 */
function check_override_memory()
{
    /* Check memory */
    $current_memory = ini_get('memory_limit');
    $current_memory = substr($current_memory, 0, strlen($current_memory) - 1);
    $new_limit      = ($current_memory + 16) . "M";

    /* Bump it by 16 megs (for getid3)*/
    if (!ini_set('memory_limit', $new_limit)) {
        return false;
    }

    // Make sure it actually worked
    $new_memory = ini_get('memory_limit');

    if ($new_limit != $new_memory) {
        return false;
    }

    return true;
}

/**
 * check_override_exec_time
 * This checks to see if we can manually override the max execution time
 */
function check_override_exec_time()
{
    $current = ini_get('max_execution_time');
    set_time_limit($current + 60);

    if ($current == ini_get('max_execution_time')) {
        return false;
    }

    return true;
}

/**
 * check_upload_size
 * This checks to see if max upload size is not too small
 */
function check_upload_size()
{
    $upload_max = return_bytes(ini_get('upload_max_filesize'));
    $post_max   = return_bytes(ini_get('post_max_size'));
    $mini       = 20971520; // 20M

    return (($upload_max >= $mini || $upload_max <= 0) && ($post_max >= $mini || $post_max <= 0));
}

function check_php_int_size()
{
    return (PHP_INT_SIZE > 4);
}

function check_php_zlib()
{
    return function_exists('gzcompress');
}

function check_php_simplexml()
{
    return function_exists('simplexml_load_string');
}

function check_php_gd()
{
    return (extension_loaded('gd') || extension_loaded('gd2'));
}

function return_bytes($val)
{
    $val  = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
            break;
    }

    return $val;
}

function check_dependencies_folder()
{
    return file_exists(AmpConfig::get('prefix') . '/lib/vendor');
}

/**
 * check_config_writable
 * This checks whether we can write the config file
 */
function check_config_writable()
{
    // file eixsts && is writable, or dir is writable
    return ((file_exists(AmpConfig::get('prefix') . '/config/ampache.cfg.php') && is_writable(AmpConfig::get('prefix') . '/config/ampache.cfg.php'))
        || (!file_exists(AmpConfig::get('prefix') . '/config/ampache.cfg.php') && is_writeable(AmpConfig::get('prefix') . '/config/')));
}

function check_htaccess_channel_writable()
{
    return ((file_exists(AmpConfig::get('prefix') . '/channel/.htaccess') && is_writable(AmpConfig::get('prefix') . '/channel/.htaccess'))
        || (!file_exists(AmpConfig::get('prefix') . '/channel/.htaccess') && is_writeable(AmpConfig::get('prefix') . '/channel/')));
}

function check_htaccess_rest_writable()
{
    return ((file_exists(AmpConfig::get('prefix') . '/rest/.htaccess') && is_writable(AmpConfig::get('prefix') . '/rest/.htaccess'))
        || (!file_exists(AmpConfig::get('prefix') . '/rest/.htaccess') && is_writeable(AmpConfig::get('prefix') . '/rest/')));
}

function check_htaccess_play_writable()
{
    return ((file_exists(AmpConfig::get('prefix') . '/play/.htaccess') && is_writable(AmpConfig::get('prefix') . '/play/.htaccess'))
        || (!file_exists(AmpConfig::get('prefix') . '/play/.htaccess') && is_writeable(AmpConfig::get('prefix') . '/play/')));
}

/**
 * debug_result
 *
 * Convenience function to format the output.
 */
function debug_result($status = false, $value = null, $comment = '')
{
    $class = $status ? 'success' : 'danger';

    if (!$value) {
        $value = $status ? T_('OK') : T_('ERROR');
    }

    return '<button type="button" class="btn btn-' . $class . '">' . scrub_out($value) .
        '</span> <em>' . $comment . '</em></button>';
}

/**
 * debug_wresult
 *
 * Convenience function to format the output.
 */
function debug_wresult($status = false, $value = null, $comment = '')
{
    $class = $status ? 'success' : 'warning';

    if (!$value) {
        $value = $status ? T_('OK') : T_('WARNING');
    }

    return '<button type="button" class="btn btn-' . $class . '">' . scrub_out($value) .
        '</span> <em>' . $comment . '</em></button>';
}
