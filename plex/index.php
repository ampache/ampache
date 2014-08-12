<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

define('NO_SESSION','1');
require_once '../lib/init.php';

if (!AmpConfig::get('plex_backend')) {
    echo "Disabled.";
    exit;
}

if (function_exists('apache_setenv')) {
   @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);

$action = $_GET['action'];

$headers = apache_request_headers();
$client = $headers['User-Agent'];
/*$deviceName = $headers['X-Plex-Device-Name'];
$clientPlatform = $headers['X-Plex-Client-Platform'];
$version = $headers['X-Plex-Version'];
$language = $headers['X-Plex-Language'];
$clientFeatures = $headers['X-Plex-Client-Capabilities'];*/
debug_event('plex', 'Request headers: '. print_r($headers, true), '5');

// Get the list of possible methods for the Plex API
$methods = get_class_methods('plex_api');
// Define list of internal functions that should be skipped
$internal_functions = array('setHeader', 'root', 'apiOutput', 'createError', 'validateMyPlex', 'getPublicIp', 'registerMyPlex', 'publishDeviceConnection', 'unregisterMyPlex');

$show_index = true;
$params = array_filter(explode('/', $action), 'strlen');
if (count($params) > 0) {
    // Hack to listen locally on port != 32400
    if (count($params) >= 2 && $params[0] == '.hack' && $params[1] == 'main:32400') {
        array_shift($params);
        array_shift($params);
        if (count($params) > 0 && $params[0] == ':') {
            array_shift($params);
        }
    }

    if (count($params) > 0) {
        $show_index = false;
        // Recurse through them and see if we're calling one of them
        for ($i = count($params); $i > 0; $i--) {
            $act = strtolower(implode('_', array_slice($params, 0, $i)));
            foreach ($methods as $method) {
                if (in_array($method, $internal_functions)) { continue; }

                // If the method is the same as the action being called
                // Then let's call this function!
                if ($act == $method) {
                    if ($act != 'users' && $act != 'users_account' && $act != 'manage_frameworks_ekspinner_resources') {
                        Plex_Api::auth_user();
                    }

                    Plex_Api::setHeader('xml');
                    Plex_Api::setPlexHeader($headers);
                    call_user_func(array('plex_api', $method), array_slice($params, $i, count($params) - $i));
                    // We only allow a single function to be called, and we assume it's cleaned up!
                    exit();
                }

            } // end foreach methods in API
        }
    }
}

if ($show_index) {
    Plex_Api::auth_user();
    Plex_Api::setHeader('xml');
    Plex_Api::setPlexHeader($headers);
    Plex_Api::root();
    exit();
}

Plex_Api::createError(404);
