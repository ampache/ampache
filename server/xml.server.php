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
 * This is accessed remotly to allow outside scripts access to ampache information
 * as such it needs to verify the session id that is passed
 */
define('NO_SESSION', '1');
define('OUTDATED_DATABASE_OK', 1);
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

// If it's not a handshake then we can allow it to take up lots of time
if (Core::get_request('action') != 'handshake') {
    set_time_limit(0);
}

/* Set the correct headers */
header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
header("Content-Disposition: attachment; filename=information.xml");

// If we don't even have access control on then we can't use this!
if (!AmpConfig::get('access_control')) {
    ob_end_clean();
    debug_event('xml.server', 'Error Attempted to use XML API with Access Control turned off', 3);
    echo XML_Data::error('4700', T_('Access Denied'), Core::get_request('action'), 'system');

    return false;
}

/**
 * Verify the existence of the Session they passed in we do allow them to
 * login via this interface so we do have an exception for action=login
 */
if (!Session::exists('api', Core::get_request('auth')) && Core::get_request('action') != 'handshake' && Core::get_request('action') != 'ping') {
    debug_event('Access Denied', 'Invalid Session attempt to API [' . Core::get_request('action') . ']', 3);
    ob_end_clean();
    echo XML_Data::error('4701', T_('Session Expired'), Core::get_request('action'), 'account');

    return false;
}

// If the session exists then let's try to pull some data from it to see if we're still allowed to do this
$username = ($_REQUEST['action'] == 'handshake') ? $_REQUEST['user'] : Session::username($_REQUEST['auth']);

if (!Access::check_network('init-api', $username, 5)) {
    debug_event('Access Denied', 'Unauthorized access attempt to API [' . Core::get_server('REMOTE_ADDR') . ']', 3);
    ob_end_clean();
    echo XML_Data::error('4742', T_('Unauthorized access attempt to API - ACL Error'), Core::get_request('action'), 'account');

    return false;
}

if ((Core::get_request('action') != 'handshake') && (Core::get_request('action') != 'ping')) {
    if (isset($_REQUEST['user'])) {
        $GLOBALS['user'] = User::get_from_username(Core::get_request('user'));
    } else {
        debug_event('xml.server', 'API session [' . Core::get_request('auth') . ']', 3);
        $GLOBALS['user'] = User::get_from_username(Session::username(Core::get_request('auth')));
    }
}

// Make sure beautiful url is disabled as it is not supported by most Ampache clients
AmpConfig::set('stream_beautiful_url', false, true);

$method = $_GET['action'];

// Retrieve the api method handler from the list of known methods
$handler = Api::METHOD_LIST[$method] ?? null;
if ($handler !== null) {
    $_GET['api_format'] = 'xml';
    call_user_func([$handler, $method], $_GET);
    // We only allow a single function to be called, and we assume it's cleaned up!
    return false;
}

// If we manage to get here, we still need to hand out an XML document
ob_end_clean();
echo XML_Data::error('4705', T_('Invalid Request'), (string) $method, 'system');
