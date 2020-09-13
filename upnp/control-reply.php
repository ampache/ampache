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
$rootMediaItems   = array();
$rootMediaItems[] = Upnp_Api::_musicMetadata('');
$rootMediaItems[] = Upnp_Api::_videoMetadata('');

    // Parse the request from UPnP player
    $requestRaw = file_get_contents('php://input');
    if ($requestRaw != '') {
        $upnpRequest = Upnp_Api::parseUPnPRequest($requestRaw);

    //!!debug_event('control-reply', 'Request: ' . $requestRaw, 5);
    } else {
        echo T_('Received an empty UPnP request');
        debug_event('control-reply', 'No request', 5);

        return false;
    }

    $items        = array();
    $totMatches   = 0;
    $responseType = "u:Error";
    switch ($upnpRequest['action']) {
        case 'search':
            $responseType = 'u:SearchResponse';
            $items        = Upnp_Api::_callSearch($upnpRequest['searchcriteria']);
            break;
        case 'browse':
            $responseType = 'u:BrowseResponse';

            if ($upnpRequest['objectid'] == '0') {
                // Root items
                if ($upnpRequest['browseflag'] == 'BrowseMetadata') {
                    $items[] = array(
                        'id' => '0',
                        'parentID' => '-1',
                        'childCount' => '2',
                        'dc:title' => T_('root'),
                        'upnp:class' => 'object.container',
                    );
                } else {
                    $items = $rootMediaItems;
                }
                break;
            } else {
                # The parse_url function returns an array in this format:
                # Array (
                #    [scheme] => http
                #    [host] => hostname
                #    [user] => username
                #    [pass] => password
                #    [path] => /path
                #    [query] => arg=value
                #    [fragment] => anchor
                # )
                $reqObjectURL = parse_url($upnpRequest['objectid']);
                switch ($reqObjectURL['scheme']) {
                    case 'amp':
                        switch ($reqObjectURL['host']) {
                            case 'music':
                                if ($upnpRequest['browseflag'] == 'BrowseMetadata') {
                                    $items = Upnp_Api::_musicMetadata($reqObjectURL['path'], $reqObjectURL['query']);
                                } else {
                                    list($totMatches, $items) = Upnp_Api::_musicChilds($reqObjectURL['path'], $reqObjectURL['query'], $upnpRequest['startingindex'], $upnpRequest['requestedcount']);
                                }
                                break;
                            case 'video':
                                if ($upnpRequest['browseflag'] == 'BrowseMetadata') {
                                    $items = Upnp_Api::_videoMetadata($reqObjectURL['path'], $reqObjectURL['query']);
                                } else {
                                    list($totMatches, $items) = Upnp_Api::_videoChilds($reqObjectURL['path'], $reqObjectURL['query'], $upnpRequest['startingindex'], $upnpRequest['requestedcount']);
                                }
                                break;
                        }
                        break;
                }
            }
            break;
        default:
            break;
    }

    $totMatches = ($totMatches == 0) ? count($items) : $totMatches;
    if ($items == null || $totMatches == 0) {
        $domDIDL = Upnp_Api::createDIDL('');
        $numRet  = 0;
    } else {
        $domDIDL = Upnp_Api::createDIDL($items);
        $numRet  = count($items);
    }

    $xmlDIDL = $domDIDL->saveXML();
    $domSOAP = Upnp_Api::createSOAPEnvelope($xmlDIDL, $numRet, $totMatches, $responseType);
    $soapXML = $domSOAP->saveXML();

    echo $soapXML;
    //!!debug_event('control-reply', 'Response: ' . $soapXML, 5);
