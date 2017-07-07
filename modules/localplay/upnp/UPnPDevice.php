<?php

class UPnPDevice
{
    private $_settings = array(
        "descriptionURL" => "",
        "host" => "",
        "controlURLs" => array(),
        "eventURLs" => array()
    );


    public function __construct($descriptionUrl)
    {
        if (! $this->restoreDescriptionUrl($descriptionUrl)) {
            $this->parseDescriptionUrl($descriptionUrl);
        }
    }

    /*
     * Reads description URL from session
     */
    private function restoreDescriptionUrl($descriptionUrl)
    {
        debug_event('UPnPDevice', 'readDescriptionUrl: ' . $descriptionUrl, 5);
        $this->_settings = json_decode(Session::read('upnp_dev_' . $descriptionUrl), true);

        if ($this->_settings['descriptionURL'] == $descriptionUrl) {
            debug_event('UPnPDevice', 'service Urls restored from session.', 5);

            return true;
        }

        return false;
    }

    private function parseDescriptionUrl($descriptionUrl)
    {
        debug_event('UPnPDevice', 'parseDescriptionUrl: ' . $descriptionUrl, 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $descriptionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        //!!debug_event('UPnPDevice', 'parseDescriptionUrl response: ' . $response, 5);

        $responseXML = simplexml_load_string($response);
        $services    = $responseXML->device->serviceList->service;
        foreach ($services as $service) {
            $serviceType                                      = $service->serviceType;
            $serviceTypeNames                                 = explode(":", $serviceType);
            $serviceTypeName                                  = $serviceTypeNames[3];
            $this->_settings['controlURLs'][$serviceTypeName] = (string)$service->controlURL;
            $this->_settings['eventURLs'][$serviceTypeName]   = (string)$service->eventSubURL;
        }

        $urldata                 = parse_url($descriptionUrl);
        $this->_settings['host'] = $urldata['scheme'] . '://' . $urldata['host'] . ':' . $urldata['port'];

        $this->_settings['descriptionURL'] = $descriptionUrl;

        Session::create(array(
            'type' => 'api',
            'sid' => 'upnp_dev_' . $descriptionUrl,
            'value' => json_encode($this->_settings)
        ));
    }

    /**
    * Sending HTTP-Request and returns parsed response
    *
    * @param string $method     Method name
    * @param array  $arguments  Key-Value array
    */
    public function sendRequestToDevice($method, $arguments, $type = 'RenderingControl')
    {
        $body  ='<?xml version="1.0" encoding="utf-8"?>';
        $body .= '<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body>';
        $body .= '  <u:' . $method . ' xmlns:u="urn:schemas-upnp-org:service:' . $type . ':1">';
        foreach ($arguments as $arg => $value) {
            $body .= ' <' . $arg . '>' . $value . '</' . $arg . '>';
        }
        $body .= '  </u:' . $method . '>';
        $body .= '</s:Body></s:Envelope>';

        $controlUrl = $this->_settings['host'] . ((substr($this->_settings['controlURLs'][$type], 0, 1) != "/") ? "/" : "") . $this->_settings['controlURLs'][$type];

        //!! TODO - need to use scheme in header ??
        $header = array(
            'SOAPACTION: "urn:schemas-upnp-org:service:' . $type . ':1#' . $method . '"',
            'CONTENT-TYPE: text/xml; charset="utf-8"',
            'HOST: ' . $this->_settings['host'],
            'Connection: close',
            'Content-Length: ' . mb_strlen($body),
        );
        //debug_event('UPnPDevice', 'sendRequestToDevice Met: ' . $method . ' | ' . $controlUrl, 5);
        //debug_event('UPnPDevice', 'sendRequestToDevice Body: ' . $body, 5);
        //debug_event('UPnPDevice', 'sendRequestToDevice Hdr: ' . print_r($header, true), 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $controlUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        curl_close($ch);
        //debug_event('UPnPDevice', 'sendRequestToDevice response: ' . $response, 5);

        $headers = array();
        $tmp     = explode("\r\n\r\n", $response);

        foreach ($tmp as $key => $value) {
            if (substr($value, 0, 8) == 'HTTP/1.1') {
                $headers[] = $tmp[$key];
                unset($tmp[$key]);
            }
        }

        $response = join("\r\n", $tmp);

        /*
        $lastHeaders = $headers[count($headers) - 1];
        $responseCode = $this->getResponseCode($lastHeaders);
        debug_event('UPnPDevice', 'sendRequestToDevice responseCode: ' . $responseCode, 5);
        if ($responseCode == 500)
        {
            debug_event('UPnPDevice', 'sendRequestToDevice HTTP-Code 500 - Create error response', 5);
        }
        else
        {
            debug_event('UPnPDevice', 'sendRequestToDevice HTTP-Code OK - Create response', 5);
        }
        */
        
        return $response;
    }

    /**
    * Filters response HTTP-Code from response headers
    * @param string $headers    HTTP response headers
    * @return mixed             Response code (int) or null if not found
    */
    /*
    private function getResponseCode($headers)
    {
        $tmp = explode("\n", $headers);
        $firstLine = array_shift($tmp);

        if(substr($headers, 0, 8) == 'HTTP/1.1') {
            return substr($headers, 9, 3);
        }

        return null;
    }
    */

    // helper function for calls that require only an instance id
    public function instanceOnly($command, $type = 'AVTransport', $id = 0)
    {
        $args     = array( 'InstanceID' => $id );
        $response = $this->sendRequestToDevice($command, $args, $type);

        ///$response = \Format::forge($response,'xml:ns')->to_array();
        ///return $response['s:Body']['u:' . $command . 'Response'];

        return $response;
    }


    //!! UPNP subscription work not for all renderers, and works strange
    //!! so now is not used
    /**
     * Subscribe
     * Subscribe to UPnP event
     */
    /*
    public function Subscribe($type = 'AVTransport')
    {
        $web_path = AmpConfig::get('web_path');
        $eventSubsUrl = $web_path . '/upnp/play-event.php?device=' . urlencode($this->_descrUrl);
        $eventUrl = $this->_host . $this->_eventURLs[$type];

        $header = array(
            'HOST: ' . $this->_host,
            'CALLBACK: <' . $eventSubsUrl . '>',
            'NT: upnp:event',
            'TIMEOUT: Second-180',
        );
        debug_event('UPnPDevice', 'Subscribe with: ' . print_r($header, true), 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $eventUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'SUBSCRIBE');

        $response = curl_exec($ch);
        curl_close( $ch );
        debug_event('UPnPDevice', 'Subscribe response: ' . $response, 5);

        $lines = explode("\r\n", trim($response));
        foreach($lines as $line) {
            $tmp = explode(':', $line);
            $key = strtoupper(trim(array_shift($tmp)));
            $value = trim(join(':', $tmp));

            if ($key == 'SID')
            {
                debug_event('UPnPDevice', 'Subscribtion SID: ' . $value, 5);
                return $value;
            }
        }

        return null;
    }
    */

    //!! UPNP subscription work not for all renderers, and works strange
    //!! so now is not used
    /**
     * UnSubscribe
     * Unsubscribe from UPnP event
     */
    /*
    public function UnSubscribe($sid, $type = 'AVTransport')
    {
        if (empty($sid))
            return;

        $eventUrl = $this->_host . $this->_eventURLs[$type];

        $header = array(
            'HOST: ' . $this->_host,
            'SID: ' . $sid,
        );

        debug_event('UPnPDevice', 'Unsubscribe from SID: ' . $sid . ' with: ' . "\n" . print_r($header, true), 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $eventUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'UNSUBSCRIBE');

        $response = curl_exec($ch);
        curl_close( $ch );
    }
    */
}
