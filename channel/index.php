<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

/*

 This is the wrapper for opening music streams from this server.  This script
   will play the local version or redirect to the remote server if that be
   the case.  Also this will update local statistics for songs as well.
   This is also where it decides if you need to be downsampled.
*/
define('NO_SESSION', '1');
require_once '../lib/init.php';
ob_end_clean();

set_time_limit(0);

$channel = new Channel($_REQUEST['channel']);
if (!$channel->id) {
    debug_event('channel', 'Unknown channel.', '1');
    exit;
}

if (!function_exists('curl_version')) {
    debug_event('channel', 'Error: Curl is required for this feature.', '1');
    exit;
}

// Authenticate the user here
if ($channel->is_private) {
    $is_auth = false;
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        $htusername = $_SERVER['PHP_AUTH_USER'];
        $htpassword = $_SERVER['PHP_AUTH_PW'];

        $auth = Auth::login($htusername, $htpassword);
        if ($auth['success']) {
            $username        = $auth['username'];
            $GLOBALS['user'] = new User($username);
            $is_auth         = true;
            Preference::init();

            if (AmpConfig::get('access_control')) {
                if (!Access::check_network('stream', $GLOBALS['user']->id, '25') and
                    !Access::check_network('network', $GLOBALS['user']->id, '25')) {
                    debug_event('UI::access_denied', "Streaming Access Denied: " . $_SERVER['REMOTE_ADDR'] . " does not have stream level access", '3');
                    UI::access_denied();
                    exit;
                }
            }
        }
    }

    if (!$is_auth) {
        header('WWW-Authenticate: Basic realm="Ampache Channel Authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo T_('Unauthorized.');
        exit;
    }
}

$url = 'http://' . $channel->interface . ':' . $channel->port . '/' . $_REQUEST['target'];
// Redirect request to the real channel server
$headers         = getallheaders();
$headers['Host'] = $channel->interface;
$reqheaders      = array();
foreach ($headers as $key => $value) {
    $reqheaders[] = $key . ': ' . $value;
}

$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_HTTPHEADER => $reqheaders,
    CURLOPT_HEADER => false,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADERFUNCTION => 'output_header',
    CURLOPT_NOPROGRESS => false,
    CURLOPT_PROGRESSFUNCTION => 'progress',
));
curl_exec($ch);
curl_close($ch);

/**
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function progress($totaldownload, $downloaded, $us, $ud)
{
    ob_flush();
}

/**
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function output_header($ch, $header)
{
    $th = trim($header);
    if (!empty($th)) {
        header($th);
    }
    return strlen($header);
}
