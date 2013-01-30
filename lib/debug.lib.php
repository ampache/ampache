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

/**
 * check_php_ver
 * checks the php version and makes
 * sure that it's good enough
 */
function check_php_ver($level=0) {

    if (floatval(phpversion()) < 5.3) {
        return false;
    }

    // Make sure that they have the sha256() algo installed
    if (!function_exists('hash_algos')) { return false; }
    $algos = hash_algos();

    if (!in_array('sha256',$algos)) {
        return false;
    }

    return true;

} // check_php_ver

/**
 * check_php_session
 * checks to make sure the needed functions
 * for sessions exist
*/
function check_php_session() {

    if (!function_exists('session_set_save_handler')) {
        return false;
    }

    return true;

} // check_php_session

/**
 * check_php_pcre
 * This makes sure they have pcre (preg_???) support
 * compiled into PHP this is required!
 */
function check_php_pcre() {

    if (!function_exists('preg_match')) {
        return false;
    }

    return true;

} // check_php_pcre

/**
 * check_config_values
 * checks to make sure that they have at least set the needed variables
 */
function check_config_values($conf) {

    if (!$conf['database_hostname']) {
        return false;
    }
    if (!$conf['database_name']) {
        return false;
    }
    if (!$conf['database_username']) {
        return false;
    }
    if (!$conf['database_password']) {
        return false;
    }
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
function check_php_memory() {

    $current_memory = ini_get('memory_limit');
    $current_memory = substr($current_memory,0,strlen($current_memory)-1);

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
function check_php_timelimit() {

    $current = intval(ini_get('max_execution_time'));
    return ($current >= 60 || $current == 0);

} // check_php_timelimit

/**
 * check_safe_mode
 * Checks to make sure we aren't in safe mode
 */
function check_safemode() {
    if (ini_get('safe_mode')) {
        return false;
    }
    return true;
}

/**
 * check_override_memory
 * This checks to see if we can manually override the memory limit
 */
function check_override_memory() {
    /* Check memory */
    $current_memory = ini_get('memory_limit');
    $current_memory = substr($current_memory,0,strlen($current_memory)-1);
    $new_limit = ($current_memory+16) . "M";

    /* Bump it by 16 megs (for getid3)*/
    if (!ini_set('memory_limit',$new_limit)) {
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
function check_override_exec_time() {
    $current = ini_get('max_execution_time');
    set_time_limit($current+60);

    if ($current == ini_get('max_execution_time')) {
        return false;
    }

    return true;
}

/**
 * check_gettext
 * This checks to see if you've got gettext installed
 */
function check_gettext() {

    if (!function_exists('gettext')) {
        return false;
    }

    return true;

} // check_gettext

/**
 * check_mbstring
 * This checks for mbstring support
 */
function check_mbstring() {

    if (!function_exists('mb_check_encoding')) {
        return false;
    }

    return true;

} // check_mbstring

/**
 * check_config_writable
 * This checks whether we can write the config file
 */
function check_config_writable() {

    // file eixsts && is writable, or dir is writable
    return ((file_exists(Config::get('prefix') . '/config/ampache.cfg.php') && is_writable(Config::get('prefix') . '/config/ampache.cfg.php')) 
        || (!file_exists(Config::get('prefix') . '/config/ampache.cfg.php') && is_writeable(Config::get('prefix') . '/config/')));
}

/**
 * generate_config
 * This takes an array of results and re-generates the config file
 * this is used by the installer and by the admin/system page
 */
function generate_config($current) {

    /* Start building the new config file */
    $distfile = Config::get('prefix') . '/config/ampache.cfg.php.dist';
    $handle = fopen($distfile,'r');
    $dist = fread($handle,filesize($distfile));
    fclose($handle);

    $data = explode("\n",$dist);

    /* Run throught the lines and set our settings */
    foreach ($data as $line) {

        /* Attempt to pull out Key */
        if (preg_match("/^;?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$line,$matches)
            || preg_match("/^;?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $line, $matches)
            || preg_match("/^;?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$line,$matches)) {

            $key    = $matches[1];
            $value  = $matches[2];

            /* Put in the current value */
            if ($key == 'config_version') {
                $line = $key . ' = ' . escape_ini($value);
            }
            elseif (isset($current[$key])) {
                $line = $key . ' = "' . escape_ini($current[$key]) . '"';
                unset($current[$key]);
            } // if set

        } // if key

        $final .= $line . "\n";

    } // end foreach line

    return $final;

} // generate_config

/**
 * escape_ini
 * Escape a value used for inserting into an ini file. 
 * Won't quote ', like addslashes does.
 */
function escape_ini($str) {

    return str_replace('"', '\"', $str);

}


/**
 * debug_ok
 * Return an "OK" with the specified string
 */
function debug_result($comment,$status=false,$value=false) {

    $class = $status ? 'ok' : 'notok';
    if (!$value) {
        $value = $status ? 'OK' : 'ERROR';
    }

    $final = '<span class="' . $class . '">' . scrub_out($value) . '</span> <em>' . $comment . '</em>';

    return $final;

} // debug_ok
?>
