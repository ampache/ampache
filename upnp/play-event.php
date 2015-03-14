<?php
    require_once '../lib/init.php';
    require_once '../modules/upnp/upnpdevice.php';

    debug_event('play-event', '1', '5');

    $headers = getallheaders();
    $request = file_get_contents('php://input');
    debug_event('play-event', ' headers: ' . print_r($headers, true), '5');

$doc = new DomDocument();
$doc->loadXML($request);
$root = $doc->childNodes->item(0);

if($root->localName == 'propertyset') {

    $property = $root->childNodes->item(0);

    if($property->localName == 'property') {

        $data = $property->textContent;

        $doc = new DOMDocument();
        $doc->loadXML($data);

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $out = $doc->saveXML();
        debug_event('play-event', 'EVENT: ' . $out, '5');

        $root = $doc->childNodes->item(0);

        if ($root->localName == 'Event') {
            $instance = $root->childNodes->item(0);

            if ($instance->localName == 'InstanceID') {
                $instanceId = 0;
                $transportState = null;

                if ($instance->hasAttributes()) {
                    foreach($instance->attributes as $attr) {
                        if($attr->localName == 'val') {
                            $instanceId = $attr->textContent;
                        }
                    }
                }

                if ($instance->hasChildNodes()) {
                    foreach ($instance->childNodes as $cn) {
                        if ($cn->localName == 'TransportState' && $cn->hasAttributes()) {
                            foreach ($cn->attributes as $attr) {
                                if ($attr->localName == 'val') {
                                    $transportState = $attr->textContent;
                                }
                            }
                        }
                    }
                }

                if($transportState != null) {
                    if(isset($headers['SID'])) {
                        $sid = $headers['SID'];
                        /*
                        file_put_contents('/tmp/tmp.log', $sid . "\n", FILE_APPEND);
                        $subscriptions = Device::getAllSubscriptions();
                        $device = null;
                        foreach($subscriptions as $deviceId => $ids) {
                            foreach($ids as $id) {
                                if($id == $sid) {
                                    $device = UPnP::getDevice($deviceId);
                                    $device->receivedEvent($transportState);
                                }
                            }
                        }
                        */
                    }
                }
            }
        }
    }
}

    $descrURL = $_GET["device"];
    debug_event('play-event', 'DEVICE: ' . $descrURL, '5');


    $dev = new UPnPDevice($descrUrl);
    $dev->UnSubscribe($sid);
    $sid = $dev->Subscribe();
?>