<?php
define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo T_("Disabled");

    return false;
}

// Make sure beautiful url is enabled
// (including upnp)
debug_event('control-reply', "\r\nEntering control-reply", 5);
AmpConfig::set('stream_beautiful_url', true, true);  // XXX needs t be false true

// set_time_limit(600); // may be ok, disabled in testing
// Response type should be text/xml, not text/html; libupnp checks this and borks if it's incorrect
// Stops vlc media player from recognizing Ampache

header("Content-Type: text/xml; charset=UTF-8");
//header("Content-Type: text/html; charset=UTF-8" );

header("Content-length: 200");  // set up the header for modification later

// Parse the request from UPnP player
$requestRaw = file_get_contents('php://input');
if ($requestRaw != '') {
    $upnpRequest = Upnp_Api::parseUPnPRequest($requestRaw);

    debug_event('control-reply', 'Request: ' . $requestRaw, 5);
} else {
    echo T_('Received an empty UPnP request');
    debug_event('control-reply', 'No request', 5);

    return false;
}

$items        = array();
$totMatches   = 0;
$responseType = "u:Error";
$soapXML      = '';
$numRet       = 0;
$filter       = $upnpRequest['filter'];
$sort         = $upnpRequest['sortcriteria'];
// upnp:class,dc:title are container defaults. Add.
if ($filter != '*') {
    // The following fields are required defaults
    $filter = $filter . ',upnp:class,dc:title,res@id,res@protocolInfo';
} elseif ($filter == '') {
    $filter = '*';
}

debug_event('control-reply', 'Action: ' . $upnpRequest['action'] . ' with filter [' . $filter . ']', 5);
switch ($upnpRequest['action']) {
    case 'systemupdateID':
        // Should reflect changes to the database; Catalog::GetLaststUpdate() doesn't cut it though
        // debug_event('control-reply', 'SystemUpdate: ' . (string) Catalog::getLastUpdate(), 5);
        $ud      = sprintf('<Id>%1$04d</Id>', 0); // 0 for now, insert something suitable when found.
        $soapXML = "<?xml version=\"1.0\"?>" .
        "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" SOAP-ENV:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">" .
        "<SOAP-ENV:Body>" .
        "<u:GetSystemUpdateIDResponse xmlns:u=\"urn:schemas-upnp-org:service:ContentDirectory:1\">" .
        $ud .
        "</u:GetSystemUpdateIDResponse>" .
        "</SOAP-ENV:Body> " .
        "</SOAP-ENV:Envelope>";
        break;
    // "<SearchCaps>dc:creator,dc:date,dc:title,upnp:album,upnp:actor,upnp:artist,upnp:class,upnp:genre,@id,@parentID,@refID</SearchCaps>" .
    // 17/10 removed upnp:author@role, from searchcaps as this is not trivial to implement
    // 20/10 put it back because it stops M1000 from searching for anything ("Search not supported on this server")
    case 'searchcapabilities':
        $soapXML = "<?xml version=\"1.0\"?>" .
        "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">" .
        "<s:Body>" .
        "<u:GetSearchCapabilitiesResponse xmlns:u=\"urn:schemas-upnp-org:service:ContentDirectory:1\">" .
        "<SearchCaps>@id,@refID,dc:title,upnp:class,upnp:genre,upnp:artist,upnp:author,upnp:author@role,upnp:album,dc:creator,upnp:rating,upnp:actor,upnp:director,upnp:toc,dc:description</SearchCaps>" .
        "</u:GetSearchCapabilitiesResponse>" .
        "</s:Body>" .
        "</s:Envelope>";
        break;
    case 'sortcapabilities':
        $soapXML = "<?xml version=\"1.0\"?>" .
        "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">" .
        "<s:Body>" .
        "<u:GetSortCapabilitiesResponse xmlns:u=\"urn:schemas-upnp-org:service:ContentDirectory:1\">" .
        "<SortCaps>dc:title,upnp:genre,upnp:album,dc:creator,upnp:actor,upnp:director,upnp:toc,dc:description</SortCaps>" .
        "</u:GetSortCapabilitiesResponse>" .
        "</s:Body>" .
        "</s:Envelope>";
        break;
    case 'search':
        debug_event('control-reply', 'Searchcriteria: ' . $upnpRequest['searchcriteria'], 5);
        debug_event('control-reply', 'Search filter : ' . $filter, 5);
        $responseType             = 'u:SearchResponse';
        list($totMatches, $items) = Upnp_Api::_callSearch($upnpRequest['searchcriteria'],$filter,$upnpRequest['startingindex'], $upnpRequest['requestedcount']);
        break;
    case 'browse':
        $responseType = 'u:BrowseResponse';
        if ($upnpRequest['objectid'] == '0') {
            debug_event('control-reply', 'Browse request for root items', 5);
            // Root items
            if ($upnpRequest['browseflag'] == 'BrowseMetadata') {
                $items[] = array(
                    'id' => '0',
                    'parentID' => '-1',
                    'childCount' => '2',
                    'searchable' => '1',
                    'dc:title' => T_('root'),
                    'upnp:class' => 'object.container',
                );
            } else {
                $filter = '*';      // Some devices don't seem to specify a sensible filter (may remove)
                //$items[] = array();
                $items[]                  = Upnp_Api::_musicMetadata('');
                $items[]                  = Upnp_Api::_videoMetadata('');
                list($totMatches, $items) = Upnp_Api::_slice($items, $upnpRequest['startingindex'], $upnpRequest['requestedcount']);
                debug_event('control-reply', 'Root items returning' . $items[0] . $items[1] , 5);
                // debug_event('control-reply', 'Root items detail ' . var_export($items, true), 5);
                // debug_event('control-reply', 'Root items sort   ' . $upnpRequest['sortcriteria'], 5);
            }
        } else {
            /**
             * The parse_url function returns an array in this format:
             * Array (
             *   [scheme] => http
             *   [host] => hostname
             *   [user] => username
             *   [pass] => password
             *   [path] => /path
             *   [query] => arg=value
             *   [fragment] => anchor
             * )
             */
            $reqObjectURL = parse_url($upnpRequest['objectid']);
            debug_event('control-reply', 'ObjectID: ' . $upnpRequest['objectid'], 5);
            switch ($reqObjectURL['scheme']) {
                case 'amp':
                    switch ($reqObjectURL['host']) {
                        case 'music':
                            if ($upnpRequest['browseflag'] == 'BrowseMetadata') {
                                $items = Upnp_Api::_musicMetadata($reqObjectURL['path']);
                                //debug_event('control-reply', 'Metadata count '. (string) $totMatches . ' '. (string) count($items), 5);
                                //debug_event('control-reply', 'Export items ' . var_export($items,true), 5);
                                $totMatches = 1;
                                $numRet     = 1;
                            } else {
                                debug_event('control-reply', 'Listrequest ', 5);
                                list($totMatches, $items) = Upnp_Api::_musicChilds($reqObjectURL['path'], $reqObjectURL['query'], $upnpRequest['startingindex'], $upnpRequest['requestedcount']);
                                debug_event('control-reply', 'non-root items sort ' . $upnpRequest['sortcriteria'], 5);
                                //debug_event('control-reply', 'Listrequest '. (string) $upnpRequest['startingindex'] . ':' . (string) $upnpRequest['requestedcount'] . ':' . (string) $totMatches, 5);
                            }
                            break;
                        case 'video':
                            if ($upnpRequest['browseflag'] == 'BrowseMetadata') {
                                $items      = Upnp_Api::_videoMetadata($reqObjectURL['path']);
                                $totMatches = 1;
                            } else {
                                list($totMatches, $items) = Upnp_Api::_videoChilds($reqObjectURL['path'], $reqObjectURL['query'], $upnpRequest['startingindex'], $upnpRequest['requestedcount']);
                            }
                            break;
                    }
                    break;
                default:
                    debug_event('control-reply', 'Unrecognized scheme: ' . $reqObjectURL['scheme'], 5);
                    break;
            }
        }
        break;
    default:
        break;
}

if ($soapXML == "") {
    $totMatches = ($totMatches == 0) ? count($items) : $totMatches;
    if ($items == null || $totMatches == 0) {
        $domDIDL = Upnp_Api::createDIDL('', '');
        $numRet  = 0;
    } else {
        $domDIDL = Upnp_Api::createDIDL($items, $filter);
        if ($numRet == 0) {
            $numRet  = count($items);
        }
    }
    $xmlDIDL  = $domDIDL->saveXML();
    $xmlDIDLs = substr($xmlDIDL, strpos($xmlDIDL, '?' . '>') + 2); // Remove the unnecessary <xml... > tag at the head of the DIDL
    $domSOAP  = Upnp_Api::createSOAPEnvelope($xmlDIDLs, $numRet, $totMatches, $responseType);
    $soapXML  = $domSOAP->saveXML();
}
debug_event('control-reply', 'Content: ' . (string) $soapXML, 5);

// Set the overall content length in the header correctly, having appended $soapXML
$contentLength = strlen($soapXML);
header("Content-Length: $contentLength");

//print $soapXML;
debug_event('control-reply', 'Response: ' . $soapXML, 5);
echo $soapXML;
