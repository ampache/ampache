<?php
define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo T_("Disabled");

    return false;
}

set_time_limit(600);

header("Content-Type: text/html; charset=UTF-8");

// Parse the request from UPnP player
$requestRaw = file_get_contents('php://input');
if ($requestRaw != '') {
    $upnpRequest = Upnp_Api::parseUPnPRequest($requestRaw);
    debug_event('cm-control-reply', 'Request: ' . $requestRaw, 5);
} else {
    echo T_('Received an empty UPnP request');
    debug_event('cm-control-reply', 'No request', 5);

    return false;
}

switch ($upnpRequest['action']) {
    case 'getprotocolinfo':
        $responseType = 'u:GetProtocolInfoResponse';
        //$items = Upnp_Api::cm_getProtocolInfo();
        break;
}
