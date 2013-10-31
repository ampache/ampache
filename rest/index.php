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
 
define('NO_SESSION','1');
require_once '../lib/init.php';

$action = strtolower($_GET['action']);
/* Set the correct default headers */
if ($action != "getcoverart" && $action != "hls" && $action != "stream" && $action != "download" && $action != "getavatar") {
    header("Content-type: text/xml; charset=" . Config::get('site_charset'));
}

// If we don't even have access control on then we can't use this!
if (!Config::get('access_control')) {
    debug_event('Access Control','Error Attempted to use Subsonic API with Access Control turned off','3');
    ob_end_clean();
    echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, T_('Access Control not Enabled'))->asXml();
    exit;
}

// Authenticate the user with preemptive HTTP Basic authentication first
$user = $_SERVER['PHP_AUTH_USER'];
if (empty($user)) {
    $user = $_GET['u'];
}
$password = $_SERVER['PHP_AUTH_PW'];
if (empty($password)) {
    $password = $_GET['p'];
}
$version = $_GET['v'];
$clientapp = $_GET['c'];

if (empty($user) || empty($password) || empty($version) || empty($action) || empty($clientapp)) {
    ob_end_clean();
    echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM)->asXml();
    exit();
}

// Decode hex-encoded password
$encpwd = strpos($password, "enc:");
if ($encpwd !== false) {
    $hex = substr($password, 4);
    $decpwd = '';
    for ($i=0; $i<strlen($hex); $i+=2) $decpwd .= chr(hexdec(substr($hex,$i,2)));
    $password = $decpwd;
}

// Check user authentication
$auth = Auth::login($user, $password);
if (!$auth['success']) {
    debug_event('Access Denied','Invalid authentication attempt to Subsonic API for user [' . $user . ']','3');
    ob_end_clean();
    echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_BADAUTH)->asXml();
    exit();
}

if (!Access::check_network('init-api', $user, 5)) {
    debug_event('Access Denied','Unauthorized access attempt to Subsonic API [' . $_SERVER['REMOTE_ADDR'] . ']', '3');
    ob_end_clean();
    echo Subsonic_XML_Data::createError(SSERROR_UNAUTHORIZED, 'Unauthorized access attempt to Subsonic API - ACL Error');
    exit();
}

$GLOBALS['user'] = User::get_from_username($user);

// Check server version
if (version_compare(Subsonic_XML_Data::API_VERSION, $version) < 0) {
    ob_end_clean();
    echo Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_APIVERSION_SERVER)->asXml();
    exit();
}

// Get the list of possible methods for the Ampache API
$methods = get_class_methods('subsonic_api');

// Define list of internal functions that should be skipped
$internal_functions = array('check_version', 'check_parameter', 'follow_stream', '_updatePlaylist');

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
//syslog(LOG_INFO, print_r($params, true));

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
?>
