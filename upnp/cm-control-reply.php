<?php
define('NO_SESSION', '1');
require_once '../lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo "Disabled.";
    exit;
}

set_time_limit(600);

header("Content-Type: text/html; charset=UTF-8");

// Parse the request from UPnP player
$requestRaw = file_get_contents('php://input');
if ($requestRaw != '') {
    $upnpRequest = Upnp_Api::parseUPnPRequest($requestRaw);
    debug_event('upnp-cm', 'Request: ' . $requestRaw, '5');
} else {
    echo 'Error: no UPnP request.';
    debug_event('upnp-cm', 'No request', '5');
    exit;
}

switch ($upnpRequest['action']) {
    case 'getprotocolinfo':
        $responseType = 'u:GetProtocolInfoResponse';
        //$items = Upnp_Api::cm_getProtocolInfo();
    break;
}
