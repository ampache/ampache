<?php

class UPnPDevice
{
    private $_host = "";
    private $_controlURLs = array();
    private $_eventURLs = array();


    public function UPnPDevice($descrUrl)
    {
        if (! $this->restoreDescriptionUrl($descrUrl))
            $this->parseDescriptionUrl($descrUrl);
    }

    /*
     * Reads description URL from session
     */
    private function restoreDescriptionUrl($descrUrl)
    {
        debug_event('upnpdevice', 'readDescriptionUrl: ' . $descrUrl, 5);

        if ($descrUrl == $_SESSION['upnp_DescriptionUrl']) {
            $this->_host = $_SESSION['upnp_host'];
            $this->_controlURLs = $_SESSION['upnp_controlURLs'];
            $this->_eventURLs = $_SESSION['upnp_eventURLs'];
            debug_event('upnpdevice', 'service Urls restored from session', 5);
            return true;
        }
        return false;
    }

    private function parseDescriptionUrl($descrUrl)
    {
        debug_event('upnpdevice', 'parseDescriptionUrl: ' . $descrUrl, 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $descrUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        debug_event('upnpdevice', 'parseDescriptionUrl response: ' . $response, 5);

        $responseXML = simplexml_load_string($response);
        $services = $responseXML->device->serviceList->service;
        foreach ($services as $service)
        {
            $serviceType = $service->serviceType;
            $serviceTypeNames = explode(":", $serviceType);
            $serviceTypeName = $serviceTypeNames[3];
            $this->_controlURLs[$serviceTypeName] = (string)$service->controlURL;
            $this->_eventURLs[$serviceTypeName] = (string)$service->eventSubURL;
        }

        $urldata = parse_url($descrUrl);
        $this->_host = $urldata['scheme'] . '://' . $urldata['host'] . ':' . $urldata['port'];

        $_SESSION['upnp_DescriptionUrl'] = $descrUrl;
        $_SESSION['upnp_host'] = $this->_host;
        $_SESSION['upnp_controlURLs'] = $this->_controlURLs;
        $_SESSION['upnp_eventURLs'] = $this->_eventURLs;
    }

    /**
    * Sending HTTP-Request and returns parsed response
    *
    * @param string $method     Method name
    * @param array  $arguments  Key-Value array
    */
    public function sendRequestToDevice( $method, $arguments, $type = 'RenderingControl')
    {
        $body  ='<?xml version="1.0" encoding="utf-8"?>';
        $body .='<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body>';
        $body .='  <u:' . $method . ' xmlns:u="urn:schemas-upnp-org:service:' . $type . ':1">';
        foreach( $arguments as $arg=>$value ) {
            $body .=' <'.$arg.'>'.$value.'</'.$arg.'>';
        }
        $body .='  </u:' . $method . '>';
        $body .='</s:Body></s:Envelope>';

        $controlUrl = $this->_host . $this->_controlURLs[$type];

        //!! todo - need to use scheme in header ??
        $header = array(
            'SOAPACTION: "urn:schemas-upnp-org:service:' . $type . ':1#' . $method . '"',
            'CONTENT-TYPE: text/xml; charset="utf-8"',
            'HOST: ' . $this->_host,
            'Connection: close',
            'Content-Length: ' . mb_strlen($body),
        );
        debug_event('upnpdevice', 'sendRequestToDevice Met: ' . $method . ' | ' . $controlUrl . ' | ' . $body, 5);
        debug_event('upnpdevice', 'sendRequestToDevice Hdr: ' . print_r($header, true), 5);

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $controlUrl );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_HEADER, TRUE );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );

        $response = curl_exec( $ch );
        curl_close( $ch );
        debug_event('upnpdevice', 'sendRequestToDevice response: ' . $response, 5);

        $headers = array();
        $tmp = explode("\r\n\r\n", $response);

        foreach($tmp as $key => $value) 
        {
            if(substr($value, 0, 8) == 'HTTP/1.1') 
            {
                $headers[] = $tmp[$key];
                unset($tmp[$key]);
            }
        }

        $lastHeaders = $headers[count($headers) - 1];

        $response = join("\r\n", $tmp);

        $responseCode = $this->getResponseCode($lastHeaders);
        debug_event('upnpdevice', 'sendRequestToDevice responseCode: ' . $responseCode, 5);

        if($responseCode == 500) 
        {
            debug_event('upnpdevice', 'sendRequestToDevice HTTP-Code 500 - Create error response', 5);
            //$response = $this->parseResponseError($response);
        } 
        else 
        {
            debug_event('upnpdevice', 'sendRequestToDevice HTTP-Code OK - Create response', 5);
            //$response = $this->parseResponse($method, $response);
        }
        
        return $response;
    }

    /**
    * Filters response HTTP-Code from response headers
    * @param string $headers    HTTP response headers
    * @return mixed             Response code (int) or null if not found
    */
    private function getResponseCode($headers) 
    {
        $tmp = explode("\n", $headers);
        $firstLine = array_shift($tmp);

        if(substr($headers, 0, 8) == 'HTTP/1.1') {
            return substr($headers, 9, 3);
        }

        return null;
    }

    // helper function for calls that require only an instance id
    public function instanceOnly($command, $type = 'AVTransport', $id = 0)
    {
        $args = array( 'InstanceID' => $id );
        $response = $this->sendRequestToDevice($command, $args, $type);
        ///$response = \Format::forge($response,'xml:ns')->to_array();
        ///return $response['s:Body']['u:' . $command . 'Response'];
        return $response;
    }


    /**
     * Subscribe
     * Subscribe to UPnP event
     */
    public function Subscribe($type = 'AVTransport')
    {
        $web_path = AmpConfig::get('web_path');
        $eventSubsUrl = $web_path . '/upnp/play-event.php';
        $eventUrl = $this->_host . $this->_eventURLs[$type];

        $header = array(
            'HOST: ' . $this->_host,
            'CALLBACK: <' . $eventSubsUrl . '>',
            'NT: upnp:event',
            'TIMEOUT: Second-180',
        );
        debug_event('upnpdevice', 'Subscribe to $this->device->getId()  with: ' . "\n" . print_r($header, true), 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $eventUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'SUBSCRIBE');

        $response = curl_exec($ch);
        curl_close( $ch );
        debug_event('upnpdevice', 'Subscribe response: ' . $response, 5);

        $lines = explode("\r\n", trim($response));
        foreach($lines as $line) {
            $tmp = explode(':', $line);
            $key = strtoupper(trim(array_shift($tmp)));
            $value = trim(join(':', $tmp));

            if ($key == 'SID')
            {
                debug_event('upnpdevice', 'Subscribtion SID: ' . $value, 5);
                return $value;
            }
        }

        return null;
    }

    /**
     * UnSubscribe
     * Unsubscribe from UPnP event
     */
    public function UnSubscribe($sid, $type = 'AVTransport')
    {
        if (empty($sid))
            return;

        $eventUrl = $this->_host . $this->_eventURLs[$type];

        $header = array(
            'HOST: ' . $this->_host,
            'SID: ' . $sid,
        );

        debug_event('upnpdevice', 'Unsubscribe from SID: ' . $sid . ' with: ' . "\n" . print_r($header, true), 5);

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

}
