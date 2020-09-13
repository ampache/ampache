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

define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!AmpConfig::get('daap_backend')) {
    echo T_("Disabled");

    return false;
}

$action = Core::get_get('action');

$headers = apache_request_headers();
//$daapAccessIndex = $headers['Client-DAAP-Access-Index'];
//$daapVersion = $headers['Client-DAAP-Version'];
//$daapValidation = $headers['Client-DAAP-Validation']; // That's header hash, we don't care about it (only required by iTunes >= 7.0)
debug_event('daap/index', 'Request headers: ' . print_r($headers, true), 5);

// Get the list of possible methods for the daap API
$methods = get_class_methods('daap_api');
// Define list of internal functions that should be skipped
$internal_functions = array('apiOutput', 'create_dictionary', 'createError', 'output_body', 'output_header', 'follow_stream');

Daap_Api::create_dictionary();

$params  = array_filter(explode('/', $action), 'strlen');
$p_count = count($params);
if ($p_count > 0) {
    // Recurse through them and see if we're calling one of them
    for ($i = $p_count; $i > 0; $i--) {
        $act = strtolower(implode('_', array_slice($params, 0, $i)));
        $act = str_replace("-", "_", $act);
        foreach ($methods as $method) {
            if (in_array($method, $internal_functions)) {
                continue;
            }

            // If the method is the same as the action being called
            // Then let's call this function!
            if ($act == $method) {
                call_user_func(array('daap_api', $method), array_slice($params, $i, $p_count - $i));
                // We only allow a single function to be called, and we assume it's cleaned up!
                return false;
            }
        } // end foreach methods in API
    }
}

Daap_Api::createError(404);
