<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

if (!AmpConfig::get('subsonic_backend')) {
    echo "Disabled.";
    exit;
}

$action = strtolower($_REQUEST['ssaction']);
// Compatibility reason
if (empty($action)) {
    $action = strtolower($_REQUEST['action']);
}
$f = $_REQUEST['f'];
$callback = $_REQUEST['callback'];
/* Set the correct default headers */
if ($action != "getcoverart" && $action != "hls" && $action != "stream" && $action != "download" && $action != "getavatar") {
    Subsonic_Api::setHeader($f);
}

// If we don't even have access control on then we can't use this!
if (!AmpConfig::get('access_control')) {
    debug_event('Access Control','Error Attempted to use Subsonic API with Access Control turned off','3');
    ob_end_clean();
    Subsonic_Api::apiOutput2($f, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, T_('Access Control not Enabled')), $callback);
    exit;
}

// Authenticate the user with preemptive HTTP Basic authentication first
$user = $_SERVER['PHP_AUTH_USER'];
if (empty($user)) {
    $user = $_REQUEST['u'];
}
$password = $_SERVER['PHP_AUTH_PW'];
if (empty($password)) {
    $password = $_REQUEST['p'];
}
$version = $_REQUEST['v'];
$clientapp = $_REQUEST['c'];

if (empty($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = $clientapp;
}

if (empty($user) || empty($password) || empty($version) || empty($action) || empty($clientapp)) {
    ob_end_clean();
    Subsonic_Api::apiOutput2($f, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM), $callback);
    exit();
}

$password = Subsonic_Api::decrypt_password($password);

// Check user authentication
$auth = Auth::login($user, $password, true);
if (!$auth['success']) {
    debug_event('Access Denied','Invalid authentication attempt to Subsonic API for user [' . $user . ']','3');
    ob_end_clean();
    Subsonic_Api::apiOutput2($f, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_BADAUTH), $callback);
    exit();
}

if (!Access::check_network('init-api', $user, 5)) {
    debug_event('Access Denied','Unauthorized access attempt to Subsonic API [' . $_SERVER['REMOTE_ADDR'] . ']', '3');
    ob_end_clean();
    Subsonic_Api::apiOutput2($f, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, 'Unauthorized access attempt to Subsonic API - ACL Error'), $callback);
    exit();
}

$GLOBALS['user'] = User::get_from_username($user);
// Check server version
if (version_compare(Subsonic_XML_Data::API_VERSION, $version) < 0) {
    ob_end_clean();
    Subsonic_Api::apiOutput2($f, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_APIVERSION_SERVER), $callback);
    exit();
}
Preference::init();

// Get the list of possible methods for the Ampache API
$methods = get_class_methods('subsonic_api');

// Define list of internal functions that should be skipped
$internal_functions = array('check_version', 'check_parameter', 'decrypt_password', 'follow_stream', '_updatePlaylist', '_setStar', 'setHeader', 'apiOutput', 'apiOutput2', 'xml2json');

// We do not use $_GET because of multiple parameters with the same name
$query_string = $_SERVER['QUERY_STRING'];
// Trick to avoid $HTTP_RAW_POST_DATA
$postdata = file_get_contents("php://input");
if (!empty($postdata)) {
    $query_string .= '&' . $postdata;
}
$query  = explode('&', $query_string);
$params = array();
foreach ($query as $param) {
    list($name, $value) = explode('=', $param);
    $decname = urldecode($name);
    $decvalue = urldecode($value);
    if (array_key_exists($decname, $params)) {
        if (!is_array($params[$decname])) {
            $oldvalue = $params[$decname];
            $params[$decname] = array();
            $params[$decname][] = $oldvalue;
        }
        $params[$decname][] = $decvalue;
    } else {
        $params[$decname] = $decvalue;
    }
}
//debug_event('subsonic', print_r($params, true), '5');
//debug_event('subsonic', print_r(apache_request_headers(), true), '5');

// Recurse through them and see if we're calling one of them
foreach ($methods as $method) {
    if (in_array($method,$internal_functions)) { continue; }

    // If the method is the same as the action being called
    // Then let's call this function!
    if ($action == $method) {
        call_user_func(array('subsonic_api',$method),$params);
        // We only allow a single function to be called, and we assume it's cleaned up!
        exit();
    }

} // end foreach methods in API

// If we manage to get here, we still need to hand out an XML document
ob_end_clean();
echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND)->asXml();
