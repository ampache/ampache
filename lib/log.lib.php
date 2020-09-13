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

/**
 * log_event
 * Logs an event to a defined log file based on config options
 * @param string $username
 * @param string $event_name
 * @param string $event_description
 * @param string $log_name
 */
function log_event($username, $event_name, $event_description, $log_name)
{
    /* Set it up here to make sure it's _always_ the same */
    $time     = time();
    $log_time = date("c", $time);

    /* must have some name */
    $log_name = $log_name ? $log_name : 'ampache';
    $username = $username ? $username : 'ampache';

    $log_filename = AmpConfig::get('log_filename');
    if (empty($log_filename)) {
        $log_filename = "%name.%Y%m%d.log";
    }

    $log_filename = str_replace("%name", $log_name, $log_filename);
    $log_filename = str_replace("%Y", date('Y'), $log_filename);
    $log_filename = str_replace("%m", date('m'), $log_filename);
    $log_filename = str_replace("%d", date('d'), $log_filename);
    $log_filename = AmpConfig::get('log_path') . "/" . $log_filename;
    $log_line     = "$log_time [$username] ($event_name) -> $event_description \n";

    // Do the deed
    $log_write = error_log($log_line, 3, $log_filename);

    if (!$log_write) {
        echo "Warning: Unable to write to log ($log_filename) Please check your log_path variable in ampache.cfg.php";
    }
} // log_event

/**
 * ampache_error_handler
 *
 * An error handler for ampache that traps as many errors as it can and logs
 * them.
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 */
function ampache_error_handler($errno, $errstr, $errfile, $errline)
{
    $level = 1;

    switch ($errno) {
        case E_WARNING:
            $error_name = 'Runtime Error';
            break;
        case E_COMPILE_WARNING:
        case E_NOTICE:
        case E_CORE_WARNING:
            $error_name = 'Warning';
            $level      = 6;
            break;
        case E_ERROR:
            $error_name = 'Fatal run-time Error';
            break;
        case E_PARSE:
            $error_name = 'Parse Error';
            break;
        case E_CORE_ERROR:
            $error_name = 'Fatal Core Error';
            break;
        case E_COMPILE_ERROR:
            $error_name = 'Zend run-time Error';
            break;
        case E_STRICT:
            $error_name = "Strict Error";
            break;
        default:
            $error_name = "Error";
            $level      = 2;
            break;
    } // end switch

    // List of things that should only be displayed if they told us to turn
    // on the firehose
    $ignores = array(
        // We know var is deprecated, shut up
        'var: Deprecated. Please use the public/private/protected modifiers',
        // getid3 spews errors, yay!
        'getimagesize() [',
        'Non-static method getid3',
        'Assigning the return value of new by reference is deprecated',
        // The XML-RPC lib is broken (kinda)
        'used as offset, casting to integer'
    );

    foreach ($ignores as $ignore) {
        if (strpos($errstr, $ignore) !== false) {
            $error_name = 'Ignored ' . $error_name;
            $level      = 7;
        }
    }

    if (error_reporting() == 0) {
        // Ignored, probably via @. But not really, so use the super-sekrit level
        $level = 7;
    }

    if (strpos($errstr, 'date.timezone') !== false) {
        $error_name = 'Warning';
        $errstr     = 'You have not set a valid timezone (date.timezone) in your php.ini file. This may cause display issues with dates. This warning is non-critical and not caused by Ampache.';
    }

    $log_line = "[$error_name] $errstr in file $errfile($errline)";
    debug_event('log.lib', $log_line, $level, '', 'ampache');
}

/**
 * debug_event
 * This function is called inside Ampache, it's actually a wrapper for the
 * log_event. It checks config for debug and debug_level and only
 * calls log event if both requirements are met.
 * @param string $type
 * @param string $message
 * @param integer $level
 * @param string $file
 * @param string $username
 * @return boolean
 */
function debug_event($type, $message, $level, $file = '', $username = '')
{
    if (!AmpConfig::get('debug') || $level > AmpConfig::get('debug_level')) {
        return false;
    }

    if (!$username && Core::get_global('user')) {
        $username = Core::get_global('user')->username;
    }

    // If the message is multiple lines, make multiple log lines
    foreach (explode("\n", (string) $message) as $line) {
        log_event($username, $type, $line, $file);
    }

    return true;
} // debug_event
