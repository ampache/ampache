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
 * This is the wrapper for opening music streams from this server.  This script
 * will play the local version or redirect to the remote server if that be
 * the case.  Also this will update local statistics for songs as well.
 * This is also where it decides if you need to be downsampled.
 */

define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';
ob_end_clean();

set_time_limit(0);

$channel = new Channel((int) Core::get_request('channel'));
if (!$channel->id) {
    debug_event('channel/index', 'Unknown channel.', 1);

    return false;
}

if (!function_exists('curl_version')) {
    debug_event('channel/index', 'Error: Curl is required for this feature.', 2);

    return false;
}

// Authenticate the user here
if ($channel->is_private) {
    $is_auth = false;
    if (filter_has_var(INPUT_SERVER, 'PHP_AUTH_USER')) {
        $htusername = Core::get_server('PHP_AUTH_USER');
        $htpassword = Core::get_server('PHP_AUTH_PW');

        $auth = Auth::login($htusername, $htpassword);
        debug_event('channel/index', 'Auth Attempt for ' . $htusername, 5);
        if ($auth['success']) {
            debug_event('channel/index', 'Auth SUCCESS', 3);
            $username        = $auth['username'];
            $GLOBALS['user'] = User::get_from_username($username);
            $is_auth         = true;
            Preference::init();

            if (AmpConfig::get('access_control')) {
                if (!Access::check_network('stream', Core::get_global('user')->id, 25) &&
                    !Access::check_network('network', Core::get_global('user')->id, 25)) {
                    debug_event('channel/index', "UI::access_denied: Streaming Access Denied: " . Core::get_user_ip() . " does not have stream level access", 2);
                    UI::access_denied();

                    return false;
                }
            }
        }
    }

    if (!$is_auth) {
        debug_event('channel/index', 'Auth FAILURE', 3);
        header('WWW-Authenticate: Basic realm="Ampache Channel Authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo T_('Unauthorized');

        return false;
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

$curl = curl_init($url);
if ($curl) {
    curl_setopt_array($curl, array(
        CURLOPT_HTTPHEADER => $reqheaders,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADERFUNCTION => 'output_header',
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => 'progress',
    ));
    curl_exec($curl);
    curl_close($curl);
}

/**
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @param $totaldownload
 * @param $downloaded
 * @param $us
 * @param $ud
 */
function progress($totaldownload, $downloaded, $us, $ud)
{
    flush();
    ob_flush();
}

/**
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @param $curl
 * @param $header
 * @return integer
 */
function output_header($curl, $header)
{
    $trimheader = trim($header);
    if (!empty($trimheader)) {
        header($trimheader);
    }

    return strlen($header);
}
